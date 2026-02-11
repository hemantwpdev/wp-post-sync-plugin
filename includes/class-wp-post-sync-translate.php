<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wordpress.org/plugins/wp-post-sync-translate
 * @since      1.0.0
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 * @author     Hemant Patel <test@gmail.com>
 */
class Wp_Post_Sync_Translate {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Post_Sync_Translate_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WP_POST_SYNC_TRANSLATE_VERSION' ) ) {
			$this->version = WP_POST_SYNC_TRANSLATE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-post-sync-translate';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_core_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Post_Sync_Translate_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Post_Sync_Translate_i18n. Defines internationalization functionality.
	 * - Wp_Post_Sync_Translate_Admin. Defines all hooks for the admin area.
	 * - Wp_Post_Sync_Translate_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-post-sync-translate-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-post-sync-translate-public.php';

		/**
		 * Core plugin functionality classes.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-database.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-auth.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-mapper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-logger.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-rest.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-sync.php';
        // Translator (target-side background translation)
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-translator.php';

		$this->loader = new Wp_Post_Sync_Translate_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Post_Sync_Translate_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Post_Sync_Translate_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wp_Post_Sync_Translate_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'wp_ajax_wpst_save_settings', $plugin_admin, 'ajax_save_settings' );
		$this->loader->add_action( 'wp_ajax_wpst_add_target', $plugin_admin, 'ajax_add_target' );
		$this->loader->add_action( 'wp_ajax_wpst_remove_target', $plugin_admin, 'ajax_remove_target' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Post_Sync_Translate_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Register core plugin functionality hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function define_core_hooks() {
		// Register REST API endpoints.
		add_action( 'rest_api_init', array( 'Wp_Post_Sync_Translate_REST', 'register_routes' ) );

		// Register post sync hooks.
		add_action( 'wp_after_insert_post', array( 'Wp_Post_Sync_Translate_Sync', 'push_post' ), 10, 3 );
		add_action( 'post_updated', function( $post_id ) {
			Wp_Post_Sync_Translate_Sync::push_post( $post_id, 'update' );
		}, 10, 1 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Post_Sync_Translate_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
