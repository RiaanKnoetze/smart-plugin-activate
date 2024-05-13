<?php
/**
 * Plugin Name: Plugin Links
 * Plugin URI:  https://github.com/RiaanKnoetze/plugin-links
 * Description: Quickly toggle plugin activation status from a new admin toolbar menu.
 * Version:     1.0
 * Author:      Riaan Knoetze
 * Author URI:  https://www.woocommerce.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: plugin-links
 *
 * @package PluginLinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'PLUGIN_LINKS_PATH', plugin_dir_path( __FILE__ ) );
define( 'PLUGIN_LINKS_URL', plugin_dir_url( __FILE__ ) );

// Include the necessary class files.
require_once PLUGIN_LINKS_PATH . 'includes/class-pluginlinks.php';
require_once PLUGIN_LINKS_PATH . 'includes/class-pluginlinks-plugin.php';

// Initialize the plugin.
new PluginLinks();
