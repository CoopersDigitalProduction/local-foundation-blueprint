<?php

namespace DeliciousBrains\WPMDBTP;

use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Profile\ProfileManager;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Container;
use DeliciousBrains\WPMDB\Pro\Addon\Addon;
use DeliciousBrains\WPMDB\Pro\Addon\AddonAbstract;
use DeliciousBrains\WPMDB\Pro\Transfers\Receiver;
use DeliciousBrains\WPMDB\Pro\UI\Template;

class ThemePluginFilesAddon extends AddonAbstract {

	/**
	 * An array strings used for translations
	 *
	 * @var array $strings
	 */
	protected $strings;

	/**
	 * @var array $default_file_ignores
	 */
	protected $default_file_ignores;

	/**
	 * @var object $file_ignores
	 */
	protected $file_ignores;

	/**
	 * @var array $accepted_fields
	 */
	protected $accepted_fields;
	public $transfer_helpers;
	public $receiver;
	public $plugin_dir_path;
	public $plugin_folder_name;
	public $plugins_url;
	public $template_path;
	/**
	 * @var Template
	 */
	public $template;
	/**
	 * @var Filesystem
	 */
	public $filesystem;
	/**
	 * @var ProfileManager
	 */
	public $profile_manager;
	/**
	 * @var Util
	 */
	private $util;
	/**
	 * @var ThemePluginFilesFinalize
	 */
	private $theme_plugin_files_finalize;

	const MDB_VERSION_REQUIRED = '1.9.3b1';

	public function __construct(
		Addon $addon,
		Properties $properties,
		Template $template,
		Filesystem $filesystem,
		ProfileManager $profile_manager,
		Util $util,
		\DeliciousBrains\WPMDB\Pro\Transfers\Files\Util $transfer_helpers,
		Receiver $receiver,
		ThemePluginFilesFinalize $theme_plugin_files_finalize
	) {
		parent::__construct(
			$addon,
			$properties
		);

		$this->plugin_slug        = 'wp-migrate-db-pro-theme-plugin-files';
		$this->plugin_version     = $GLOBALS['wpmdb_meta']['wp-migrate-db-pro-theme-plugin-files']['version'];
		$plugin_file_path         = dirname( __DIR__ ) . '/wp-migrate-db-pro-theme-plugin-files.php';
		$this->plugin_dir_path    = plugin_dir_path( $plugin_file_path );
		$this->plugin_folder_name = basename( $this->plugin_dir_path );
		$this->plugins_url        = trailingslashit( plugins_url( $this->plugin_folder_name ) );
		$this->template_path      = $this->plugin_dir_path . 'template/';

		$this->transfer_helpers = $transfer_helpers;
		$this->receiver         = $receiver;

		// Fields that can be saved in a 'profile'
		$this->accepted_fields = array(
			'migrate_themes',
			'migrate_plugins',
			'select_plugins',
			'select_themes',
			'file_ignores',
		);

		$this->template                    = $template;
		$this->filesystem                  = $filesystem;
		$this->profile_manager             = $profile_manager;
		$this->util                        = $util;
		$this->theme_plugin_files_finalize = $theme_plugin_files_finalize;
	}

