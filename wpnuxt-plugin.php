<?php
/*
 * WPNuxt Plugin
 *
 * @package           WPNuxt
 * @author            Wouter Vernaillen
 * @copyright         2024 Wouter Vernaillen
 * @license           GPL-2.0-or-later
 * 
 * @wordpress-plugin
 * Plugin Name:       WPNuxt
 * Plugin URI:        https://wpnuxt.com
 * Description:       A plugin to prepare WordPress as a headless CMS to use with WPNuxt
 * Version:           0.0.4
 * Requires at least: 6.0
 * Tested up to:      6.5
 * Requires PHP:      7.4
 * Author:            Wouter Vernaillen
 * Author URI:        https://github.com/vernaillen
 * Plugin URI:        https://github.com/vernaillen/wpnuxt-plugin
 * GitHub Plugin URI: https://github.com/vernaillen/wpnuxt-plugin
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpnuxt-plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

define('WPNUXT_PLUGIN_VERSION', '0.0.4');
define('WPNUXT_WP_GRAPHQL_VERSION', '1.28.1');
define('WPNUXT_WP_GRAPHQL_CONTENT_BLOCKS_VERSION', 'v4.1.0');
define('WPNUXT_ADVANCED_CUSTOM_FIELDS_VERSION', '6.3.6');
define('WPNUXT_WP_GRAPHQL_FOR_ACF_VERSION', '2.4.1');

require __DIR__ . '/includes/deny-public-access.php';
require __DIR__ . '/includes/frontend-links.php';
require __DIR__ . '/includes/cache-purge.php';
require __DIR__ . '/includes/content-updated.php';
require __DIR__ . '/includes/block-editor.php';

// Define Globals
global $wpnuxt_plugin_list;
global $github_version;

add_action('admin_enqueue_scripts', function () {
    if (isset($_GET['page']) && $_GET['page'] === 'wpnuxt') {
        wp_enqueue_style('admin_css_wpnuxt', plugins_url('/assets/styles.css', __FILE__), false, WPNUXT_PLUGIN_VERSION);
    }
});


// Add filter to add the settings link to the plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'plugin_action_links_wpnuxt');
function plugin_action_links_wpnuxt($links)
{
    $admin_url = get_admin_url(null, 'options-general.php?page=wpnuxt');
    if (is_array($links)) {
        if (is_string($admin_url)) {
            $links[] = '<a href="' . esc_url($admin_url) . '">Settings</a>';
            return $links;
        } else {
            error_log('WPNuxt: admin_url is not a string');
        }
    } else {
        error_log('WPNuxt: $links is not an array');
    }
}

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$wpnuxt_plugin_list = [
    'wp-graphql' => [
        'name' => 'WPGraphQL',
        'description' => 'A GraphQL API for WordPress with a built-in GraphiQL playground.',
        'url' => 'https://downloads.wordpress.org/plugin/wp-graphql.' . WPNUXT_WP_GRAPHQL_VERSION . '.zip',
        'file' => 'wp-graphql/wp-graphql.php',
        'icon' => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
        'slug' => 'wp-graphql',
        'required' => 'true',
        'info_url' => 'https://www.wpgraphql.com/'
    ],
    'wp-graphql-content-blocks' => [
        'name' => 'WPGraphQL Content Blocks',
        'description' => 'WordPress plugin that extends WPGraphQL to support querying (Gutenberg) Blocks as data.',
        'url' => 'https://github.com/wpengine/wp-graphql-content-blocks/releases/download/' . WPNUXT_WP_GRAPHQL_CONTENT_BLOCKS_VERSION . '/wp-graphql-content-blocks.zip',
        'file' => 'wp-graphql-content-blocks/wp-graphql-content-blocks.php',
        'icon' => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
        'slug' => 'wp-graphql-content-blocks',
        'required' => 'true',
        'info_url' => 'https://www.wpgraphql.com/'
    ],
    'advanced-custom-fields' => [
        'name' => 'Advanced Custom Fields',
        'description' => 'Advanced Custom Fields (ACF) turns WordPress sites into a fully-fledged content management system by giving you all the tools to do more with your data.',
        'url' => 'https://downloads.wordpress.org/plugin/advanced-custom-fields.' . WPNUXT_ADVANCED_CUSTOM_FIELDS_VERSION . '.zip',
        'file' => 'advanced-custom-fields/acf.php',
        'icon' => 'https://ps.w.org/advanced-custom-fields/assets/icon.svg?rev=3096880',
        'slug' => 'advanced-custom-fields',
        'required' => 'false',
        'info_url' => 'https://www.wpgraphql.com/'
    ],
    'wpgraphql-acf' => [
        'name' => 'WPGraphQL for ACF',
        'description' => 'WPGraphQL for Advanced Custom Fields',
        'url' => 'https://downloads.wordpress.org/plugin/wpgraphql-acf.' . WPNUXT_WP_GRAPHQL_FOR_ACF_VERSION . '.zip',
        'file' => 'wpgraphql-acf/wpgraphql-acf.php',
        'icon' => 'https://ps.w.org/wpgraphql-acf/assets/icon-128x128.png?rev=3012214',
        'slug' => 'wpgraphql-acf',
        'required' => 'false',
        'info_url' => 'https://www.wpgraphql.com/'
    ],
];

function wpnuxt_icon_data_uri(int $size): string
{
    return 'data:image/svg+xml;base64,' . base64_encode('<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M281.44 397.667H438.32C443.326 397.667 448.118 395.908 452.453 393.427C456.789 390.946 461.258 387.831 463.76 383.533C466.262 379.236 468.002 374.36 468 369.399C467.998 364.437 466.266 359.563 463.76 355.268L357.76 172.947C355.258 168.65 352.201 165.534 347.867 163.053C343.532 160.573 337.325 158.813 332.32 158.813C327.315 158.813 322.521 160.573 318.187 163.053C313.852 165.534 310.795 168.65 308.293 172.947L281.44 219.587L227.733 129.13C225.229 124.834 222.176 120.307 217.84 117.827C213.504 115.346 208.713 115 203.707 115C198.701 115 193.909 115.346 189.573 117.827C185.238 120.307 180.771 124.834 178.267 129.13L46.8267 355.268C44.3208 359.563 44.0022 364.437 44 369.399C43.9978 374.36 44.3246 379.235 46.8267 383.533C49.3288 387.83 53.7979 390.946 58.1333 393.427C62.4688 395.908 67.2603 397.667 72.2667 397.667H171.2C210.401 397.667 238.934 380.082 258.827 346.787L306.88 263.4L332.32 219.587L410.053 352.44H306.88L281.44 397.667ZM169.787 352.44H100.533L203.707 174.36L256 263.4L221.361 323.784C208.151 345.387 193.089 352.44 169.787 352.44Z" fill="#00DC82"/></svg>');
}

add_action('admin_bar_menu', 'register_wpnuxt_admin_bar_menu', 500);
function register_wpnuxt_admin_bar_menu(WP_Admin_Bar $admin_bar)
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $icon = sprintf(
        '<span class="ab-icon" style="background:url(\'%s\') center/20px no-repeat;"></span>',
        wpnuxt_icon_data_uri(20)
    );
    $admin_bar->add_menu([
        'id'    => 'wpnuxt',
        'title' => $icon . '<span class="ab-label">WPNuxt</span>',
        'href'  => admin_url('admin.php?page=wpnuxt'),
    ]);
}

/**
 * Add the options page
 */
 add_action( 'admin_menu', function () {
    $nuxtIcon = wpnuxt_icon_data_uri(22);
	add_menu_page(
		'WPNuxt Options', 
        'WPNuxt', 
        'manage_options', 
        'wpnuxt',
		false,
		$nuxtIcon,
		80
	);
	add_submenu_page(
		'WPNuxt Options',
		'WPNuxt', 
		'Dashboard',
		'manage_options',
		'wpnuxt',
		'wpNuxtOptionsPageHtml',
	);
});

