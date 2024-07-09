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
 * Version:           0.0.1
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

define('WPNUXT_PLUGIN_VERSION', '0.0.1');
define('WP_GRAPHQL_VERSION', '1.27.0');
define('WP_GRAPHQL_CONTENT_BLOCKS_VERSION', 'v4.0.0');
define('FAUST_WP_VERSION', '1.3.1');
define('ADVANCED_CUSTOM_FIELDS_VERSION', '6.3.1');
define('WP_GRAPHQL_FOR_ACF_VERSION', '2.2.0');

// Define Globals
global $plugin_list;
global $github_version;

add_action('admin_enqueue_scripts', function () {
    if (isset($_GET['page']) && $_GET['page'] === 'wpnuxt') {
        wp_enqueue_style('admin_css_wpnuxt', plugins_url('assets/styles.css', __FILE__), false, WPNUXT_PLUGIN_VERSION);
        wp_enqueue_script('admin_js', plugins_url('/assets/admin.js', __FILE__), ['jquery'], WPNUXT_PLUGIN_VERSION, true);
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

$plugin_list = [
    'wp-graphql' => [
        'name' => 'WPGraphQL',
        'description' => 'A GraphQL API for WordPress with a built-in GraphiQL playground.',
        'url' => 'https://downloads.wordpress.org/plugin/wp-graphql.' . WP_GRAPHQL_VERSION . '.zip',
        'file' => 'wp-graphql/wp-graphql.php',
        'icon' => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
        'slug' => 'wp-graphql',
        'required' => 'true',
        'info_url' => 'https://www.wpgraphql.com/'
    ],
    'wp-graphql-content-blocks' => [
        'name' => 'WPGraphQL Content Blocks',
        'description' => 'WordPress plugin that extends WPGraphQL to support querying (Gutenberg) Blocks as data.',
        'url' => 'https://github.com/wpengine/wp-graphql-content-blocks/releases/download/' . WP_GRAPHQL_CONTENT_BLOCKS_VERSION . '/wp-graphql-content-blocks.zip',
        'file' => 'wp-graphql-content-blocks/wp-graphql-content-blocks.php',
        'icon' => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
        'slug' => 'wp-graphql-content-blocks',
        'required' => 'true',
        'info_url' => 'https://www.wpgraphql.com/'
    ],
    'faustwp' => [
        'name' => 'Faust.js',
        'description' => 'Faust.js Companion Plugin for WordPress, by WPEngine.',
        'url' => 'https://downloads.wordpress.org/plugin/faustwp.zip',
        'file' => 'faustwp/faustwp.php',
        'icon' => 'https://faustjs.org/_next/image?url=%2Fimages%2Ffaust-logo-256x256.png&w=48&q=75',
        'slug' => 'faustwp',
        'required' => 'true',
        'info_url' => 'https://www.wpgraphql.com/'
    ],
    'advanced-custom-fields' => [
        'name' => 'Advanced Custom Fields',
        'description' => 'Advanced Custom Fields (ACF) turns WordPress sites into a fully-fledged content management system by giving you all the tools to do more with your data.',
        'url' => 'https://downloads.wordpress.org/plugin/advanced-custom-fields.' . ADVANCED_CUSTOM_FIELDS_VERSION . '.zip',
        'file' => 'advanced-custom-fields/acf.php',
        'icon' => 'https://ps.w.org/advanced-custom-fields/assets/icon.svg?rev=3096880',
        'slug' => 'advanced-custom-fields',
        'required' => 'false',
        'info_url' => 'https://www.wpgraphql.com/'
    ],
    'wpgraphql-acf' => [
        'name' => 'WPGraphQL for ACF',
        'description' => 'WPGraphQL for Advanced Custom Fields',
        'url' => 'https://downloads.wordpress.org/plugin/wpgraphql-acf.' . WP_GRAPHQL_FOR_ACF_VERSION . '.zip',
        'file' => 'wpgraphql-acf/wpgraphql-acf.php',
        'icon' => 'https://ps.w.org/wpgraphql-acf/assets/icon-128x128.png?rev=3012214',
        'slug' => 'wpgraphql-acf',
        'required' => 'false',
        'info_url' => 'https://www.wpgraphql.com/'
    ],
];

/**
 * Add the options page
 */
add_action('admin_menu', function () {
    add_options_page('WPNuxt Options', 'WPNuxt', 'manage_options', 'wpnuxt', 'wpNuxtOptionsPageHtml');
});

function wpNuxtOptionsPageHtml()
{
    $options = get_option('wpnuxt_options');

    $nuxtIcon = 'data:image/svg+xml;base64,' . base64_encode('<svg width="40" height="40" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M281.44 397.667H438.32C443.326 397.667 448.118 395.908 452.453 393.427C456.789 390.946 461.258 387.831 463.76 383.533C466.262 379.236 468.002 374.36 468 369.399C467.998 364.437 466.266 359.563 463.76 355.268L357.76 172.947C355.258 168.65 352.201 165.534 347.867 163.053C343.532 160.573 337.325 158.813 332.32 158.813C327.315 158.813 322.521 160.573 318.187 163.053C313.852 165.534 310.795 168.65 308.293 172.947L281.44 219.587L227.733 129.13C225.229 124.834 222.176 120.307 217.84 117.827C213.504 115.346 208.713 115 203.707 115C198.701 115 193.909 115.346 189.573 117.827C185.238 120.307 180.771 124.834 178.267 129.13L46.8267 355.268C44.3208 359.563 44.0022 364.437 44 369.399C43.9978 374.36 44.3246 379.235 46.8267 383.533C49.3288 387.83 53.7979 390.946 58.1333 393.427C62.4688 395.908 67.2603 397.667 72.2667 397.667H171.2C210.401 397.667 238.934 380.082 258.827 346.787L306.88 263.4L332.32 219.587L410.053 352.44H306.88L281.44 397.667ZM169.787 352.44H100.533L203.707 174.36L256 263.4L221.361 323.784C208.151 345.387 193.089 352.44 169.787 352.44Z" fill="#00DC82"/></svg>');
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
    global $plugin_list;

    register_setting('wpnuxt_options', 'wpnuxt_options');

    // Return true if all plugins are active
    $is_all_plugins_active = array_reduce($plugin_list, function ($carry, $plugin) {
        return $carry && ($plugin['required'] == 'false' || is_plugin_active($plugin['file']));
    }, true);

    // if all plugins are active don't show required plugins section
    // if (!$is_all_plugins_active) {
        add_settings_section('required_plugins', 'Plugins', 'requiredPluginsCallback', 'wpnuxt');
    // } else {
    //}
    add_settings_section('global_setting', 'Global Settings', 'global_setting_callback', 'wpnuxt');
    add_settings_section('deploy_button', 'Deploy', 'deployButtonCallback', 'wpnuxt');
}

/**
 * Registers admin bar menu
 *
 * @param \WP_Admin_Bar $admin_bar The Admin Bar Instance
 *
 * @return void
 */
function register_admin_bar_menu( WP_Admin_Bar $admin_bar ) {
    if ( ! current_user_can( 'manage_options' )) {
        return;
    }

    $nuxtIcon = 'data:image/svg+xml;base64,' . base64_encode('<svg width="22" height="22" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M281.44 397.667H438.32C443.326 397.667 448.118 395.908 452.453 393.427C456.789 390.946 461.258 387.831 463.76 383.533C466.262 379.236 468.002 374.36 468 369.399C467.998 364.437 466.266 359.563 463.76 355.268L357.76 172.947C355.258 168.65 352.201 165.534 347.867 163.053C343.532 160.573 337.325 158.813 332.32 158.813C327.315 158.813 322.521 160.573 318.187 163.053C313.852 165.534 310.795 168.65 308.293 172.947L281.44 219.587L227.733 129.13C225.229 124.834 222.176 120.307 217.84 117.827C213.504 115.346 208.713 115 203.707 115C198.701 115 193.909 115.346 189.573 117.827C185.238 120.307 180.771 124.834 178.267 129.13L46.8267 355.268C44.3208 359.563 44.0022 364.437 44 369.399C43.9978 374.36 44.3246 379.235 46.8267 383.533C49.3288 387.83 53.7979 390.946 58.1333 393.427C62.4688 395.908 67.2603 397.667 72.2667 397.667H171.2C210.401 397.667 238.934 380.082 258.827 346.787L306.88 263.4L332.32 219.587L410.053 352.44H306.88L281.44 397.667ZM169.787 352.44H100.533L203.707 174.36L256 263.4L221.361 323.784C208.151 345.387 193.089 352.44 169.787 352.44Z" fill="#00DC82"/></svg>');
$icon_url = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0MDAgNDAwIj48cGF0aCBmaWxsPSIjRTEwMDk4IiBkPSJNNTcuNDY4IDMwMi42NmwtMTQuMzc2LTguMyAxNjAuMTUtMjc3LjM4IDE0LjM3NiA4LjN6Ii8+PHBhdGggZmlsbD0iI0UxMDA5OCIgZD0iTTM5LjggMjcyLjJoMzIwLjN2MTYuNkgzOS44eiIvPjxwYXRoIGZpbGw9IiNFMTAwOTgiIGQ9Ik0yMDYuMzQ4IDM3NC4wMjZsLTE2MC4yMS05Mi41IDguMy0xNC4zNzYgMTYwLjIxIDkyLjV6TTM0NS41MjIgMTMyLjk0N2wtMTYwLjIxLTkyLjUgOC4zLTE0LjM3NiAxNjAuMjEgOTIuNXoiLz48cGF0aCBmaWxsPSIjRTEwMDk4IiBkPSJNNTQuNDgyIDEzMi44ODNsLTguMy0xNC4zNzUgMTYwLjIxLTkyLjUgOC4zIDE0LjM3NnoiLz48cGF0aCBmaWxsPSIjRTEwMDk4IiBkPSJNMzQyLjU2OCAzMDIuNjYzbC0xNjAuMTUtMjc3LjM4IDE0LjM3Ni04LjMgMTYwLjE1IDI3Ny4zOHpNNTIuNSAxMDcuNWgxNi42djE4NUg1Mi41ek0zMzAuOSAxMDcuNWgxNi42djE4NWgtMTYuNnoiLz48cGF0aCBmaWxsPSIjRTEwMDk4IiBkPSJNMjAzLjUyMiAzNjdsLTcuMjUtMTIuNTU4IDEzOS4zNC04MC40NSA3LjI1IDEyLjU1N3oiLz48cGF0aCBmaWxsPSIjRTEwMDk4IiBkPSJNMzY5LjUgMjk3LjljLTkuNiAxNi43LTMxIDIyLjQtNDcuNyAxMi44LTE2LjctOS42LTIyLjQtMzEtMTIuOC00Ny43IDkuNi0xNi43IDMxLTIyLjQgNDcuNy0xMi44IDE2LjggOS43IDIyLjUgMzEgMTIuOCA0Ny43TTkwLjkgMTM3Yy05LjYgMTYuNy0zMSAyMi40LTQ3LjcgMTIuOC0xNi43LTkuNi0yMi40LTMxLTEyLjgtNDcuNyA5LjYtMTYuNyAzMS0yMi40IDQ3LjctMTIuOCAxNi43IDkuNyAyMi40IDMxIDEyLjggNDcuN00zMC41IDI5Ny45Yy05LjYtMTYuNy0zLjktMzggMTIuOC00Ny43IDE2LjctOS42IDM4LTMuOSA0Ny43IDEyLjggOS42IDE2LjcgMy45IDM4LTEyLjggNDcuNy0xNi44IDkuNi0zOC4xIDMuOS00Ny43LTEyLjhNMzA5LjEgMTM3Yy05LjYtMTYuNy0zLjktMzggMTIuOC00Ny43IDE2LjctOS42IDM4LTMuOSA0Ny43IDEyLjggOS42IDE2LjcgMy45IDM4LTEyLjggNDcuNy0xNi43IDkuNi0zOC4xIDMuOS00Ny43LTEyLjhNMjAwIDM5NS44Yy0xOS4zIDAtMzQuOS0xNS42LTM0LjktMzQuOSAwLTE5LjMgMTUuNi0zNC45IDM0LjktMzQuOSAxOS4zIDAgMzQuOSAxNS42IDM0LjkgMzQuOSAwIDE5LjItMTUuNiAzNC45LTM0LjkgMzQuOU0yMDAgNzRjLTE5LjMgMC0zNC45LTE1LjYtMzQuOS0zNC45IDAtMTkuMyAxNS42LTM0LjkgMzQuOS0zNC45IDE5LjMgMCAzNC45IDE1LjYgMzQuOSAzNC45IDAgMTkuMy0xNS42IDM0LjktMzQuOSAzNC45Ii8+PC9zdmc+';

    $icon = sprintf(
        '<span class="custom-icon" style="
background-image:url(\'%s\'); float:left; width:22px !important; height:22px !important;
margin-left: 5px !important; margin-top: 5px !important; margin-right: 2px !important;
"></span>',
        $nuxtIcon
    );

    $admin_bar->add_menu(
        [
            'id'    => 'wpnuxt',
            'title' => $icon . __( '<span class="font-serif">WP</span>Nuxt', 'wpnuxt' ),
            'href'  => trailingslashit( admin_url() ) . 'admin.php?page=wpnuxt',
        ]
    );
}
add_action( 'admin_bar_menu', 'register_admin_bar_menu', 500);


// Section callback
function requiredPluginsCallback()
{
    global $plugin_list;    
    // Return true if all plugins are active
    $is_all_plugins_active = array_reduce($plugin_list, function ($carry, $plugin) {
        return $carry && ($plugin['required'] == 'false' || is_plugin_active($plugin['file']));
    }, true);
    ?>
    <details  <?php echo $is_all_plugins_active ? '' : 'open'; ?>>
        <summary>Required & recommended plugins <?php echo $is_all_plugins_active ? '✅' : ''; ?></summary>
        <div class="wpnuxt-section">
            <h4>Required plugins:</h4>
            <ul class="required-plugins-list">
                <?php foreach ($plugin_list as $plugin): 
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
                <?php foreach ($plugin_list as $plugin):
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
        global $plugin_list;

        $upgrader = new Plugin_Upgrader();
        $plugin = $plugin_list[$_GET['install_plugin']];
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

    $site_name = get_bloginfo('name');
    $gql_settings = get_option('graphql_general_settings');
    $gql_endpoint = isset($gql_settings['graphql_endpoint']) ? $gql_settings['graphql_endpoint'] : 'graphql';
    $endpoint = get_site_url() . '/' . $gql_endpoint;
    $faustwp_settings = get_option('faustwp_settings');

    // Enable Public Introspection
    $publicIntrospectionEnabled = isset($gql_settings['public_introspection_enabled']) ? $gql_settings['public_introspection_enabled'] == 'on' : false;
    // faustwp_settings[frontend_uri]:
    $frontend_uri_is_set = isset($faustwp_settings['frontend_uri']) ? str_starts_with($faustwp_settings['frontend_uri'], 'http') : false;

    $allSettingHaveBeenMet =
        $publicIntrospectionEnabled && 
        $frontend_uri_is_set
    ?>

    <details <?php echo $allSettingHaveBeenMet ? '' : 'open'; ?>>
        <summary>Required plugin settings for WPNuxt <?php echo $allSettingHaveBeenMet ? '✅' : ''; ?></summary>
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
                            <span style="color: #D63638;"><?php echo $publicIntrospectionEnabled ? '✅' : '(disabled)'; ?></span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><h4><a href="/wp-admin/admin.php?page=faustwp-settings">Faust settings</a></h4></td>
                </tr>
                <tr>
                    <td scope="row">Frontend uri</td>
                    <td>
                        <div class="flex">
                        <span style="color: #D63638;"><?php echo $frontend_uri_is_set ? '✅ ' . $faustwp_settings['frontend_uri'] : '(missing)'; ?></span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </details>
<?php
}

function deployButtonCallback()
{
    $site_name = get_bloginfo('name');
    $gql_settings = get_option('graphql_general_settings');
    $gql_endpoint = isset($gql_settings['graphql_endpoint']) ? $gql_settings['graphql_endpoint'] : 'graphql';
    $endpoint = get_site_url() . '/' . $gql_endpoint;
    $faustwp_settings = get_option('faustwp_settings');

    // Enable Public Introspection
    $publicIntrospectionEnabled = isset($gql_settings['public_introspection_enabled']) ? $gql_settings['public_introspection_enabled'] == 'on' : false;
    // faustwp_settings[frontend_uri]:
    $frontend_uri_is_set = isset($faustwp_settings['frontend_uri']) ? str_starts_with($faustwp_settings['frontend_uri'], 'http') : false;

    $allSettingHaveBeenMet =
        $publicIntrospectionEnabled && $frontend_uri_is_set
    ?>

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="wpnuxt_options[build_hook]">Deploy your Site.</label></th>
                <td>
                    <div class="flex">
                        <a id="netlify-button" href="https://app.netlify.com/start/deploy?repository=https://github.com/vernaillen/wpnuxt-demo#WPNUXT_WORDPRESS_URL=<?php echo $endpoint; ?>&NUXT_IMAGE_DOMAINS=<?php echo $_SERVER['HTTP_HOST']; ?>" target="_blank" class="mr-8">
                            <img src="<?php echo plugins_url('assets/netlify.svg', __FILE__, ); ?>" alt="Deploy to Netlify" width="160" height="40">
                        </a>
                        <a href="https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%vernaillen%2wpnuxt-demo3&repository-name=<?php echo $site_name; ?>&env=WPNUXT_WORDPRESS_URL,NUXT_IMAGE_DOMAINS" target="_blank" class="vercel-button" data-metrics-url="https://vercel.com/p/button">
                            <svg data-testid="geist-icon" fill="none" height="15" width="15" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2L2 19.7778H22L12 2Z" fill="#fff" stroke="#fff" stroke-width="1.5"></path>
                            </svg>
                            <span>Deploy to Vercel</span>
                        </a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
<?php
}

// Field callback
function global_setting_callback()
{
    $options = get_option('wpnuxt_options');
    $primary_color = isset($options['primary_color']) ? $options['primary_color'] : '#7F54B2';
    ?>

    <div class="global_setting wpnuxt-section">
        <table class="form-table" role="presentation">
            <tbody>

                <!-- LOGO -->
                <tr>
                    <th scope="row"><label for="wpnuxt_options[logo]">Logo</label></th>
                    <td>
                        <input type="text" class="widefat" name="wpnuxt_options[logo]" value="<?php echo isset($options['logo']) ? $options['logo'] : ''; ?>" placeholder="e.g. https://example.com/logo.png" />
                        <p class="description">You can upload the logo in the Media Library and copy the URL here.</p>
                    </td>
                </tr>

                <!-- FRONT END URL -->
                <tr>
                    <th scope="row"><label for="wpnuxt_options[frontEndUrl]">Front End URL</label></th>
                    <td>
                        <input type="text" class="widefat" name="wpnuxt_options[frontEndUrl]" value="<?php echo isset($options['frontEndUrl']) ? $options['frontEndUrl'] : ''; ?>" placeholder="e.g. https://mysite.netlify.app" />
                        <p class="description">This is the URL of your Nuxt site not the WordPress site.</p>
                    </td>
                </tr>
            </tbody>
        </table>
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
