<?php namespace WP_Job_Manager_TopResume_Integration;

use DateTime;
use WP_Error;

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

		// WP Job Manager - resume fields validation
		add_filter( 'submit_resume_form_validate_fields', [ &$this, 'validate_resume_extra_fields' ], 15, 3 );
	}

	/**
	 * Validate resume's extra fields
	 *
	 * @param boolean|WP_Error $is_valid
	 * @param array            $fields
	 * @param array            $values
	 *
	 * @return boolean|WP_Error
	 */
	public function validate_resume_extra_fields( $is_valid, $fields, $values )
	{
		if ( !isset( $fields['resume_fields'] ) || !isset( $values['resume_fields'] ) )
		{
			// skip missing resume fields section
			return $is_valid;
		}

		// defaults
		$values['resume_fields'] = wp_parse_args( $values['resume_fields'], [
			'candidate_name'       => '',
			'candidate_first_name' => '',
			'candidate_last_name'  => '',
			'candidate_education'  => [],
			'candidate_experience' => [],
		] );

		if ( $values['resume_fields']['candidate_name'] !== ( $values['resume_fields']['candidate_first_name'] . ' ' . $values['resume_fields']['candidate_last_name'] ) )
		{
			// candidate name is not as intended
			return new WP_Error( 'wpjm_tri_candidate_name', __( 'Invalid candidate name!', WPJM_TRI_DOMAIN ) );
		}

		// vars
		$multi_entries  = array_merge( $values['resume_fields']['candidate_education'], $values['resume_fields']['candidate_experience'] );
		$entry_defaults = [
			'date'       => '',
			'start_date' => '',
			'end_date'   => '',
		];

		foreach ( $multi_entries as $entry )
		{
			// defaults
			$entry = wp_parse_args( $entry, $entry_defaults );

			$start_date = DateTime::createFromFormat( 'F j, Y', $entry['start_date'] );
			$end_date   = DateTime::createFromFormat( 'F j, Y', $entry['end_date'] );
			if ( false === $end_date || false === $start_date )
			{
				// invalid date(s)
				return new WP_Error( 'wpjm_tri_entry_date', __( 'Given start/end date is not valid!', WPJM_TRI_DOMAIN ) );
			}

			if ( $start_date >= $end_date )
			{
				// invalid date range
				return new WP_Error( 'wpjm_tri_entry_date_range', __( 'Given start/end duration is not valid!', WPJM_TRI_DOMAIN ) );
			}

			if ( $entry['date'] !== ( $entry['start_date'] . ' / ' . $entry['end_date'] ) )
			{
				// invalid date(s)
				return new WP_Error( 'wpjm_tri_entry_date_singular', __( 'Given start/end date is not valid!', WPJM_TRI_DOMAIN ) );
			}
		}

		return $is_valid;
	}

	/**
	 * Override resume date field template
	 *
	 * @param string $template
	 * @param string $template_name
	 * @param string $template_path
	 *
	 * @return string
	 */
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
			// store fields information
			$education_notes = $fields['resume_fields']['candidate_education']['fields']['notes'];
			$education_major = $fields['resume_fields']['candidate_education']['fields']['major'];

			// remove
			unset(
				$fields['resume_fields']['candidate_education']['fields']['notes'],
				$fields['resume_fields']['candidate_education']['fields']['major']
			);

			// re-add them in the right order
			$fields['resume_fields']['candidate_education']['fields']['major'] = $education_major;
			$fields['resume_fields']['candidate_education']['fields']['notes'] = $education_notes;

			// start/end date range
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
