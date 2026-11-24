<?php
/**
 * Plugin Name: Rommelfangen Living Room Concerts
 * Description: Wohnzimmerkonzerte, Website-Vorlagen und WooCommerce-Ticketanbindung für einzelne Websites oder Multisite.
 * Version: 1.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.2
 * Author: rommelfs
 * License: GPL-2.0-or-later
 * Text Domain: wohnzimmerkonzerte
 * Update URI: https://github.com/rommelfs/wohnzimmerkonzerte
 */
if (!defined('ABSPATH')) exit;
// Prevent duplicate legacy functions when upgrading from the original theme/core layout.
if (function_exists('rl_events') || get_stylesheet() === 'wohnzimmer') {
    $message = 'Rommelfangen: zuerst das alte Plugin wohnzimmer-core deaktivieren und das alte Theme wohnzimmer durch ein normales WordPress-Theme ersetzen. Danach wohnzimmerkonzerte aktivieren.';
    register_activation_hook(__FILE__, static function () use ($message) { wp_die(esc_html($message)); });
    add_action('admin_notices', static function () use ($message) { echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>'; });
    return;
}
define('RL_VERSION', '1.1.0');
define('RL_REWRITE_VERSION', '1');
define('RL_PLUGIN_FILE', __FILE__);
define('RL_PLUGIN_DIR', __DIR__ . '/');
require_once RL_PLUGIN_DIR . 'includes/selection.php';
require_once RL_PLUGIN_DIR . 'includes/concerts.php';
require_once RL_PLUGIN_DIR . 'includes/settings.php';
require_once RL_PLUGIN_DIR . 'includes/lifecycle.php';
require_once RL_PLUGIN_DIR . 'includes/frontend.php';
register_activation_hook(__FILE__, 'rl_activate');
register_deactivation_hook(__FILE__, 'rl_deactivate');
