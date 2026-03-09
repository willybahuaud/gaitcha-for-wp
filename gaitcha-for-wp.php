<?php
/**
 * Plugin Name: Gaitcha for WordPress
 * Plugin URI:  https://github.com/willybahuaud/gaitcha-for-wp
 * Description: Self-hosted behavioral captcha — no external dependency.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Willy Bahuaud
 * Author URI:  https://wabeo.fr
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gaitcha-for-wp
 *
 * @package GaitchaWP
 */

defined( 'ABSPATH' ) || exit;

define( 'GAITCHA_WP_VERSION', '1.0.2-dev' );
define( 'GAITCHA_WP_FILE', __FILE__ );
define( 'GAITCHA_WP_PATH', plugin_dir_path( __FILE__ ) );
define( 'GAITCHA_WP_URL', plugin_dir_url( __FILE__ ) );
define( 'GAITCHA_WP_BASENAME', plugin_basename( __FILE__ ) );

require_once GAITCHA_WP_PATH . 'vendor/autoload.php';

/**
 * Generates and stores the HMAC secret on activation.
 *
 * @return void
 */
function gaitcha_wp_activate() {
	if ( ! get_option( 'gaitcha_secret' ) ) {
		add_option( 'gaitcha_secret', wp_generate_password( 64, false, false ), '', false );
	}
}
register_activation_hook( __FILE__, 'gaitcha_wp_activate' );

/**
 * Cleans up transient cache on deactivation.
 *
 * @return void
 */
function gaitcha_wp_deactivate() {
	delete_transient( 'gaitcha_wp_github_release' );
}
register_deactivation_hook( __FILE__, 'gaitcha_wp_deactivate' );

/**
 * Bootstraps the plugin after all plugins are loaded.
 *
 * @return void
 */
function gaitcha_wp_init() {
	$plugin = new GaitchaWP\Plugin();
	$plugin->init();
}
add_action( 'plugins_loaded', 'gaitcha_wp_init' );
