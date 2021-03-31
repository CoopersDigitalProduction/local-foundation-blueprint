<?php global $loaded_profile; ?>
<div class="option-section themes-plugins-options hidden">
	<label class="themes-plugins checkbox-label" for="migrate_themes">
		<input type="checkbox" name="migrate_themes" value="1" class="themes-plugins-toggle" data-available="1" id="migrate_themes"<?php echo( isset( $loaded_profile['migrate_themes'] ) ? ' checked="checked"' : '' ); ?> />
		<?php _e( 'Theme Files', 'wp-migrate-db-pro-theme-plugin-files' ); ?>
	</label>

	<div class="indent-wrap expandable-content select-wrap themes-wrap">
		<select multiple="multiple" name="select_themes[]" id="select-themes" class="multiselect" autocomplete="off">
		</select>
		<br />
		<a href="#" class="multiselect-select-all js-action-link"><?php _e( 'Select All', 'wp-migrate-db' ); ?></a>
		<span class="select-deselect-divider">/</span>
		<a href="#" class="multiselect-deselect-all js-action-link"><?php _e( 'Deselect All', 'wp-migrate-db' ); ?></a>
		<span class="select-deselect-divider">/</span>
		<a href="#" class="multiselect-invert-selection js-action-link"><?php _e( 'Invert Selection', 'wp-migrate-db' ); ?></a>
	</div>
</div>

<div class="option-section themes-plugins-options hidden">
	<label class="themes-plugins checkbox-label" for="migrate_plugins">
		<input type="checkbox" name="migrate_plugins" value="1" class="themes-plugins-toggle" data-available="1" id="migrate_plugins"<?php echo( isset( $loaded_profile['migrate_plugins'] ) ? ' checked="checked"' : '' ); ?> />
		<?php _e( 'Plugin Files', 'wp-migrate-db-pro-theme-plugin-files' ); ?>
	</label>

	<div class="indent-wrap expandable-content select-wrap plugins-wrap">
		<select multiple="multiple" name="select_plugins[]" id="select-plugins" class="multiselect" autocomplete="off">
		</select>
		<br />
		<a href="#" class="multiselect-select-all js-action-link"><?php _e( 'Select All', 'wp-migrate-db' ); ?></a>
		<span class="select-deselect-divider">/</span>
		<a href="#" class="multiselect-deselect-all js-action-link"><?php _e( 'Deselect All', 'wp-migrate-db' ); ?></a>
		<span class="select-deselect-divider">/</span>
		<a href="#" class="multiselect-invert-selection js-action-link"><?php _e( 'Invert Selection', 'wp-migrate-db' ); ?></a>
	</div>
</div>
<div class="option-section exclude-paths" style="display:none">
	<div class="header-expand-collapse clearfix">
		<div class="expand-collapse-arrow collapsed">&#x25BC;</div>
		<div class="option-heading tables-header"><?php _e( 'Exclude Files', 'wp-migrate-db' ); ?></div>
	</div>

	<div class="indent-wrap expandable-content">
		<p><?php _e( 'Skip transferring files matching the following (use <a href="https://deliciousbrains.com/wp-migrate-db-pro/doc/ignored-files/" target="_blank">.gitignore syntax</a>)', 'wp-migrate-db' ); ?></p>

		<div class="wrapper">
			<textarea name="file_ignores" id="file-ignores"><?php echo isset( $loaded_profile['file_ignores'] ) ? $loaded_profile['file_ignores'] : ".DS_Store\n.git\nnode_modules"; ?></textarea>
		</div>
	</div>
</div>

<div class="option-section hidden themes-plugins-errors">
	<p class="themes-plugins-migration-unavailable inline-message warning themes-plugins-message" style="display: none; margin: 10px 0 0 0;">
		<strong><?php _e( 'Addon Missing', 'wp-migrate-db-pro-theme-plugin-files' ); ?></strong> &mdash; <?php _e( 'The Theme & Plugin Files addon is inactive on the <strong>remote site</strong>. Please install and activate it to enable Theme & Plugin Files migration.', 'wp-migrate-db-pro-theme-plugin-files' ); ?>
	</p>

	<p class="themes-plugins-different-plugin-version-notice inline-message warning themes-plugins-message" style="display: none; margin: 10px 0 0 0;">
		<strong><?php _e( 'Version Mismatch', 'wp-migrate-db-pro-theme-plugin-files' ); ?></strong> &mdash; <?php printf( __( 'We have detected you have version <span class="themes-plugins-remote-version"></span> of WP Migrate DB Pro Theme & Plugin Files at <span class="themes-plugins-remote-location"></span> but are using %1$s here. Please go to the <a href="%2$s">Plugins page</a> on both installs and check for updates.', 'wp-migrate-db-pro-theme-plugin-files' ), $GLOBALS['wpmdb_meta'][ 'wp-migrate-db-pro-theme-plugin-files' ]['version'], network_admin_url( 'plugins.php' ) ); ?>
	</p>

</div>


