<?php
/**
 * Smart Plugin Activate
 *
 * @package SmartPluginActivate
 * @license GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Smart Plugin Activate
 * Plugin URI:  https://github.com/RiaanKnoetze/smart-plugin-activate
 * Description: Quickly toggle plugin activation status from the toolbar.
 * Version:     1.1
 * Author:      Riaan Knoetze
 * Author URI:  https://woocommerce.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-plugin-activate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'PLUGINTOGGLE_URL' ) ) {
	define( 'PLUGINTOGGLE_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'PLUGINTOGGLE_DIR' ) ) {
	define( 'PLUGINTOGGLE_DIR', plugin_dir_path( __FILE__ ) );
}

require_once PLUGINTOGGLE_DIR . 'includes/class-smartpluginactivate.php';
require_once PLUGINTOGGLE_DIR . 'includes/class-smartpluginactivate-plugin.php';
require_once PLUGINTOGGLE_DIR . 'includes/class-smartpluginactivate-toolbar.php';

$smartpluginactivate = new SmartPluginActivate();

add_action( 'init', array( $smartpluginactivate, 'load_plugin' ) );