	public function register() {
		if ( ! $this->meets_version_requirements( self::MDB_VERSION_REQUIRED ) ) {
			return;
		}

		// Register Queue manager actions
		Container::getInstance()->get( 'queue_manager' )->register();
		add_action( 'admin_init', [ $this, 'plugin_name' ] );

		add_action( 'wpmdb_before_finalize_migration', array( $this->theme_plugin_files_finalize, 'maybe_finalize_tp_migration' ) );
		add_action( 'wpmdb_migration_complete', array( $this->theme_plugin_files_finalize, 'cleanup_transfer_migration' ) );
		add_action( 'wpmdb_respond_to_push_cancellation', array( $this->theme_plugin_files_finalize, 'remove_tmp_files' ) );
		add_action( 'wpmdb_cancellation', array( $this->theme_plugin_files_finalize, 'remove_tmp_files' ) );

		add_action( 'wpmdb_after_advanced_options', array( $this, 'migration_form_controls' ) );
		add_action( 'wpmdb_load_assets', array( $this, 'load_assets' ) );
		add_filter( 'wpmdb_diagnostic_info', array( $this, 'diagnostic_info' ) );
		add_filter( 'wpmdb_establish_remote_connection_data', array( $this, 'establish_remote_connection_data' ) );
		add_filter( 'wpmdb_accepted_profile_fields', array( $this, 'accepted_profile_fields' ) );
		add_filter( 'wpmdb_nonces', array( $this, 'add_nonces' ) );
		add_filter( 'wpmdb_data', array( $this, 'js_variables' ) );
		add_filter( 'wpmdb_site_details', array( $this, 'filter_site_details' ) );
	}

	public function plugin_name() {
		$this->addon_name = $this->addon->get_plugin_name( 'wp-migrate-db-pro-theme-plugin-files/wp-migrate-db-pro-theme-plugin-files.php' );
	}

	/**
	 * Whitelist media setting fields for use in AJAX save in core
	 *
	 * @param array $profile_fields Array of profile fields
	 *
	 * @return array Updated array of profile fields
	 */
	public function accepted_profile_fields( $profile_fields ) {
		return array_merge( $profile_fields, $this->accepted_fields );
	}

	/**
	 * Load media related assets in core plugin
	 */
	public function load_assets() {
		$plugins_url = trailingslashit( plugins_url( $this->plugin_folder_name ) );
		$version     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->plugin_version;
		$ver_string  = '-' . str_replace( '.', '', $this->plugin_version );

		$src = $plugins_url . 'asset/build/css/styles.css';
		wp_enqueue_style( 'wp-migrate-db-pro-theme-plugin-files-styles', $src, array( 'wp-migrate-db-pro-styles' ), $version );

		$src = $plugins_url . "asset/build/js/bundle{$ver_string}.js";
		wp_enqueue_script( 'wp-migrate-db-pro-theme-plugin-files-script', $src, array(
			'jquery',
			'wp-migrate-db-pro-script',
		), $version, true );

		wp_localize_script( 'wp-migrate-db-pro-theme-plugin-files-script', 'wpmdbtp_settings', $this->localize_scripts() );
	}

	public function localize_scripts() {
		$loaded_profile = $this->profile_manager->default_profile;

		if ( isset( $_GET['wpmdb-profile'] ) ) {
			$loaded_profile = $this->profile_manager->get_profile( (int) $_GET['wpmdb-profile'] );
		}

		return array(
			'strings'        => $this->get_strings(),
			'loaded_profile' => $loaded_profile,
		);
	}


	/**
	 * Get translated strings for javascript and other functions
	 *
	 * @return array Array of translations
	 */
	public function get_strings() {
		$strings = array(
			'themes'                 => __( 'Themes', 'wp-migrate-db-pro-theme-plugin-files' ),
			'plugins'                => __( 'Plugins', 'wp-migrate-db-pro-theme-plugin-files' ),
			'theme_and_plugin_files' => __( 'Theme & Plugin Files', 'wp-migrate-db-pro-theme-plugin-files' ),
			'theme_active'           => __( '(active)', 'wp-migrate-db-pro-theme-plugin-files' ),
			'select_themes'          => __( 'Please select themes for migration.', 'wp-migrate-db-pro-theme-plugin-files' ),
			'select_plugins'         => __( 'Please select plugins for migration.', 'wp-migrate-db-pro-theme-plugin-files' ),
			'remote'                 => __( 'remote', 'wp-migrate-db-pro-theme-plugin-files' ),
			'local'                  => __( 'local', 'wp-migrate-db-pro-theme-plugin-files' ),
			'failed_to_transfer'     => __( 'Failed to transfer file.', 'wp-migrate-db-pro-theme-plugin-files' ),
			'file_transfer_error'    => __( 'Theme & Plugin Files Transfer Error', 'wp-migrate-db-pro-theme-plugin-files' ),
			'loading_transfer_queue' => __( 'Loading transfer queue', 'wp-migrate-db-pro-theme-plugin-files' ),
			'current_transfer'       => __( 'Transferring: ', 'wp-migrate-db-pro-theme-plugin-files' ),
		);

		if ( is_null( $this->strings ) ) {
			$this->strings = $strings;
		}

		return $this->strings;
	}

