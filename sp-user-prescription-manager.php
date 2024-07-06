<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://bit.ly/dan-singian-resume
 * @since             1.0.0
 * @package           Sp_User_Prescription_Manager
 *
 * @wordpress-plugin
 * Plugin Name:       SP User Prescription Management
 * Plugin URI:        https://summitpharma.com.au
 * Description:       Manage User Prescriptions Approval
 * Version:           1.0.0
 * Author:            Daniel Singian
 * Author URI:        https://bit.ly/dan-singian-resume/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sp-user-prescription-manager
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SP_USER_PRESCRIPTION_MANAGER_VERSION', '1.1.0' );
define( 'SP_USER_PRESCRIPTION_MANAGER_NAME', 'sp-user-prescription-manager' );

if ( ! defined( 'SP_UPM_PLUGIN_FILE' ) ) {
	define( 'SP_UPM_PLUGIN_FILE', __FILE__ );
}

define( 'SP_UPM_TEXT_DOMAIN', 'sp-user-prescription-manager');

/**
 * The code that runs during plugin activation.
 */
function activate_sp_user_prescription_manager() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sp-upm-activator.php';
	Sp_User_Prescription_Manager_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_sp_user_prescription_manager() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sp-upm-deactivator.php';
	Sp_User_Prescription_Manager_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_sp_user_prescription_manager' );
register_deactivation_hook( __FILE__, 'deactivate_sp_user_prescription_manager' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-sp-upm-manager.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function sp_upm() {
	return new Sp_User_Prescription_Manager();
}

sp_upm()->run();