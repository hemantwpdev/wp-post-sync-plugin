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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-post-sync-translate-admin.js', array( 'jquery' ), rand(0,999999), false );

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

	/**
	 * Save settings via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'wp-post-sync-translate', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'host';

		Wp_Post_Sync_Translate_Settings::set_mode( $mode );

		if ( 'target' === $mode ) {
			$target_key = isset( $_POST['target_key'] ) ? sanitize_text_field( $_POST['target_key'] ) : '';
			$language = isset( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : 'fr';
			$chatgpt_key = isset( $_POST['chatgpt_key'] ) ? sanitize_text_field( $_POST['chatgpt_key'] ) : '';

			if ( empty( $target_key ) ) {
				wp_send_json_error( 'Target key is required' );
			}

			if ( strlen( $target_key ) < 16 ) {
				wp_send_json_error( 'Target key must be at least 16 characters long' );
			}

			Wp_Post_Sync_Translate_Settings::set_target_key( $target_key );
			Wp_Post_Sync_Translate_Settings::set_target_language( $language );
			Wp_Post_Sync_Translate_Settings::set_chatgpt_key( $chatgpt_key );
		}

		wp_send_json_success( 'Settings saved successfully' );
	}

	/**
	 * Add target via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function ajax_add_target() {
		check_ajax_referer( 'wp-post-sync-translate', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';

		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

		if ( ! $url ) {
			wp_send_json_error( 'Invalid URL' );
		}

		if ( Wp_Post_Sync_Translate_Settings::add_target( $url ) ) {
			$target = Wp_Post_Sync_Translate_Settings::get_target( $url );
			wp_send_json_success( $target );
		} else {
			wp_send_json_error( 'Failed to add target' );
		}
	}

	/**
	 * Remove target via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function ajax_remove_target() {
		check_ajax_referer( 'wp-post-sync-translate', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';

		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

		if ( Wp_Post_Sync_Translate_Settings::remove_target( $url ) ) {
			wp_send_json_success( 'Target removed' );
		} else {
			wp_send_json_error( 'Failed to remove target' );
		}
	}


}
