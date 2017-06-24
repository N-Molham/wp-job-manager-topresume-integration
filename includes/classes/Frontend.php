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
		add_filter( 'submit_resume_form_fields', [ &$this, 'modify_resume_form_fields' ], 15 );

		// WP Job Manager - resume data update action
		add_action( 'resume_manager_update_resume_data', [ &$this, 'update_resume_candidate_name' ], 15, 2 );

		// WP Job Manager - before resume form fields
		add_action( 'submit_resume_form_resume_fields_start', [ &$this, 'resume_form_inline_css' ], 15 );

		// WP Job Manager - template path filter
		add_filter( 'job_manager_locate_template', [ &$this, 'override_date_field_template' ], 15, 3 );
	}

	public function override_date_field_template( $template, $template_name, $template_path )
	{
		if ( 'form-fields/date-field.php' !== $template_name )
		{
			// skip unwanted templates
			return $template;
		}

		return $this->plugin->get_view_template_path( 'date-field' );
	}

	/**
	 * Resume form inline CSS
	 *
	 * @return void
	 */
	public function resume_form_inline_css()
	{
		$assets_path    = Helpers::enqueue_path();
		$assets_version = Helpers::assets_version();

		wp_enqueue_style( 'wpjm-tri-resume', sprintf( '%s/assets/', untrailingslashit( WPJM_TRI_URI ) ) . 'dist/css/frontend.css', null, $assets_version );

		wp_enqueue_script( 'wpjm-tri-resume', $assets_path . 'js/resume_form.js', [
			'jquery',
			'jmfe-date-field',
		], $assets_version, true );
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
		if ( !isset( $values['resume_fields'] ) )
		{
			// skip unknown fields
			return;
		}

		if ( !empty( $values['resume_fields']['candidate_education'] ) )
		{
			// dd($values['resume_fields']['candidate_education']);
		}

		if ( isset( $values['resume_fields']['candidate_name'] ) )
		{
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
	}

	/**
	 * Add extra fields to resume fields list
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function modify_resume_form_fields( $fields )
	{
		if ( !isset( $fields['resume_fields'] ) )
		{
			// skip!
			return $fields;
		}

		if ( isset( $fields['resume_fields']['candidate_name'] ) )
		{
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
				'priority'    => 1.4,
				// set priority to place the field just right after the "candidate_first_name" field
			];
		}

		$date_field_type = class_exists( 'WP_Job_Manager_Field_Editor_Assets' ) ? 'date' : 'text';

		if ( isset( $fields['resume_fields']['candidate_education'] ) )
		{
			$fields['resume_fields']['candidate_education']['fields']['start_date'] = [
				'label'       => __( 'Start date', WPJM_TRI_DOMAIN ),
				'type'        => $date_field_type,
				'required'    => true,
				'placeholder' => '',
			];

			$fields['resume_fields']['candidate_education']['fields']['end_date'] = [
				'label'       => __( 'End date', WPJM_TRI_DOMAIN ),
				'type'        => $date_field_type,
				'required'    => true,
				'placeholder' => '',
			];
		}

		if ( isset( $fields['resume_fields']['candidate_experience'] ) )
		{
			$fields['resume_fields']['candidate_experience']['fields']['start_date'] = [
				'label'       => __( 'Start date', WPJM_TRI_DOMAIN ),
				'type'        => $date_field_type,
				'required'    => true,
				'placeholder' => '',
			];

			$fields['resume_fields']['candidate_experience']['fields']['end_date'] = [
				'label'       => __( 'End date', WPJM_TRI_DOMAIN ),
				'type'        => $date_field_type,
				'required'    => true,
				'placeholder' => '',
			];
		}

		return $fields;
	}
}
