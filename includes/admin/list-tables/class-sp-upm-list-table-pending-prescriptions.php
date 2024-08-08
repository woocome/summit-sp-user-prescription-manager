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

class Sp_Upm_List_Table_Pending_Prescriptions extends WP_List_Table {

    // define $table_data property
    private $table_data;
	
	private $filters = array(
        'all' => 'All',
        'erectile-dysfunction' => 'Erectile Dysfunction',
        'premature-ejaculation' => 'Premature Ejaculation',
        'hairloss' => 'Hairloss',
        'nrt' => 'NRT',
    );

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Pending Prescription', SP_UPM_TEXT_DOMAIN ),
            'plural'   => __( 'Pending Prescriptions', SP_UPM_TEXT_DOMAIN ), //plural name of the listed records
            'ajax'     => true //does this table support ajax?
        ]);
    }

	public function extra_tablenav($which) {
		if ($which == 'top') {
            $current = isset($_REQUEST['treatment']) ? $_REQUEST['treatment'] : 'all';
            $views = array();

            foreach ($this->filters as $key => $value) {
                $class = ($current == $key) ? ' class="current"' : '';
                $views[$key] = sprintf('<a href="?page=%s&treatment=%s"%s>%s</a>', $_REQUEST['page'], $key, $class, $value);
            }

            echo '<ul class="subsubsub">';

            $count = count($views);
            $index = 0;
			
            foreach ($views as $view) {
                echo '<li class="all">' . $view;
				
				$index++;

                if ($index < $count) {
                    echo ' | ';
                }

                echo '</li>';
            }

            echo '</ul>';
        }
    }
	
	private static function filter_by_treatment() {
        $treatment = ( isset( $_GET['treatment'] ) ) ? esc_sql( $_GET['treatment'] ) : 'all';
		$query = "";

		if ($treatment != 'all') {
			$product_category = get_term_by('slug', $treatment, 'product_cat');

			if (! empty($product_category)) $query .= " WHERE (final_treatment_cat_id = {$product_category->term_id} OR treatment_id = {$product_category->term_id})";
		}

		return $query;
	}

    private static function get_data(int $per_page = 20, int $page_number = 1) {
        global $wpdb;

        $table = $wpdb->prefix .'doctors_appointments';
        $orderby = ( isset( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'created_at';
        $order = ( isset( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'DESC';
        $search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

        $filter_query = self::build_search_query($search_key, self::filter_by_treatment());
        $query = "SELECT * FROM %i" . $filter_query;

        if ($orderby === 'appointment_date') {
            $query .= " ORDER BY %i $order, appointment_time  $order";
        } else if($orderby === 'status') {
            $query .= " ORDER BY %i $order, appointment_date $order, appointment_time  $order";
        } else {
            $query .= " ORDER BY %i $order";
        }

        $query .= " LIMIT %d OFFSET %d";

        // Prepare and execute the final query
        $query = $wpdb->prepare($query, [$table, $orderby , $per_page, (($page_number - 1) * $per_page)]);
        $results = $wpdb->get_results($query, ARRAY_A);

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

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count() {
        global $wpdb;

        $table = $wpdb->prefix . 'doctors_appointments';

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
        $available_points = "";

        switch ( $column_name ) {
            case 'treatment':
                $treatment = get_term( $item['treatment_id'], 'product_cat' );
                return "<strong>$treatment->name</strong>";
            case 'appointment':
                $date = new DateTime($item['appointment_date']);
                $time = new DateTime($item['appointment_time']);

                return  $date->format('F d, Y') . ' - ' . "<strong>{$time->format('h:i A')}</strong>";
            case 'prescriber':
                return $item['status'] == 2 ? get_the_title(absint($item['doctor_id'])) : "";
            case 'final_treatment':
                $treatment = get_term( $item['final_treatment_cat_id'], 'product_cat' );
                return !is_wp_error($treatment) ? "<strong>$treatment->name</strong>" : "-";
            case 'product':
                return $this->get_product_name($item);
            case 'status':
                $label = $this->get_status_label( $item['status'] );
                $pre_screening_form = get_field('category_wp_form', 'product_cat_' . $item['treatment_id']);
                $button = '';

                if ($item['status'] == sp_upm_doctors_appointments()::PAID && (! empty($pre_screening_form) || $pre_screening_form)) {
                    $button = self::display_send_pre_sreening_form($item);
                }

                return $label . $button;
            case 'date':
                return $item['created_at'];
            case 'marketing_code':
                return $item['marketing_code'];
            default:
                return $item[$column_name]; //Show the whole array for troubleshooting purposes
        }
    }

    public static function get_date_time_text($item) {
        $date = new DateTime($item['appointment_date']);
        $time = new DateTime($item['appointment_time']);

        return $date->format('F d, Y') . ' - ' . $time->format('h:i A');
    }

    public static function display_send_pre_sreening_form($item) {
        ob_start();

        sp_upm_get_template_part('content', 'send-pre-screening-form', [
            'treatment_id' => $item['treatment_id'],
            'button_label' => 'Send Form Link',
            'user_id' => $item['user_id']
        ]);

        return ob_get_clean();
    }

    /**
     * Customer Name,
     * and Actions
     */
    public function column_customer($item) {
        $user = get_user_by("id", $item['user_id'] );
        $customer = "";

        $view_entry = admin_url('admin.php') . sprintf("?page=%s&view=%s&entry_id=%s", 'wpforms-entries', 'details', $item['entry_id']);
        $print_pdf = admin_url('admin.php') . sprintf("?page=%s&view=%s&entry_id=%s", 'wpforms-entries', 'print', $item['entry_id']);

        $actions = array(
            'print_pdf'    => sprintf('<a href="%s" target="_blank">%s</a>', $print_pdf, __('Print PDF', SP_UPM_TEXT_DOMAIN)),
            'view'    => sprintf('<a href="%s" target="_blank">%s</a>', $view_entry, __('View Entry', SP_UPM_TEXT_DOMAIN)),
            // 'edit'    => sprintf('<a href="?page=%s&user_id=%s">' . __('Edit Pending Presriptions', SP_UPM_TEXT_DOMAIN) . '</a>', 'user-pending-prescriptions', $item['user_id']),
        );

        if (class_exists('user_switching')) {
            global $user_switching;
            $link = $user_switching::maybe_switch_url( $user );

            $actions['switch_to_user'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url( $link ),
                esc_html__( 'Switch&nbsp;To', 'user-switching' )
            );
        }

        if ($user) {
            $display_name = get_user_meta( $item['user_id'],'first_name', true ) . ' ' . get_user_meta( $item['user_id'],'last_name', true );
            $display_name .= '</br>' . "<span>$user->user_email</span>";
            $edit_user = admin_url( 'user-edit.php?user_id=' . $user->ID );
            $customer = sprintf('<strong><a href="%s">%s</a></strong>', $edit_user, $display_name);
        }

        return sprintf('%1$s %2$s', $customer, $this->row_actions($actions));
    }

    public function get_status_label($status) {
        ob_start();

        sp_upm_get_template_part('content', 'status', ['status' => $status]);

        return ob_get_clean();
    }

    public function get_product_name($item) {
        $meta = json_decode($item['meta']);
        if (! $meta || ! property_exists($meta, 'product_id')) return "-";

        $product = wc_get_product($meta->product_id);

        return $product ? $product->get_title() : "-";
    }

    public function column_actions($item) {
        ob_start();

        sp_upm_get_template_part('content', 'pending-prescriptions-action', ['item' => $item]);

        $html = ob_get_clean();

        return $html;
    }

    public function single_row( $item ) {
        echo "<tr class='prescription pending-prescription-{$item['entry_id']}' data-id='{$item['entry_id']}' data-form-id='{$item['form_id']}' data-user-id='{$item['user_id']}' data-treatment-id='{$item['treatment_id']}'>";
            $this->single_row_columns( $item );
        echo '</tr>';
    }

    private static function treatment_products_field($treatment_id, $entry_id) {
        ob_start();

        sp_upm_get_template_part('content', 'treatment-products', ['treatment_id' => $treatment_id, 'entry_id' => $entry_id]);

        return ob_get_clean();
    }

    public static function approve_prescription_form() {
        ob_start();

        sp_upm_get_template_part('/forms/approve', 'prescription');

        return ob_get_clean();
    }

    public static function edit_consultation_form() {
        ob_start();

        sp_upm_get_template_part('/forms/edit', 'prescription');

        return ob_get_clean();
    }

    private static function active_until_date_field($entry_id) {
        sp_upm_get_template_part('content', 'date-field', ['entry_id' => $entry_id]);
    }

    public function get_columns() {
        $columns = array(
            'customer'          => __('Customer', SP_UPM_TEXT_DOMAIN),
            'date'              => __('Date Submitted', SP_UPM_TEXT_DOMAIN),
            'treatment'         => __('Initial Treatment', SP_UPM_TEXT_DOMAIN),
            'appointment'       => __('Booking Date', SP_UPM_TEXT_DOMAIN),
            'status'            => __('Status', SP_UPM_TEXT_DOMAIN),
            'prescriber'        => __('Prescriber', SP_UPM_TEXT_DOMAIN),
            'final_treatment'   => __('Final Treatment', SP_UPM_TEXT_DOMAIN),
            'product'           => __('Product', SP_UPM_TEXT_DOMAIN),
            'marketing_code'    => __('Marketing', SP_UPM_TEXT_DOMAIN),
            'actions'           => __('&nbsp', SP_UPM_TEXT_DOMAIN)
        );

        return $columns;
    }

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'treatment'  => array('treatment_id', true),
            'date'   => array('created_at', true),
            'appointment'   => array('appointment_date', true),
            'status'   => array('status', true),
        );

        return $sortable_columns;
    }

    public function prepare_items() {
        $per_page     = $this->get_items_per_page( 'pending_prescriptions_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();

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

function sp_upm_pending_prescriptions() {
    return new Sp_Upm_List_Table_Pending_Prescriptions();
}