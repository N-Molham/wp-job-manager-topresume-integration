<?php namespace WP_Job_Manager_TopResume_Integration;

/**
 * TopResume API logic
 *
 * @package WP_Job_Manager_TopResume_Integration
 */
class Top_Resume extends Component
{
	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init()
	{
		parent::init();

		// WP Job Manager - resume form fields filter
		add_filter( 'submit_resume_form_fields', [ &$this, 'send_to_top_resume_checkbox_field' ], 20 );

		// WP Job Manager - Resume reviewed and submitted action
		add_action( 'resume_manager_resume_submitted', [ &$this, 'post_resume_through_api' ], 15 );
	}

	/**
	 * Send submitted resume to TopResume to evaluate
	 *
	 * @param int $resume_id
	 *
	 * @return void
	 */
	public function post_resume_through_api( $resume_id )
	{

	}

	/**
	 * Add extra checkbox field asking for permission to send resume to TopResume
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function send_to_top_resume_checkbox_field( $fields )
	{
		if ( !isset( $fields['resume_fields'] ) )
		{
			// skip!
			return $fields;
		}
		
		$fields['resume_fields']['send_to_top_resume'] = [
			'label'       => __( 'Free Evaluation', WPJM_TRI_DOMAIN ),
			'type'        => 'checkbox',
			'required'    => false,
			'priority'    => 15,
			'description' => sprintf( __( 'Yes, Get me free resume review from <strong>%s</strong> partner, <img src="https://www.topresume.com/images/universal/logos/logo-topresume.svg" alt="TopResume" class="top-resume-logo" width="120" />', WPJM_TRI_DOMAIN ), get_bloginfo('name') ),
		];

		return $fields;
	}
}
