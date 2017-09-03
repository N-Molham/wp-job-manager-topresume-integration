<?php
/**
 * Created by Nabeel
 * Date: 2016-01-22
 * Time: 2:38 AM
 *
 * @package WP_Job_Manager_TopResume_Integration
 */

use WP_Job_Manager_TopResume_Integration\Component;
use WP_Job_Manager_TopResume_Integration\Plugin;

if ( ! function_exists( 'wp_job_manager_topresume_integration' ) ):
	/**
	 * Get plugin instance
	 *
	 * @return Plugin
	 */
	function wp_job_manager_topresume_integration() {
		return Plugin::get_instance();
	}
endif;

if ( ! function_exists( 'wpjm_tri_component' ) ):
	/**
	 * Get plugin component instance
	 *
	 * @param string $component_name
	 *
	 * @return Component|null
	 */
	function wpjm_tri_component( $component_name ) {
		if ( isset( wp_job_manager_topresume_integration()->$component_name ) ) {
			return wp_job_manager_topresume_integration()->$component_name;
		}

		return null;
	}
endif;

if ( ! function_exists( 'wpjm_tri_view' ) ):
	/**
	 * Load view
	 *
	 * @param string  $view_name
	 * @param array   $args
	 * @param boolean $return
	 *
	 * @return void
	 */
	function wpjm_tri_view( $view_name, $args = null, $return = false ) {
		if ( $return ) {
			// start buffer
			ob_start();
		}

		wp_job_manager_topresume_integration()->load_view( $view_name, $args );

		if ( $return ) {
			// get buffer flush
			return ob_get_clean();
		}
	}
endif;

if ( ! function_exists( 'wpjm_tri_version' ) ):
	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	function wpjm_tri_version() {
		return wp_job_manager_topresume_integration()->version;
	}
endif;