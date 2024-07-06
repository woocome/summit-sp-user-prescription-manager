<?php
/**
 * Consultation Appointment Rebooking
 *
 * @link       https://bit.ly/dan-singian-resume
 * @since      1.0.0
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/public
 */

class Sp_Upm_Appointment_Rebooking
{
    /**
     * @var self $instance
     */
    private static $instance;

    public function __construct() {
    }

    public function init() {
        $appointment_rebooking_form_id = 31131;

        add_action( 'wpforms_wp_footer_end', [ $this, 'handle_rebooking_script' ] );
        add_action("wpforms_process_complete_$appointment_rebooking_form_id", [$this, 'save_new_booking_appointment'], 10, 2);
        add_shortcode( 'sp_display_rebooking_form', [$this, 'display_rebooking_form'] );
    }

    public function display_rebooking_form() {
        if (isset($_GET['treatment_cat_id'])) {
            $product_cat_id = sanitize_text_field($_GET['treatment_cat_id']);

            $is_eligible_for_rebooking = sp_upm_doctors_appointments()->check_if_eligible_for_rebooking($product_cat_id);

            if ($is_eligible_for_rebooking == false) {
                echo do_shortcode('[elementor-template id="43585"]');
            } else {
                echo do_shortcode('[elementor-template id="43582"]');
            }
        }
    }

    public function handle_rebooking_script() {
        if (isset($_GET['treatment_cat_id'])) {
            $treatment_cat_id = sanitize_text_field( $_GET['treatment_cat_id'] );
            $product_cat = get_term_by( 'id', $treatment_cat_id, 'product_cat' );

            if (! $product_cat) return;

            $row = sp_upm_doctors_appointments()->get_consultation_item_by('treatment_id', $product_cat->term_id);

            if ($row) {
                $initial_time = "";
                $initial_date = sp_upm_doctors_appointments()->convert_date_time($row['appointment_date'], 'Y-m-d', 'd/m/Y');

                // Convert the given date string to a DateTime object
                $intial_booking_date = DateTime::createFromFormat('d/m/Y', $initial_date);

                // Get today's date
                $today = new DateTime();

                if ($intial_booking_date <= $today) {
                    $rebooking_message = "Please select another time and date for your doctor\'s phone appointment";
                } else {
                    $rebooking_message = "Confirm that the appointment time and date for your doctor\'s call are correct.";
                    $initial_time = sp_upm_doctors_appointments()->convert_date_time($row['appointment_time'], 'H:i:s', 'g:i A');
                }

                sp_upm_get_template_part('/scripts/content', 'consultation-rebooking', [
                    'initial_date' => $initial_date,
                    'initial_time' => $initial_time,
                    'rebooking_message' => $rebooking_message
                ]);
            }
        }
    }

    public function save_new_booking_appointment($fields, $entry) {
        global $wpdb;

        // Get the current user ID
        $user_id = get_current_user_id();

        $doctor_appointments_class = sp_upm_doctors_appointments();
        $user_field_id = $doctor_appointments_class->get_field_id_by_name($fields, "User ID", 'hidden');
        $category_field_id = $doctor_appointments_class->get_field_id_by_name($fields, "Treatment", 'hidden');
        $booking_field_id = $doctor_appointments_class->get_field_id_by_name($fields, "Appointment Date and Time");

        $appointment = $entry['fields'][$booking_field_id];

        if ($category_id = absint($entry['fields'][$category_field_id])) {
            try {
                $product_consultation = get_term_meta($category_id, 'treatment_medication_service', true);

                $row = $doctor_appointments_class->get_consultation_item_by('treatment_id', $category_id);

                $date = $doctor_appointments_class->convert_date_time($appointment['date'], 'd/m/Y', 'Y-m-d');
                $time = $doctor_appointments_class->convert_date_time($appointment['time'], 'g:i A', 'H:i:s');
                $result = $doctor_appointments_class->update_appointment_date_time(absint($row['entry_id']), $date, $time);

                if ($row['status'] == 0) {
                    // empty cart first
                    if( ! WC()->cart->is_empty() ) WC()->cart->empty_cart();

                    // add consultation product
                    WC()->cart->add_to_cart( $product_consultation );

                    // Redirect to the checkout page
                    if (wp_redirect(wc_get_checkout_url())) {
                        exit;
                    }
                } else {
                    $doctor_appointments_class->update(['has_rebook' => true], ['entry_id' => absint($row['entry_id'])]);
                }
            } catch (\Exception $e) {
                
            }
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

function sp_upm_appointment_rebooking() {
    return Sp_Upm_Appointment_Rebooking::get_instance();
}