<?php

/**
 * Return a value from the options table if it exists,
 * or return a default value
 *
 * @since 2.15
 */
function get_mtphr_post_duplicator_settings() {
	
	// Get the options
	$settings = get_option('mtphr_post_duplicator_settings', array());
	
	$defaults = array(
		'status' => 'same',
		'type' => 'same',
		'timestamp' => 'current',
		'title' => __('Copy', 'post-duplicator'),
		'slug' => 'copy',
		'time_offset' => false,
		'time_offset_days' => 0,
		'time_offset_hours' => 0,
		'time_offset_minutes' => 0,
		'time_offset_seconds' => 0,
		'time_offset_direction' => 'newer'
	);
	
	// Filter the settings
	$settings = apply_filters( 'mtphr_post_duplicator_settings', $settings );
	
	// Return the settings
	return wp_parse_args( $settings, $defaults );
}


function mtphr_post_duplicator_submitbox( $post ) {
	if( $post->post_status == 'publish' ) {
		$post_type = get_post_type_object( $post->post_type );
		$nonce = wp_create_nonce( 'm4c_ajax_file_nonce' );
		?>
		<div class="misc-pub-section misc-pub-duplicator" id="duplicator">
			<a class="m4c-duplicate-post button button-small" rel="<?php echo $nonce; ?>" href="#" data-postid="<?php echo $post->ID; ?>"><?php printf( __( 'Duplicate %s', 'post-duplicator' ), $post_type->labels->singular_name ); ?></a><span class="spinner" style="float:none;margin-top:2px;margin-left:4px;"></span>
		</div>
		<?php
	}
}
add_action( 'post_submitbox_misc_actions', 'mtphr_post_duplicator_submitbox' );