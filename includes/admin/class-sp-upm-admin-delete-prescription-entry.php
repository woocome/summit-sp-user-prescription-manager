<?php
/**
 * Class for Deleting Prescription Entry, and WP Form E
 *
 * @since      1.0.0
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/admin/includes
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */

class Sp_Upm_Admin_Delete_Prescription_Entry
{
    public function __construct() {
        $this->init_hooks();
    }

    public function init_hooks() {
        add_action( 'wp_ajax_delete_prescription_entry', [ $this, 'handle_delete'] );
    }

    public function handle_delete() {
        if ( isset( $_REQUEST['ajax_nonce'] ) && wp_verify_nonce( $_REQUEST['ajax_nonce'], 'sp_upm_delete_ajax_nonce' ) ) {
            // delete from database
            global $wpdb;

            $entry_id = absint(sanitize_text_field($_REQUEST['entry_id']));

            $doctors_appointment = new Sp_Upm_Admin_Doctors_Appointments();

            $deleted = $wpdb->delete($doctors_appointment->table_name, ['entry_id' => $entry_id]);

            if ($deleted) {
                // delete wpform entry
                wpforms()->get( 'entry' )->delete($entry_id);
            }

            wp_send_json_success([
                'message' => __('Entry successfully deleted!.', SP_UPM_TEXT_DOMAIN),
                'data' => $entry_id
            ]);
        }

        wp_send_json_error([
            'message' => __('Unknown request.', SP_UPM_TEXT_DOMAIN),
        ]);
    }
}