<?php
/**
 * Purges the Nuxt frontend's nuxt-multi-cache when content is saved.
 *
 * @package WPNuxt
 */

namespace WPNuxt\Cache_Purge;

use function WPNuxt\Deny_Public_Access\{
	get_frontend_uri,
	get_wpnuxt_option,
};

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'save_post', __NAMESPACE__ . '\\purge_cache' );
function purge_cache( $post_id ) {
	$frontend_uri = get_frontend_uri();
	if ( ! get_wpnuxt_option( 'purgeCache' ) || ! $frontend_uri || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$purge_url = untrailingslashit( $frontend_uri ) . '/api/purgeCache';
	$response  = wp_remote_post(
		$purge_url,
		array(
			'headers' => array( 'x-nuxt-multi-cache-token' => (string) get_wpnuxt_option( 'cacheToken' ) ),
			'timeout' => 5,
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( 'WPNuxt: cache purge at ' . $purge_url . ' failed: ' . $response->get_error_message() );
	} elseif ( wp_remote_retrieve_response_code( $response ) >= 400 ) {
		error_log( 'WPNuxt: cache purge at ' . $purge_url . ' returned ' . wp_remote_retrieve_response_code( $response ) );
	}
}
