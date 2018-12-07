<?php

/**
 * Add a duplicate post link.
 *
 * @since 2.20
 */
function mtphr_post_duplicator_action_row_link( $post ) {

	$settings = get_mtphr_post_duplicator_settings();

	// Get the post type object
	$post_type = get_post_type_object( $post->post_type );
	
	// Set the button label
	$label = sprintf( __( 'Duplicate %s', 'post-duplicator' ), $post_type->labels->singular_name );
	
	// Modify the label if duplicating to new post type
	if( $settings['type'] != 'same' ) {
		$new_post_type = get_post_type_object(  $settings['type'] );
		if( $post_type->name != $new_post_type->name ) {
			$label = sprintf( __( 'Duplicate %1$s to %2$s', 'post-duplicator' ), $post_type->labels->singular_name, $new_post_type->labels->singular_name );
		}
	}
	
	// Create a nonce & add an action
	$nonce = wp_create_nonce( 'm4c_ajax_file_nonce' );
	
	// Return the link
	return '<a class="m4c-duplicate-post" rel="'.$nonce.'" href="#" data-postid="'.$post->ID.'">'.$label.'</a>';
}

// Add the duplicate link to post actions
function mtphr_post_duplicator_action_row( $actions, $post ){
	if( function_exists('mtphr_post_duplicator_action_row_link') ) {
		$actions['duplicate_post'] = mtphr_post_duplicator_action_row_link( $post );
	}
	return $actions;
}
add_filter( 'post_row_actions', 'mtphr_post_duplicator_action_row', 10, 2 );
add_filter( 'page_row_actions', 'mtphr_post_duplicator_action_row', 10, 2 );
add_filter( 'cuar/core/admin/content-list-table/row-actions', 'mtphr_post_duplicator_action_row', 10, 2 );

