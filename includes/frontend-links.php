<?php
/**
 * Points preview links, permalinks and sitemap URLs to the Nuxt frontend.
 * Replaces Faust.js's preview link and URL rewrites, and works with or without Faust.
 *
 * @package WPNuxt
 */

namespace WPNuxt\Frontend_Links;

use function WPNuxt\Deny_Public_Access\{
	get_frontend_uri,
	get_wpnuxt_option,
	is_faust_loaded,
};

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function is_preview_rewrite_enabled(): bool {
	return get_wpnuxt_option( 'previewLinks' ) && get_frontend_uri();
}

function is_link_rewrite_enabled(): bool {
	return get_wpnuxt_option( 'rewriteLinks' ) && get_frontend_uri();
}

function to_frontend_url( string $url ): string {
	return str_replace( trailingslashit( get_home_url() ), trailingslashit( get_frontend_uri() ), $url );
}

function to_wp_url( string $url ): string {
	return str_replace( trailingslashit( get_frontend_uri() ), trailingslashit( get_home_url() ), $url );
}

/**
 * Faust's own versions of these filters would undo or duplicate ours.
 */
add_action( 'plugins_loaded', __NAMESPACE__ . '\\unhook_faust_filters' );
function unhook_faust_filters() {
	if ( ! is_faust_loaded() ) {
		return;
	}
	$faust = 'WPE\FaustWP\Replacement\\';
	if ( is_preview_rewrite_enabled() ) {
		remove_filter( 'preview_post_link', $faust . 'post_preview_link', 1000 );
		remove_action( 'enqueue_block_editor_assets', $faust . 'enqueue_preview_scripts' );
		remove_filter( 'rest_api_init', $faust . 'register_preview_link_hooks_for_all_draft_post_types' );
	}
	if ( is_link_rewrite_enabled() ) {
		foreach ( array( 'post_link', 'page_link', 'post_type_link' ) as $hook ) {
			remove_filter( $hook, $faust . 'post_link', 1000 );
		}
		remove_filter( 'term_link', $faust . 'term_link', 1000 );
		remove_filter( 'wp_sitemaps_posts_entry', $faust . 'sitemaps_posts_entry' );
		remove_filter( 'wp_sitemaps_taxonomies_entry', $faust . 'sitemaps_taxonomies_entry' );
		remove_filter( 'wpseo_xml_sitemap_post_url', $faust . 'yoast_sitemap_post_url' );
	}
}

/**
 * Sends the editor's Preview button to the same path on the frontend, keeping WordPress's
 * preview params (preview_id, preview_nonce, p, page_id, _thumbnail_id) for WPNuxt's preview mode.
 */
add_filter( 'preview_post_link', __NAMESPACE__ . '\\preview_post_link', 1000, 2 );
function preview_post_link( $link, $post ) {
	if ( ! is_preview_rewrite_enabled() ) {
		return $link;
	}

	$args = wp_parse_args( (string) wp_parse_url( $link, PHP_URL_QUERY ) );
	if ( ! isset( $args['p'] ) && ! isset( $args['page_id'] ) && ! isset( $args['preview_id'] ) ) {
		$args['preview_id'] = $post->ID;
	}
	$args['preview'] = 'true';

	return add_query_arg( $args, untrailingslashit( get_frontend_uri() ) . get_preview_path( $post ) );
}

/**
 * Unpublished posts have no pretty permalink yet, so use the one they will get.
 */
function get_preview_path( \WP_Post $post ): string {
	if ( 'publish' === $post->post_status ) {
		return wp_make_link_relative( get_permalink( $post ) );
	}
	if ( ! function_exists( 'get_sample_permalink' ) ) {
		require_once ABSPATH . 'wp-admin/includes/post.php';
	}
	list( $template, $name ) = get_sample_permalink( $post );
	$url                       = str_replace( array( '%postname%', '%pagename%' ), $name, $template );
	return wp_make_link_relative( $url );
}

add_filter( 'post_link', __NAMESPACE__ . '\\post_link', 1000 );
add_filter( 'page_link', __NAMESPACE__ . '\\post_link', 1000 );
add_filter( 'post_type_link', __NAMESPACE__ . '\\post_link', 1000 );
/**
 * Same exceptions as Faust: the editor's own REST requests, permalink editing and the
 * classic editor's link search keep WordPress URLs, so links inserted in content stay internal.
 */
function post_link( $link ) {
	global $pagenow;
	$editor_pages = array( 'admin-ajax.php', 'index.php', 'edit.php', 'post.php', 'post-new.php', 'upload.php', 'media-new.php' );

	if ( ! is_link_rewrite_enabled() || is_graphql_request() ) {
		return $link;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- only checks which request this is.
	if ( 'post-new.php' === $pagenow && empty( $_POST ) ) {
		return $link;
	}
	if ( in_array( $pagenow, $editor_pages, true ) && ! empty( $_POST['samplepermalinknonce'] ) ) {
		return $link;
	}
	if ( wp_doing_ajax() && isset( $_POST['action'] ) && 'wp-link-ajax' === $_POST['action'] ) {
		return $link;
	}
	// phpcs:enable
	if ( in_array( $pagenow, $editor_pages, true ) && defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) ) {
		return $link;
	}

	return to_frontend_url( $link );
}

add_filter( 'term_link', __NAMESPACE__ . '\\term_link', 1000 );
function term_link( $link ) {
	if ( ! is_link_rewrite_enabled() || is_graphql_request() ) {
		return $link;
	}
	return to_frontend_url( $link );
}

/**
 * WPNuxt queries content by uri, so GraphQL responses keep WordPress URLs.
 */
function is_graphql_request(): bool {
	return function_exists( 'is_graphql_request' ) && \is_graphql_request();
}

add_filter( 'wp_sitemaps_posts_entry', __NAMESPACE__ . '\\sitemap_entry' );
add_filter( 'wp_sitemaps_posts_show_on_front_entry', __NAMESPACE__ . '\\sitemap_entry' );
add_filter( 'wp_sitemaps_taxonomies_entry', __NAMESPACE__ . '\\sitemap_entry' );
add_filter( 'wp_sitemaps_users_entry', __NAMESPACE__ . '\\sitemap_entry' );
add_filter( 'wpseo_sitemap_entry', __NAMESPACE__ . '\\sitemap_entry' );
function sitemap_entry( $entry ) {
	if ( is_link_rewrite_enabled() && is_array( $entry ) && isset( $entry['loc'] ) ) {
		$entry['loc'] = to_frontend_url( $entry['loc'] );
	}
	return $entry;
}

/**
 * Yoast's homepage, posts page and post type archive entries skip wpseo_sitemap_entry.
 */
add_filter( 'wpseo_sitemap_post_type_first_links', __NAMESPACE__ . '\\yoast_first_links' );
function yoast_first_links( $links ) {
	return is_array( $links ) ? array_map( __NAMESPACE__ . '\\sitemap_entry', $links ) : $links;
}

/**
 * Yoast drops sitemap URLs on another domain right after this filter, so hand it the
 * WordPress URL here; sitemap_entry() switches it to the frontend afterwards.
 */
add_filter( 'wpseo_xml_sitemap_post_url', __NAMESPACE__ . '\\yoast_sitemap_post_url' );
function yoast_sitemap_post_url( $url ) {
	return is_link_rewrite_enabled() ? to_wp_url( $url ) : $url;
}
