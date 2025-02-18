<?php
/**
 * SummitPharma UPM Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @package    Sp_User_Prescription_Manager
 * @subpackage Sp_User_Prescription_Manager/admin
 * @author     Daniel Singian <singian.daniel@gmail.com>
 */
/**
 * Custom version of get_template_part() for plugin folders.
 *
 * @param string $slug The slug name for the generic template.
 * @param string $name The name of the specialized template.
 * @param array $args Optional. Additional arguments passed to the template.
 */
function sp_upm_get_template_part($slug, $name = null, $args = array()) {
    // Construct path to the template file in your plugin folder.
    $plugin_template = plugin_dir_path(SP_UPM_PLUGIN_FILE) . 'templates/' . $slug . '.php';

    // If a specialized template name is provided, append it to the path.
    if ($name !== null) {
        $plugin_template = plugin_dir_path(SP_UPM_PLUGIN_FILE) . 'templates/' . $slug . '-' . $name . '.php';
    }

    // Check if the template file exists.
    if (file_exists($plugin_template)) {
        // If template file exists, extract the arguments array.
        if (is_array($args) && isset($args)) {
            extract($args);
        }

        // Include the template file.
        include $plugin_template;
    }
}

function check_if_product_is_public($product_id) {
    return has_term( 'public', 'product_cat', $product_id) ;
}

function get_users_by_product_category($category_id) {
    global $wpdb;

    // Get all child categories of the specified category
    $category_ids = get_term_children($category_id, 'product_cat');
    // Add the parent category ID to the array
    $category_ids[] = $category_id;

    // Convert category IDs array to comma-separated string for the SQL query
    $category_ids_string = implode(',', array_map('intval', $category_ids));

    $query = $wpdb->prepare("
        SELECT DISTINCT u.ID, u.user_email, u.display_name
        FROM {$wpdb->users} u
        JOIN {$wpdb->postmeta} pm ON pm.meta_value = u.ID AND pm.meta_key = '_customer_user'
        JOIN {$wpdb->posts} o ON o.ID = pm.post_id
        JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = o.ID
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id
        JOIN {$wpdb->term_relationships} tr ON tr.object_id = oim.meta_value
        JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
        LEFT JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = 'mc_manual_batch_1'
        WHERE o.post_type = 'shop_order'
        AND um.meta_value IS NULL
        AND o.post_status IN ('wc-completed', 'wc-processing')
        AND oim.meta_key = '_product_id'
        AND tt.taxonomy = 'product_cat'
        AND tt.term_id IN ($category_ids_string)
        AND o.post_date >= '2024-06-01 00:00:00'
    ");

    $users = $wpdb->get_results($query);

    return $users;
}