	/**
	 * Add media related javascript variables to the page
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function js_variables( $data ) {
		$data['theme_plugin_files_version'] = $this->plugin_version;

		return $data;
	}


	/**
	 * Adds extra information to the core plugin's diagnostic info
	 */
	public function diagnostic_info( $diagnostic_info ) {
		$diagnostic_info['themes-plugins'] = array(
			"Theme & Plugin Files",
			'Transfer Bottleneck' => size_format( $this->get_transfer_bottleneck() ),
			'Themes Permissions'  => decoct( fileperms( $this->filesystem->slash_one_direction( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' ) ) & 0777 ),
			'Plugins Permissions' => decoct( fileperms( $this->filesystem->slash_one_direction( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' ) ) & 0777 ),
		);

		return $diagnostic_info;
	}

	/**
	 * Check the remote site has the media addon setup
	 *
	 * @param array $data Connection data
	 *
	 * @return array Updated connection data
	 */
	public function establish_remote_connection_data( $data ) {
		$data['theme_plugin_files_available'] = '1';
		$data['theme_plugin_files_version']   = $this->plugin_version;

		//@TODO - move to core plugin
		if ( function_exists( 'ini_get' ) ) {
			$max_file_uploads = ini_get( 'max_file_uploads' );
		}

		$max_file_uploads                            = ( empty( $max_file_uploads ) ) ? 20 : $max_file_uploads;
		$data['theme_plugin_files_max_file_uploads'] = apply_filters( 'wpmdbtp_max_file_uploads', $max_file_uploads );

		return $data;
	}

	/**
	 * Media addon nonces for core javascript variables
	 *
	 * @param array $nonces Array of nonces
	 *
	 * @return array Updated array of nonces
	 */
	public function add_nonces( $nonces ) {
		$nonces['wpmdb_migrate_themes_plugins']  = Util::create_nonce( 'migrate-themes-plugins' );
		$nonces['wpmdb_save_ignores']            = Util::create_nonce( 'wpmdb-save-ignores' );
		$nonces['wpmdb_initiate_file_migration'] = Util::create_nonce( 'wpmdb-initiate-file-migration' );
		$nonces['wpmdb_transfer_files']          = Util::create_nonce( 'wpmdb-transfer-files' );
		$nonces['wpmdb_get_queue_items']         = Util::create_nonce( 'wpmdb-get-queue-items' );

		return $nonces;
	}

	public function migration_form_controls() {
		$this->template->template( 'migrate', '', [], $this->template_path );
	}

	/**
	 *
	 * @return array
	 *
	 */

	public function get_local_themes() {
		$themes       = wp_get_themes();
		$active_theme = wp_get_theme();
		$set_active   = false;
		$theme_list   = array();

		foreach ( $themes as $key => $theme ) {
			if ( ! is_multisite() ) {
				$set_active = ( $key == $active_theme->stylesheet );
			}

			$theme_list[ $key ] = array(
				array(
					'name'   => html_entity_decode( $theme->Name ),
					'active' => $set_active,
					'path'   => $this->filesystem->slash_one_direction( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $key ),
				),
			);
		}

		return $theme_list;
	}

	/**
	 * @return array
	 */
	public function get_plugin_paths() {
		$plugin_root = $this->filesystem->slash_one_direction( WP_PLUGIN_DIR );

		$plugins_dir  = @opendir( $plugin_root );
		$plugin_files = array();

		if ( $plugins_dir ) {
			while ( false !== ( $file = readdir( $plugins_dir ) ) ) {
				if ( '.' === $file[0] ) {
					continue;
				}

				if ( stristr( $file, 'wp-migrate-db' ) ) {
					continue;
				}

				if ( is_dir( $plugin_root . DIRECTORY_SEPARATOR . $file ) ) {
					$plugin_files[ $file ] = $plugin_root . DIRECTORY_SEPARATOR . $file;
				} else {
					if ( '.php' === substr( $file, - 4 ) ) {
						$plugin_files[ $file ] = $plugin_root . DIRECTORY_SEPARATOR . $file;
					}
				}
			}
			closedir( $plugins_dir );
		}

		return $plugin_files;
	}

	/**
	 * @return array
	 */
	public function get_local_plugins() {
		$plugins      = get_plugins();
		$plugin_paths = $this->get_plugin_paths();

		// @TODO get MU plugins in the list as well
		$active_plugins = $this->get_active_plugins();

		$plugin_list = array();

		foreach ( $plugins as $key => $plugin ) {
			$base_folder = preg_replace( '/\/(.*)\.php/i', '', $key );

			$plugin_excluded = $this->check_plugin_exclusions( $base_folder );

			if ( $plugin_excluded ) {
				continue;
			}

			$plugin_path         = array_key_exists( $base_folder, $plugin_paths ) ? $plugin_paths[ $base_folder ] : false;
			$plugin_list[ $key ] = array(
				array(
					'name'   => $plugin['Name'],
					'active' => in_array( $key, $active_plugins ),
					'path'   => $plugin_path,
				),
			);
		}

		return $plugin_list;
	}

	/**
	 *
	 * @param string $plugin
	 *
	 * @return bool
	 */
	public function check_plugin_exclusions( $plugin ) {

		// Exclude MDB plugins
		$plugin_exclusions = apply_filters( 'wpmdbtp_plugin_list', array( 'wp-migrate-db' ) );

		foreach ( $plugin_exclusions as $exclusion ) {
			if ( stristr( $plugin, $exclusion ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array|bool|mixed|void
	 */
	protected function get_active_plugins() {
		$active_plugins = get_option( 'active_plugins' );

		if ( is_multisite() ) {

			// get active plugins for the network
			$network_plugins = get_site_option( 'active_sitewide_plugins' );
			if ( $network_plugins ) {
				$network_plugins = array_keys( $network_plugins );
				$active_plugins  = array_merge( $active_plugins, $network_plugins );
			}
		}

		return $active_plugins;
	}

	/**
	 * @param $site_details
	 *
	 * @return mixed
	 */
	public function filter_site_details( $site_details ) {
		$folder_writable = $this->receiver->is_tmp_folder_writable( 'themes' );

		$site_details['plugins']                   = $this->get_local_plugins();
		$site_details['plugins_path']              = $this->filesystem->slash_one_direction( WP_PLUGIN_DIR );
		$site_details['themes']                    = $this->get_local_themes();
		$site_details['themes_path']               = $this->filesystem->slash_one_direction( WP_CONTENT_DIR ) . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
		$site_details['content_dir']               = $this->filesystem->slash_one_direction( WP_CONTENT_DIR );
		$site_details['local_tmp_folder_check']    = $folder_writable;
		$site_details['local_tmp_folder_writable'] = $folder_writable['status'];
		$site_details['transfer_bottleneck']       = $this->get_transfer_bottleneck();
		$site_details['max_request_size']          = $this->util->get_bottleneck();
		$site_details['php_os']                    = PHP_OS;

		return $site_details;
	}

	/**
	 *
	 * @return int
	 */
	public function get_transfer_bottleneck() {
		$bottleneck = $this->util->get_max_upload_size();

		// Subtract 250 KB from min for overhead
		$bottleneck -= 250000;

		return $bottleneck;
	}
}
