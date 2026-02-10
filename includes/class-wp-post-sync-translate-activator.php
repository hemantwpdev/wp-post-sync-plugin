<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wordpress.org/plugins/wp-post-sync-translate
 * @since      1.0.0
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 * @author     Hemant Patel <test@gmail.com>
 */
class Wp_Post_Sync_Translate_Activator {

	/**
	 * Create database tables and initialize settings.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-database.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';

		// Create audit logs table.
		Wp_Post_Sync_Translate_Database::create_tables();

		// Set default mode to host if not set.
		if ( ! get_option( Wp_Post_Sync_Translate_Settings::OPTION_MODE ) ) {
			Wp_Post_Sync_Translate_Settings::set_mode( 'host' );
		}
	}

}
