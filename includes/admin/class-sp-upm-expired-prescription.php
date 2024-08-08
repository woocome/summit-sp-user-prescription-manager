<?php

use Acowebs\WCPA\Free\Order;

class Sp_Expired_Prescription
{

    /**
     * @var self $instance
     */
    private static $instance;

    private $user_id;

    public function __construct()
    {
    }

    public function init_hooks()
    {
        add_shortcode('expired_script_message', [$this, 'expired_script_message']);
    }

    public function get_expired_prescription() {
        if (! is_user_logged_in()) return false;

        $user_id = get_current_user_id();

        global $wpdb;

        $query = "SELECT * FROM $wpdb->usermeta
            WHERE `meta_key` LIKE 'user_prescriptions_%_active_date'
                AND `meta_value` <> ''
                AND (
                    (LENGTH(`meta_value`) = 8 AND `meta_value` < DATE_FORMAT(NOW(), '%Y%m%d'))
                    OR
                    (LENGTH(`meta_value`) = 10 AND `meta_value` < DATE_FORMAT(NOW(), '%Y-%m-%d'))
                )
                AND `user_id` = {$user_id}
                LIMIT 1
        ";

        $row = $wpdb->get_row($query);

        return $row;
    }

    public function expired_script_message() {
        $user_id = get_current_user_id();
        $row = $this->get_expired_prescription();
        if (! $row) return;

        ob_start();

        $key = $this->extract_integer($row->meta_key);

        $product_cat_id = absint(get_user_meta($user_id, "user_prescriptions_{$key}_prescribed_categories", true));
        $dateString = $row->meta_value;

        $date = $this->format_date($dateString);
        $product_cat = get_term_by('id', $product_cat_id, 'product_cat');

        echo "Your prescription for ($product_cat->name) looks like might need an update. Feel free to reach out to our pharmacy for guidance. Expiry date: {$date}";

        return ob_get_clean();
    }

    public function extract_integer($string) {
        preg_match('/\d+/', $string, $matches);
        return isset($matches[0]) ? (int)$matches[0] : null;
    }

    public function format_date($dateString) {
        // Check the length of the date string to determine its format
        $format = strlen($dateString) == 8 ? 'Ymd' : ( strlen($dateString) == 10 ? 'Y-m-d' : null);
        if (! $format) return null;
    
        $date = DateTime::createFromFormat($format, $dateString);

        // Return the formatted date
        if ($date) {
            return $date->format('F j, Y');
        } else {
            return null;
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