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
		require_once WPJM_TRI_DIR . 'vendor/autoload.php';

		$is_debug = defined( 'WPJM_TRI_RESUME' ) && isset( $_GET['wpjm_tri_test'] );

		// query resume
		$resume = get_post( $resume_id );
		if ( null === $resume || 'resume' !== $resume->post_type || 'yes' === $resume->_wpjm_sent_to_api )
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
			[
				'name'     => 'email',
				'contents' => $resume->_candidate_email,
			],
			[
				'name'     => 'first_name',
				'contents' => $resume->_candidate_first_name,
			],
			[
				'name'     => 'last_name',
				'contents' => $resume->_candidate_last_name,
			],
		];

		// API credentials - Development > Production
		$partner_key = defined( 'WPJM_TRI_DEV' ) && WPJM_TRI_DEV ? 'xt2loDmOE9mZW' : 'otqxLr1U5HPWn';
		$secret_key  = defined( 'WPJM_TRI_DEV' ) && WPJM_TRI_DEV ? 'J71XiHpR3wWChWxVSlskoNI09wEiSDeBK' : 'BOPKa6TTBwefOBV1UgqnHY881dGR92sX';

		if ( defined( 'WPJM_TRI_PARTNER_KEY' ) )
		{
			// Constant override 
			$partner_key = WPJM_TRI_PARTNER_KEY;
		}

		if ( defined( 'WPJM_TRI_SECRET_KEY' ) )
		{
			// Constant override 
			$secret_key = WPJM_TRI_SECRET_KEY;
		}

		$request_body[] = [
			'name'     => 'partner_key',
			'contents' => $partner_key,
		];

		$request_body[] = [
			'name'     => 'secret_key',
			'contents' => $secret_key,
		];

		// fetch resume files directory
		$upload_dir       = wp_upload_dir();
		$resumes_dir_path = $upload_dir['basedir'] . '/resumes/resume_files';
		$resumes_dir_url  = $upload_dir['baseurl'] . '/resumes/resume_files';

		// determine resume file absolute path
		$resume_file_path = $resumes_dir_path . str_replace( $resumes_dir_url, '', $resume_file_url );
		if ( !file_exists( $resume_file_path ) || !is_readable( $resume_file_path ) )
		{
			// unable to locate or read the file!
			return;
		}

		$request_body[] = [
			'name'     => 'resume_file',
			'contents' => fopen( $resume_file_path, 'rb' ),
		];

		/**
		 * Filter TopResume API post request body
		 *
		 * @param array   $request_body
		 * @param WP_Post $resume
		 *
		 * @return array
		 */
		$request_body = apply_filters( 'wpjm_tri_request_body', $request_body, $resume );

		// request client
		$client = new \GuzzleHttp\Client();

		try
		{
			// make the POST request
			$response = $client->post( 'https://api.talentinc.com/v1/resume', apply_filters( 'wpjm_tri_request_args', [
				'multipart' => $request_body,
			] ) );

			// mark as sent
			update_post_meta( $resume_id, '_wpjm_sent_to_api', 'yes' );

			if ( $is_debug )
			{
				// debug
				echo '<pre>';
				var_dump( $request_body );
				var_dump( $response->getBody()->getContents() );
				die();
			}
		}
		catch ( \GuzzleHttp\Exception\ClientException $exception )
		{
			// do nothing
			if ( $is_debug )
			{
				// debug
				echo '<pre>';
				var_dump( $request_body );
				var_dump( $exception->hasResponse() ? $exception->getResponse()->getReasonPhrase() : $exception );
				die();
			}
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
			'value'       => 1,
			'description' => sprintf(
				__( 'Yes, Get me free resume review from <strong>%s</strong> partner, <img src="%s" alt="TopResume" class="top-resume-logo" width="200" />', WPJM_TRI_DOMAIN ),
				get_bloginfo( 'name' ),
				untrailingslashit( WPJM_TRI_URI ) . '/assets/images/topresume-monster-logo.png'
			),
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
