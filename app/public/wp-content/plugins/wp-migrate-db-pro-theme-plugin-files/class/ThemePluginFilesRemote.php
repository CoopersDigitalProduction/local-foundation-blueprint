<?php

namespace DeliciousBrains\WPMDBTP;

use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\Scramble;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Pro\Queue\Manager;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\FileProcessor;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\TransferManager;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Pro\Transfers\Receiver;
use DeliciousBrains\WPMDB\Pro\Transfers\Sender;

class ThemePluginFilesRemote {

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
	 * @var Http
	 */
	private $http;
	/**
	 * @var Helper
	 */
	private $http_helper;
	/**
	 * @var MigrationStateManager
	 */
	private $migration_state_manager;
	/**
	 * @var Settings
	 */
	private $settings;
	/**
	 * @var Properties
	 */
	private $properties;
	/**
	 * @var Sender
	 */
	private $sender;
	/**
	 * @var Filesystem
	 */
	private $filesystem;
	/**
	 * @var Scramble
	 */
	private $scrambler;

	public function __construct(
		Util $util,
		FileProcessor $file_processor,
		Manager $queue_manager,
		TransferManager $transfer_manager,
		Receiver $receiver,
		Http $http,
		Helper $http_helper,
		MigrationStateManager $migration_state_manager,
		Settings $settings,
		Properties $properties,
		Sender $sender,
		Filesystem $filesystem,
		Scramble $scramble
	) {
		$this->queueManager            = $queue_manager;
		$this->transfer_util           = $util;
		$this->file_processor          = $file_processor;
		$this->transfer_manager        = $transfer_manager;
		$this->receiver                = $receiver;
		$this->http                    = $http;
		$this->http_helper             = $http_helper;
		$this->migration_state_manager = $migration_state_manager;
		$this->settings                = $settings->get_settings();
		$this->properties              = $properties;
		$this->sender                  = $sender;
		$this->filesystem              = $filesystem;
		$this->scrambler                = $scramble;
	}

	public function register() {
		add_action( 'wp_ajax_nopriv_wpmdbtp_respond_to_get_remote_themes', array( $this, 'ajax_respond_to_get_remote_themes' ) );
		add_action( 'wp_ajax_nopriv_wpmdbtp_respond_to_get_remote_plugins', array( $this, 'ajax_respond_to_get_remote_plugins' ) );
		add_action( 'wp_ajax_nopriv_wpmdbtp_respond_to_save_queue_status', array( $this, 'ajax_respond_to_save_queue_status' ) );
		add_action( 'wp_ajax_nopriv_wpmdb_transfers_send_file', array( $this, 'ajax_respond_to_request_files', ) );
		add_action( 'wp_ajax_nopriv_wpmdb_transfers_receive_file', array( $this, 'ajax_respond_to_post_file' ) );
		add_filter( 'wpmdb_establish_remote_connection_data', array( $this, 'establish_remote_connection_data' ) );
	}

	public function establish_remote_connection_data( $data ) {
		$receiver         = $this->receiver;
		$tmp_folder_check = $receiver->is_tmp_folder_writable( 'themes' );

		$data['remote_theme_plugin_files_available'] = true;
		$data['remote_theme_plugin_files_version']   = $GLOBALS['wpmdb_meta']['wp-migrate-db-pro-theme-plugin-files']['version'];
		$data['remote_tmp_folder_check']             = $tmp_folder_check;
		$data['remote_tmp_folder_writable']          = $tmp_folder_check['status'];

		return $data;
	}

	public function ajax_respond_to_get_remote_themes() {
		$this->respond_to_get_remote_folders( 'themes' );
	}

	public function ajax_respond_to_get_remote_plugins() {
		$this->respond_to_get_remote_folders( 'plugins' );
	}

	/**
	 * @param $stage
	 *
	 * @return mixed|null
	 */
	public function respond_to_get_remote_folders( $stage ) {
		add_filter( 'wpmdb_before_response', array( $this->scrambler, 'scramble' ) );

		$key_rules = array(
			'action'          => 'key',
			'remote_state_id' => 'key',
			'intent'          => 'key',
			'folders'         => 'string',
			'excludes'        => 'string',
			'stage'           => 'string',
			'sig'             => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'intent',
			'folders',
			'excludes',
			'stage',
		) );

