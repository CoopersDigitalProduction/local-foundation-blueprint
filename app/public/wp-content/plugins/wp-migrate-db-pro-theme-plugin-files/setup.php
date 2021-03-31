<?php

function wpmdb_setup_theme_plugin_files_addon( $cli ) {
	global $wpmdbpro_theme_plugin_files;

	// Allows hooks to bypass the regular admin / ajax checks to force load the addon (required for the CLI addon).
	$force_load = apply_filters( 'wp_migrate_db_pro_theme_plugin_files_force_load', false );

	if ( false === $force_load && ! is_null( $wpmdbpro_theme_plugin_files ) ) {
		return $wpmdbpro_theme_plugin_files;
	}

	if ( false === $force_load && ( ! function_exists( 'wp_migrate_db_pro_loaded' ) || ! wp_migrate_db_pro_loaded() ) ) {
		return false;
	}

	$container = \DeliciousBrains\WPMDB\Container::getInstance();

	if( class_exists( '\DeliciousBrains\WPMDB\Pro\ServiceProvider' ) ){
		$container->get( 'tp_addon' )->register();
		$container->get( 'tp_addon_local' )->register();
		$container->get( 'tp_addon_remote' )->register();

		if ( $cli ) {
			//		$wpmdbpro_theme_plugin_files = \DeliciousBrains\WPMDB\Container::getInstance()->get( 'tp_addon_cli' );
		} else {
			$wpmdbpro_theme_plugin_files = \DeliciousBrains\WPMDB\Container::getInstance()->get( 'tp_addon' );
		}
	}

	load_plugin_textdomain( 'wp-migrate-db-pro-theme-plugin-files', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	return $wpmdbpro_theme_plugin_files;
}

