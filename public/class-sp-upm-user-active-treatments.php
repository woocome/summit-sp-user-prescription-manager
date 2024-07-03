<?php
/**
 * User Active Treatments
 *
 * @link       https://bit.ly/dan-singian-resume
 * @since      1.0.0
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/public
 */

class Sp_Upm_User_Active_Treatments
{
    /**
     * @var self $instance
     */
    private static $instance;
	
    public function __construct() {
    }

    public function init() {
        add_shortcode('active_treatment_top_panel', __CLASS__ . '::top_panel');
        add_shortcode('active_treatment_mens_health', __CLASS__ . '::mens_health');
        add_shortcode('active_treatment_nrt', __CLASS__ . '::nrt');
    }

    // Utility method to get current user prescriptions
    private static function getCurrentUserPrescriptions() {
        $user_id = get_current_user_id();
        return get_field('user_prescriptions', 'user_' . $user_id);
    }

    // Utility method to start capturing HTML output
    private static function startOutputBuffering() {
        ob_start();
    }

    // Utility method to get captured HTML from buffer
    private static function getBufferedOutput() {
        return ob_get_clean();
    }

    // Handle output of treatment wrapper start
    private static function outputTreatmentWrapperStart() {
        echo '<div class="treatments-wrapper">';
    }

    // Close the treatments wrapper
    private static function outputTreatmentWrapperEnd() {
        echo '</div>';
    }

    public static function top_panel() {
        $prescriptions = self::getCurrentUserPrescriptions();

        self::startOutputBuffering();
		
        foreach ($prescriptions as $prescription) {
            $product_category = $prescription['prescribed_categories'];
            $prescribed_medication_id = absint($prescription['prescribed_medication']);
			
			if (! $prescribed_medication_id) continue;

			$product = wc_get_product($prescribed_medication_id);
			
			if ($product->get_status() != 'private') continue;

            sp_upm_get_template_part('content', 'mc-active-treatments-item', $prescription);
        }

        $content = self::getBufferedOutput();

        self::startOutputBuffering();

        self::outputTreatmentWrapperStart();
        echo $content;
        self::outputTreatmentWrapperEnd();

        $result = self::getBufferedOutput();

        return ! empty($content) ? $result : '';
    }

    public static function mens_health() {
        $prescriptions = self::getCurrentUserPrescriptions();

        if (empty($prescriptions) || !is_array($prescriptions)) {
            return '';
        }

        self::startOutputBuffering();
        foreach ($prescriptions as $prescription) {
            $product_id = absint($prescription['prescribed_medication']);
            $product = wc_get_product($product_id);

            if (!$product_id || ($product && $product->get_status() === 'private')) {
                continue; // Skip this prescription
            }

            sp_upm_get_template_part('content', 'mh-active-treatments-item', $prescription);
        }
        $content = self::getBufferedOutput();

        self::startOutputBuffering();
        self::outputTreatmentWrapperStart("Men's Health Treatments");

        echo $content;

        self::outputTreatmentWrapperEnd();
        $result = self::getBufferedOutput();

        return ! empty($content) ? $result : '';
    }

    public static function nrt() {
        $prescriptions = self::getCurrentUserPrescriptions();

        if (empty($prescriptions) || !is_array($prescriptions)) {
            return '';
        }

        self::startOutputBuffering();
        foreach ($prescriptions as $prescription) {
            $category = $prescription['prescribed_categories'];

            if ($category->slug != 'nicotine-vape' ) {
                continue; // Skip this prescription
            }

            sp_upm_get_template_part('content', 'nrt-active-treatment-item', $prescription);
        }

        $item = self::getBufferedOutput();

        self::startOutputBuffering();
        self::outputTreatmentWrapperStart("NRT");

        echo $item;

        self::outputTreatmentWrapperEnd();
        $result = self::getBufferedOutput();

        return ! empty($item) ? $result : "";
    }

    public static function check_product_for_subscription($product_id) {
        $subscriptions = self::user_active_subscriptions();

        foreach ( $subscriptions as $subscription ) {
            // Check that the subscription has the product we're interested in.
            if ( $subscription->has_product( $product_id ) ) {
                return $subscription;
            }
        }

        return false;
    }

    public static function get_subscription_product_name($subscription) {
        foreach ($subscription->get_items() as $key => $item) {
            return $item->get_name();
        }

        return false;
    }

    private static function user_active_subscriptions() {
        $user_id = get_current_user_id();

        $subscriptions = wcs_get_subscriptions([
            'customer_id' => $user_id,
            'subscription_status' => 'active',
        ]);

        return $subscriptions;
    }

    /** Singleton instance */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

function sp_upm_user_active_treatments() {
    return Sp_Upm_User_Active_Treatments::get_instance();
}