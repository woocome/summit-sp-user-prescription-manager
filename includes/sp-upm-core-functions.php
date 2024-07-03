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