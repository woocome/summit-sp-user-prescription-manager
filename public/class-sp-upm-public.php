<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://bit.ly/dan-singian-resume
 * @since      1.0.0
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/public
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */
class Class_Sp_Upm_Public {

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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->includes();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Sp_User_Prescription_Manager_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Sp_User_Prescription_Manager_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( SP_UPM_PLUGIN_FILE ) . 'assets/css/style.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Sp_User_Prescription_Manager_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Sp_User_Prescription_Manager_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
         wp_register_script( $this->plugin_name, plugin_dir_url( SP_UPM_PLUGIN_FILE ) . 'assets/js/sp-upm-public.js', array( 'jquery' ), $this->version, false );
         wp_localize_script( $this->plugin_name, 'sp_upm_ajax_public', [
            'ajax_nonce'=> wp_create_nonce('sp_upm_ajax_nonce'),
            'admin_url' => admin_url( '/admin-ajax.php' ),
            'starter_kit_action'    => 'starter_kit_add_to_cart',
            'has_previous_nrt' => sp_upm_starter_kit()->has_customer_purchased_nrt(get_current_user_id())
         ]);
         wp_enqueue_script( $this->plugin_name );
    }
    
    /**
     *  Add a custom email to the list of emails WooCommerce should load
     *
     * @since 0.1
     * @param array $email_classes available email classes
     * @return array filtered available email classes
     */
    public function add_new_subscription_order_woocommerce_email( $email_classes ) {
        include_once SP_UPM_ABSPATH . 'public/class-spm-upm-public-new-subscription-email.php';

        // add the email class to the list of email classes that WooCommerce loads
        $email_classes['WC_New_Subscription_Email'] = new Sp_Upm_Public_New_Subscription_Email();

        return $email_classes;
    }

    public function includes() {
        include_once SP_UPM_ABSPATH . 'public/class-sp-upm-starter-kit.php';
        include_once SP_UPM_ABSPATH . 'public/class-woocommerce.php';
        include_once SP_UPM_ABSPATH . 'public/class-sp-upm-appointment-rebooking.php';
        include_once SP_UPM_ABSPATH . 'public/class-sp-upm-user-active-treatments.php';

        sp_upm_starter_kit()->init();
        sp_upm_woocommerce()->init();
        sp_upm_appointment_rebooking()->init();
        sp_upm_user_active_treatments()->init();
    }

    public function page_redirect() {
        // Check if it's the WooCommerce checkout page

        if (! class_exists('WooCommerce')) return;
        global $post;
        // if product category is set as publicly available

        if (is_page('nt-step-by-step-pre-booking-form')) {
            $has_valid_promo_code = false;
            $redirect_url = esc_url(home_url('/natural-remedies/'));
            $promo_code = isset($_GET['fid_promotion']) ? sanitize_text_field($_GET['fid_promotion']) : null;

            if ($promo_code) {
                $consultation = sp_upm_consultation_booking();
                $consultation->set_promo_code($promo_code);
                $has_valid_promo_code = $consultation->is_promo_code_valid();
            }

            if ( ! $has_valid_promo_code ) {
                wp_safe_redirect($redirect_url);
                exit;
            }
        }

        if(is_product_category()) {
            $term = get_queried_object();
            $is_public_category = get_term_meta( $term->term_id, 'is_public_category', true );

            if ($is_public_category) return;
        }

        // Check if the user is logged in
        if (is_user_logged_in()) {
            // Get the current user ID
            $user_id = get_current_user_id();

            // Check if the user has the 'subscriber' role
            if (in_array('subscriber', (array) get_userdata($user_id)->roles)) {
                // Redirect the subscriber away from the WooCommerce product pages
                if (is_shop() || is_product_category() || is_product_tag()) {
                    wp_redirect(home_url('/public-shop/'));
                    exit();
                }
				
				if (is_page('/pharmacy-shop/')) {
                    wp_redirect(home_url('/under-review/'));
                    exit();
				}
            }
			
            if (! current_user_can('administrator') && is_page( 'public-shop' ) && in_array('customer',  (array) get_userdata($user_id)->roles)) {
                wp_redirect(home_url('/pharmacy-shop/')); // Redirect to the home page or any other desired page
                exit();
            }
        } else {
            if (is_product()) {
                $term_id = get_post_meta($post->ID, '_yoast_wpseo_primary_product_cat', true);
                $is_public_category = absint(get_term_meta( $term_id, 'is_public_category', true ));
                if ($is_public_category) return;
            }

            if (is_shop() || is_product_category() || is_product_tag() || is_product() || is_page( 'pharmacy-shop' )) {
                wp_redirect(home_url('/public-shop/')); // Redirect to the home page or any other desired page
                exit();
            }
        }
    }
}