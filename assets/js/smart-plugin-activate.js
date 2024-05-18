/**
 * Smart Plugin Activate Admin Toolbar Script
 *
 * This script adjusts the max height of the plugin links menu in the WordPress admin toolbar
 * to fit within the viewport, ensuring it is fully visible and accessible.
 *
 * @package SmartPluginActivate
 * @version 1.0
 */

(function ( window, undefined ) {
	'use strict';

	var document = window.document,
		container, menuItem, setMaxHeight, toolbar, toolbarHeight;

	setMaxHeight = function () {
		container.style.maxHeight = window.innerHeight - toolbarHeight + 'px';
	};

	window.addEventListener(
		'load',
		function () {
			toolbar = document.getElementById( 'wpadminbar' );
			if ( ! toolbar ) {
				return;
			}

			toolbarHeight = toolbar.clientHeight;
			menuItem      = document.getElementById( 'wp-admin-bar-smart-plugin-activate' );
			container     = menuItem.querySelector( '.ab-sub-wrapper' );

			setMaxHeight();
			window.addEventListener( 'resize', setMaxHeight );
		}
	);

})( this );
