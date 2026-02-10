<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wordpress.org/plugins/wp-post-sync-translate
 * @since      1.0.0
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/admin
 * @author     Hemant Patel <test@gmail.com>
 */
class Wp_Post_Sync_Translate_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if ( ! isset( $_GET['page'] ) || 'wp-post-sync-translate' !== $_GET['page'] ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-post-sync-translate-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! isset( $_GET['page'] ) || 'wp-post-sync-translate' !== $_GET['page'] ) {
			return;
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-post-sync-translate-admin.js', array( 'jquery' ), $this->version, false );

		// Localize script with nonce and AJAX URLs.
		wp_localize_script(
			$this->plugin_name,
			'wpstTranslate',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp-post-sync-translate' ),
			)
		);
	}

	/**
	 * Add plugin settings menu.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_options_page(
			'WP Post Sync & Translate',
			'Post Sync & Translate',
			'manage_options',
			'wp-post-sync-translate',
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Display plugin settings page.
	 *
	 * @since 1.0.0
	 */
	public function display_plugin_admin_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/wp-post-sync-translate-admin-display.php';
	}


}
