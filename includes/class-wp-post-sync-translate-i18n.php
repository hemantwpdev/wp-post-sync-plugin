<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://wordpress.org/plugins/wp-post-sync-translate
 * @since      1.0.0
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 * @author     Hemant Patel <test@gmail.com>
 */
class Wp_Post_Sync_Translate_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wp-post-sync-translate',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
