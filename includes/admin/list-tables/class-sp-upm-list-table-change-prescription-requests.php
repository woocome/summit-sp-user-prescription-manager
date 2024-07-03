<?php
/**
 * WP Forms entries list of items for consultations
 *
 * @since      1.0.0
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/admin/inc
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Sp_Upm_List_Table_Change_Prescription_Requests extends WP_List_Table {

    public function __construct() {
		parent::__construct( [
			'singular' => __( 'Change Prescription Request', SP_UPM_TEXT_DOMAIN ),
			'plural'   => __( 'Change Prescription Requests', SP_UPM_TEXT_DOMAIN ), //plural name of the listed records
			'ajax'     => true //does this table support ajax?
		]);
    }

    // Get table data
    private static function get_table_data(int $per_page = 5, int $page_number = 1) {
        global $wpdb;

        $orderby = ( isset( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'entry_id';
        $order = ( isset( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'ASC';
	    $search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
        $user_id = null;

        $inner_query = "
            SELECT entry_id, 
                form_id, 
                user_id, 
                status, 
                fields, 
                date, 
                @row_num := IF(@prev_form_id = form_id AND @prev_user_id = user_id, @row_num + 1, 1) AS row_num, 
                @prev_form_id := form_id, 
                @prev_user_id := user_id 
            FROM {$wpdb->prefix}wpforms_entries, 
                (SELECT @row_num := 0, @prev_form_id := NULL, @prev_user_id := NULL) AS vars 
            WHERE status = '' 
            AND user_id != 0
        ";
        
        $query = "SELECT re.form_id, 
                re.user_id, 
                re.entry_id, 
                re.status, 
                re.fields, 
                re.date, 
                tm.term_id AS treatment 
            FROM ($inner_query) AS re
            JOIN {$wpdb->prefix}termmeta AS tm ON re.form_id = tm.meta_value 
            WHERE re.row_num = 1 
            AND tm.meta_key = 'category_wp_form' 
            AND NOT EXISTS (
                SELECT 1 
                FROM {$wpdb->prefix}wpforms_entry_meta AS em 
                WHERE em.entry_id = re.entry_id
                AND em.type = 'approved_prescription'
            )
        ";
        
        if ($search_key) {
            // Prepare search key for $wpdb->prepare()
            $esc_like_search_key = '%' . $wpdb->esc_like($search_key) . '%';

            // Start group condition
            $query .= " AND (`fields` LIKE '$esc_like_search_key'";
        
            $user_id = null;
        
            if (is_email($search_key)) {
                $user = get_user_by("email", $search_key);

                if ($user) {
                    $user_id = $user->ID;
                    $query .= " OR `user_id` = $user_id";
                }
            }
        
            // Close group condition
            $query .= ")";
        }

        $query .= " ORDER BY %i $order LIMIT %d OFFSET %d";
        
        // Prepare and execute the final query
        $query = $wpdb->prepare($query, [$orderby, $per_page, (($page_number - 1) * $per_page)]);
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Check for errors
        if ($wpdb->last_error) {
            return [];
        } else {
            return $results;
        }
    }

    // Get table data
    private static function get_data(int $per_page = 5, int $page_number = 1) {
        global $wpdb;

        $orderby = ( isset( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'entry_id';
        $order = ( isset( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'ASC';
        $search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
        $user_id = null;

        $query = "SELECT * FROM %i";

        $query .= " ORDER BY %i $order LIMIT %d OFFSET %d";

        // Prepare and execute the final query
        $query = $wpdb->prepare($query, [$orderby, $per_page, (($page_number - 1) * $per_page)]);
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Check for errors
        if ($wpdb->last_error) {
            return [];
        } else {
            return $results;
        }
    }

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
        
        $table_name = "ksb_wpforms_entries";
        $table_entry_meta = "ksb_wpforms_entry_meta";

        $query = $wpdb->prepare("
            SELECT COUNT(*) AS count 
            FROM (
                SELECT *,
                    @row_num := IF(@prev_form_id = form_id AND @prev_user_id = user_id, @row_num + 1, 1) AS row_num,
                    @prev_form_id := form_id,
                    @prev_user_id := user_id
                FROM (
                    SELECT re.entry_id, re.form_id, re.user_id
                    FROM $table_name AS re
                    WHERE re.status = ''
                    AND re.user_id != 0
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM $table_entry_meta AS em 
                        WHERE em.entry_id = re.entry_id 
                        AND em.type = %s
                    )
                    ORDER BY form_id, user_id, entry_id DESC
                ) AS ranked
                CROSS JOIN (SELECT @row_num := 0, @prev_form_id := NULL, @prev_user_id := NULL) AS vars
            ) AS subquery
            WHERE subquery.row_num = 1;
        ", 'approved_prescription');

		return $wpdb->get_var($query);
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param mixed $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		$available_points = "";

        switch ( $column_name ) {
            case 'treatment':
                $treatment = get_term( $item['treatment'], 'product_cat' );
                return "<strong>$treatment->name</strong>";
            case 'appointment':
                return $this->get_status_label( $item['status'] );
            case 'status':
                return $this->get_status_label( $item['status'] );
            case 'date':
                return $item['date'];
            case 'prescription':
                return self::treatment_products_field($item['treatment'], $item['entry_id']);
            case 'end_date':
                return self::active_until_date_field($item['entry_id']);
            default:
                return $item[$column_name]; //Show the whole array for troubleshooting purposes
        }
	}

    public function get_status_label($status) {
        return $status == 0 ? 'awaiting payment' : ($status == 1 ? 'paid' : 'approved');
    }

    public function column_customer($item) {
        $user = get_user_by("id", $item['user_id'] );
        $display_name = "";

        $actions = array(
            'view'    => sprintf('<a href="?page=%s&view=%s&entry_id=%s" target="_blank">' . __('View Entry', SP_UPM_TEXT_DOMAIN) . '</a>', 'wpforms-entries', 'details', $item['entry_id']),
            'edit'    => sprintf('<a href="?page=%s&user_id=%s">' . __('Edit Pending Presriptions', SP_UPM_TEXT_DOMAIN) . '</a>', 'user-pending-prescriptions', $item['user_id']),
        );
        
        if ($user) {
            $display_name = get_user_meta( $item['user_id'],'first_name', true ) . ' ' . get_user_meta( $item['user_id'],'last_name', true );
            $display_name .= '</br>' . $user->user_email;
        }

        return sprintf('%1$s %2$s', $display_name, $this->row_actions($actions));
    }

    public function column_actions($item) {
        ob_start();

        sp_upm_get_template_part('content', 'pending-prescriptions-action', ['item' => $item]);

        $html = ob_get_clean();

        return $html;
    }

    public function single_row( $item ) {
        echo "<tr class='prescription pending-prescription-{$item['entry_id']}' data-id='{$item['entry_id']}' data-form-id='{$item['form_id']}' data-user-id='{$item['user_id']}' data-treatment-id='{$item['treatment']}'>";
            $this->single_row_columns( $item );
        echo '</tr>';
    }

	private static function treatment_products_field($treatment_id, $entry_id) {
        sp_upm_get_template_part('content', 'treatment-products', ['treatment_id' => $treatment_id, 'entry_id' => $entry_id]);
    }

	private static function active_until_date_field($entry_id) {
        sp_upm_get_template_part('content', 'date-field', ['entry_id' => $entry_id]);
    }

    public function get_columns() {
        $columns = array(
            'customer'      => __('Customer', SP_UPM_TEXT_DOMAIN),
            'treatment'     => __('Treatment', SP_UPM_TEXT_DOMAIN),
            'appointment'   => __('Booking Date', SP_UPM_TEXT_DOMAIN),
            'status'        => __('Status', SP_UPM_TEXT_DOMAIN),
            'prescription'  => __('Prescribe Product', SP_UPM_TEXT_DOMAIN),
            'end_date'      => __('Active Until Date', SP_UPM_TEXT_DOMAIN),
            'date'          => __('Date Submitted', SP_UPM_TEXT_DOMAIN),
            'actions'       => __('&nbsp', SP_UPM_TEXT_DOMAIN)
        );

        return $columns;
    }

    public function prepare_items() {
		$per_page     = $this->get_items_per_page( 'pending_prescriptions_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items = self::record_count() - 3; // TODO: Remove temporary

        $columns = $this->get_columns();
        $hidden = array();

        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->set_pagination_args(array(
            'total_items' => $total_items, // total number of items
            'per_page'    => $per_page, // items to show on a page
            'total_pages' => ceil( $total_items / $per_page ) // use ceil to round up
        ));

        $this->items = self::get_data($per_page, $current_page);;
    }

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'treatment'  => array('treatment', true),
            'date'   => array('date', true)
        );

        return $sortable_columns;
    }

    public function usort_reorder($a, $b)
    {
        // If no sort, default to user_login
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'entry_id';

        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);

        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }
}

function sp_upm_change_prescription_requests() {
    return new Sp_Upm_List_Table_Change_Prescription_Requests();
}