<?php

function wpmdb_setup_multisite_tools_addon( $cli ) {
	global $wpmdbpro_multisite_tools;

	$container = \DeliciousBrains\WPMDB\Container::getInstance();

	if ( class_exists( '\DeliciousBrains\WPMDB\Pro\ServiceProvider' ) ) {
		$container->get( 'mst_addon' )->register();
		$container->get( 'mst_addon_cli' )->register();
		if ( $cli ) {
			$wpmdbpro_multisite_tools = \DeliciousBrains\WPMDB\Container::getInstance()->get( 'mst_addon_cli' );
		} else {
			$wpmdbpro_multisite_tools = \DeliciousBrains\WPMDB\Container::getInstance()->get( 'mst_addon' );
		}
	}

	// Allows hooks to bypass the regular admin / ajax checks to force load the addon (required for the CLI addon).
	$force_load = apply_filters( 'wp_migrate_db_pro_multisite_tools_force_load', false );

	if ( false === $force_load && ! is_null( $wpmdbpro_multisite_tools ) ) {
		return $wpmdbpro_multisite_tools;
	}

	if ( false === $force_load && ( ! function_exists( 'wp_migrate_db_pro_loaded' ) || ! wp_migrate_db_pro_loaded() || ( is_multisite() && wp_is_large_network() ) ) ) {
		return false;
	}

	load_plugin_textdomain( 'wp-migrate-db-pro-multisite-tools', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	return $wpmdbpro_multisite_tools;
}

