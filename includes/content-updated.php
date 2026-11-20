<?php
/**
 * Records when content was last saved and exposes it at /wp-json/wpnuxt/v1/updated,
 * so the frontend can check whether it needs to refresh.
 *
 * @package WPNuxt
 */

namespace WPNuxt\Content_Updated;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OPTION = 'wpnuxt_last_updated';

add_action( 'save_post', __NAMESPACE__ . '\\record_update' );
function record_update( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	update_option( OPTION, time(), false );
}

add_action( 'rest_api_init', __NAMESPACE__ . '\\register_route' );
function register_route() {
	register_rest_route(
		'wpnuxt/v1',
		'/updated',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\get_last_updated',
			'permission_callback' => '__return_true',
		)
	);
}

function get_last_updated() {
	return array( 'lastUpdated' => (int) get_option( OPTION, 0 ) );
}