		$verification = $this->http_helper->verify_signature( $filtered_post, $this->settings['key'] );

		if ( ! $verification ) {
			return $this->transfer_util->ajax_error( $this->invalid_content_verification_error . ' (#100tp)', $filtered_post );
		}

		$abs_path = 'plugins' === $stage ? WP_PLUGIN_DIR : WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;

		$files    = $this->file_processor->get_local_files( unserialize( $state_data['folders'] ), $this->filesystem->slash_one_direction( $abs_path ), $stage, unserialize( $state_data['excludes'] ) );

		if ( empty( $files ) ) {
			return $this->http->end_ajax( __( 'No files returned from the remote server.', 'wp-migrate-db' ) . ' (#101tp)' );
		}

		// @TODO potentially use streaming in future
		$str = serialize( $files );

		return $this->http->end_ajax( $str );
	}

	/**
	 *
	 * Fired off a nopriv AJAX hook that listens to pull requests for file batches
	 *
	 * @return mixed
	 */
	public function ajax_respond_to_request_files() {

		$key_rules = array(
			'action'          => 'key',
			'remote_state_id' => 'key',
			'stage'           => 'string',
			'intent'          => 'string',
			'bottleneck'      => 'numeric',
			'sig'             => 'string',
		);

		$state_data    = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );
		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'stage',
			'intent',
			'bottleneck',
		) );

		$settings = $this->settings;

		if ( ! $this->http_helper->verify_signature( $filtered_post, $settings['key'] ) ) {
			return $this->transfer_util->ajax_error( $this->properties->invalid_content_verification_error . ' (#100tp)', $filtered_post );
		}

		try {
			$this->sender->respond_to_send_file( $state_data );
		} catch ( \Exception $e ) {
			$this->transfer_util->catch_general_error( $e->getMessage() );
		}
	}

	/**
	 *
	 * Respond to request to save queue status
	 *
	 * @return mixed|null
	 */
	public function ajax_respond_to_save_queue_status() {
		$key_rules = array(
			'action'          => 'key',
			'remote_state_id' => 'key',
			'stage'           => 'string',
			'intent'          => 'string',
			'sig'             => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'intent',
			'stage',
		) );

		$settings = $this->settings;

		if ( ! $this->http_helper->verify_signature( $filtered_post, $settings['key'] ) ) {
			return $this->transfer_util->ajax_error( $this->properties->invalid_content_verification_error . ' (#100tp)', $filtered_post );
		}

		if ( empty( $_POST['queue_status'] ) ) {
			return $this->transfer_util->ajax_error( __( 'Saving queue status to remote failed.' ) );
		}

		$queue_status = filter_var( $_POST['queue_status'], FILTER_SANITIZE_STRING );
		$queue_data   = unserialize( gzdecode( base64_decode( $queue_status ) ) );

		if ( $queue_data ) {
			$this->transfer_util->remove_tmp_folder( $state_data['stage'] );

			try {
				$this->transfer_util->save_queue_status( $queue_data, $state_data['stage'], $state_data['remote_state_id'] );
			} catch ( \Exception $e ) {
				return $this->transfer_util->ajax_error( sprintf( __( 'Unable to save remote queue status - %s', 'wp-migrate-db' ), $e->getMessage() ) );
			}

			return $this->http->end_ajax( json_encode( true ) );
		}
	}

	/**
	 * @return null
	 * @throws \Exception
	 */
	public function ajax_respond_to_post_file() {
		$key_rules = array(
			'action'          => 'key',
			'remote_state_id' => 'key',
			'stage'           => 'string',
			'intent'          => 'string',
			'sig'             => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'stage',
			'intent',
		) );

		$settings = $this->settings;

		if ( ! isset( $_POST['content'] ) || ! $this->http_helper->verify_signature( $filtered_post, $settings['key'] ) ) {
			throw new \Exception( __( 'Failed to respond to payload post.', 'wp-migrate-db' ) );
		}

		$payload_content = filter_var( $_POST['content'], FILTER_SANITIZE_STRING );
		$receiver        = $this->receiver;

		try {
			$receiver->receive_post_data( $state_data['stage'], $payload_content );
		} catch ( \Exception $e ) {
			return $this->transfer_util->catch_general_error( $e->getMessage() );
		}
	}
}
