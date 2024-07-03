<?php
/**
 * WooCommerce Functionalities
 *
 * @link       https://bit.ly/dan-singian-resume
 * @since      1.0.0
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/public
 */

class Sp_Upm_WooCommerce
{
    /**
     * @var self $instance
     */
    private static $instance;

    public function __construct() {
    }

    public function init() {
        add_shortcode('sp_upm_checkout_purchase_summary', __CLASS__ . '::purchase_summary');
        add_action( 'admin_post_send_pre_screening_form_to_user', __CLASS__ . '::send_pre_screening_form' );
        add_action( 'admin_post_nopriv_send_pre_screening_form_to_user', __CLASS__ . '::send_pre_screening_form' );

        add_action('woocommerce_checkout_process', [$this, 'check_customer_purchase_limit']);
        add_action('woocommerce_before_cart', [$this, 'check_customer_purchase_limit']);
    }

    public static function send_pre_screening_form() {
        $treatment_id = absint($_REQUEST['treatment_id']);
        $user = wp_get_current_user();

        $mail = new Sp_Upm_Email_Sender($treatment_id);
        $mail->setSubject('🚀 Pre-Screening Form for Your Upcoming Consultation 🚀');

        ob_start();

        sp_upm_get_template_part('/emails/content', 'email-pre-screening-form', ['treatment_id' => $treatment_id, 'name' => $user->first_name]);

        $content = ob_get_clean();

        $mail->setContent($content);
        $test = $mail->send($user);

        $redirect_uri = add_query_arg('sent', '1', wp_get_referer());

        wp_safe_redirect( $redirect_uri );
    }

    public static function purchase_summary() {
        ob_start();

        sp_upm_get_template_part('/public/content', 'consultation-purchase-summary');

        return ob_get_clean();
    }

    public function check_customer_purchase_limit() {
        if (!defined('DOING_AJAX')) return;

        $cart_items = WC()->cart->get_cart();

        foreach ($cart_items as $cart_item) {
            $product_id = $cart_item['product_id'];

            $total_purchased = self::get_customer_monthly_product_order($product_id);
            $cart_item_quantity = $cart_item['quantity'];

            if (! self::validate_monthly_limit($product_id, $total_purchased, $cart_item_quantity)) {
                wc_add_notice(self::limit_purchase_notice_message($product_id, $total_purchased, $cart_item_quantity), 'error');
            }
        }
    }

    private static function limit_purchase_notice_message($product_id, $total_purchased, $additional_quantity) {
        $max_quantity = absint(get_field('maximum_monthly_purchase_per_customer', $product_id));
        $product_cat_id = absint(get_post_meta($product_id, '_yoast_wpseo_primary_product_cat', true));
        $email = get_field('email_reply_to', 'product_cat_' . $product_cat_id);

        return sprintf(
            'You can only purchase %d of "%s" per month. You have already purchased %d this month. You are currently trying to add %d to your cart. If you need to increase your limit, please contact us at <a href="mailto:%s.au">%s</a>.',
            $max_quantity,
            get_the_title($product_id),
            $total_purchased,
            $additional_quantity,
            $email,
            $email
        );
    }

    private static function validate_monthly_limit($product_id, $total_purchased, $additional_quantity) {
        $max_quantity = absint(get_field('maximum_monthly_purchase_per_customer', $product_id));
        if (! $max_quantity) return true;

        // if product total quantity is less than or equal to max monthly quantity
        if (($total_purchased + $additional_quantity) <= $max_quantity) return true;

        return false;
    }

    /**
     * @param int $product_id
     */
    public static function get_customer_monthly_product_order($product_id) {
        global $wpdb;
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');

        $user_id = get_current_user_id();

        $query = $wpdb->prepare(
            "SELECT SUM(oim_qty.meta_value)
             FROM {$wpdb->prefix}woocommerce_order_items oi
             JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_pid ON oi.order_item_id = oim_pid.order_item_id
             JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty ON oi.order_item_id = oim_qty.order_item_id
             JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = oi.order_id
             JOIN {$wpdb->prefix}posts p ON p.ID = oi.order_id
             WHERE oim_pid.meta_key = '_product_id'
             AND oim_pid.meta_value = %d
             AND oim_qty.meta_key = '_qty'
             AND pm.meta_key = '_customer_user'
             AND pm.meta_value = %d
             AND p.post_type = 'shop_order'
             AND p.post_status IN ('wc-completed', 'wc-processing')
             AND p.post_date BETWEEN %s AND %s",
            $product_id, $user_id, $current_month_start, $current_month_end
        );

        $total_purchased = (int) $wpdb->get_var($query);

        return $total_purchased;
    }

    public static function add_to_cart($data) {
        if (empty($data) || ! is_array($data)) return false;

        foreach ($data as $item) {
            $product_id = absint($item['product_id']);
            $parent_product_id = wp_get_post_parent_id($product_id);
            $quantity = absint($item['quantity']);

            if ($quantity) {
                $max_quantity = self::get_product_max_quantity($parent_product_id);
                $total_purchased = self::get_customer_monthly_product_order($parent_product_id);
                $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

                if (! $passed_validation) return false;

                // Validate monthly limit
                if ($max_quantity) {

                    if (! self::validate_monthly_limit($parent_product_id, $total_purchased, $quantity)) {
                        return [
                            'success' => false,
                            'header' => 'Purchase limit reached!',
                            'message' => self::limit_purchase_notice_message($parent_product_id, $total_purchased, $quantity)
                        ];
                    }
                }

                WC()->cart->add_to_cart( $product_id, $quantity );
            }
        }

        return ['success' => true];
    }

    public static function get_product_max_quantity($product_id) {
        $max_quantity = absint(get_field('maximum_monthly_purchase_per_customer', $product_id));

        return $max_quantity;
    }

    /** Singleton instance */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

function sp_upm_woocommerce() {
    return Sp_Upm_WooCommerce::get_instance();
}