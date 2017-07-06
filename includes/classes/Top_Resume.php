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

		// Job Manager settings fields filter
		add_filter( 'job_manager_settings', [ &$this, 'settings_fields' ], 20 );

		// WP Job Manager - resume form fields filter
		add_filter( 'submit_resume_form_fields', [ &$this, 'send_to_top_resume_checkbox_field' ], 20 );

		// WP Job Manager - Resume reviewed and submitted action
		add_action( 'resume_manager_resume_submitted', [ &$this, 'post_resume_through_api' ], 15 );
		add_action( 'wpjm_tri_resume_submitted', [ &$this, 'post_resume_through_api' ] );

		if ( defined( 'WPJM_TRI_RESUME' ) && isset( $_GET['wpjm_tri_test'] ) )
		{
			// WordPress initialization
			add_action( 'init', [ &$this, 'api_submission_test' ], 100 );
		}

		// WooCommerce after registration redirect URL
		add_filter( 'woocommerce_registration_redirect', [ &$this, 'redirect_use_to_submit_resume' ], 100 );

		// WordPress assets load
		add_action( 'wp_enqueue_scripts', [ &$this, 'load_assets' ], 20 );

		// Post status change hook
		add_action( 'transition_post_status', [ &$this, 'trigger_resume_submit' ], 10, 3 );
	}

	/**
	 * Manual trigger API submit for resume on status switch to "publish"
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function trigger_resume_submit( $new_status, $old_status, $post )
	{
		if ( 'publish' === $new_status && 'resume' === get_post_type( $post ) )
		{
			do_action( 'wpjm_tri_resume_submitted', $post );
		}
	}

	/**
	 * Load JS & CSS assets
	 *
	 * @return void
	 */
	public function load_assets()
	{
		wp_enqueue_script( 'wpjm-tri-register-form', Helpers::enqueue_path() . 'js/register_form.js', [ 'jquery' ], Helpers::assets_version(), false );
		wp_localize_script( 'wpjm-tri-register-form', 'wpjm_top_resume', [
			'post_resume_url' => resume_manager_get_permalink( 'submit_resume_form' ),
		] );
	}

	/**
	 * Redirect new user to submit a resume
	 *
	 * @return string
	 */
	public function redirect_use_to_submit_resume()
	{
		return resume_manager_get_permalink( 'submit_resume_form' );
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
		if ( $resume_id instanceof WP_Post )
		{
			$resume    = $resume_id;
			$resume_id = $resume->ID;
		}
		else
		{
			$resume = get_post( $resume_id );
		}

		if ( null === $resume || 'resume' !== $resume->post_type )
		{
			// skip non-resume post
			return;
		}

		if ( 'yes' === $resume->_wpjm_sent_to_api )
		{
			Helpers::log( sprintf( 'Resume [%s] already submitted', $resume_id ), 'info' );

			return;
		}

		// Resume file URL
		$resume_file_url = get_resume_file( $resume_id );
		if ( empty( $resume_file_url ) )
		{
			// skip resume with no file!
			Helpers::log( sprintf( 'Resume [%s] has not file attached', $resume_id ) );

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

		// API credentials
		$partner_key = get_option( 'wpjm_tri_partner_key' );
		$secret_key  = get_option( 'wpjm_tri_secret_key' );

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

		if ( empty( $partner_key ) || empty( $secret_key ) )
		{
			// skip if any of the credentials keys are missing
			Helpers::log( sprintf( 'API access missing, key=[%s], secret=[%s]', $partner_key, $secret_key ) );

			return;
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
			Helpers::log( sprintf( 'Unable to locate/read resume [%s] file [%s]', $resume_id, $resume_file_path ) );

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

			Helpers::log( sprintf( 'Resume [%s] submitted, api-response[%s]', $resume_id, $response->getBody()->getContents() ), 'api-info' );

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
			$error_phrase = $exception->hasResponse() ? $exception->getResponse()->getReasonPhrase() : $exception->getMessage();
			Helpers::log( $error_phrase, 'api-error' );

			if ( false !== strpos( mb_strtolower( $error_phrase ), 'already exists' ) )
			{
				// mark as sent
				update_post_meta( $resume_id, '_wpjm_sent_to_api', 'yes' );
				Helpers::log( sprintf( 'Resume [%s] already submitted, api-response[%s]', $resume_id, $error_phrase ), 'api-error' );
			}

			// do nothing
			if ( $is_debug )
			{
				// debug
				echo '<pre>';
				var_dump( $request_body );
				var_dump( $error_phrase );
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
	 * TopResume API credentials
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function settings_fields( $settings )
	{
		$settings['top_resume_integration'] = [
			__( 'TopResume Integration', WPJM_TRI_DOMAIN ),
			[
				[
					'name'  => 'wpjm_tri_partner_key',
					'std'   => '',
					'label' => __( 'Partner Key', WPJM_TRI_DOMAIN ),
					'desc'  => __( 'API access credentials', WPJM_TRI_DOMAIN ),
					'type'  => 'input',
				],
				[
					'name'  => 'wpjm_tri_secret_key',
					'std'   => '',
					'label' => __( 'Secret Key', WPJM_TRI_DOMAIN ),
					'desc'  => __( 'API access credentials', WPJM_TRI_DOMAIN ),
					'type'  => 'input',
				],
			],
		];

		return $settings;
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
