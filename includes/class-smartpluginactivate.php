<?php
/**
 * Smart Plugin Activate
 *
 * @package SmartPluginActivate
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class SmartPluginActivate
 *
 * Manages the smart plugin activation functionality.
 *
 * @since 1.0
 */
class SmartPluginActivate {

	/**
	 * Initializes the plugin by setting up hooks.
	 *
	 * @since 1.0
	 */
	public function load_plugin() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		add_action( 'admin_bar_menu', array( $this, 'setup_toolbar' ), 100 );
		add_action( 'admin_bar_init', array( $this, 'enqueue_assets' ) );
		add_filter( 'wp_redirect', array( $this, 'redirect' ), 1 );
		add_action( 'admin_page_access_denied', array( $this, 'redirect_disabled_screen' ) );
		add_action( 'load-plugins.php', array( $this, 'flush_plugins_cache' ) );

		add_filter( 'smartpluginactivate_force_plugins_refresh', array( $this, 'plugins_changed' ) );
		add_filter( 'smartpluginactivate_force_plugins_refresh', array( $this, 'active_plugins_changed' ) );

		add_action( 'admin_menu', array( $this, 'add_active_plugins_submenu' ) );
		add_action( 'admin_menu', array( $this, 'modify_plugins_menu_link' ), 20 );
	}

	/**
	 * Sets up the admin toolbar with plugin-related nodes.
	 *
	 * @param WP_Admin_Bar $toolbar The WP_Admin_Bar instance.
	 *
	 * @since 1.0
	 */
	public function setup_toolbar( $toolbar ) {
		$plugins = $this->get_plugins();

		if ( empty( $plugins ) ) {
			return;
		}

		new SmartPluginActivate_Toolbar( $toolbar, $plugins );
	}

	/**
	 * Enqueues the necessary scripts and styles.
	 *
	 * @since 1.0
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'smart-plugin-activate', PLUGINTOGGLE_URL . 'assets/js/smart-plugin-activate.js', array(), '1.0', true );
		wp_enqueue_style( 'smart-plugin-activate', PLUGINTOGGLE_URL . 'assets/css/smart-plugin-activate.css', array(), '1.0' );
	}

	/**
	 * Redirects after a plugin activation/deactivation.
	 *
	 * @param string $location The redirect location URL.
	 * @return string The filtered redirect location URL.
	 *
	 * @since 1.0
	 */
	public function redirect( $location ) {
		if (
			false !== strpos( $location, 'plugins.php' ) &&
			! empty( $_REQUEST['smartpluginactivate_redirect_to'] ) &&
			false === strpos( $location, 'error=true' )
		) {
			$redirect = rawurldecode( sanitize_text_field( wp_unslash( $_REQUEST['smartpluginactivate_redirect_to'] ) ) );
			$redirect = wp_sanitize_redirect( $redirect );
			$location = wp_validate_redirect( $redirect, $location );

			if ( isset( $_REQUEST['action'] ) && 'deactivate' === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ) {
				$location = add_query_arg( 'smartpluginactivate_revive', wp_create_nonce( 'revive' ), $location );
			}
		}

		return $location;
	}

	/**
	 * Redirects to the plugins page if access is denied.
	 *
	 * @since 1.0
	 */
	public function redirect_disabled_screen() {
		if (
			empty( $_GET['smartpluginactivate_revive'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['smartpluginactivate_revive'] ) ), 'revive' )
		) {
			return;
		}

		wp_safe_redirect( self_admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * Flushes the plugins cache.
	 *
	 * @since 1.0
	 */
	public function flush_plugins_cache() {
		delete_transient( 'smartpluginactivate_plugins' );
	}

	/**
	 * Retrieves the plugins.
	 *
	 * @return array The array of plugins.
	 *
	 * @since 1.0
	 */
	protected function get_plugins() {
		$plugins = get_transient( 'smartpluginactivate_plugins' );

		if (
			! $plugins ||
			! ( reset( $plugins ) instanceof SmartPluginActivate_Plugin ) ||
			apply_filters( 'smartpluginactivate_force_plugins_refresh', false )
		) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = array();
			foreach ( get_plugins() as $plugin_file => $plugin_data ) {
				$plugin                  = new SmartPluginActivate_Plugin( $plugin_file, $plugin_data );
				$plugins[ $plugin_file ] = $plugin;
			}

			set_transient( 'smartpluginactivate_plugins', $plugins, DAY_IN_SECONDS );
		}

		return $plugins;
	}

	/**
	 * Checks if plugins have changed.
	 *
	 * @param bool $refresh Whether to refresh the plugins.
	 * @return bool Whether the plugins have changed.
	 *
	 * @since 1.0
	 */
	public function plugins_changed( $refresh ) {
		$data = scandir( WP_PLUGIN_DIR );
		return $this->has_changed( 'plugins', $data ) ? true : $refresh;
	}

	/**
	 * Checks if active plugins have changed.
	 *
	 * @param bool $refresh Whether to refresh the active plugins.
	 * @return bool Whether the active plugins have changed.
	 *
	 * @since 1.0
	 */
	public function active_plugins_changed( $refresh ) {
		$data = (array) get_option( 'active_plugins', array() );
		sort( $data );
		return $this->has_changed( 'active_plugins', $data ) ? true : $refresh;
	}

	/**
	 * Determines if a hash has changed.
	 *
	 * @param string $key The key to identify the hash.
	 * @param array  $data The data to hash.
	 * @return bool Whether the hash has changed.
	 *
	 * @since 1.0
	 */
	protected function has_changed( $key, $data ) {
		$option_name  = sprintf( 'smartpluginactivate_hash-%s', sanitize_key( $key ) );
		$hash         = md5( maybe_serialize( $data ) );
		$hash_changed = ( get_option( $option_name, '' ) !== $hash );

		if ( $hash_changed ) {
			update_option( $option_name, $hash );
		}

		return $hash_changed;
	}

	/**
	 * Adds an "Active Plugins" submenu under the Plugins menu.
	 *
	 * @since 1.1
	 */
	public function add_active_plugins_submenu() {
		add_submenu_page(
			'plugins.php',
			__( 'Active Plugins', 'smart-plugin-activate' ),
			__( 'Active Plugins', 'smart-plugin-activate' ),
			'manage_options',
			'active-plugins',
			'__return_true',
			0
		);
	}

	/**
	 * Modifies the Plugins menu link to point to the active plugins.
	 *
	 * @since 1.1
	 */
	public function modify_plugins_menu_link() {
		global $submenu;

		if ( isset( $submenu['plugins.php'] ) && is_array( $submenu['plugins.php'] ) ) {
			$submenu['plugins.php'][0][2] = 'plugins.php?plugin_status=active';
		}
	}
}
