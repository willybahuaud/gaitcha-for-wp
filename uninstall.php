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

// On multisite, clean up each site's options.
if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'gaitcha_secret' );
		delete_option( 'gaitcha_settings' );
		delete_option( 'gaitcha_used_tokens' );
		delete_transient( 'gaitcha_wp_github_release' );
		restore_current_blog();
	}
} else {
	delete_option( 'gaitcha_secret' );
	delete_option( 'gaitcha_settings' );
	delete_option( 'gaitcha_used_tokens' );
	delete_transient( 'gaitcha_wp_github_release' );
}