function wpNuxtOptionsPageHtml()
{
    $options = get_option('wpnuxt_options');

    $nuxtIcon = wpnuxt_icon_data_uri(40);
    $icon = sprintf(
        '<span class="custom-icon" style="
            background-image:url(\'%s\'); float:left; width:40px !important; height:40px !important;
            margin-top: -10px !important; margin-right: 0 !important;
            "></span>',
        $nuxtIcon
    );
    ?>
    <div class="wpnuxt-admin-toolbar">
        <a href="https://wpnuxt.com" class="wpnuxt-logo" target="_blank">
            <h1><?php echo $icon ?> <span class="font-serif">WP</span>Nuxt</h1>
        </a>
        <?php if (isset($options['build_hook'])): ?>
            <button id="deploy-button" class="wpnuxt-button button button-primary button-large">Deploy</button>
        <?php endif;?>
    </div>
    <div class="wrap">
        <form action="options.php" method="post">
            <?php settings_fields('wpnuxt_options');
                do_settings_sections('wpnuxt');
                submit_button();
            ?>
        </form>
    </div>
<?php
}

// Register settings
add_action('admin_init', 'registerWPNuxtSettings');
function registerWPNuxtSettings()
{
    global $wpnuxt_plugin_list;

    register_setting('wpnuxt_options', 'wpnuxt_options');

    // Return true if all plugins are active
    $is_all_plugins_active = array_reduce($wpnuxt_plugin_list, function ($carry, $plugin) {
        return $carry && ($plugin['required'] == 'false' || is_plugin_active($plugin['file']));
    }, true);

    // if all plugins are active don't show required plugins section
    // if (!$is_all_plugins_active) {
        add_settings_section('required_plugins', 'Plugins', 'requiredWPNuxtPluginsCallback', 'wpnuxt');
    // } else {
    //}
    add_settings_section('global_setting', 'Global Settings', 'globalWPNuxtSettingCallback', 'wpnuxt');
}

// Section callback
function requiredWPNuxtPluginsCallback()
{
    global $wpnuxt_plugin_list;    
    // Return true if all plugins are active
    $is_all_plugins_active = array_reduce($wpnuxt_plugin_list, function ($carry, $plugin) {
        return $carry && ($plugin['required'] == 'false' || is_plugin_active($plugin['file']));
    }, true);
    ?>
    <details  <?php echo $is_all_plugins_active ? '' : 'open'; ?>>
        <summary>Required & recommended plugins <?php echo $is_all_plugins_active ? '✅' : '❎'; ?></summary>
        <div class="wpnuxt-section">
            <h4>Required plugins:</h4>
            <ul class="required-plugins-list">
                <?php foreach ($wpnuxt_plugin_list as $plugin): 
                    if ($plugin['required'] === 'false') {
                        continue;
                    }?>
                    <li class="required-plugin">
                        <img src="<?php echo $plugin['icon']; ?>" width="64" height="64">
                        <div>
                            <h4 class="plugin-name"><?php echo $plugin['name']; ?></h4>
                            <p class="plugin-description"><?php echo $plugin['description']; ?></p>
                            <p class="plugin-info"><a href="<?php echo $plugin['info_url']; ?>" target="_blank">Plugin homepage</a></p>
                            <div class="plugin-state plugin-state_<?php echo $plugin['slug']; ?>">
                                <!-- Loadding -->
                                <div class="plugin-state_loading">
                                    <img src="/wp-admin/images/loading.gif" alt="Loading" width="20" height="20" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;" />
                                    Checking
                                </div>

                                <!-- Installed -->
                                <div class="plugin-state_installed" style="display:none;">
                                    <span style="color: #41b782">✅ Installed</span>
                                </div>

                                <!-- Not Installed -->
                                <a class="plugin-state_install button" style="display:none;" href="/wp-admin/options-general.php?page=wpnuxt&install_plugin=<?php echo $plugin['slug']; ?>">Install Now</a>
                                <script>
                                    jQuery(document).ready(function($) {
                                        $.ajax({
                                            url: ajaxurl,
                                            type: 'POST',
                                            data: {
                                                action: 'check_plugin_status',
                                                security: '<?=wp_create_nonce('my_nonce_action')?>',
                                                plugin: '<?=esc_attr($plugin['slug'])?>',
                                                file: '<?=esc_attr($plugin['file'])?>',
                                            },
                                            success(response) {
                                                if (response === 'installed') {
                                                    $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_installed').show();
                                                } else {
                                                    $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_install').show();
                                                }
                                                $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_loading').hide();
                                            },
                                            error(error) {
                                                console.log(error);
                                            }
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                    </li>
                <?php endforeach;?>
            </ul>
            <h4>Recommended plugins:</h4>
            <ul class="required-plugins-list">
                <?php foreach ($wpnuxt_plugin_list as $plugin):
                    if ($plugin['required'] === 'true') {
                        continue;
                    }?>
                    <li class="required-plugin">
                        <img src="<?php echo $plugin['icon']; ?>" width="64" height="64">
                        <div>
                            <h4 class="plugin-name"><?php echo $plugin['name']; ?></h4>
                            <p class="plugin-description"><?php echo $plugin['description']; ?></p>
                            <p class="plugin-info"><a href="<?php echo $plugin['info_url']; ?>" target="_blank">Plugin homepage</a></p>
                            <div class="plugin-state plugin-state_<?php echo $plugin['slug']; ?>">
                                <!-- Loadding -->
                                <div class="plugin-state_loading">
                                    <img src="/wp-admin/images/loading.gif" alt="Loading" width="20" height="20" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;" />
                                    Checking
                                </div>

                                <!-- Installed -->
                                <div class="plugin-state_installed" style="display:none;">
                                    <span style="color: #41b782">✅ Installed</span>
                                </div>

                                <!-- Not Installed -->
                                <a class="plugin-state_install button" style="display:none;" href="/wp-admin/options-general.php?page=wpnuxt&install_plugin=<?php echo $plugin['slug']; ?>">Install Now</a>
                                <script>
                                    jQuery(document).ready(function($) {
                                        $.ajax({
                                            url: ajaxurl,
                                            type: 'POST',
                                            data: {
                                                action: 'check_plugin_status',
                                                security: '<?=wp_create_nonce('my_nonce_action')?>',
                                                plugin: '<?=esc_attr($plugin['slug'])?>',
                                                file: '<?=esc_attr($plugin['file'])?>',
                                            },
                                            success(response) {
                                                if (response === 'installed') {
                                                    $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_installed').show();
                                                } else {
                                                    $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_install').show();
                                                }
                                                $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_loading').hide();
                                            },
                                            error(error) {
                                                console.log(error);
                                            }
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                    </li>
                <?php endforeach;?>
            </ul>
        </div>
    </details>
    <?php
/**
     * Check if the plugin is installed.
     */
    if (isset($_GET['install_plugin'])) {
        global $wpnuxt_plugin_list;

        $upgrader = new Plugin_Upgrader();
        $plugin = $wpnuxt_plugin_list[$_GET['install_plugin']];
        $fileURL = WP_PLUGIN_DIR . '/' . $plugin['file'];

        if (!is_plugin_active($plugin['file'])) {
            if (file_exists($fileURL)) {
                activate_plugin($plugin['file'], '/wp-admin/options-general.php?page=wpnuxt');
            } else {
                $result = $upgrader->install($plugin['url']);
                if (!is_wp_error($result)) {
                    activate_plugin($plugin['file']);
                }
            }
        }
    }

    $gql_settings = get_option('graphql_general_settings');
    $frontend_uri = \WPNuxt\Deny_Public_Access\get_frontend_uri();

    $permalink_structure = get_option( 'permalink_structure' );

    // Enable Public Introspection
    $publicIntrospectionEnabled = isset($gql_settings['public_introspection_enabled']) ? $gql_settings['public_introspection_enabled'] == 'on' : false;
    $frontend_uri_is_set = str_starts_with($frontend_uri, 'http');

    $permalink_structure_is_set = $permalink_structure == '/%postname%/';

    $allSettingHaveBeenMet =
        $publicIntrospectionEnabled && 
        $frontend_uri_is_set &&
        $permalink_structure_is_set
    ?>

    <details <?php echo $allSettingHaveBeenMet ? '' : 'open'; ?>>
        <summary>Required plugin settings for WPNuxt <?php echo $allSettingHaveBeenMet ? '✅' : '❎'; ?></summary>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <td colspan="2">
                        <p>These settings are required for WPNuxt to work properly. Click the links below to go to the respective settings page.</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><h4><a href="/wp-admin/admin.php?page=graphql-settings">WPGraphQL settings</a></h4></td>
                </tr>
                <tr>
                    <td scope="row">Enable Public Introspection.</td>
                    <td>
                        <div class="flex">
                            <span style="color: #D63638;"><?php echo $publicIntrospectionEnabled ? '✅' : '❎'; ?></span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><h4>WPNuxt Global Settings (below)</h4></td>
                </tr>
                <tr>
                    <td scope="row">Front End URL</td>
                    <td>
                        <div class="flex">
                            <span style="color: #D63638;"><?php echo $frontend_uri_is_set ? '✅ ' . esc_html($frontend_uri) : '❎'; ?></span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><h4><a href="/wp-admin/options-permalink.php">Permalinks</a></h4></td>
                </tr>
                <tr>
                    <td scope="row">Set permalink structure to: /%postname%/</td>
                    <td>
                        <div class="flex">
                            <span style="color: #D63638;"><?php echo ($permalink_structure_is_set ? '✅ ' : '❎') . ' ' . $permalink_structure; ?></span>
                        </div>
                    </td>
                </tr>
                
            </tbody>
        </table>
    </details>
<?php
}

// Field callback
function globalWPNuxtSettingCallback()
{
    $options = get_option('wpnuxt_options');

    $frontend_uri = \WPNuxt\Deny_Public_Access\get_frontend_uri();
    $wpNuxt = null;
    if ($frontend_uri) {
        $response = wp_remote_get(untrailingslashit($frontend_uri) . '/api/config', [
            'headers' => ['Accept' => 'application/json'],
        ]);
        if (!is_wp_error($response)) {
            $json_obj = json_decode(wp_remote_retrieve_body($response));
            $wpNuxt = $json_obj->wpNuxt ?? null;
        }
    }
    ?>

    <div class="global_setting wpnuxt-section">
        <table class="form-table" role="presentation">
            <tbody>
                <!-- FRONT END URL -->
                <tr>
                    <th scope="row"><label for="wpnuxt_options[frontEndUrl]">Front End URL</label></th>
                    <td>
                        <input type="text" class="widefat" name="wpnuxt_options[frontEndUrl]" value="<?php echo isset($options['frontEndUrl']) ? $options['frontEndUrl'] : ''; ?>" placeholder="e.g. https://my-nuxt-frontend.netlify.app" />
                        <p class="description">This is the URL of your Nuxt site not the WordPress site. When empty, the Faust.js frontend URI is used.</p>
                    </td>
                </tr>
                <!-- PUBLIC REDIRECT -->
                <tr>
                    <th scope="row">Public route redirect</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpnuxt_options[publicRedirect]" value="1" <?php checked(!empty($options['publicRedirect'])); ?> />
                            Redirect requests for the WordPress front end to the Front End URL
                        </label>
                        <p class="description">Replaces the Faust.js "Enable public route redirects" setting. When this is on, Faust's redirect is skipped.</p>
                    </td>
                </tr>
                <!-- PREVIEW LINKS -->
                <tr>
                    <th scope="row">Preview links</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpnuxt_options[previewLinks]" value="1" <?php checked(!empty($options['previewLinks'])); ?> />
                            Open the editor's Preview on the Front End URL
                        </label>
                        <p class="description">Replaces Faust.js's preview links. Keeps WordPress's preview parameters, which WPNuxt's preview mode reads.</p>
                    </td>
                </tr>
                <!-- LINK REWRITES -->
                <tr>
                    <th scope="row">Permalinks and sitemaps</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpnuxt_options[rewriteLinks]" value="1" <?php checked(!empty($options['rewriteLinks'])); ?> />
                            Point post, page and term links and sitemap URLs to the Front End URL
                        </label>
                        <p class="description">Replaces Faust.js's "Enable Post and Category URL rewrites". GraphQL responses keep the WordPress URLs.</p>
                    </td>
                </tr>
                <!-- CACHE PURGE -->
                <tr>
                    <th scope="row">Cache purge</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpnuxt_options[purgeCache]" value="1" <?php checked(!empty($options['purgeCache'])); ?> />
                            Purge the frontend cache when a post or page is saved
                        </label>
                        <p class="description">Calls <code>/api/purgeCache</code> on the Front End URL with the token below in the <code>x-nuxt-multi-cache-token</code> header.</p>
                        <input type="password" class="regular-text" name="wpnuxt_options[cacheToken]" value="<?php echo esc_attr($options['cacheToken'] ?? ''); ?>" placeholder="nuxt-multi-cache token" autocomplete="off" />
                    </td>
                </tr>
                <!-- WORDFENCE CENTRAL -->
                <tr>
                    <th scope="row">Wordfence Central</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpnuxt_options[allowWordfenceCentral]" value="1" <?php checked(!empty($options['allowWordfenceCentral'])); ?> />
                            Allow Wordfence Central to connect
                        </label>
                        <p class="description">Don't redirect the Wordfence Central pairing link (<code>?wfcentral_admin_redirect=…</code>), so pairing reaches wp-admin.</p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php if ($wpNuxt): ?>
            hasBlocksModule: <?php echo esc_html($wpNuxt->hasBlocksModule ?? ''); ?><br>
            hasAuthModule: <?php echo esc_html($wpNuxt->hasAuthModule ?? ''); ?><br>
            defaultMenuName: <?php echo esc_html($wpNuxt->defaultMenuName ?? ''); ?><br>
        <?php endif; ?>
    </div>
<?php
}

/**
 * Check if a plugin is active
 */
add_action('wp_ajax_check_plugin_status', function () {
    check_ajax_referer('my_nonce_action', 'security');

    // Get the plugin slug and file from the AJAX request
    $plugin_file = sanitize_text_field($_POST['file']);
    echo is_plugin_active($plugin_file) ? 'installed' : 'not_installed';

    wp_die();
});