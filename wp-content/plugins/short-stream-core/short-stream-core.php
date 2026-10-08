<?php
/**
 * Plugin Name:       Short Stream Core
 * Plugin URI:        https://short.stream
 * Description:       Core backend engine for Short Stream: ShortTV Drama Video Management, Storage & CDN Integration, REST Endpoints, Admin Dashboard, and Security.
 * Version:           1.0.0
 * Author:            Short Engineering
 * Author URI:        https://short.stream
 * Text Domain:       short-stream-core
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'SHORT\\Core\\Core' ) || defined( 'SHORT_CORE_VERSION' ) ) {
	return;
}

define( 'SHORT_CORE_VERSION', '1.0.0' );
define( 'SHORT_CORE_FILE', __FILE__ );
define( 'SHORT_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SHORT_CORE_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( $class ) {
	$prefix_short = 'SHORT\\Core\\';
	$base_dir = SHORT_CORE_PATH . 'includes/';

	if ( strncmp( $prefix_short, $class, strlen( $prefix_short ) ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, strlen( $prefix_short ) );
	$parts = explode( '\\', $relative_class );
	$file_name = 'class-' . strtolower( str_replace( '_', '-', array_pop( $parts ) ) ) . '.php';
	$sub_path  = strtolower( str_replace( '_', '-', implode( '/', $parts ) ) );

	$file = $base_dir . ( $sub_path ? $sub_path . '/' : '' ) . $file_name;

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

if ( ! class_exists( 'SHORT\\Core\\Activator' ) && file_exists( SHORT_CORE_PATH . 'includes/class-activator.php' ) ) {
	require_once SHORT_CORE_PATH . 'includes/class-activator.php';
}
if ( ! class_exists( 'SHORT\\Core\\Deactivator' ) && file_exists( SHORT_CORE_PATH . 'includes/class-deactivator.php' ) ) {
	require_once SHORT_CORE_PATH . 'includes/class-deactivator.php';
}

register_activation_hook( __FILE__, array( 'SHORT\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SHORT\\Core\\Deactivator', 'deactivate' ) );

if ( ! class_exists( 'SHORT\\Core\\Core' ) && file_exists( SHORT_CORE_PATH . 'includes/class-core.php' ) ) {
	require_once SHORT_CORE_PATH . 'includes/class-core.php';
}

if ( ! function_exists( 'run_short_core' ) ) {
	function run_short_core() {
		if ( class_exists( 'SHORT\\Core\\Core' ) ) {
			$core = new \SHORT\Core\Core();
			$core->run();
		}
	}
	add_action( 'plugins_loaded', 'run_short_core' );
}
