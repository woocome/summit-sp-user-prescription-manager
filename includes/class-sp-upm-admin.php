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
class Sp_User_Prescription_Manager_Admin {
    
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
     * @var Sp_Upm_Admin_Doctors_Appointments
     */
    private Sp_Upm_Admin_Doctors_Appointments $doctors_appointment;

    public $pending_prescriptions_table;

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

        $this->define_constants();
        $this->includes();

        $this->init();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( SP_UPM_PLUGIN_FILE ) . 'assets/css/sp-upm-admin-css.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     * TODO: set proper version
     * 
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_register_script( $this->plugin_name, plugin_dir_url( SP_UPM_PLUGIN_FILE ) . 'assets/js/sp-upm-admin.js', array('jquery'), $this->version, true );
        wp_localize_script( $this->plugin_name, 'sp_upm_ajax', [
            'approve_ajax_nonce'=> wp_create_nonce('sp_upm_approve_ajax_nonce'),
            'delete_ajax_nonce'=> wp_create_nonce('sp_upm_delete_ajax_nonce'),
            'edit_ajax_nonce'=> wp_create_nonce('sp_upm_edit_ajax_nonce'),
            'admin_url' => admin_url( '/admin-ajax.php' ),
            'approve_action'    => 'approve_pending_prescription',
            'delete_action'    => 'delete_prescription_entry',
            'edit_action'    => 'edit_prescription_entry',
        ]);
        wp_enqueue_script( $this->plugin_name );
    }

    public function define_constants() {
        $this->define( 'SP_UPM_ABSPATH', dirname( SP_UPM_PLUGIN_FILE ) . '/' );
    }

    public function includes() {
        include_once SP_UPM_ABSPATH . 'includes/admin/class-sp-upm-admin-doctors-appointments.php';
        include_once SP_UPM_ABSPATH . 'includes/admin/class-sp-upm-admin-consultation-booking.php';
        include_once SP_UPM_ABSPATH . 'includes/admin/class-sp-upm-admin-repeat-counts.php';
        include_once SP_UPM_ABSPATH . 'includes/admin/class-sp-upm-admin-change-prescription-requests.php';
        include_once SP_UPM_ABSPATH . 'includes/admin/class-sp-upm-admin-delete-prescription-entry.php';
        include_once SP_UPM_ABSPATH . 'includes/admin/class-sp-upm-admin-repeat-counts.php';
        include_once SP_UPM_ABSPATH . 'includes/admin/class-sp-upm-expired-prescription.php';
    }

    public function init() {
        $this->doctors_appointment = new Sp_Upm_Admin_Doctors_Appointments();
        $this->doctors_appointment->init_hooks();

        new Sp_Upm_Admin_Delete_Prescription_Entry();

        sp_upm_repeat_counts()->init_hooks();
        sp_upm_consultation_booking()->init();
        Sp_Expired_Prescription::get_instance()->init_hooks();
    }

    /**
     * Screen options
     */
    public function screen_option()
    {
        $option = 'per_page';

        $args = [
            'label' => 'Pending Prescriptions',
            'default' => 20,
            'option' => 'sp_user_prescription_manager_per_page'
        ];

        add_screen_option( $option, $args );

        $this->pending_prescriptions_table = new Sp_Upm_List_Table_Pending_Prescriptions();
    }

    /**
     * Screen options
     */
    public function screen_option_requests()
    {
        $option = 'per_page';

        $args = [
            'label' => 'Pending Prescriptions',
            'default' => 20,
            'option' => 'pending_prescriptions_per_page'
        ];

        add_screen_option( 'per_page', $args );

        $this->pending_prescriptions_table = new Sp_Upm_List_Table_Pending_Prescriptions();
    }

    public function plugin_menu() {
        $hook = add_users_page(
            __('Pending Prescriptions', SP_UPM_TEXT_DOMAIN),
            __('Pending Prescriptions', SP_UPM_TEXT_DOMAIN),
            'manage_options',
            'user-prescriptions-manager',
            [ $this, 'load_pending_prescriptions_table' ],
        );

        $hook_requests = add_users_page(
            __('Change Prescription Requests', SP_UPM_TEXT_DOMAIN),
            __('Change Prescription Requests', SP_UPM_TEXT_DOMAIN),
            'manage_options',
            'change-prescription-requests',
            [ $this, 'load_change_prescription_requests_table' ],
        );

        // Pending Prescriptions Table
        add_action("load-$hook", [$this, 'screen_option']);

        // Change Prescription Requests Table
        add_action("load-$hook_requests", [$this, 'screen_option_requests']);

        return $hook;
    }

    public function load_pending_prescriptions_table() {
        sp_upm_get_template_part('/modals/content', 'approve-prescription-modal');
        sp_upm_get_template_part('/modals/content', 'delete-prescription-modal');
        sp_upm_get_template_part('/modals/content', 'edit-prescription-modal');

        $this->pending_prescriptions_table->prepare_items();

        sp_upm_get_template_part('content', 'pending-prescriptions-table', ['table_class' => $this]);
    }

    public function load_change_prescription_requests_table() {
        sp_upm_get_template_part('content', 'approve-modal');

        $this->pending_prescriptions_table->prepare_items();

        sp_upm_get_template_part('content', 'pending-prescriptions-table', ['table_class' => $this]);
    }

    public function user_pending_prescriptions() {
        echo "here";
    }

    /**
     * Approved Treatment
     * - Customer is ready to buy subscription
     */
    public function handle_approve_pending_prescription() {
        if ( isset( $_REQUEST['ajax_nonce'] ) && wp_verify_nonce( $_REQUEST['ajax_nonce'], 'sp_upm_approve_ajax_nonce' ) ) {
            $entry_id = absint(sanitize_text_field($_REQUEST['entry_id']));
            $user_id = absint(sanitize_text_field($_REQUEST['user_id']));
            $form_id = absint(sanitize_text_field($_REQUEST['form_id']));
            $prescriber_id = absint(sanitize_text_field($_REQUEST['prescriber_id']));
            $product_id = absint(sanitize_text_field($_REQUEST['product_id']));
            $treatment_id = absint(sanitize_text_field($_REQUEST['treatment_id']));
            $max_repeat_count = absint(sanitize_text_field($_REQUEST['max_repeat_count']));
            $date = sanitize_text_field($_REQUEST['date']);
            reset_user_prescription_caching($user_id);

            if ( $entry_id && $user_id && $product_id && $treatment_id && $prescriber_id ) {
                $existing_prescriptions = get_field('user_prescriptions', 'user_' . $user_id);

                if ( ! empty( $existing_prescriptions ) ) {
                    foreach ($existing_prescriptions as $key => $prescription) {
                        $treatment = $prescription['prescribed_categories'];
                        $prescribed_medication = $prescription['prescribed_medication'];

                        if ( $treatment->term_id === $treatment_id && empty($prescribed_medication)) {
                            $result = self::update_user_metafields($user_id, ($key + 1), $treatment_id, $product_id, $prescriber_id, $date);
                            $approved_status = sp_upm_doctors_appointments()::APPROVED;

                            $meta = [
                                'prescriber_id' => $prescriber_id,
                                'product_id' => $product_id,
                                'admin_user' => get_current_user_id()
                            ];

                            $this->update_telehealth_row($approved_status, $entry_id, $prescriber_id, $treatment_id, $product_id, $meta);

                            if ( $result ) {
                                // send notification
                                $this->send_approved_prescription_email($user_id, $product_id, $treatment_id, $prescriber_id);
                                $this->change_user_role_to_customer($user_id);

                                $this->doctors_appointment->init_mailchimp_api();

                                $consultation = [
                                    'user_id' => $user_id,
                                    'form_id' => $form_id
                                ];

                                $prefix = $this->doctors_appointment->get_treatment_mailchimp_initials($treatment_id);

                                $is_a_starter_kit = get_term_meta($treatment_id, 'is_category_a_starter_kit', true);
                                $new_tag = $is_a_starter_kit ? 'TREATMENT ORDERED' : 'TREATMENT APPROVED';
                                $tags = $this->doctors_appointment->prepare_mailchimp_tags($consultation, "$prefix - AWAITING DR CONSULT", "$prefix - $new_tag");
                                error_log("NEW TAG - $new_tag");

                                $this->doctors_appointment->update_mailchimp_tags($tags, $form_id, $user_id);

                                $_product = wc_get_product($product_id);

                                $this->doctors_appointment->update_member_custom_fields($form_id, $user_id, [
                                    "{$prefix}PRSCRBR" => $this->doctors_appointment->get_prescriber_name($prescriber_id),
                                    "{$prefix}LSTAPPT" => date("F d, Y"),
                                    "{$prefix}TPLAN" => $_product->get_title()
                                ]);

                                wp_send_json_success([
                                    'message' => __("Prescription Approved Successfully!", SP_UPM_TEXT_DOMAIN),
                                    'data' => date("d/m/Y")
                                ]);
                            } else {
                                wp_send_json_error([
                                    'message' => __('Error occured. Unable to update user prescription.', SP_UPM_TEXT_DOMAIN),
                                ]);
                            }
                        }
                    }
                }

                wp_send_json_error([
                    'message' => __('Request prescription not found. Please check existing user prescriptions.', SP_UPM_TEXT_DOMAIN),
                ]);
            }

            wp_send_json_error([
                'product_id' => $product_id,
                'user_id' => $user_id,
                'treatment_id' => $treatment_id,
                'message' => __('Missing required fields. Please try again.', SP_UPM_TEXT_DOMAIN),
            ]);
        }

        wp_send_json_error([
            'message' => __('Request token expired. Please refresh the page.', SP_UPM_TEXT_DOMAIN),
        ]);
    }

    public function update_telehealth_row($status, $entry_id, $doctor_id, $final_treatment_cat_id, $medication_id, $meta) {
        global $wpdb;

        $table = $wpdb->prefix . 'doctors_appointments';

        $wpdb->update($table, [
            'status' => $status,
            'doctor_id' => $doctor_id,
            'final_treatment_cat_id' => $final_treatment_cat_id,
            'medication_id' => $medication_id,
            'meta' => wp_json_encode($meta)
        ], ['entry_id' => $entry_id]);
    }

    /**
     * Email notification for Approved Prescriptions
     * 
     * @param int $user_id
     * @param int $product_id
     * @param int $treatment_id
     * 
     * @return void
     */
    public function send_approved_prescription_email(int $user_id, int $product_id, int $treatment_id, int $prescriber_id) : void
    {
        $treatment = get_term($treatment_id, 'product_cat');
        $is_nrt = $treatment_id == 58;

        $product = wc_get_product($product_id);
        $user = get_user_by('id', $user_id);
        $prescriber = get_post($prescriber_id);

        $email_from = get_term_meta($treatment_id, 'email_from', true);
        $reply_to = get_term_meta($treatment_id, 'email_reply_to', true);
        $subject = get_term_meta($treatment_id, 'email_subject', true);
        $subject = ! empty($subject) ? $subject : "Exciting News! ({$treatment->name} - {$product->get_name()}) Approved for You!";

        $headers   = array();
        $headers[] = sprintf( 'From: %1$s <%2$s>', "SummitPharma", ! empty($email_from) ? $email_from : 'menshealth@summitpharma.com.au' );
        $headers[] = sprintf( 'Reply-To: %1$s <%2$s>', "SummitPharma", ! empty($reply_to) ? $reply_to : 'menshealth@summitpharma.com.au'  );
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $content = $this->get_email_approved_prescription_html([
            'medication_name' => $product->get_name(),
            'customer_name' => $user->first_name,
            'concern' => $treatment->name,
            'prescriber' => $prescriber->post_title,
            'is_starter_kit' => $is_nrt
        ]);

        wp_mail( $user->user_email, $subject, $content, $headers );
    }

    public function get_email_approved_prescription_html(array $args) {
        ob_start();

        if ($args['is_starter_kit']) {
            sp_upm_get_template_part('/emails/content', 'email-approved-starter-kit', $args);
        } else {
            sp_upm_get_template_part('/emails/content', 'email-approved-prescription', $args);
        }
            

        return ob_get_clean();
    }

    /**
     * Add Customer role
     * 
     * @param int $user_id
     * 
     * @return void
     */
    public function change_user_role_to_customer($user_id) {
        $user = get_user_by('id', $user_id);

        if ( ! empty( $user->roles ) && is_array( $user->roles ) && ! in_array('customer', $user->roles) ) {
            $user->add_role('customer');
        }
    }

    /**
     * Add Customer role
     * 
     * @param int $user_id
     * 
     * @return void
     */
    public function remove_subscriber_role_of_user($user_id, $role) {
        if (strtolower($role) === 'customer') {
            $user = get_user_by('id', $user_id);

            $user->remove_role('subscriber');
        }
    }

    private static function update_user_metafields( $user_id, $row_index, $treatment_id, $product_id, $prescriber_id, $date ) {
        // Update user prescriptions repeater field
        $user_prescriptions = [
            'prescribed_categories' => $treatment_id,
            'prescribed_medication' => $product_id,
            'prescriber' => $prescriber_id,
            'active_date' => $date,
        ];

        $result = update_row( 'user_prescriptions', $row_index, $user_prescriptions, 'user_' . $user_id);

        // Update allowed categories
        $current_values = get_user_meta($user_id, 'allowed_categories', true) ?? [];
        $current_values[] = $treatment_id;

        update_field('allowed_categories', $current_values, 'user_' . $user_id);

        return $result;
    }
    
    public function create_doctors_appointments_table() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_name = $wpdb->prefix . 'doctors_appointments';

        $charset_collate = $wpdb->get_charset_collate();

        // SQL query to create the table
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE {$table_name} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doctor_id INT NOT NULL,
                user_id INT NOT NULL,
                treatment_id INT NOT NULL,
                entry_id INT NOT NULL,
                form_id INT NOT NULL,
                appointment_date DATE NOT NULL,
                appointment_time TIME NOT NULL,
                appointment_reason TEXT,
                internal_note VARCHAR(255),
                meta LONGTEXT,
                status INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL
            ) {$charset_collate};";

            // Execute the SQL query
            dbDelta( $sql );
        }

        $this->add_new_db_column($table_name, 'final_treatment_cat_id');
        $this->add_new_db_column($table_name, 'medication_id');
        $this->add_new_db_column($table_name, 'has_rebook');
        $this->add_new_db_column($table_name, 'marketing_code', 'string');
    }
    
    public function add_new_db_column($table, $column_name, $column_type = 'int') {
        global $wpdb;
        
        $row = $wpdb->get_results(  $wpdb->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = %s AND column_name = %s", $table, $column_name)  );


        if(empty($row)){
            $query = "ALTER TABLE %i ADD %i";

            switch ($column_type) {
                case 'int':
                    break;
                case 'string':
                    $query .= " VARCHAR(255) NULL";
                default:
                    # code...
                    break;
            }

            $query = $wpdb->prepare($query, $table, $column_name);

            $wpdb->query($query);
        }
    }

    public function restrict_product_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) 
    {
        $product = wc_get_product($product_id);

        $user_id = get_current_user_id();
        $primary_category_id = false;
        $allow_product = true;
        $service_category_id = get_field('consulation_products_category', 'option');

        if (function_exists('yoast_get_primary_term_id')) {
            $parent_id = $product->get_parent_id();
            $primary_category_id =  absint(yoast_get_primary_term_id('product_cat', $parent_id));
        }

        if ($service_category_id === $primary_category_id) return true;

        // if parent, and child
        if ($user_id && $primary_category_id) {
            $are_children_restricted = get_term_meta($primary_category_id, 'are_children_restricted', true);
            $allowed_categories = get_user_meta($user_id, 'allowed_categories', true);

            if(!empty($allowed_categories) && is_array($allowed_categories))
            {
                foreach($allowed_categories as $allowed_category_id)
                {
                    if ($allowed_category_id == $primary_category_id) $allow_product = true;

                    $child_categories = get_term_children($allowed_category_id, 'product_cat');

                    // Check if child categories are found
                    if (!empty($child_categories) && !is_wp_error($child_categories)) {
                        // Extract child category IDs from the category objects

                        foreach($child_categories as $child_category_id) {
                            if ($child_category_id == $primary_category_id) $allow_product = true;
                        }
                    }
                }
            }
        }

        if (! $allow_product) {
            // if not
            $this->restricted_product_cart_error_message($product_id);
        }
    }

    private function restricted_product_cart_error_message( $product_id ) {
        $key_to_remove = WC()->cart->find_product_in_cart($product_id);
        $primary_category_id =  absint(yoast_get_primary_term_id('product_cat', $product_id));

        if ($key_to_remove !== false) {
            // Remove the product from the cart
            WC()->cart->remove_cart_item($key_to_remove);

            // Optionally, you can update the cart to reflect the changes
            WC()->cart->calculate_totals();
        }

        WC()->cart->empty_cart();
        wc_add_notice('Sorry, this product cannot be added to your cart! Here' . ' === ' . $primary_category_id, 'error');
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

    /**
     * Get the current request data ($_REQUEST superglobal).
     * This method is added to ease unit testing.
     *
     * @return array The $_REQUEST superglobal.
     */
    protected function request_data() {
        return $_REQUEST;
    }
}