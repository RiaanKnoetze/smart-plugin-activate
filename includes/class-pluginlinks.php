<?php
/**
 * Main PluginLinks Class.
 *
 * @package PluginLinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class PluginLinks.
 */
class PluginLinks {
	/**
	 * The current URL.
	 *
	 * @var string
	 */
	private $current_url;

	/**
	 * PluginLinks constructor.
	 */
	public function __construct() {
		// Hook the initialization process to 'plugins_loaded' action.
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		add_action( 'admin_bar_menu', array( $this, 'setup_toolbar' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'wp_redirect', array( $this, 'redirect' ), 1 );
		add_action( 'admin_page_access_denied', array( $this, 'redirect_disabled_screen' ) );
		add_action( 'load-plugins.php', array( $this, 'flush_plugins_cache' ) );
		add_filter( 'pluginlinks_force_plugins_refresh', array( $this, 'plugins_changed' ) );
		add_filter( 'pluginlinks_force_plugins_refresh', array( $this, 'active_plugins_changed' ) );
	}

	/**
	 * Set up the toolbar with plugin links.
	 *
	 * @param WP_Admin_Bar $toolbar Toolbar object.
	 */
	public function setup_toolbar( $toolbar ) {
		$plugins = $this->get_plugins();

		if ( empty( $plugins ) ) {
			return;
		}

		$this->add_top_level_node( $toolbar, count( $plugins ) );
		$this->add_group_to_node( $toolbar );

		foreach ( $plugins as $plugin ) {
			if ( ! $plugin->is_network_related() ) {
				$this->add_plugin_to_group( $toolbar, $plugin );
			}
		}
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'plugin-links', PLUGIN_LINKS_URL . 'assets/js/plugin-links.js', array(), '1.0.0', true );
		wp_enqueue_style( 'plugin-links', PLUGIN_LINKS_URL . 'assets/css/plugin-links.css', array(), '1.0.0' );
	}

	/**
	 * Redirect to the previous URL after plugin links.
	 *
	 * @param string $location The URL to redirect to.
	 * @return string
	 */
	public function redirect( $location ) {
		if (
			false !== strpos( $location, 'plugins.php' ) &&
			isset( $_REQUEST['pluginlinks_redirect_to'], $_REQUEST['_wpnonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'pluginlinks_action' ) &&
			false === strpos( $location, 'error=true' )
		) {
			$redirect = sanitize_text_field( wp_unslash( $_REQUEST['pluginlinks_redirect_to'] ) );
			$redirect = wp_sanitize_redirect( $redirect );
			$location = wp_validate_redirect( $redirect, $location );

			if ( isset( $_REQUEST['action'] ) && 'deactivate' === $_REQUEST['action'] ) {
				$location = add_query_arg( 'pluginlinks_revive', wp_create_nonce( 'revive' ), $location );
			}
		}

		return $location;
	}

	/**
	 * Redirect to the plugins page if the admin page is disabled.
	 */
	public function redirect_disabled_screen() {
		if ( isset( $_GET['pluginlinks_revive'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['pluginlinks_revive'] ) ), 'revive' ) ) {
			wp_safe_redirect( self_admin_url( 'plugins.php' ) );
			exit;
		}
	}

	/**
	 * Flush the cached plugins.
	 */
	public function flush_plugins_cache() {
		delete_transient( 'pluginlinks_plugins' );
	}

	/**
	 * Get the list of plugins.
	 *
	 * @return array
	 */
	protected function get_plugins() {
		$plugins = get_transient( 'pluginlinks_plugins' );

		if ( ! $plugins || ! ( reset( $plugins ) instanceof PluginLinks_Plugin ) || apply_filters( 'pluginlinks_force_plugins_refresh', false ) ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = array();
			foreach ( get_plugins() as $plugin_file => $plugin_data ) {
				$plugin                  = new PluginLinks_Plugin( $plugin_file, $plugin_data );
				$plugins[ $plugin_file ] = $plugin;
			}

			set_transient( 'pluginlinks_plugins', $plugins, DAY_IN_SECONDS );
		}

		return $plugins;
	}

	/**
	 * Check if plugins have changed.
	 *
	 * @param bool $refresh Whether to refresh.
	 * @return bool
	 */
	public function plugins_changed( $refresh ) {
		$data = scandir( WP_PLUGIN_DIR );
		return $this->has_changed( 'plugins', $data ) ? true : $refresh;
	}

	/**
	 * Check if active plugins have changed.
	 *
	 * @param bool $refresh Whether to refresh.
	 * @return bool
	 */
	public function active_plugins_changed( $refresh ) {
		$data = (array) get_option( 'active_plugins', array() );
		sort( $data );
		return $this->has_changed( 'active_plugins', $data ) ? true : $refresh;
	}

	/**
	 * Check if data has changed.
	 *
	 * @param string $key  The data key.
	 * @param mixed  $data The data.
	 * @return bool
	 */
	protected function has_changed( $key, $data ) {
		$option_name  = sprintf( 'pluginlinks_hash-%s', sanitize_key( $key ) );
		$hash         = md5( maybe_serialize( $data ) );
		$hash_changed = ( get_option( $option_name, '' ) !== $hash );

		if ( $hash_changed ) {
			update_option( $option_name, $hash );
		}

		return $hash_changed;
	}

	/**
	 * Add top level node to the toolbar.
	 *
	 * @param WP_Admin_Bar $toolbar      Toolbar object.
	 * @param int          $plugin_count Number of plugins.
	 */
	protected function add_top_level_node( $toolbar, $plugin_count ) {
		$node_args = array(
			'id'    => 'plugin-links',
			'title' => sprintf( '<span class="ab-icon"></span> <span class="ab-label">%s</span>', __( 'Plugins', 'plugin-links' ) ),
			'href'  => self_admin_url( 'plugins.php' ),
			'meta'  => array(
				'class' => ( $plugin_count > 20 ) ? 'has-many' : '',
			),
		);
		$toolbar->add_node( $node_args );
	}

	/**
	 * Add group to the toolbar node.
	 *
	 * @param WP_Admin_Bar $toolbar Toolbar object.
	 */
	protected function add_group_to_node( $toolbar ) {
		$node_args = array(
			'id'     => 'plugin-links-group',
			'group'  => true,
			'parent' => 'plugin-links',
		);
		$toolbar->add_node( $node_args );
	}

	/**
	 * Add plugin to the toolbar group.
	 *
	 * @param WP_Admin_Bar       $toolbar Toolbar object.
	 * @param PluginLinks_Plugin $plugin Plugin object.
	 */
	protected function add_plugin_to_group( $toolbar, $plugin ) {
		$node_args = array(
			'id'     => 'plugin-links_' . sanitize_title( $plugin->get_name() ),
			'title'  => $plugin->get_name(),
			'href'   => $plugin->get_links_url( $this->get_current_url() ),
			'parent' => 'plugin-links-group',
			'meta'   => array(
				'class' => $plugin->is_active() ? 'is-active' : '',
			),
		);
		$toolbar->add_node( $node_args );
	}

	/**
	 * Get the current URL.
	 *
	 * @return string
	 */
	protected function get_current_url() {
		global $wp;

		if ( empty( $this->current_url ) ) {
			$url               = is_admin() ? add_query_arg( array() ) : home_url( add_query_arg( array(), $wp->request ) );
			$url               = remove_query_arg( array( '_wpnonce', 'redirect_to' ), $url );
			$this->current_url = $url;
		}

		return $this->current_url;
	}
}

// Initialize the PluginLinks class.
new PluginLinks();
