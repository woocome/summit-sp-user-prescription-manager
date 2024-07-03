<?php

/**
 * Subscription Repeats
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/admin
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */
class Sp_Upm_Admin_Repeat_Counts extends SP_UPM_DB {
    
    /**
     * @var self $instance
     */
    private static $instance;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct() {
        global $wpdb;

        $this->table_name = $wpdb->prefix . 'treatment_repeat_counts';
    }

    public function init_hooks() {
        add_action('init', [$this, 'create_database_table']);

        add_action( 'woocommerce_product_after_variable_attributes', [$this, 'variation_settings_fields'], 10, 3 );
        add_action( 'woocommerce_save_product_variation', [$this, 'save_variation_settings_fields'], 10, 2 );
        add_filter( 'woocommerce_available_variation', [$this, 'load_variation_settings_fields'] );

        add_action( 'sp_upm_subscription_treatment_ordered', [$this, 'record_treatment'], 10, 3);
    }

    public function variation_settings_fields( $loop, $variation_data, $variation ) {
        woocommerce_wp_text_input(
            array(
                'id'            => "sp_upm_repeat_counts{$loop}",
                'name'          => "sp_upm_repeat_counts[{$loop}]",
                'type'          => 'number',
                'value'         => get_post_meta( $variation->ID, 'sp_upm_repeat_counts', true ),
                'label'         => __( 'Repeat Counts', 'woocommerce' ),
                'desc_tip'      => true,
                'description'   => __( 'Value of per repeats.', 'woocommerce' ),
                'wrapper_class' => 'form-row form-row-full',
            )
        );
    }

    public function save_variation_settings_fields( $variation_id, $loop ) {
        $text_field = $_POST['sp_upm_repeat_counts'][ $loop ];

        if ( ! empty( $text_field ) ) {
            update_post_meta( $variation_id, 'sp_upm_repeat_counts', esc_attr( $text_field ));
        }
    }

    public function load_variation_settings_fields( $variation ) {
        $variation['sp_upm_repeat_counts'] = get_post_meta( $variation[ 'variation_id' ], 'sp_upm_repeat_counts', true );

        return $variation;
    }

    /**
     * Retrieve the list of columns for the database table.
     *
     * @since 1.0.1
     *
     * @return array List of columns.
     */
    public function get_columns() {
        return [
            'product_id' => '%d',
            'treatment_id' => '%d',
            'user_id' => '%d',
            'appointment_id' => '%d',
            'count' => '%d',
            'meta' => '%s',
            'transaction_type' => '%d',
            'created_at' => '%s',
            'updated_at' => '%s',
            'deleted_at' => '%s',
        ];
    }

    /**
     * Retrieve column defaults.
     *
     * @since 1.0.1
     *
     * @return array All defined column defaults.
     */
    public function get_column_defaults() {
        return [
            'product_id' => '',
            'treatment_id' => '',
            'user_id' => '',
            'entry_id' => '',
            'transaction_type' => '',
            'appointment_id' => '',
            'count' => '',
            'meta' => '',
            'status' => 0,
        ];
    }

    public function create_database_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Check if table exists
		if ($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) {
			// SQL query to create the table
			$sql = "CREATE TABLE {$this->table_name} (
				id INT AUTO_INCREMENT PRIMARY KEY,
				user_id INT NOT NULL,
				order_id INT NOT NULL,
				treatment_id INT NOT NULL,
				product_id INT NOT NULL,
				count INT NOT NULL,
				appointment_id INT NULL,
				meta LONGTEXT NULL,
				transaction_type INT DEFAULT 1,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				deleted_at TIMESTAMP NULL
			) {$charset_collate};";

			// Execute the SQL query
			dbDelta( $sql );
		}
	}

    /**
     * Record the initial treatment count
     */
    public function record_treatment($consultation, $treatment, $order) {
        $user_id = absint($consultation['user_id']);
        $order_id = $order->get_id();
        $treatment_id = absint($consultation['final_treatment_cat_id']);
        $product_id = absint($consultation['prescribed_medication']);
        $count = absint(get_post_meta($product_id, 'sp_upm_repeat_counts', true));
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

function sp_upm_repeat_counts() {
    return Sp_Upm_Admin_Repeat_Counts::get_instance();
}