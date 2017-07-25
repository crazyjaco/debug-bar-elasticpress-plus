<?php
/*
 Plugin Name: Debug Bar ElasticPress Plus
 Plugin URI: http://github.com/crazyjaco/debug-bar-elasticpress-plus
 Description: Forks the debug-bar-elasticpress plugin from 10up and adds stuff.
 Author: 10up, CrazyJaco
 Version: 1.0
 Author URI: http://10up.com
 */

define( 'EPP_DEBUG_VERSION', '1.0' );

/**
 * Register panel
 *
 * @param array $panels
 * @return array
 */
function epp_add_debug_bar_panel( $panels ) {
	require_once( dirname( __FILE__ ) . '/classes/class-epp-debug-bar-elasticpress-plus.php' );
	$panels[] = EPP_Debug_Bar_ElasticPress_Plus::factory();
	return $panels;
}

add_filter( 'debug_bar_panels', 'epp_add_debug_bar_panel' );

/**
 * Add explain=true to elastic post query
 *
 * @param array $formatted_args
 * @param array $args
 * @return array
 */
function epp_add_explain_args( $formatted_args, $args ) {
	if( isset( $_GET['explain'] ) ){
		$formatted_args['explain'] = true;
	}
	return $formatted_args;
}
add_filter( 'ep_formatted_args', 'epp_add_explain_args', 10, 2 );
