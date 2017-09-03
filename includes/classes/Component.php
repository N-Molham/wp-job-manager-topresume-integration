<?php namespace WP_Job_Manager_TopResume_Integration;

/**
 * Base Component
 *
 * @package WP_Job_Manager_TopResume_Integration
 */
class Component extends Singular {
	/**
	 * Plugin Main Component
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init() {
		// vars
		$this->plugin = Plugin::get_instance();
	}
}
