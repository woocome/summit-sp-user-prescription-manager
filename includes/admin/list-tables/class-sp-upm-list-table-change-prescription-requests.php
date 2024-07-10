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
    private static function get_data(int $per_page = 5, int $page_number = 1) {
        global $wpdb;

        $table = sp_upm_change_prescription_request()->get_table_name();
        $orderby = ( isset( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'entry_id';
        $order = ( isset( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'ASC';
        $search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

        $filter_query = self::build_search_query($search_key, self::filter_by_treatment());
        $query = "SELECT user_id, entry_id, current_product_id, requested_product_id, reason, status, created_at FROM %i" . $filter_query;
        $query .= " ORDER BY %i $order LIMIT %d OFFSET %d";

        // Prepare and execute the final query
        $query = $wpdb->prepare($query, [$table, $orderby, $per_page, (($page_number - 1) * $per_page)]);
        $results = $wpdb->get_results($query, ARRAY_A);

        var_dump($query);
        
        // Check for errors
        if ($wpdb->last_error) {
            return [];
        } else {
            return $results;
        }
    }

    private static function build_search_query($search_key, $existing_condition) {
        global $wpdb;

        $query = $existing_condition;

        if ($search_key) {
            // Prepare search key for $wpdb->prepare()
            $esc_like_search_key = '%' . $wpdb->esc_like($search_key) . '%';
            $conjunction = ($query == "") ? " WHERE " : " AND ";

            // Start group condition
            $query .= " {$conjunction} (`meta` LIKE '$esc_like_search_key'";
            $query .= " OR JSON_EXTRACT(meta, '$.customer_name') LIKE '$esc_like_search_key'";

            $user_id = null;

            if (is_email($search_key)) {
                $user = get_user_by("email", $search_key);
                $query .= " OR JSON_EXTRACT(meta, '$.customer_email') LIKE '$esc_like_search_key'";

                if ($user) {
                    $user_id = $user->ID;
                    $query .= " OR `user_id` = $user_id";
                }
            }

            // Close group condition
            $query .= ")";
        }

        return $query;
    }

    private static function filter_by_treatment() {
        $treatment = ( isset( $_GET['treatment'] ) ) ? esc_sql( $_GET['treatment'] ) : 'all';
        $query = "";

        if ($treatment != 'all') {
            $product_category = get_term_by('slug', $treatment, 'product_cat');

            if (! empty($product_category)) $query .= " WHERE product_cat_id = {$product_category->term_id}";
        }

        return $query;
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count() {
        global $wpdb;

        $table = sp_upm_change_prescription_request()->get_table_name();

        $search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

        $filter_query = self::build_search_query($search_key, self::filter_by_treatment());
        $query = "SELECT COUNT(id) AS count FROM %i" . $filter_query;

        $query = $wpdb->prepare($query, $table);

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
        switch ( $column_name ) {
            case 'current_product_id':
            case 'requested_product_id':
                $product = wc_get_product(absint($item[$column_name]));
                return $product->get_name();
            case 'status':
                return sp_upm_change_prescription_request()->get_status_label($item[$column_name]);
            case 'created_at':
                return $item['created_at'];
            default:
                return $item[$column_name]; //Show the whole array for troubleshooting purposes
        }
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
        if (absint($item['status'])) return;

        ob_start();

        sp_upm_get_template_part('content', 'change-prescription-actions', $item);

        return ob_get_clean();
    }

    public function get_columns() {
        $columns = array(
            'customer'      => __('Customer', SP_UPM_TEXT_DOMAIN),
            'current_product_id'     => __('Current Prescription', SP_UPM_TEXT_DOMAIN),
            'requested_product_id'     => __('Requested Medication', SP_UPM_TEXT_DOMAIN),
            'reason'     => __('Reason', SP_UPM_TEXT_DOMAIN),
            'status'        => __('Status', SP_UPM_TEXT_DOMAIN),
            'created_at'          => __('Date Submitted', SP_UPM_TEXT_DOMAIN),
            'actions'       => __('&nbsp', SP_UPM_TEXT_DOMAIN)
        );

        return $columns;
    }

    public function prepare_items() {
		$per_page     = $this->get_items_per_page( 'requests_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items = self::record_count(); // TODO: Remove temporary

        $columns = $this->get_columns();
        $hidden = array();

        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->set_pagination_args(array(
            'total_items' => $total_items, // total number of items
            'per_page'    => $per_page, // items to show on a page
            'total_pages' => ceil( $total_items / $per_page ) // use ceil to round up
        ));

        $this->items = self::get_data($per_page, $current_page);
    }

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'treatment'  => array('treatment', true),
            'created_at'   => array('created_at', true)
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