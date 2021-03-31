<?php

namespace DeliciousBrains\WPMDBTP;

use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Pro\Queue\Manager;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\FileProcessor;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\TransferManager;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Pro\Transfers\Receiver;

/**
 * Class ThemePluginFilesLocal
 *
 * Handles local themes/plugins logic
 *
 */
class ThemePluginFilesLocal {

	/**
	 * @var Util
	 */
	public $transfer_util;
	/**
	 * @var TransferManager
	 */
	public $transfer_manager;
	/**
	 * @var FileProcessor
	 */
	public $file_processor;
	/**
	 * @var Manager
	 */
	public $queueManager;
	/**
	 * @var Receiver
	 */
	public $receiver;
	/**
	 * @var \DeliciousBrains\WPMDB\Common\Util\Util
	 */
	public $util;
	/**
	 * @var MigrationStateManager
	 */
	public $migration_state_manager;
	/**
	 * @var Http
	 */
	public $http;
	/**
	 * @var TransferCheck
	 */
	private $check;
	/**
	 * @var Filesystem
	 */
	private $filesystem;

	public function __construct(
		Util $util,
		\DeliciousBrains\WPMDB\Common\Util\Util $common_util,
		FileProcessor $file_processor,
		Manager $queue_manager,
		TransferManager $transfer_manager,
		Receiver $receiver,
		MigrationStateManager $migration_state_manager,
		Http $http,
		Filesystem $filesystem,
		TransferCheck $check
	) {
		$this->util                    = $common_util;
		$this->queueManager            = $queue_manager;
		$this->transfer_util           = $util;
		$this->file_processor          = $file_processor;
		$this->transfer_manager        = $transfer_manager;
		$this->receiver                = $receiver;
		$this->migration_state_manager = $migration_state_manager;
		$this->http                    = $http;
		$this->check                   = $check;
		$this->filesystem              = $filesystem;
	}

	public function register() {
		add_action( 'wp_ajax_wpmdb_initiate_file_migration', array( $this, 'ajax_initiate_file_migration' ) );
		add_action( 'wpmdb_initiate_migration', array( $this->check, 'transfer_check' ) );
		add_action( 'wp_ajax_wpmdb_get_queue_items', array( $this, 'ajax_get_queue_items' ) );
		add_action( 'wp_ajax_wpmdb_transfer_files', array( $this, 'ajax_transfer_files' ) );
	}

	/**
	 *
	 * @TODO Break this up into smaller, testable functions
	 * @return bool|null
	 */
	public function ajax_initiate_file_migration() {
		$this->http->check_ajax_referer( 'wpmdb-initiate-file-migration' );
		$this->util->set_time_limit();

		$key_rules = array(
			'action'             => 'key',
			'stage'              => 'string',
			'excludes'           => 'string',
			'migration_state_id' => 'key',
			'folders'            => 'string',
			'nonce'              => 'key',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules );

		if ( empty( $state_data['folders'] ) ) {
			return $this->transfer_util->ajax_error( __( 'Error: empty folder list supplied.', 'wp-migrate-db' ) );
		}

		$excludes = isset( $state_data['excludes'] ) ? $state_data['excludes'] : '';
		$excludes = explode( '\n', str_replace( '"', '', $excludes ) );

		//State data populated
		$files = json_decode( $state_data['folders'] );

		if ( ! is_array( $files ) ) {
			return $this->transfer_util->ajax_error( __( 'Invalid folder list supplied (invalid array)', 'wp-migrate-db' ) );
		}

		// @TODO this needs to be implemented for remotes on a pull
		$verified_folders = $this->verify_files_for_migration( $files );

		if ( 'pull' === $state_data['intent'] ) {
			// Set up local meta data
			$file_list = $this->transfer_util->get_remote_files( $files, 'wpmdbtp_respond_to_get_remote_' . $state_data['stage'], $excludes );
		} else {

			// Push = get local files
			$abs_path  = 'plugins' === $state_data['stage'] ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/themes/';

			$file_list = $this->file_processor->get_local_files( $verified_folders, $abs_path, $state_data['stage'], $excludes );
		}

		if ( ! $file_list ) {
			$this->http->end_ajax( $file_list );
		}

		$queue_status = $this->populate_queue( $file_list, $state_data['intent'], $state_data['stage'], $state_data['migration_state_id'] );
		set_site_transient( 'wpmdb_queue_status', $queue_status );

		return $this->http->end_ajax( json_encode( [ 'queue_status' => $queue_status ] ) );
	}

