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

    private $table_name = 'change_medication_requests';

    private $sp_wpforms;

    const STATUS_APPROVE = 1;
    const STATUS_REJECT = 2;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct(  ) {
        $this->plugin_name = SP_USER_PRESCRIPTION_MANAGER_NAME;
        $this->version = SP_USER_PRESCRIPTION_MANAGER_VERSION;
        $this->sp_wpforms = sp_upm_wpforms();
    }

    public function init_hooks() {
        add_action('wpforms_process_complete_' . $this->get_form_id(), [$this, 'save_change_prescription_request'], 15, 4);
        add_action('init', array($this, 'create_table'));

        add_action( 'wp_ajax_change_prescription_request', [ $this, 'handle_change_prescription_request'] );
    }

    /**
     * Retrieve the list of columns for the database table.
     *
     * @since 1.1.6
     *
     * @return array List of columns.
     */
    public function get_columns() {
        return [
            'user_id' => '%d',
            'entry_id' => '%d',
            'product_cat_id' => '%d',
            'current_product_id' => '%d',
            'requested_product_id' => '%d',
            'reason' => '%s',
            'meta' => '%s',
            'status' => '%d',
            'created_at' => '%s',
            'updated_at' => '%s',
            'deleted_at' => '%s',
        ];
    }

    /**
     * Retrieve column defaults.
     *
     * 
     * @since 1.1.6
     *
     * @return array All defined column defaults.
     */
    public function get_column_defaults() {
        return [
            'user_id' => '',
            'entry_id' => '',
            'product_cat_id' => '',
            'current_product_id' => '',
            'requested_product_id' => '',
            'reason' => '',
            'meta' => '',
            'status' => 0,
        ];
    }

    /**
     * Insert a new record into the database.
     *
     * @since 1.0.0
     *
     * @param array  $data Column data.
     *
     * @return int ID for the newly inserted record. 0 otherwise.
     */
    public function add( $data ) {
        global $wpdb;

        // Set default values.
        $data = wp_parse_args( $data, $this->get_column_defaults() );

        // Initialise column format array.
        $column_formats = $this->get_columns();

        // Force fields to lower case.
        $data = array_change_key_case( $data );

        // White list columns.
        $data = array_intersect_key( $data, $column_formats );

        // Reorder $column_formats to match the order of columns given in $data.
        $data_keys      = array_keys( $data );
        $column_formats = array_merge( array_flip( $data_keys ), $column_formats );

        $wpdb->insert( $this->get_table_name(), $data, $column_formats );

        return $wpdb->insert_id ?? $wpdb->last_error;
    }

    /**
     * Insert a new record into the database. This runs the add() method.
     *
     * @see add()
     *
     * @since 1.0.0
     *
     * @param array $data Column data.
     *
     * @return int ID for the newly inserted record.
     */
    public function insert( $data ) {
        return $this->add( $data );
    }

    public function save_change_prescription_request($fields, $entry, $form_data, $entry_id) {
        $data = [];
        $form_id = absint($form_data['id']);

        if ($this->get_row_by_column('entry_id', $entry_id)) return;

        // Get the current user ID
        $user_id = get_current_user_id();
        $user = get_user_by('id', $user_id);
        $product_cat_field_id = $this->sp_wpforms->get_field_id_by_name($fields, "Current Treatment", 'select');
        $current_product_field_id = $this->sp_wpforms->get_field_id_by_name($fields, "Current Medication", 'select');
        $requested_product_field_id = $this->sp_wpforms->get_field_id_by_name($fields, "Change To", 'select');
        $reason_field_id = $this->sp_wpforms->get_field_id_by_name($fields, "Reason For Change", 'textarea');

        $product_cat_id = absint($entry['fields'][$product_cat_field_id]);
        $current_product_id = absint($entry['fields'][$current_product_field_id]);
        $requested_product_id = absint($entry['fields'][$requested_product_field_id]);
        $reason = $entry['fields'][$reason_field_id];

        $data = [
            'user_id' => $user_id,
            'entry_id' => $entry_id,
            'product_cat_id' => $product_cat_id,
            'current_product_id' => $current_product_id,
            'requested_product_id' => $requested_product_id,
            'reason' => $reason
        ];

        $result = $this->insert($data);
    }

    public function get_row_by_column($column, $value) {
        global $wpdb;

        $table = $this->get_table_name();

        $query = $wpdb->prepare("SELECT
            user_id,
            product_cat_id,
            current_product_id,
            requested_product_id
            FROM %i
            WHERE %i = %s
            LIMIT 1",
            [$table, $column, $value]
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        return $result;
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

    public function get_table_name() {
        global $wpdb;

        return $wpdb->prefix . $this->table_name;
    }

    public function get_form_id() {
        $form_id = wp_cache_get('change_medication_request_form');

        if (! $form_id) {
            $form_id = get_field('change_medication_request_form', 'option');
            wp_cache_set('change_medication_request_form', $form_id);
        }

        return $form_id;
    }

    public function get_status_label($status) {
        switch ($status) {
            case 1:
                return "Approved";
            case 2:
                return "Rejected";
            default:
                # code...
                return "Pending";
        }
    }

    public function handle_change_prescription_request() {

        if ( !isset( $_REQUEST['ajax_nonce'] ) || !wp_verify_nonce( $_REQUEST['ajax_nonce'], 'ajax_nonce' ) ) return;

        global $wpdb;

        $entry_id = sanitize_text_field($_REQUEST['entry_id']);
        $action = sanitize_text_field($_REQUEST['action_type']);

        $wpdb->query('START TRANSACTION');

        $request_item = $this->get_row_by_column('entry_id', $entry_id);
        if (! $request_item) throw new Exception("Change medication request not found.");

        try {
            $status = ($action == 'approve') ? self::STATUS_APPROVE : self::STATUS_REJECT;

            $result = $wpdb->update(
                $this->get_table_name(),
                ['status' => $status],
                ['entry_id' => $entry_id],
                ['%d'],  // data format for status
                ['%d']   // where format for entry_id
            );

            if ( ! $result) throw new Exception("Failed to update the requested item.");

            $user = get_user_by('id', $request_item['user_id']);
            if (! $user) throw new Exception("User not found.");

            // Update user_prescriptions usermeta field
            $this->update_user_prescription_field($user->ID, $request_item['requested_product_id'], $request_item['current_product_id']);

            // send email notification to customer
            $this->sendEmail($request_item, $action, $user);

            // Commit the transaction
            $wpdb->query('COMMIT');

            // Send a success response
            wp_send_json_success('Change prescription updated successfully and email sent to customer.');
        } catch (Exception $e) {
            // Rollback the transaction if there was an error
            $wpdb->query('ROLLBACK');

            // Send an error response
            wp_send_json_error('Error: ' . $e->getMessage());
        }

        wp_die();
    }

    private function update_user_prescription_field($user_id, $requested_product_id, $current_product_id) {
        global $wpdb;

        $query = $wpdb->prepare(
            'UPDATE ' . $wpdb->usermeta . ' SET meta_value = %d
            WHERE user_id = %d
            AND meta_key LIKE %s
            AND meta_value = %d',
            $requested_product_id,
            $user_id,
            'user_prescriptions_%_prescribed_medication',
            $current_product_id
        );

        return $wpdb->query($query);
    }
    
    private function sendEmail($item, $action, $user) {
        $mail = new Sp_Upm_Email_Sender($item['product_cat_id']);
        $mail->setSubject('Update on Your Medication Change Request');
        $mail->setContent($this->get_email_template($item, $action, $user));
        if (! $mail->send($user)) throw new Exception("Failed to send an email to the customer.");
    }

    public function get_email_template($item, $action, $user) {
        ob_start();

        $product_cat = get_term_by('id', $item['product_cat_id'], 'product_cat');

        sp_upm_get_template_part('/emails/content', "{$action}-change-prescription", [
            'user' => $user,
            'product_cat' => $product_cat->name,
            'product' => get_the_title($item['requested_product_id']),
            'prescriber' => get_the_title($item['prescriber_id'])
        ]);

        return ob_get_clean();
    }

    public function create_table() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_name = $this->get_table_name();

        $charset_collate = $wpdb->get_charset_collate();

        // SQL query to create the table
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE {$table_name} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                entry_id INT NOT NULL,
                product_cat_id INT NOT NULL,
                current_product_id INT NOT NULL,
                requested_product_id INT NOT NULL,
                reason TEXT,
                meta LONGTEXT,
                status INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL
            ) {$charset_collate};";

            // Execute the SQL query
            dbDelta( $sql );
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

function sp_upm_change_prescription_request() {
    return Sp_Upm_Admin_Change_Prescription_Requests::get_instance();
}