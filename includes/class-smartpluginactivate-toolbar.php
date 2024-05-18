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
 * Class SmartPluginActivate_Toolbar
 *
 * Handles the addition of plugin-related nodes to the WordPress admin toolbar.
 *
 * @since 1.1
 */
class SmartPluginActivate_Toolbar {
	/**
	 * WP_Admin_Bar instance.
	 *
	 * @var WP_Admin_Bar
	 */
	protected $toolbar;

	/**
	 * Current URL.
	 *
	 * @var string
	 */
	protected $current_url;

	/**
	 * Constructor.
	 *
	 * @param WP_Admin_Bar $toolbar WP_Admin_Bar instance.
	 * @param array        $plugins Array of plugin objects.
	 */
	public function __construct( WP_Admin_Bar $toolbar, $plugins ) {
		$this->toolbar = $toolbar;

		$this->add_top_level_node( count( $plugins ) );
		$this->add_group_to_node();

		$visible_plugins = 0;
		foreach ( $plugins as $plugin_file => $plugin ) {
			if ( ! $plugin->is_network_related() ) {
				$this->add_plugin_to_group( $plugin );
				++$visible_plugins;
			}
		}

		if ( ! $visible_plugins ) {
			$this->toolbar->remove_node( 'smart-plugin-activate' );
		}
	}

	/**
	 * Adds the top-level node to the admin toolbar.
	 *
	 * @param int $plugin_count Number of plugins.
	 */
	protected function add_top_level_node( $plugin_count ) {
		$node_args = array(
			'id'    => 'smart-plugin-activate',
			'title' => sprintf( '<span class="ab-icon"></span> <span class="ab-label">%s</span>', __( 'Plugins', 'smart-plugin-activate' ) ),
			'href'  => self_admin_url( 'plugins.php' ),
			'meta'  => array(
				'class' => ( $plugin_count > 20 ) ? 'has-many' : '',
			),
		);
		$this->toolbar->add_node( $node_args );
	}

	/**
	 * Adds a group to the top-level node in the admin toolbar.
	 */
	protected function add_group_to_node() {
		$node_args = array(
			'id'     => 'smart-plugin-activate-group',
			'group'  => true,
			'parent' => 'smart-plugin-activate',
		);
		$this->toolbar->add_node( $node_args );
	}

	/**
	 * Adds a plugin to the group in the admin toolbar.
	 *
	 * @param SmartPluginActivate_Plugin $plugin Plugin object.
	 */
	protected function add_plugin_to_group( SmartPluginActivate_Plugin $plugin ) {
		$node_args = array(
			'id'     => 'smart-plugin-activate_' . sanitize_title( $plugin->get_name() ),
			'title'  => esc_html( $plugin->get_name() ),
			'href'   => esc_url( $plugin->get_toggle_url( $this->get_current_url() ) ),
			'parent' => 'smart-plugin-activate-group',
			'meta'   => array(
				'class' => $plugin->is_active() ? 'is-active' : '',
			),
		);
		$this->toolbar->add_node( $node_args );
	}

	/**
	 * Gets the current URL.
	 *
	 * @return string Current URL.
	 */
	protected function get_current_url() {
		global $wp;

		if ( empty( $this->current_url ) ) {
			$url               = is_admin() ? add_query_arg( array() ) : home_url( add_query_arg( array(), $wp->request ) );
			$url               = remove_query_arg( array( '_wpnonce', 'redirect_to' ), $url );
			$this->current_url = $url;
		}

		return esc_url( $this->current_url );
	}
}
