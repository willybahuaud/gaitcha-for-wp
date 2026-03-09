<?php
/**
 * Gaitcha for WordPress uninstall script.
 *
 * Removes all plugin data from the database when the plugin
 * is deleted via the WordPress admin.
 *
 * @package GaitchaWP
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'gaitcha_secret' );
delete_option( 'gaitcha_used_tokens' );
delete_transient( 'gaitcha_wp_github_release' );
