<?php
/**
 * Opens the block editor without fullscreen mode, while users who turn it on keep it.
 *
 * @package WPNuxt
 */

namespace WPNuxt\Block_Editor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core sets its own default (fullscreen on) in initializeEditor(), so ours is set after the
 * editor has loaded. A default only applies until the user picks the mode themselves.
 */
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\default_fullscreen_off' );
function default_fullscreen_off() {
	wp_add_inline_script(
		'wp-edit-post',
		"wp.domReady( function() {
			if ( ! window._wpLoadBlockEditor || ! wp.preferences ) {
				return;
			}
			window._wpLoadBlockEditor.then( function() {
				wp.data.dispatch( wp.preferences.store ).setDefaults( 'core/edit-post', { fullscreenMode: false } );
			} );
		} );"
	);
}
