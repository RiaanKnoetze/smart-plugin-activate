<?php
/**
 * PluginLinks_Plugin Class.
 *
 * This class represents an individual plugin and provides methods to
 * retrieve its properties and status, as well as generate activation
 * and deactivation URLs.
 *
 * @package PluginLinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class PluginLinks_Plugin
 *
 * @package PluginLinks
 */
class PluginLinks_Plugin {
	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Plugin file.
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * Plugin status.
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
	 * PluginLinks_Plugin constructor.
	 *
	 * @param string $file Plugin file.
	 * @param array  $data Plugin data.
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
	 * Check if the plugin is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return 'active' === $this->status;
	}

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the plugin file.
	 *
	 * @return string
	 */
	public function get_file() {
		return $this->file;
	}

	/**
	 * Check if the plugin is network related.
	 *
	 * @return bool
	 */
	public function is_network_related() {
		return is_multisite() &&
			( ! is_admin() || ! get_current_screen()->in_admin( 'network' ) ) &&
			! empty( $this->network_status );
	}

	/**
	 * Get the links URL for the plugin.
	 *
	 * @param string $redirect Redirect URL.
	 * @return string
	 */
	public function get_links_url( $redirect = '' ) {
		$action = $this->is_active() ? 'deactivate' : 'activate';

		$query_args = array(
			'action' => $action,
			'plugin' => $this->file,
		);

		if ( ! empty( $redirect ) ) {
			$query_args['pluginlinks_redirect_to'] = rawurlencode( $redirect );
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
	 * Set the plugin status.
	 */
	protected function set_status() {
		$this->status = is_plugin_active( $this->file ) ? 'active' : 'inactive';
	}

	/**
	 * Set the network status for the plugin.
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
