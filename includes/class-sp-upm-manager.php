<?php
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
 * @package    Sp_User_Prescription_Manager
 * 
 * @subpackage Sp_User_Prescription_Manager/includes
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */
class Sp_User_Prescription_Manager {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Sp_User_Prescription_Manager_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'SP_USER_PRESCRIPTION_MANAGER_VERSION' ) ) {
			$this->version = SP_USER_PRESCRIPTION_MANAGER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = SP_USER_PRESCRIPTION_MANAGER_NAME;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( SP_UPM_PLUGIN_FILE ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		/**
		 * Filter to adjust the base templates path.
		 */
		return apply_filters( 'sp_upm_template_path', 'sp-user-prescription-manager/' );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Sp_User_Prescription_Manager_Loader. Orchestrates the hooks of the plugin.
	 * - Sp_User_Prescription_Manager_i18n. Defines internationalization functionality.
	 * - Sp_User_Prescription_Manager_Admin. Defines all hooks for the admin area.
	 * - Sp_User_Prescription_Manager_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sp-upm-loader.php';
		
		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sp-upm-i18n.php';
		
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/sp-upm-core-functions.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sp-upm-db.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sp-upm-email-sender.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sp-upm-wpforms.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sp-upm-mailchimp.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sp-upm-autoloader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sp-upm-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-sp-upm-public.php';

		$this->loader = new Sp_User_Prescription_Manager_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Sp_User_Prescription_Manager_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Sp_User_Prescription_Manager_i18n();

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
		$plugin_admin = new Sp_User_Prescription_Manager_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $plugin_admin, 'create_doctors_appointments_table' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'plugin_menu' );

		$this->loader->add_action( 'wp_ajax_approve_pending_prescription', $plugin_admin, 'handle_approve_pending_prescription' );

		$this->loader->add_action( 'add_user_role', $plugin_admin, 'remove_subscriber_role_of_user', 11, 2 );
        $this->loader->add_action('woocommerce_add_to_cart', $plugin_admin,'restrict_product_add_to_cart', 10, 6);

		new Sp_Upm_Admin_Change_Prescription_Requests( $this->plugin_name, $this->version );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Class_Sp_Upm_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'template_redirect', $plugin_public, 'page_redirect', 20 );
		$this->loader->add_filter( 'woocommerce_email_classes', $plugin_public, 'add_new_subscription_order_woocommerce_email' );
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
	 * @return    Sp_User_Prescription_Manager_Loader    Orchestrates the hooks of the plugin.
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
