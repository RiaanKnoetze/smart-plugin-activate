<?php
/**
 * Smart Plugin Activate
 *
 * @package SmartPluginActivate
 * @since 1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class SmartPluginActivate_Plugin
 *
 * Represents a plugin and provides methods to manage its state.
 *
 * @since 1.1
 */
class SmartPluginActivate_Plugin {
	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * Plugin status (active/inactive).
	 *
	 * @var string
	 */
	protected $status;

	/**
	 * Plugin network status.
	 *
	 * @var string
	 */
	protected $network_status;

	/**
	 * Constructor.
	 *
	 * @param string $file The plugin file path.
	 * @param array  $data Optional. Plugin data. Default empty array.
	 */
	public function __construct( $file, $data = array() ) {
		$this->file = $file;

		if ( empty( $data ) ) {
			$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $file, false, false );
		}

		$this->name = $data['Name'];
		$this->set_status();
		$this->set_network_status();
	}

	/**
	 * Checks if the plugin is active.
	 *
	 * @return bool True if the plugin is active, false otherwise.
	 */
	public function is_active() {
		return 'active' === $this->status;
	}

	/**
	 * Gets the plugin name.
	 *
	 * @return string The plugin name.
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Gets the plugin file path.
	 *
	 * @return string The plugin file path.
	 */
	public function get_file() {
		return $this->file;
	}

	/**
	 * Checks if the plugin is network related.
	 *
	 * @return bool True if the plugin is network related, false otherwise.
	 */
	public function is_network_related() {
		return is_multisite() &&
			( ! is_admin() || ! get_current_screen()->in_admin( 'network' ) ) &&
			! empty( $this->network_status );
	}

	/**
	 * Gets the URL to toggle the plugin's activation status.
	 *
	 * @param string $redirect Optional. The redirect URL after toggling. Default empty string.
	 * @return string The URL to toggle the plugin's activation status.
	 */
	public function get_toggle_url( $redirect = '' ) {
		$action = $this->is_active() ? 'deactivate' : 'activate';

		$query_args = array(
			'action' => $action,
			'plugin' => $this->file,
		);

		if ( ! empty( $redirect ) ) {
			$query_args['smartpluginactivate_redirect_to'] = rawurlencode( $redirect );
		}

		return wp_nonce_url(
			add_query_arg(
				$query_args,
				self_admin_url( 'plugins.php' )
			),
			$action . '-plugin_' . $this->file
		);
	}

	/**
	 * Sets the plugin status.
	 */
	protected function set_status() {
		$this->status = is_plugin_active( $this->file ) ? 'active' : 'inactive';
	}

	/**
	 * Sets the plugin network status.
	 */
	protected function set_network_status() {
		if ( ! is_multisite() ) {
			return;
		}

		if ( is_plugin_active_for_network( $this->file ) ) {
			$this->network_status = 'network-activated';
		} elseif ( is_network_only_plugin( $this->file ) ) {
			$this->network_status = 'network-only';
		}
	}
}
