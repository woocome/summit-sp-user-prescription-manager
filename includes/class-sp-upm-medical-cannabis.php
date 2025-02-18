<?php

class Sp_MedicalCannabis
{
    
    /**
     * @var self $instance
     */
    private static $instance;

    public static function init_hooks()
    {
        add_action( 'woocommerce_payment_complete', __CLASS__ . '::mark_customer_for_follow_up', 10 );
        add_action( 'woocommerce_order_status_processing', __CLASS__ . '::mark_customer_for_follow_up', 10 );
    }

    public static function mark_customer_for_follow_up($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $customer = $order->get_user();
        if (!$customer) return;
        
        $user_id = $customer->ID;
        $has_mc_products = false;
        
        // Get category 57 and all its children
        $category_ids = get_term_children(57, 'product_cat');
        $category_ids[] = 57; // Add parent category to the array
        
        // Get and Loop Over Order Items
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();

            // Get product categories
            $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));

            // Check if any of the product's categories match our target categories
            foreach ($category_ids as $cat_id) {
                if (in_array($cat_id, $product_categories)) {
                    $has_mc_products = true;
                    break 2; // Break both loops since we found a match
                }
            }
        }
        
        // Only proceed if we found matching products
        if ($has_mc_products) {
            $tags[] = [
                'name' => "MC_CUSTOMER_FOLLOW_UP",
                'status' => 'active'
            ];

            $mailchimp = sp_upm_mailchimp('fc1a219d30');
            $mailchimp->add_update_list_member($customer->user_email, true);
            $mailchimp->update_mailchimp_tags($tags, $customer->user_email);
        }
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