	/**
	 * Get queue items in batches to populate the UI
	 *
	 * @return mixed|null
	 */
	public function ajax_get_queue_items() {
		$this->http->check_ajax_referer( 'wpmdb-get-queue-items' );
		$this->util->set_time_limit();

		$key_rules = array(
			'action'             => 'key',
			'stage'              => 'string',
			'migration_state_id' => 'key',
			'nonce'              => 'key',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules );

		if ( empty( $state_data['folders'] ) ) {
			return $this->transfer_util->ajax_error( __( 'Error: empty folder list supplied.', 'wp-migrate-db' ) );
		}

		$queue_status = get_site_transient( 'wpmdb_queue_status' );
		$count        = apply_filters( 'wpmdb_tranfers_queue_batch_size', 1000 );
		$offset       = isset( $queue_status['offset'] ) ? $queue_status['offset'] : 0;

		$q_data = $this->queueManager->list_jobs( $count, $offset );

		if ( empty( $q_data ) ) {
			delete_site_transient( 'wpmdb_queue_status' );

			return $this->http->end_ajax( json_encode( [ 'status' => 'complete' ] ) );
		}

		$file_data  = $this->process_file_data( $q_data );
		$result_set = $this->transfer_util->process_queue_data( $file_data, $state_data, 0 );

		$queue_status['offset'] = $offset + $count;
		set_site_transient( 'wpmdb_queue_status', $queue_status );

		return $this->http->end_ajax( json_encode( [ 'queue_status' => $queue_status, 'items' => $result_set ] ) );
	}

	/**
	 * @return null
	 */
	public function ajax_transfer_files() {
		$this->http->check_ajax_referer( 'wpmdb-transfer-files' );
		$this->util->set_time_limit();

		$key_rules = array(
			'action'             => 'key',
			'stage'              => 'string',
			'offset'             => 'numeric',
			'migration_state_id' => 'key',
			'nonce'              => 'key',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules );
		$count      = apply_filters( 'wpmdbtp_file_batch_size', 100 );
		$data       = $this->queueManager->list_jobs( $count );

		$processed = $this->process_file_data( $data );

		if ( empty( $data ) ) {
			do_action( 'wpmdbtp_file_transfer_complete' );

			// Clear out queue in case there is a next step
			$this->queueManager->truncate_queue();

			return $this->http->end_ajax( json_encode( [ 'status' => 'complete' ] ) );
		}

		$remote_url = $state_data['url'];
		$processed  = $this->transfer_manager->manage_file_transfer( $remote_url, $processed, $state_data );

		$result = [
			'status' => $processed,
		];

		//Client should check error status for files and if a 500 is encountered kill the migration stage
		return $this->http->end_ajax( json_encode( $result ) );
	}


	/**
	 * Process data
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function process_file_data( $data ) {
		$result_set = [];

		if ( ! empty( $data ) ) {
			foreach ( $data as $size => $record ) {
				$display_path                  = $record->file['subpath'];
				$record->file['relative_path'] = $display_path;

				$result_set[] = $record->file;
			}
		}

		return $result_set;
	}


	protected function populate_queue( $file_data, $intent, $stage, $migration_state_id ) {
		foreach ( $file_data['files'] as $item ) {
			if ( is_array( $item ) ) {
				$this->transfer_util->enqueue_files( $item, $this->queueManager );
			}
		}

		$queue_status = [
			'total'    => $file_data['meta']['count'],
			'size'     => $file_data['meta']['size'],
			'manifest' => $file_data['meta']['manifest'],
		];

		if ( 'pull' === $intent ) {
			$this->transfer_util->remove_tmp_folder( $stage );
			try {
				$this->transfer_util->save_queue_status( $queue_status, $stage, $migration_state_id );
			} catch ( \Exception $e ) {
				return $this->transfer_util->ajax_error( sprintf( __( 'Unable to save local queue status - %s', 'wp-migrate-db' ), $e->getMessage() ) );
			}
		} else {
			// Push
			try {
				$this->transfer_util->save_queue_status_to_remote( $queue_status, 'wpmdbtp_respond_to_save_queue_status' );
			} catch ( \Exception $e ) {
				$this->transfer_util->ajax_error( $e->getMessage() );
			}
		}

		// Manifest can get quite large, so remove it once it's no longer needed
		unset( $queue_status['manifest'] );

		return $queue_status;
	}

	public function verify_files_for_migration( $files ) {
		$paths = [];

		foreach ( $files as $file ) {
			if ( $this->filesystem->file_exists( $file ) ) {
				$paths[] = $file;
			}
		}

		return $paths;
	}
}
