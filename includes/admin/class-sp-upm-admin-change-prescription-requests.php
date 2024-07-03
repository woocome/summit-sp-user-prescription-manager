<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/admin
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */
class Sp_Upm_Admin_Change_Prescription_Requests {
    
    /**
     * @var self $instance
     */
    private static $instance;

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

        $this->init_hooks();
    }

    public function init_hooks() {
        // add_action('wpforms_process_complete_23294', [$this, 'save_change_prescription_request'], 15, 4);
    }

    public function save_change_prescription_request($fields, $entry, $form_data, $entry_id) {
        $data = [];
        $form_id = absint($form_data['id']);

        foreach ($fields as $id => $field) {
            $field_name = strtolower(str_replace(' ', '_', $field['name']));

            $data[$field_name] = [
                'name' => $field['name'],
                'value' => $field['value'],
                'input_value' => $entry['fields'][$id],
            ];
        }

        wpforms()->get( 'entry_meta' )->add(
            [
                'entry_id' => $entry_id,
                'form_id'  => $form_id,
                'user_id'  => get_current_user_id(),
                'type'     => 'change_prescription',
                'data'     => json_encode($data),
                'status'   => 'pending'
            ],
            'entry_meta'
        );
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self(self::$plugin_name, self::$version);
        }

        return self::$instance;
    }
}