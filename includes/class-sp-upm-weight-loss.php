<?php

// ACF
// Popup
// template/public/bmi-metrics
// Customise
// Product Categories
// Treatment
// Booking Form
    // - Confirmation
    // - Titles
    // - Notifications
    // - Form Locker
// Consultation Page

// Product Consultation  - treatment category
// My ACcouynt consultations

class Sp_Weight_Loss
{
    
    /**
     * @var self $instance
     */
    private static $instance;

    // ref: /wp-admin/admin.php?page=wcpa-admin-ui#/form/45117
    private static $height_key = 'number_9079216831';
    private static $weight_key = 'number_8145044271';

    private static $meta_key = '_WCPA_order_meta_data';

    public static function init_hooks()
    {
        add_action( 'woocommerce_payment_complete', __CLASS__ . '::save_order_data', 10 );
        add_action( 'woocommerce_order_status_processing', __CLASS__ . '::save_order_data', 10 );

        add_shortcode( 'user_bmi_metrics_history', __CLASS__ . '::user_bmi_metrics_history' );
    }

    public static function save_order_data($order_id)
    {
        $order = wc_get_order($order_id);
        if (! $order) return;

        $user_id = $order->get_user_id();

        // Get and Loop Over Order Items
        foreach ( $order->get_items() as $item_id => $item ) {

            $weight_loss_meta = $item->get_meta(self::$meta_key);

            // check if item is already processed
            if (absint($item->get_meta('_is_wl_processed'))) continue;

            // check if it has weight loss meta data
            if (! is_array($weight_loss_meta) || ! count($weight_loss_meta)) continue;

            // get first key, based on the meta data structure
            $firstKey = array_key_first($weight_loss_meta);
            $fields = $weight_loss_meta[$firstKey]['fields'];

            $data = [
                'product' => $item->get_product_id(),
                'quantity' => $item->get_quantity(),
                'order_id' => $order_id,
                'date' => $order->get_date_created()->format('m/d/Y')
            ];

            foreach ($fields[0] as $key => $field) {
                if (! isset($field['elementId'])) continue;

                switch ($field['elementId']) {
                    case self::$height_key:
                        $data['height'] = $field['value'];
                        break;
                    case self::$weight_key:
                        $data['weight'] = $field['value'];
                        break;
                    default:
                }
            }

            if (self::add_row($user_id, $data)) {
                $item->add_meta_data('_is_wl_processed', 1);
                $item->save();
            }
        }
    }

    public static function add_row($user_id, $data)
    {
        return add_row('weight_loss_history', $data, 'user_' . $user_id);
    }

    public static function get_bmi_metrics()
    {
        $user_id = get_current_user_id();

        $metrics = get_field('weight_loss_history', 'user_' . $user_id);

        return $metrics;
    }

    public static function user_bmi_metrics_history()
    {
        $bmi_metrics = self::get_bmi_metrics();

        ob_start();

        sp_upm_get_template_part('/public/content', 'bmi-metrics', ['bmi_metrics' => $bmi_metrics]);

        return ob_get_clean();
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