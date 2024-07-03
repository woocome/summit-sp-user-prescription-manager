<?php
/**
 * Starter Kit Class
 *
 * @link       https://bit.ly/dan-singian-resume
 * @since      1.0.0
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/public
 */

class Sp_Upm_Starter_Kit
{
    /**
     * @var self $instance
     */
    private static $instance;

    public static $repeater_key = 'starter_kit';

    public static $sf_product_key = 'product';
    public static $sf_heading_key = 'heading';
    public static $sf_custom_price = 'custom_price';
    public static $sf_no_of_columns = 'columns';

    public static $starter_kit = [];

    public function __construct() {
    }

    public function init() {
        add_shortcode('starter_kit', [$this, 'handle_starter_kit_shortcode']);
        add_action('wp_ajax_starter_kit_add_to_cart', [$this, 'handle_starter_kit_add_to_cart']);
    }

    public function repeater_keys() {
        return [
            'product',
            'heading',
            'custom_price'
        ];
    }


    public function handle_starter_kit_shortcode() {
        ob_start();

        sp_upm_get_template_part('content', 'starter-kit');

        $html = ob_get_clean();

        return $html;
    }

    public static function starter_kit_items() {
        ob_start();

        $page_id = get_the_ID();

        if (have_rows(self::$repeater_key, $page_id)) {

            while( have_rows(self::$repeater_key, $page_id) ) : the_row();

                sp_upm_get_template_part('content', 'starter-kit-item', [
                    'product' => get_sub_field(self::$sf_product_key),
                    'heading' => get_sub_field(self::$sf_heading_key),
                    'custom_price' => get_sub_field(self::$sf_custom_price),
                    'columns' => get_sub_field(self::$sf_no_of_columns)
                ]);

            endwhile;
        }

        $html = ob_get_contents();

        ob_end_clean();

        return $html;
    }

    public function handle_starter_kit_add_to_cart() {
        // Validate the authenticity of the ajax request
        if (! isset($_REQUEST['ajax_nonce']) || ! wp_verify_nonce( $_REQUEST['ajax_nonce'], 'sp_upm_ajax_nonce' ))
            wp_send_json_error(['message' => "Token expired. Please refresh, and try again."]);
        
        $data = um_account_sanitize_data($_REQUEST['data']);
        $action_type = sanitize_text_field($_REQUEST['action_type']);
        $redirect_url =  $action_type == 'checkout' ? wc_get_checkout_url() : wc_get_cart_url();

        // Validate data from the user
        if (empty($data) || ! is_array($data)) wp_send_json_error(['message' => "Invalid product data."]);

        $response = sp_upm_woocommerce()::add_to_cart($data);
        if ($response['success']) wp_send_json_success(['redirect_url' => $redirect_url,]);

        // Error adding products to cart
        wp_send_json_error(['message' => $response['message'] ?? 'Something went wrong.']);
    }

    private static function reset_cart() {
        if( ! WC()->cart->is_empty() ) WC()->cart->empty_cart();
    }

    /**
     * @param int $user_id
     */
    public function has_customer_purchased_nrt($user_id) {
        $product_cat = get_term_by( 'slug', 'nrt', 'product_cat' );

        return $this->has_customer_ordered_product_in_category($product_cat->term_id, $user_id);
    }

    /**
     * @param int $category_id
     * @param int $user_id
     */
    public function has_customer_ordered_product_in_category($category_id, $user_id) {
        global $wpdb;

        // Get the term ID for the specified category slug
        $category_term = get_term_by('id', $category_id, 'product_cat');

        if (!$category_term) {
            return false; // Category does not exist
        }

        $category_id = $category_term->term_id;

        // Query to check if the customer has ordered any product in the specified category
        $query = "
            SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_items AS order_items
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta ON order_items.order_item_id = itemmeta.order_item_id
            INNER JOIN {$wpdb->prefix}posts AS products ON itemmeta.meta_value = products.ID
            INNER JOIN {$wpdb->prefix}term_relationships AS relationships ON products.ID = relationships.object_id
            INNER JOIN {$wpdb->prefix}postmeta AS order_meta ON order_items.order_id = order_meta.post_id
            WHERE itemmeta.meta_key = '_product_id'
            AND relationships.term_taxonomy_id = %d
            AND order_meta.meta_key = '_customer_user'
            AND order_meta.meta_value = %d
            AND order_items.order_id IN (
                SELECT ID FROM {$wpdb->prefix}posts
                WHERE post_type = 'shop_order'
                AND post_status IN ('wc-completed', 'wc-processing')
            )
        ";
    
        $results = $wpdb->get_var($wpdb->prepare($query, $category_id, $user_id));
    
        return $results > 0;
    }

    public function get_category_consultation_product( $id ) {
        return get_term_meta($id, 'treatment_medication_service', true);
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

function sp_upm_starter_kit() {
    return Sp_Upm_Starter_Kit::get_instance();
}