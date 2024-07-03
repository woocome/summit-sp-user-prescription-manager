<?php

/**
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/admin
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */

class Sp_Upm_Wpforms
{
    
    /**
     * @var self $instance
     */
    private static $instance;

    /**
     * Get field id by name
     * 
     * @param array $fields
     * @param string $field_name
     * @param string $type
     * 
     * @return int|boolean
     */
    public function get_field_id_by_name(array $fields, $field_name, $type) : int {
        foreach ( $fields as $id => $field ) {
            if ((
                    $field['name'] == $field_name
                    || str_contains(strtolower($field['name']), strtolower($field_name))
                )
                && ($field['type'] == $type)
            ) {
                return $id;
            }
        }

        return false;
    }

    public function get_product_category_by_form_id(int $form_id, $key = 'category_wp_form') {
        global $wpdb;

        $query = $wpdb->prepare("SELECT term_id FROM %i WHERE `meta_value` = %d AND meta_key = %s ORDER BY term_id DESC LIMIT 1", $wpdb->termmeta, $form_id, $key);
        $result = $wpdb->get_row($query, ARRAY_A);

        if (! isset($result['term_id'])) return false;

        $product_category = get_term_by('id', absint($result['term_id']), 'product_cat');

        return $product_category;
    }

    public function get_wpforms_consultation_booking_forms() {
        global $wpdb;

        $query = "SELECT meta_value FROM $wpdb->termmeta WHERE `meta_key` = 'consultation_booking_form'";

        $results = $wpdb->get_results($query, ARRAY_A);

        return !empty($results) ? array_column($results, 'meta_value') : [];
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

function sp_upm_wpforms() {
    return Sp_Upm_Wpforms::get_instance();
}