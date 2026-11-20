<?php
/**
 * Redirects WP front-end requests to the Nuxt frontend.
 * Replaces Faust.js's public route redirect, and works with or without Faust.
 *
 * @package WPNuxt
 */

namespace WPNuxt\Deny_Public_Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FAUST_REDIRECT_CALLBACK = 'WPE\FaustWP\Deny_Public_Access\deny_public_access';

function get_wpnuxt_option( string $key ) {
	$options = get_option( 'wpnuxt_options' );
	return is_array( $options ) && isset( $options[ $key ] ) ? $options[ $key ] : null;
}

function is_faust_loaded(): bool {
	return function_exists( 'WPE\FaustWP\Settings\faustwp_get_setting' );
}

function is_redirect_enabled(): bool {
	return (bool) get_wpnuxt_option( 'publicRedirect' );
}

function is_faust_redirect_enabled(): bool {
	return is_faust_loaded() && \WPE\FaustWP\Settings\is_redirects_enabled();
}

/**
 * WPNuxt's own frontend URL, falling back to Faust's so existing sites keep working.
 */
function get_frontend_uri(): string {
	$frontend_uri = (string) get_wpnuxt_option( 'frontEndUrl' );
	if ( ! $frontend_uri && is_faust_loaded() ) {
		$frontend_uri = (string) \WPE\FaustWP\Settings\faustwp_get_setting( 'frontend_uri' );
	}
	return $frontend_uri;
}

/**
 * Wordfence Central pairing sends the browser to the front end with this query arg,
 * and Wordfence handles it on template_redirect at priority 1001, after any redirect.
 */
function is_wordfence_central_request(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return get_wpnuxt_option( 'allowWordfenceCentral' ) && isset( $_GET['wfcentral_admin_redirect'] );
}

/**
 * The theme/plugin file editor loads the front end to check a save for fatal errors.
 *
 * @see https://github.com/WordPress/wordpress-develop/blob/6.6.2/src/wp-includes/load.php
 */
function doing_file_editor_save(): bool {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( ! isset( $_REQUEST['wp_scrape_key'], $_REQUEST['wp_scrape_nonce'] ) ) {
		return false;
	}
	$key   = substr( sanitize_key( wp_unslash( $_REQUEST['wp_scrape_key'] ) ), 0, 32 );
	$nonce = wp_unslash( $_REQUEST['wp_scrape_nonce'] );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	return get_transient( 'scrape_key_' . $key ) === $nonce;
}

add_action( 'template_redirect', __NAMESPACE__ . '\\maybe_unhook_faust_redirect', 0 );
function maybe_unhook_faust_redirect() {
	if ( is_redirect_enabled() || is_wordfence_central_request() ) {
		remove_action( 'template_redirect', FAUST_REDIRECT_CALLBACK, 99 );
	}
}

add_action( 'template_redirect', __NAMESPACE__ . '\\deny_public_access', 99 );
function deny_public_access() {
	if (
		! is_redirect_enabled() ||
		is_wordfence_central_request() ||
		is_customize_preview() ||
		doing_file_editor_save() ||
		is_feed() ||
		is_robots() ||
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		isset( $_GET['_wp-find-template'] ) // Allow loading full site editor.
	) {
		return;
	}

	$frontend_uri = get_frontend_uri();
	if ( ! $frontend_uri ) {
		return;
	}

	$request_uri  = home_url( add_query_arg( null, null ) );
	$redirect_url = str_replace( trailingslashit( get_home_url() ), trailingslashit( $frontend_uri ), $request_uri );

	if ( wp_redirect( esc_url_raw( $redirect_url, array( 'http', 'https' ) ), 302, 'WPNuxt plugin' ) ) {
		exit;
	}
}

add_action( 'admin_notices', __NAMESPACE__ . '\\both_redirects_notice' );
function both_redirects_notice() {
	if ( ! current_user_can( 'manage_options' ) || ! is_redirect_enabled() || ! is_faust_redirect_enabled() ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
		esc_html__( 'Both the WPNuxt and the Faust.js public route redirects are enabled. WPNuxt\'s redirect is used; you can turn off Faust\'s.', 'wpnuxt-plugin' ),
		esc_url( admin_url( 'options-general.php?page=faustwp-settings' ) ),
		esc_html__( 'Faust.js settings', 'wpnuxt-plugin' )
	);
}
