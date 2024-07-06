<?php

use WPFormsMailchimp\Provider\Api;
/**
 * Manage Doctors Appointments
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/admin
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */
class Sp_Upm_Admin_Doctors_Appointments {
    
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

    public $mailchimp_api;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    public $post_type = 'doctor';

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    public $table_name = '';

    const AWAITING_PAYMENT = 0;
    const PAID = 1;
    const APPROVED = 2;
    const STATUS_ORDERED = 3;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct() {
        $this->plugin_name = SP_USER_PRESCRIPTION_MANAGER_NAME;
        $this->version = SP_USER_PRESCRIPTION_MANAGER_VERSION;

        global $wpdb;

        $this->table_name = $wpdb->prefix . 'doctors_appointments';
    }

    public function init_hooks() {
        add_action('wpforms_process_complete', [$this, 'save_appointment_request'], 10, 4);
        add_action('init', [$this, 'register_doctors_post_type']);

        if (! isset($_GET['summit-treatment']) || ! isset($_GET['treatment_cat_id'])) {
            add_action( 'wpforms_wp_footer_end', [ $this, 'display_date_time_ranges' ] );
        }

        add_action( 'woocommerce_payment_complete', [ $this, 'update_telehealth_consult_status' ], 10 );
        add_action( 'woocommerce_order_status_processing', [ $this, 'update_telehealth_consult_status' ], 10 );

        add_action( 'woocommerce_payment_complete', [ $this, 'mailchimp_mark_customer_with_treatment_ordered' ], 10 );
        add_action( 'woocommerce_order_status_processing', [ $this, 'mailchimp_mark_customer_with_treatment_ordered' ], 10 );

        add_action( 'sp_upm_consultation_payment_paid', [ $this, 'update_mailchimp_customer_data' ], 10, 2);
        add_action('acf/input/admin_footer', [ $this, 'customize_acf_date_time_picker' ]);
        add_filter( 'wpforms_process_filter', [ $this, 'hidden_booking_time_field'], 10, 3 );

        add_action( 'wp_ajax_edit_prescription_entry', [ $this, 'handle_edit_prescription_entry'] );
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
            'doctor_id' => '%d',
            'user_id' => '%d',
            'treatment_id' => '%d',
            'entry_id' => '%d',
            'form_id' => '%d',
            'appointment_date' => '%s',
            'appointment_time' => '%s',
            'appointment_reason' => '%s',
            'meta' => '%s',
            'internal_note' => '%s',
            'status' => '%d',
            'has_rebook' => '%d',
            'marketing_code' => '%s',
            'created_at' => '%s',
            'updated_at' => '%s',
            'deleted_at' => '%s',
        ];
    }

    /**
     * Retrieve column defaults.
     *
     * @since 1.1.6
     *
     * @return array All defined column defaults.
     */
    public function get_column_defaults() {
        return [
            'doctor_id' => '',
            'user_id' => '',
            'treatment_id' => '',
            'entry_id' => '',
            'form_id' => '',
            'appointment_date' => '',
            'appointment_time' => '',
            'appointment_reason' => '',
            'meta' => '',
            'internal_note' => '',
            'status' => 0,
            'has_rebook' => 0,
            'marketing_code' => '',
        ];
    }
    
    /**
     * Insert a new record into the database.
     *
     * @since 1.0.0
     *
     * @param array  $data Column data.
     * @param string $type Optional. Data type context.
     *
     * @return int ID for the newly inserted record. 0 otherwise.
     */
    public function add( $data, $type = '' ) {
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

        $wpdb->insert( $this->table_name, $data, $column_formats );

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

    public function register_doctors_post_type() {
        register_post_type( $this->post_type, array(
            'labels' => array(
                'name' => 'Doctors',
                'singular_name' => 'Doctor',
                'menu_name' => 'Doctors',
                'all_items' => 'All Doctors',
                'edit_item' => 'Edit Doctor',
                'view_item' => 'View Doctor',
                'view_items' => 'View Doctors',
                'add_new_item' => 'Add New Doctor',
                'add_new' => 'Add New Doctor',
                'new_item' => 'New Doctor',
                'search_items' => 'Search Doctors',
                'not_found' => 'No doctor found',
                'not_found_in_trash' => 'No doctors found in Trash',
                'items_list' => 'Doctors list',
                'item_published' => 'Doctor published.',
            ),
            'public' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-plus-alt',
            'supports' => array(
                0 => 'title',
                1 => 'author',
                2 => 'editor',
                3 => 'excerpt',
                4 => 'revisions',
                5 => 'thumbnail',
                6 => 'custom-fields',
            ),
            'delete_with_user' => false,
        ));

        if (isset($_GET['update_marketing_code_column'])) {
            global $wpdb;

            // Define your custom table and WPForms tables
            $custom_table = $wpdb->prefix . 'doctors_appointments';
            $wpforms_entries_table = $wpdb->prefix . 'wpforms_entries';
            $wpforms_entry_fields_table = $wpdb->prefix . 'wpforms_entry_fields';

            // Define an array of field_id values to update
            $field_ids = array(511, 509);

            // Loop through each field_id and execute the update query
            foreach ($field_ids as $field_id) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $custom_table da
                        JOIN $wpforms_entry_fields_table wf ON da.entry_id = wf.entry_id
                        SET da.marketing_code = wf.value
                        WHERE wf.field_id = %d
                        AND wf.value != ''",
                        $field_id
                    )
                );
            }
        }
    }

    public function save_appointment_request($fields, $entry, $form_data, $entry_id) {
        $booking_forms = sp_upm_wpforms()->get_wpforms_consultation_booking_forms();

        // Get the current user ID
        $user_id = get_current_user_id();
        $user = get_user_by('id', $user_id);
        $form_id = absint($form_data['id']);

        $product_category = $this->get_assigned_product_category_by_form_id($form_id);
        // exit if no category found
        if ( ! $product_category ) return;
        error_log("HAS CATEGORY === " . $product_category->term_id);

        // There's a new booking form id
        $booking_form_id = get_field('consultation_booking_form', 'product_cat_' . $product_category->term_id);
        if (in_array($booking_form_id, $booking_forms)) return;

        // get date and time value
        $date_time_field_id = $this->get_field_id_by_name($fields, "Your Consultation");
        $email_field_id = $this->get_field_id_by_name($fields, "Email", 'email');
        $email = $entry['fields'][$email_field_id];

        if (! $user_id || ! $user) {
            $user = get_user_by('email', $email);
            $user_id = $user->ID;
            wp_set_current_user($user_id, $user->user_login);
            wp_set_auth_cookie($user_id);
        }

        error_log("APPOINTMENT START === " . $user_id . " === " . $form_id);

        if ($date_time_field_id && $entry['fields'][$date_time_field_id]) {
            $telehealth_date = $entry['fields'][$date_time_field_id];
            error_log("BOOKING === " . json_encode($telehealth_date));

            // if user session id is not recognized yet
            if ( ! $user) {
                $user = get_user_by("email", $email);
                $user_id = $user ? $user->ID : false;
            }

            $doctor_id = get_term_meta($product_category->term_id, 'assigned_doctor', true);

            // If no user found or doctor, exit;
            if ( ! $user_id || ! $doctor_id ) return;

            $date = $this->convert_date_time($telehealth_date['date'], 'd/m/Y', 'Y-m-d');
            $time = $this->convert_date_time($telehealth_date['time'], 'h:i A', 'H:i:s');

            $meta = [
                'treatment_name'    => $product_category->name,
                'customer_email'    => $email,
                'customer_name'     => $user->first_name . ' ' . $user->last_name,
            ];

            $data = [
                'doctor_id' => $doctor_id,
                'user_id' => $user_id,
                'treatment_id' => $product_category->term_id,
                'entry_id' => $entry_id,
                'form_id' => $form_id,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'meta' => json_encode($meta),
            ];

            $row_id = $this->insert($data);

            if ($row_id) {
                $term_id = $product_category->term_id;
                $this->update_user_allowed_categories($user_id, $term_id);

                $readable_date = $this->convert_date_time($telehealth_date['date'], 'd/m/Y', 'F d, Y');
                $this->add_initial_user_prescription($user_id, $term_id, $readable_date . ' - ' . $time);
            }

            /**
             * TODO: Check if we still need this
             */
            // add entry meta so that we can exclude the entry on the query
            wpforms()->get( 'entry_meta' )->add(
                [
                    'entry_id' => $entry_id,
                    'form_id'  => $form_id,
                    'user_id'  => $user_id,
                    'type'     => 'appointment_log',
                    'data'     => sprintf("Appointment Data Result ID %d", $row_id),
                ],
                'entry_meta'
            );

            error_log("APPOINTMENT END === " . $user_id . " === " . $form_id . " === " . $product_category->term_id);
        }
    }

    public function create($doctor_id, $form_id, $entry_id, $user, $product_cat, $date, $time, $status = 0, $marketing_code = '') {
        $meta = [
            'treatment_name'    => $product_cat->name,
            'customer_email'    => $user->user_email,
            'customer_name'     => $user->first_name . ' ' . $user->last_name,
        ];

        $data = [
            'doctor_id' => $doctor_id,
            'user_id' => $user->ID,
            'treatment_id' => $product_cat->term_id,
            'entry_id' => $entry_id,
            'form_id' => $form_id,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'meta' => json_encode($meta),
            'status' => $status,
            'marketing_code' => $marketing_code
        ];

        $row_id = $this->insert($data);

        do_action('sp_upm_doctors_appointment_created', $row_id, $user->ID, $meta['treatment_name']);

        return $row_id;
    }

    public function update_user_allowed_categories($user_id, $term_id) {
        $allowed_categories = get_user_meta($user_id, 'allowed_categories', true);

        if (empty($allowed_categories)) $allowed_categories = [];
        $allowed_categories[] = $term_id;

        update_field('allowed_categories', $allowed_categories, 'user_' . $user_id);
    }

    public function add_initial_user_prescription($user_id, $term_id, $consultation_schedule, $status = 'awaiting_payment') {
        $user_prescriptions = [
            'prescribed_categories' => $term_id,
            'prescribed_medication' => null,
            'active_date' => '',
            'date_and_time_consultation' => $consultation_schedule,
            'status' => $status
        ];

        add_row('user_prescriptions', $user_prescriptions, 'user_' . $user_id);
    }

    /**
     * Get field id by name
     * 
     * @param array $fields
     * @param string $field_name
     * @param string $type
     * 
     * @return int|boolean
     */
    public function get_field_id_by_name(array $fields, $field_name, $type = 'date-time') : int {
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

    /**
     * @param int $form_id
     */
    public function get_assigned_product_category_by_form_id(int $form_id) {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT term_id FROM %i
            WHERE `meta_value` = %d
            AND (`meta_key` = 'category_wp_form' OR `meta_key` = 'consultation_booking_form')
            ORDER BY term_id DESC LIMIT 1", $wpdb->prefix . 'termmeta', $form_id);
        $result = $wpdb->get_row($query, ARRAY_A);

        if (! $result) return false;

        $product_category = get_term_by('id', absint($result['term_id']), 'product_cat');

        return $product_category;
    }

    public function format_date_and_time_entries_for_wpform($doctor_id) {
        $appointments = $this->get_doctor_appointment_dates($doctor_id);

        if ( ! $appointments ) return false;

        $formatted = [];

        foreach ($appointments as $key => $appointment) {
            $date = $this->convert_date_time($appointment['date'], 'Y-m-d', 'd/m/Y');
            $time = $this->convert_date_time($appointment['time'], 'H:i:s', 'g:i A');
            $formatted[$date][] = $time;
        }

        return $formatted;
    }

    public function get_doctor_id_by_form_id($form_id) {
        $product_category = $this->get_assigned_product_category_by_form_id($form_id);
        $doctor_id = get_term_meta($product_category->term_id, 'assigned_doctor', true);

        return $doctor_id;
    }

    /**
     * @param string $from_format
     * @param string $to_format
     * 
     * @return string|boolean
     */
    public function convert_date_time(string $value, string $from_format, string $to_format) {
        if (! $value) return false;

        $dateTime = DateTime::createFromFormat($from_format, $value);

        return $dateTime ? $dateTime->format($to_format) : false;
    }

    /**
     * @param int $doctor_id
     * 
     * @return array|null
     */
    public function get_doctor_appointment_dates($doctor_id) {
        global $wpdb;

        $query = $wpdb->prepare("SELECT appointment_date as date, appointment_time as time FROM %i WHERE `doctor_id` = %d AND appointment_date >= CURDATE()", $this->table_name, $doctor_id);

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }

    public function display_date_time_ranges($forms = [], $doctor_id = null) {
        $form_id = absint(array_key_first( $forms ));
        $doctor_id = $form_id === 35337 ? 27958 : $doctor_id;
        $doctor_id = $doctor_id ?? $this->get_doctor_id_by_form_id($form_id);

        if (isset($_GET['treatment_cat_id'])) {
            $doctor_id = get_field('assigned_doctor', 'product_cat_' . absint($_GET['treatment_cat_id']));
        }

        $appointment_dates = wp_json_encode($this->format_date_and_time_entries_for_wpform($doctor_id));
        $available_weekdays = wp_json_encode($this->get_day_of_the_week_availability($doctor_id));
        $available_time_range = wp_json_encode($this->get_time_range_availability($doctor_id) ?? []);
        $disabled_dates = wp_json_encode(array_column($this->get_disabled_dates($doctor_id), 'disable_date'));
        $disabled_date_time_range = wp_json_encode($this->get_disabled_date_time_range($doctor_id));

        sp_upm_get_template_part('/scripts/content', 'appointment-dates', [
            'appointment_dates' => $appointment_dates,
            'available_weekdays' => $available_weekdays,
            'available_time_range' => $available_time_range,
            'disabled_dates' => $disabled_dates,
            'disabled_date_time_range' => $disabled_date_time_range
        ]);
    }

    public function get_day_of_the_week_availability($doctor_id) {
        $availability = get_field('select_active_day', $doctor_id);

        return $availability;
    }

    public function get_time_range_availability($doctor_id) {
        $availability = get_field('time_range', $doctor_id);

        if ($availability) {
            $from = $this->convert_date_time($availability['time_from'], 'g:i A', 'H:i:s');
            $to = $this->convert_date_time($availability['time_to'], 'g:i A', 'H:i:s');
    
            return [
                'from' => $from,
                'to' => $to,
                'interval' => $availability['time_interval']
            ];
        }

        return false;
    }

    public function get_disabled_dates($doctor_id) {
        $availability = get_field('date_setting
        s', $doctor_id);

        return $availability ?? [];
    }

    public function get_disabled_date_time_range($doctor_id) {
        $availability = get_field('disable_date_time_range', $doctor_id);

        return $availability;
    }

    /**
     * Updating of Telehealth Consultation Appointment
     */
    public function update_telehealth_consult_status( $order_id ) {
        global $wpdb;
        error_log("=== ORDER INITIATED {$order_id} ===");

        try {
            $order = wc_get_order( $order_id );
            if (!$order) return;

            $user_id = $order->get_customer_id();

            if (! $user_id) {
                $email = $order->get_billing_email();
                $user = get_user_by('email', $email);
                if (! $user) return;

                $user_id = $user->ID;
            } else {
                $user = get_user_by('id', $user_id);
            }

            error_log("USER FOUND.");

            // Assuming only one treatment per order for simplification
            $treatment_category_id = $this->get_treatment_category_id_from_order($order);
            if (! $treatment_category_id) return;

            error_log("TREATMENT CAT FOUND." . ' === ' . $treatment_category_id);

            $consultation = $this->get_consultation_item($user_id, $treatment_category_id, self::AWAITING_PAYMENT);
            if (! $consultation) return;

            error_log("CONSULTATION FOUND." . ' === ' . $consultation['id']);

            $wpdb->update(
                $this->table_name,
                ['status' => self::PAID],
                ['id' => $consultation['id']]
            );

            $this->update_user_prescription_to_paid( $user_id, absint($treatment_category_id));

            $default_status = 'wc-custom-status';
            $order->update_status( $default_status );


            // Mailchimp tag update and notification sending encapsulated
            $this->process_after_payment_actions($treatment_category_id, $consultation, $user);

            do_action('sp_upm_consultation_payment_paid', $consultation, $treatment_category_id);
            
            error_log("=== ORDER JOURNEY {$order_id} END ===");
        } catch (\Exception $e) {
            error_log($e->getMessage() . " = " . $e->getLine());
        }
    }

    /**
     * Gets the initials of each word in a given string.
     *
     * @param string $string
     * @return string|mixed|bool
     */
    public function get_treatment_mailchimp_initials($product_category_id) {
        if (! $product_category_id) return false;

        $initials = get_term_meta($product_category_id, 'mailchimp_initials', true);

        return strtoupper($initials);
    }

    /**
     * Update the customer mailchimp tag
     * with TREATMENT ORDERED
     */
    public function mailchimp_mark_customer_with_treatment_ordered( $order_id ) {
        global $wpdb;

        $order = wc_get_order( $order_id );
        if (!$order) return;

        $user_id = $order->get_customer_id();
        if (! $user_id) return;

        $treatment = $this->get_purchased_prescribed_treatment( $order, $user_id );
        if (empty($treatment) || ! is_array($treatment)) return;
        $treatment_category_id = $treatment['prescribed_categories']->term_id;

        $prefix = $this->get_treatment_mailchimp_initials($treatment_category_id);

        $consultation = $this->get_consultation_item($user_id, $treatment_category_id, self::APPROVED);
        $treatment = $this->get_treatment_category($treatment_category_id);
        if (! $consultation || ! $treatment) return;

        $new_tag = "$prefix - TREATMENT ORDERED";
        $tags = $this->prepare_mailchimp_tags($consultation, "$prefix - TREATMENT APPROVED", $new_tag);

        if (empty($tags)) return;

        $tags[] = [
            'name' => "$prefix - CUSTOMER ORDERED 1 YEAR",
            'status' => 'active'
        ];

        $this->update_mailchimp_tags($tags, absint($consultation['form_id']), $user_id);

        $wpdb->update($this->table_name,
            ['status' => self::STATUS_ORDERED],
            ['id' => $consultation['id']]
        );

        do_action('sp_upm_subscription_treatment_ordered', $consultation, $treatment, $order);
    }

    protected function get_treatment_category($id) {
        $treatment = get_term($id, 'product_cat');

        return $treatment ?? false;
    }

    public function get_purchased_prescribed_treatment( $order, $user_id ) {
        $prescribed_treatments = get_field('user_prescriptions', "user_$user_id");

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $top_up_product = get_field('select_top_up_product', $product_id);

            foreach ($prescribed_treatments as $treatment) {
                $treatment_product_id = absint($treatment['prescribed_medication']);
                $treatment_category = $treatment['prescribed_categories'];

                if (($product_id == $treatment_product_id || $top_up_product->ID == $treatment_product_id) && $treatment_category) {
                    return $treatment;
                }
            }
        }

        return false;
    }

    protected function get_treatment_category_id_from_order($order) {
        $consultation_products = get_field('select_consultation_products', 'option');

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            if (in_array($product_id, $consultation_products)) {
                if ($treatment_id = get_field('treatment_category', $product_id)) {
                    return $treatment_id;
                }
            }
        }

        return false;
    }

    /**
     * Method for removing, and adding of new mailchimp tags
     * to a customer
     */
    public function prepare_mailchimp_tags($consultation, $exclude_tag, $include_tag) {
        $user = get_user_by('id', absint($consultation['user_id']));
        $email = $user->user_email;

        $existing_tags = $this->get_existing_mailchimp_tags(absint($consultation['form_id']), $email);
        $tags = $this->filter_tags($existing_tags, $exclude_tag);

        $tags[] = [
            'name' => $include_tag,
            'status' => 'active'
        ];

        return $tags;
    }

    public function process_after_payment_actions($treatment_category_id, $consultation, $user) {
        $treatment = get_term($treatment_category_id, 'product_cat');

        // send an email notification to admin
        $this->admin_paid_consultation_notification([
            'customer_name' => $user->user_firstname . ' ' . $user->user_lastname,
            'customer_email' => $user->user_email,
            'concern' => $treatment->name,
            'appointment_schedule' => $consultation['appointment_date'] . ' - ' . $consultation['appointment_time']
        ]);
    }

    /**
     * Send an email to menshealth admin
     * when a consultation service booking is paid
     */
    public function admin_paid_consultation_notification($args) {
        $headers   = array();
        $headers[] = sprintf( 'From: %1$s <%2$s>', "SummitPharma", "menshealth@summitpharma.com.au" );
        $headers[] = sprintf( 'Reply-To: %1$s <%2$s>', "SummitPharma", "menshealth@summitpharma.com.au" );
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        ob_start();

        sp_upm_get_template_part('/emails/content', 'email-consultation-paid', $args);

        $content = ob_get_clean();

        wp_mail( 'menshealth@summitpharma.com.au', "Confirmation of Payment for Telehealth Consultation!", $content, $headers );
    }
    
    public function check_if_eligible_for_rebooking( $product_cat_id ) {
        if (! $product_cat_id) return false;

        $consultation = $this->get_consultation_item_by('treatment_id', $product_cat_id);
        if (! $consultation) return false;

        return ($consultation['has_rebook'] == false);
    }

    public function get_consultation_item($user_id, $treatment_id, $status) {
        global $wpdb;

        $query = $wpdb->prepare('SELECT
                id,
                user_id,
                entry_id,
                form_id,
                final_treatment_cat_id,
                medication_id,
                status,
                appointment_date,
                appointment_time,
                has_rebook,
                created_at
            FROM %i
            WHERE user_id = %d
            AND treatment_id = %d
            AND status = %d
            ORDER BY id DESC
            LIMIT 1',
            $this->table_name, $user_id, $treatment_id, $status
        );

        return $wpdb->get_row($query, ARRAY_A);
    }

    public function get_consultation_item_by($column, $value, $user_id = false) {
        global $wpdb;

        $user_id = !$user_id ? get_current_user_id() : $user_id;

        $query = 'SELECT
                id,
                user_id,
                entry_id,
                form_id,
                final_treatment_cat_id,
                medication_id,
                appointment_date,
                appointment_time,
                has_rebook,
                status,
                created_at
            FROM %i
        ';

        $query .= " WHERE {$column} = {$value}";
        $query .= " AND user_id = {$user_id}";
        $query .= " ORDER BY id DESC LIMIT 1";

        $query = $wpdb->prepare($query, $this->table_name);

        return $wpdb->get_row($query, ARRAY_A);
    }

    public function update_user_prescription_to_paid( $user_id, $treatment_id ) {
        $user_key = 'user_' . $user_id;
        $prescriptions = get_field('user_prescriptions', $user_key);

        foreach ($prescriptions as $key => $prescription) {
            $product_category = $prescription['prescribed_categories'];
            $prescribed_medication = $prescription['prescribed_medication'];

            if ($product_category->term_id === $treatment_id && ($prescription['status'] == 'awaiting_payment' && empty($prescribed_medication))) {
                // Update user prescriptions repeater field
                $user_prescriptions = [
                    'prescribed_categories' => $product_category->term_id,
                    'status' => 'paid'
                ];

                update_row( 'user_prescriptions', $key +  1, $user_prescriptions, $user_key);

                error_log("UPDATED TO PAID");
                return;
            }
        }
    }

    public function init_mailchimp_api() {
        $providers = wpforms_get_providers_options();

        if ($mailchimp = $providers['mailchimpv3']) {
            $mailchimpv3 = reset($mailchimp);
            $mailChimpApi = new Api($mailchimpv3['api']);

            $this->mailchimp_api = $mailChimpApi;
        }
    }

    public function get_hash_email($email) {
        if (! $email || ! is_email($email)) return;
        $this->init_mailchimp_api();

        return $this->mailchimp_api::subscriberHash($email);
    }

    public function get_mailchimp_list_id_by_form_id($form_id) {
        $form = wpforms()->get( 'form' )->get($form_id);
        $formData = wpforms_decode($form->post_content);

        if ($formData['providers'] && $formData['providers']['mailchimpv3']) {
            $mailchimp = reset($formData['providers']['mailchimpv3']);

            return $mailchimp['list_id'];
        }

        return false;
    }

    public function update_mailchimp_tags(array $tags, $form_id, $user_id) {
        $user = get_user_by('id', $user_id);
        $email = $user->user_email;
        $list_id = $this->get_mailchimp_list_id_by_form_id($form_id);

        $this->mailchimp_api->update_member_tags($list_id, $email, ['tags' => $tags]);
    }

    public function get_existing_mailchimp_tags($form_id, $email) {
        $hash = $this->get_hash_email($email);
        $list_id = $this->get_mailchimp_list_id_by_form_id($form_id);

        $response = $this->mailchimp_api->get( "lists/{$list_id}/members/{$hash}/tags");

        return $response['tags'] ?? false;
    }

    public function update_member_custom_fields($form_id, $user_id, $merge_fields) {
        $this->init_mailchimp_api();
        $user = get_user_by('id', $user_id);
        $email = $user->user_email;

        $list_id = $this->get_mailchimp_list_id_by_form_id($form_id);

        $data = [
            'merge_fields' => $merge_fields
        ];

        $response = $this->mailchimp_api->update_list_member( $list_id, $email, $data, true);

        return $response;
    }

    public function filter_tags($existing_tags, $exclude) {
        if (! is_array($existing_tags)) return [];

        $tags = array_map(function($tag) use ($exclude) {
            $is_excluded = str_contains(strtolower($tag['name']), $exclude) || $tag['name'] == $exclude;

            $tag = [
                'name' => $tag['name'],
                'status' => $is_excluded ? 'inactive' : 'active'
            ];

            return $tag;
        }, $existing_tags);

        return $tags;
    }

    public function update_mailchimp_customer_data($consultation, int $treatment_category_id) {
        $this->init_mailchimp_api();

        $prefix  = $this->get_treatment_mailchimp_initials($treatment_category_id);

        $new_tag = "$prefix - AWAITING DR CONSULT";
        $tags = $this->prepare_mailchimp_tags($consultation, "{$prefix} TELEHEALTH CONSULTATION PENDING PAYMENT", $new_tag);
        if (empty($tags)) return;

        error_log("USER TAGGED WITH" . ' === ' . json_encode($tags));

        $this->update_mailchimp_tags($tags, $consultation['form_id'], $consultation['user_id']);
    }

    public function customize_acf_date_time_picker() {
        sp_upm_get_template_part('/scripts/content', 'acf-date-time-picker');
    }

    public function hidden_booking_time_field( $fields, $entry, $form_data ) {
         // Only run on the form with ID 727
        $date_time_field_id = $this->get_field_id_by_name($fields, "Your Consultation");

        if (! $date_time_field_id) {
            $date_time_field_id = $this->get_field_id_by_name($fields, "Appointment Date and Time");
        }

        $hidden_date_time_field_id = $this->get_field_id_by_name($fields, "Booking Date and Time", 'hidden');

        if ($hidden_date_time_field_id && $date_time_field_id) {
            //Look for the hidden field we want to replace the value of
            $fields[$hidden_date_time_field_id]['value'] = $this->convert_date_time($fields[$date_time_field_id]['value'], 'd/m/Y g:i A', 'F d, Y g:i A');
        }

        return $fields; 
    }

    public function handle_edit_prescription_entry() {
        $result = "";

        if ( isset( $_REQUEST['ajax_nonce'] ) && wp_verify_nonce( $_REQUEST['ajax_nonce'], 'sp_upm_edit_ajax_nonce' ) ) {
            global $wpdb;

            $entry_id = absint(sanitize_text_field($_REQUEST['entry_id']));
            $user_id = absint(sanitize_text_field($_REQUEST['user_id']));
            $form_id = absint(sanitize_text_field($_REQUEST['form_id']));
            $data = $_REQUEST['initial'];
            $treatment_cat_id = $data['final_treatment_id'] ?? $data['treatment_id'];

            if (! $treatment_cat_id) $this->response_error('Missing product category ID.');

            $prefix = $this->get_treatment_mailchimp_initials($treatment_cat_id);

            $appointment_date = $_REQUEST['appointment_date'];
            $appointment_time = $_REQUEST['appointment_time'];

            $date = $this->convert_date_time($appointment_date, 'Y-m-d', 'F d, Y');
            $time = $this->convert_date_time($appointment_time, 'H:i', 'g:i A');

            $response = $this->update_member_custom_fields($form_id, $user_id, [
                "{$prefix}BKDNT" => $date . ' - ' . $time,
                "{$prefix}THSCHED" => $appointment_date
            ]);

            if ( ! empty($response)) {
                $result = $this->update_appointment_date_time($entry_id, $appointment_date, $appointment_time);

                wp_send_json_success([
                    'message' => 'Success'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Failed to update mailchimp custom field',
                    'result' => $response,
                    'date' => $date,
                    'time' => $time
                ]);
            }
        }

        wp_send_json_error([
            'message' => 'Failed',
            'result' => $result
        ]);
    }

    public function get_prescriber_name($prescriber_id) {
        $prescriber = get_post($prescriber_id);

        if (! $prescriber) return;

        return $prescriber->post_title;
    }

    public function response_error( $message = '' ) {
        wp_send_json_error([
            'message' => $message
        ]);
    }

    public function update_appointment_date_time($entry_id, $appointment_date, $appointment_time) {
        return $this->update(
            [
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time
            ],
            [
                'entry_id' => $entry_id
            ]
        );
    }

    public function update(array $data, array $where) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            $data,
            $where
        );

        return $result;
    }

    public function get_treatment_pending_appointment( $product_category_id,  $user_id ) {
        $user_prescriptions = get_field('user_prescriptions', 'user_' . $user_id);

        foreach ($user_prescriptions as $key => $user_prescription) {
            $treatment = $user_prescription['prescribed_categories'];

            if ($treatment->term_id === $product_category_id && empty($user_prescription['prescribed_medication'])) {
                return $user_prescription;
            }
        }

        return false;
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

function sp_upm_doctors_appointments() {
    return Sp_Upm_Admin_Doctors_Appointments::get_instance();
}