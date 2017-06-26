<?php namespace WP_Job_Manager_TopResume_Integration;

use WP_Post;

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

		if ( defined( 'WPJM_TRI_RESUME' ) && isset( $_GET['wpjm_tri_test'] ) )
		{
			// WordPress initialization
			add_action( 'init', [ &$this, 'api_submission_test' ], 100 );
		}
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
		// query resume
		$resume = get_post( $resume_id );
		if ( null === $resume || 'resume' !== $resume->post_type )
		{
			// skip non-resume post
			return;
		}

		// Resume file URL
		$resume_file_url = get_resume_file( $resume_id );
		if ( empty( $resume_file_url ) )
		{
			// skip resume with no file!
			return;
		}

		// Resume's candidate basic information
		$request_body = [
			'email'      => $resume->_candidate_email,
			'first_name' => $resume->_candidate_first_name,
			'last_name'  => $resume->_candidate_last_name,
		];

		// fetch resume files directory
		$upload_dir       = wp_upload_dir();
		$resumes_dir_path = $upload_dir['basedir'] . '/resumes/resume_files';
		$resumes_dir_url  = $upload_dir['baseurl'] . '/resumes/resume_files';

		// determine resume file absolute path
		$request_body['resume_file'] = $resumes_dir_path . str_replace( $resumes_dir_url, '', $resume_file_url );

		// API credentials - Development > Production
		$request_body['partner_key'] = defined( 'WPJM_TRI_DEV' ) && WPJM_TRI_DEV ? 'xt2loDmOE9mZW' : 'otqxLr1U5HPWn';
		$request_body['secret_key']  = defined( 'WPJM_TRI_DEV' ) && WPJM_TRI_DEV ? 'J71XiHpR3wWChWxVSlskoNI09wEiSDeBK' : 'BOPKa6TTBwefOBV1UgqnHY881dGR92sX';

		if ( defined( 'WPJM_TRI_PARTNER_KEY' ) )
		{
			// Constant override 
			$request_body['partner_key'] = WPJM_TRI_PARTNER_KEY;
		}

		if ( defined( 'WPJM_TRI_SECRET_KEY' ) )
		{
			// Constant override 
			$request_body['secret_key'] = WPJM_TRI_SECRET_KEY;
		}

		/**
		 * Filter TopResume API post request body
		 *
		 * @param array   $request_body
		 * @param WP_Post $resume
		 *
		 * @return array
		 */
		$request_body = apply_filters( 'wpjm_tri_request_args', $request_body, $resume );

		if ( !file_exists( $request_body['resume_file'] ) || !is_readable( $request_body['resume_file'] ) )
		{
			// unable to locate or read the file!
			return;
		}

		// load file data
		$request_body['resume_file'] = '@' . $request_body['resume_file'] . ';type=' . mime_content_type( $request_body['resume_file'] );

		// make the POST request
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, 'https://api.talentinc.com/v1/resume' );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $request_body );
		$response = curl_exec( $curl );
		curl_close( $curl );

		if ( defined( 'WPJM_TRI_RESUME' ) && isset( $_GET['wpjm_tri_test'] ) )
		{
			// debug
			echo '<pre>';
			var_dump( $response );
			die();
		}
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
			'description' => sprintf( __( 'Yes, Get me free resume review from <strong>%s</strong> partner, <img src="https://www.topresume.com/images/universal/logos/logo-topresume.svg" alt="TopResume" class="top-resume-logo" width="120" />', WPJM_TRI_DOMAIN ), get_bloginfo( 'name' ) ),
		];

		return $fields;
	}

	/**
	 * API submission test/debug
	 *
	 * @return void
	 */
	public function api_submission_test()
	{
		/**
		 * Manual trigger for resume submission
		 *
		 * @param int $resume_id
		 */
		do_action( 'resume_manager_resume_submitted', WPJM_TRI_RESUME );
	}
}
