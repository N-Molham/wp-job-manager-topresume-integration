<?php namespace WP_Job_Manager_TopResume_Integration;

/**
 * Frontend logic
 *
 * @package WP_Job_Manager_TopResume_Integration
 */
class Frontend extends Component
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
		add_filter( 'submit_resume_form_fields', [ &$this, 'add_name_fields' ], 15 );

		// WP Job Manager - resume data update action
		add_action( 'resume_manager_update_resume_data', [ &$this, 'update_resume_candidate_name' ], 15, 2 );
	}

	/**
	 * Setup candidate name field based on first/last name
	 *
	 * @param int   $resume_id
	 * @param array $values
	 *
	 * @return void
	 */
	public function update_resume_candidate_name( $resume_id, $values )
	{
		if ( !isset( $values['resume_fields'] ) || !isset( $values['resume_fields']['candidate_name'] ) )
		{
			// skip unknown fields
			return;
		}

		$values['resume_fields'] = wp_parse_args( $values['resume_fields'], [
			'candidate_first_name' => '',
			'candidate_last_name'  => '',
		] );

		// new candidate name
		$candidate_name = trim( $values['resume_fields']['candidate_first_name'] . ' ' . $values['resume_fields']['candidate_last_name'] );

		if ( !empty( $candidate_name ) )
		{
			// update value
			update_post_meta( $resume_id, '_candidate_name', $candidate_name );
			wp_update_post( [ 'ID' => $resume_id, 'post_title' => $candidate_name ] );
		}
	}

	/**
	 * Add First & last name fields to resume fields list
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function add_name_fields( $fields )
	{
		if ( !isset( $fields['resume_fields'] ) || !isset( $fields['resume_fields']['candidate_name'] ) )
		{
			// skip unknown fields
			return $fields;
		}

		// first name
		$fields['resume_fields']['candidate_first_name'] = [
			'label'       => __( 'First Name', WPJM_TRI_DOMAIN ),
			'type'        => 'text',
			'required'    => true,
			'placeholder' => __( 'First Name', WPJM_TRI_DOMAIN ),
			'priority'    => 1.2, // set priority to place the field just right after the "candidate_name" field
		];

		// last name
		$fields['resume_fields']['candidate_last_name'] = [
			'label'       => __( 'Last Name', WPJM_TRI_DOMAIN ),
			'type'        => 'text',
			'required'    => true,
			'placeholder' => __( 'Last Name', WPJM_TRI_DOMAIN ),
			'priority'    => 1.4, // set priority to place the field just right after the "candidate_first_name" field
		];

		return $fields;
	}
}
