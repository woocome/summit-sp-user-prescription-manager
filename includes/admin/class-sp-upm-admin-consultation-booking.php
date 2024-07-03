<?php

use WPFormsMailchimp\Provider\Api;

/**
 * Consultation Booking
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/admin
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */
class Sp_Upm_Admin_Consultation_Booking {
    
    /**
     * @var self $instance
     */
    private static $instance;

    private Sp_Upm_Wpforms $sp_upm_wpforms;
    private Sp_Upm_Admin_Doctors_Appointments $doctors_appointment;

    private $promo_code = "";

    private $is_consulation_free = false;

    public function __construct()
    {
        $this->sp_upm_wpforms = sp_upm_wpforms();
        $this->doctors_appointment = sp_upm_doctors_appointments();
    }

    public function init() {
        add_action('wpforms_process_complete', [$this, 'save_booking_consultations'], 10, 4);
        add_action('wpforms_process_complete_43749', [$this, 'process_nt_submission'], 10, 4);
        add_action('sp_upm_consultation_booking_complete', [$this, 'update_user_allowed_categories'], 10, 2);
        add_action('sp_upm_consultation_booking_complete', [$this, 'add_initial_user_prescription'], 10, 4);
        add_action('sp_upm_consultation_payment_status', [$this, 'update_mailchimp_customer_data'], 10, 3);
        add_action('sp_upm_consultation_payment_status', [$this, 'update_mailchimp_customer_data'], 10, 3);
    }

    // Validate Free Consult Promo Code
    public function is_promo_code_valid() {
        if (empty($this->get_promo_code())) return false;
    
        return in_array($this->get_promo_code(), $this->get_valid_promo_codes());
    }

    private function get_valid_promo_codes() {
        return [
            'zl2v9kslvdfykjn2r5y8n5mzB',
            'phooxrlo3wc5j66hiq9ss70tB',
            's4eiszio0ck18vzo1qp8x4riB'
        ];
    }

    public function set_promo_code($code) {
        $this->promo_code = $code;

        return $this;
    }

    public function get_promo_code() {
        return $this->promo_code;
    }

    public function save_booking_consultations($fields, $entry, $form_data, $entry_id) {
        $forms = $this->sp_upm_wpforms->get_wpforms_consultation_booking_forms();

        error_log("==== CONSULTATION BOOKING === {$entry_id}");

        // check if form is a consultation booking form
        if ( ! in_array($form_data['id'], $forms)) return;
    
        // Get the current user ID
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $form_id = absint($form_data['id']);

        $product_cat = $this->sp_upm_wpforms->get_product_category_by_form_id($form_id, 'consultation_booking_form');
        if (! $product_cat) return;

        $is_free_consultation = get_field('free_consultation', "product_cat_{$product_cat->term_id}");
        $this->set_is_consultation_free($is_free_consultation);

        error_log("--- Product Category Found ");

        $doctor_id = get_term_meta($product_cat->term_id, 'assigned_doctor', true);

        // get date and time value
        $date_time_field_id = $this->sp_upm_wpforms->get_field_id_by_name($fields, 'Your Consultation', 'date-time');
        $marketing_code_field_id = $this->sp_upm_wpforms->get_field_id_by_name($fields, 'Marketing', 'hidden');
        $email_field_id = $this->sp_upm_wpforms->get_field_id_by_name($fields, 'Email', 'email');
        $promo_code_field_id = $this->sp_upm_wpforms->get_field_id_by_name($fields, 'Marketing Promotion', 'hidden');

        $email = $entry['fields'][$email_field_id];

        if (! $user_id) {
            $user = get_user_by('email', $email);
            $user_id = $user->ID;
            wp_set_current_user($user_id, $user->user_login);
            wp_set_auth_cookie($user_id);
        }

        if (! $user_id || ! $date_time_field_id) return;

        error_log("--- User or Date Time Found ");

        $booking_date_time = $entry['fields'][$date_time_field_id];
        $this->promo_code = $promo_code_field_id ? $entry['fields'][$promo_code_field_id] : false;

        $date = $this->doctors_appointment->convert_date_time($booking_date_time['date'], 'd/m/Y', 'Y-m-d');
        $time = $this->doctors_appointment->convert_date_time($booking_date_time['time'], 'h:i A', 'H:i:s');
        $status =  $this->is_consulation_free ? 1 : 0; // 1 = Free
        $is_promo_code_valid = $this->is_promo_code_valid();

        if (! $status && $is_promo_code_valid) {
            $this->set_is_consultation_free(true);

            $status = 1;
        }

        $marketing_code = $entry['fields'][$marketing_code_field_id];
        $row_id = $this->doctors_appointment->create($doctor_id, $form_id, $entry_id, $user, $product_cat, $date, $time, $status, $marketing_code);

        if (! $row_id) {
            error_log("Error occured during the saving of data");
            return;
        }

        error_log("--- Result Found ");

        do_action('sp_upm_consultation_payment_status', $row_id, $product_cat, $status);

        do_action('sp_upm_consultation_booking_complete', $user, $product_cat, $booking_date_time, $form_id);

        if ($is_promo_code_valid) {

            // Redirect to the checkout page
            if (wp_safe_redirect(esc_url(home_url('/thank-you-nrt/')))) {
                exit;
            }
        }
    }

    public function set_is_consultation_free($is_free = false) {
        $this->is_consulation_free = $is_free;
    }

    public function update_user_allowed_categories($user, $product_cat) {
        $this->doctors_appointment->update_user_allowed_categories($user->ID, $product_cat->term_id);
    }

    public function add_initial_user_prescription($user, $product_cat, $booking_date_time) {
        $readable_date = $this->doctors_appointment->convert_date_time($booking_date_time['date'], 'd/m/Y', 'F d, Y');
        $time = $this->doctors_appointment->convert_date_time($booking_date_time['time'], 'h:i A', 'H:i:s');

        $schedule = $readable_date . ' - ' . $time;
        $status = $this->is_consulation_free ? 'paid' : 'awaiting_payment';

        $this->doctors_appointment->add_initial_user_prescription($user->ID, $product_cat->term_id, $schedule, $status );
    }

    public function update_mailchimp_customer_data($consultation_row_id, $product_cat, $status) {
        if (! $status) return;

        $consultation = $this->doctors_appointment->get_consultation_item_by('id', $consultation_row_id);

        $this->doctors_appointment->update_mailchimp_customer_data($consultation, $product_cat->term_id);
    }

    public function process_nt_submission($fields, $entry, $form_data, $entry_id) {
        $option = $this->sp_upm_wpforms->get_field_id_by_name($fields, 'Select Your Preferred Option', 'radio');
        $selected = $entry['fields'][$option];

        if ($selected == 'Directly book an appointment for free') {
            $home_url = esc_url(home_url());
            wp_redirect("https://widget.medirecords.com/#/?pid=7dbe6074-6540-4f2c-bc35-b7aaac3a0ca3&token=z2WaeFShknYyEog7lz1ydz37Aj4&host=$home_url");
            exit;
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

function sp_upm_consultation_booking() {
    return Sp_Upm_Admin_Consultation_Booking::get_instance();
}