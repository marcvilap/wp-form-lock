<?php

namespace WPFormsLocker;

use WPFormsLocker\Lockers\UniqueAnswer;

/**
 * Various admin functionality.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Admin hooks.
	 *
	 * @since 2.0.0
	 */
	public function hooks() {

		// Admin form builder enqueues.
		add_action( 'wpforms_builder_enqueues_before', [ $this, 'admin_builder_enqueues' ] );

		// Add Unique Answer toggle setting to selected core fields.
		add_action( 'wpforms_field_options_bottom_advanced-options', [ $this, 'field_unique_answer_toggle' ], 10, 2 );

		// Register form builder settings area.
		add_filter( 'wpforms_builder_settings_sections', [ $this, 'builder_settings_register' ], 30, 2 );

		// Form builder settings content.
		add_action( 'wpforms_form_settings_panel_content', [ $this, 'builder_settings_content' ], 30, 2 );
	}

	/**
	 * Enqueues for the admin form builder.
	 *
	 * @since 1.0.0
	 */
	public function admin_builder_enqueues() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-admin-builder-form-locker',
			wpforms_form_locker()->url . "assets/js/admin-builder-form-locker{$min}.js",
			[ 'jquery', 'wpforms-builder', 'wpforms-utils' ],
			WPFORMS_FORM_LOCKER_VERSION,
			true
		);

		wp_localize_script(
			'wpforms-admin-builder-form-locker',
			'wpforms_admin_builder_form_locker',
			[
				'date_format'                        => $this->get_date_format(),
				'time_format'                        => $this->get_time_format(),
				'require_restrict_user_entry_option' => esc_html__(
					'To limit the number of entries a user can submit, please select <strong>Restrict by IP address</strong> or <strong>Restrict by email address</strong>.',
					'wpforms-form-locker'
				),
			]
		);

		wp_enqueue_script(
			'wpforms-flatpickr',
			WPFORMS_PLUGIN_URL . 'assets/lib/flatpickr/flatpickr.min.js',
			[ 'jquery' ],
			'4.6.9',
			true
		);

		wp_enqueue_script(
			'wpforms-jquery-timepicker',
			WPFORMS_PLUGIN_URL . 'assets/lib/jquery.timepicker/jquery.timepicker.min.js',
			[ 'jquery' ],
			'1.11.5',
			true
		);

		wp_enqueue_script(
			'wpforms-datepair',
			wpforms_form_locker()->url . 'assets/js/vendor/datepair.min.js',
			[],
			'0.4.16',
			true
		);

		wp_enqueue_script(
			'wpforms-jquery-datepair',
			wpforms_form_locker()->url . 'assets/js/vendor/jquery.datepair.min.js',
			[ 'jquery', 'wpforms-datepair' ],
			'0.4.16',
			true
		);

		wp_enqueue_style(
			'wpforms-form-locker-admin-builder',
			wpforms_form_locker()->url . "assets/css/admin-builder{$min}.css",
			[],
			'1.11.5'
		);

		wp_enqueue_style(
			'wpforms-jquery-timepicker',
			WPFORMS_PLUGIN_URL . 'assets/lib/jquery.timepicker/jquery.timepicker.min.css',
			[],
			'1.11.5'
		);

		wp_enqueue_style(
			'wpforms-flatpickr',
			WPFORMS_PLUGIN_URL . 'assets/lib/flatpickr/flatpickr.min.css',
			[],
			'4.6.9'
		);
	}

	/**
	 * Add setting to core fields to allow limiting to unique answers only.
	 *
	 * This setting gets added to name, email, single text, URL, password, and phone fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $field    Field settings.
	 * @param object $instance Field base class instance.
	 */
	public function field_unique_answer_toggle( $field, $instance ) {

		// Limit to our specific field types.
		if ( ! in_array( $field['type'], UniqueAnswer::get_unique_answer_field_types(), true ) ) {
			return;
		}

		// Create checkbox setting.
		$instance->field_element(
			'row',
			$field,
			[
				'slug'    => 'unique_answer',
				'content' => $instance->field_element(
					'toggle',
					$field,
					[
						'slug'    => 'unique_answer',
						'value'   => isset( $field['unique_answer'] ) ? '1' : '0',
						'desc'    => esc_html__( 'Require unique answer', 'wpforms-form-locker' ),
						'tooltip' => esc_html__( 'Check this option to require only unique answers for the current field.', 'wpforms-form-locker' ),
					],
					false
				),
			]
		);
	}

	/**
	 * Form Locker form builder register settings area.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sections Settings area sections.
	 *
	 * @return array
	 */
	public function builder_settings_register( $sections ) {

		$sections['form_locker'] = esc_html__( 'Form Locker', 'wpforms-form-locker' );

		return $sections;
	}

	/**
	 * Array of available form email fields.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	private function get_email_field_options( $form_data ) {

		$fields        = [ '' => '--- Select Field ---' ];
		$email_options = wpforms_get_form_fields( $form_data, [ 'email' ] );

		if ( ! empty( $email_options ) ) {
			foreach ( $email_options as $id => $email_option ) {
				$fields[ $id ] = ! empty( $email_option['label'] )
					? esc_attr( $email_option['label'] )
					: sprintf( /* translators: %d - field ID. */
						esc_html__( 'Field #%d', 'wpforms-form-locker' ),
						absint( $id )
					);
			}
		}

		return $fields;
	}

	/**
	 * Form Locker form builder settings content.
	 *
	 * @since 1.0.0
	 *
	 * @param object $instance Settings panel instance.
	 */
	public function builder_settings_content( $instance ) {

		$email_fields = $this->get_email_field_options( $instance->form_data );

		echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-form_locker">';

			echo '<div class="wpforms-panel-content-section-title">';
				esc_html_e( 'Form Locker', 'wpforms-form-locker' );
			echo '</div>';
			$password_enable_option = ! empty( $instance->form_data['settings']['form_locker_password_enable'] ) ? [ 'value' => $instance->form_data['settings']['form_locker_password_enable'] ] : [];

			// Verification block.
			$verification = wpforms_panel_field(
				'toggle',
				'settings',
				'form_locker_verification',
				$instance->form_data,
				esc_html__( 'Enable verification', 'wpforms-form-locker' ),
				$password_enable_option,
				false
			);

			wpforms_panel_fields_group(
				$verification,
				[
					'description' => esc_html__( 'Require users to enter information before accessing the form.', 'wpforms-form-locker' ),
					'title'       => esc_html__( 'Form Verification', 'wpforms-form-locker' ),
				]
			);

			wpforms_panel_field(
				'select',
				'settings',
				'form_locker_verification_type',
				$instance->form_data,
				esc_html__( 'Type', 'wpforms-form-locker' ),
				[
					'default' => 'password',
					'options' => [
						'password' => esc_html__( 'Password', 'wpforms-form-locker' ),
						'age'      => esc_html__( 'Age', 'wpforms-form-locker' ),
						'email'    => esc_html__( 'Email', 'wpforms-form-locker' ),
					],
				]
			);

			wpforms_panel_field(
				'text',
				'settings',
				'form_locker_password',
				$instance->form_data,
				esc_html__( 'Password', 'wpforms-form-locker' ),
				[
					'class' => 'wpforms-form-locker-password ',
				]
			);

			echo '<div class="wpforms-flex-container wpforms-flex-container-end wpforms-form-locker-age">';

			wpforms_panel_field(
				'text',
				'settings',
				'form_locker_age',
				$instance->form_data,
				esc_html__( 'Criteria', 'wpforms-form-locker' ),
				[
					'type'    => 'number',
					'default' => '18',
					'class'   => 'wpforms-panel-field-half wpforms-form-locker-age ',
				]
			);

			wpforms_panel_field(
				'select',
				'settings',
				'form_locker_age_criteria',
				$instance->form_data,
				'',
				[
					'default' => '>',
					'options' => [
						'>=' => esc_html__( 'Minimum', 'wpforms-form-locker' ),
						'<=' => esc_html__( 'Maximum', 'wpforms-form-locker' ),
						'==' => esc_html__( 'Equal to', 'wpforms-form-locker' ),
					],
					'class'   => 'wpforms-panel-field-half wpforms-form-locker-age ',
				]
			);
			echo '</div>';

			wpforms_panel_field(
				'tinymce',
				'settings',
				'form_locker_password_message',
				$instance->form_data,
				esc_html__( 'Display Message', 'wpforms-form-locker' ),
				[
					'tinymce' => [
						'editor_height' => 175,
					],
					'tooltip' => esc_html__( 'This message is displayed to visitors above the password form.', 'wpforms-form-locker' ),
					'class'   => 'wpforms-form-locker-password ',
				]
			);

			wpforms_panel_field(
				'tinymce',
				'settings',
				'form_locker_age_message',
				$instance->form_data,
				esc_html__( 'Display Message', 'wpforms-form-locker' ),
				[
					'tinymce' => [
						'editor_height' => 175,
					],
					'tooltip' => esc_html__( 'This message is displayed to visitors above the age verification form.', 'wpforms-form-locker' ),
					'class'   => 'wpforms-form-locker-age ',
				]
			);

			wpforms_panel_field(
				'tinymce',
				'settings',
				'form_locker_email_message',
				$instance->form_data,
				esc_html__( 'Display Message', 'wpforms-form-locker' ),
				[
					'tinymce' => [
						'editor_height' => 175,
					],
					'tooltip' => esc_html__( 'This message is displayed to visitors above the email verification form.', 'wpforms-form-locker' ),
					'class'   => 'wpforms-form-locker-email ',
				]
			);

			// Schedule locker block.
			$schedule = wpforms_panel_field(
				'toggle',
				'settings',
				'form_locker_schedule_enable',
				$instance->form_data,
				esc_html__( 'Form scheduling', 'wpforms-form-locker' ),
				[],
				false
			);

			wpforms_panel_fields_group(
				$schedule,
				[
					'description' => esc_html__( 'Accept form entries during a specific date/time period.', 'wpforms-form-locker' ),
					'title'       => esc_html__( 'Form Scheduling', 'wpforms-form-locker' ),
				]
			);

			echo '<div id="wpforms-form-locker-schedule-datetime-block"><div class="wpforms-flex-container wpforms-flex-container-end">';

			wpforms_panel_field(
				'text',
				'settings',
				'form_locker_schedule_start_date',
				$instance->form_data,
				esc_html__( 'Start Date', 'wpforms-form-locker' ),
				[
					'class'       => 'wpforms-panel-field-datetime wpforms-panel-field-half',
					'input_class' => 'readonly-active wpforms-datepair-date wpforms-datepair-start',
					'after'       => '<button type="button" class="wpforms-clear-datetime-field" title="' . esc_attr__( 'Clear Start Date', 'wpforms-form-locker' ) . '"><i class="fa fa-times-circle fa-lg"></i></button>',
				]
			);

			wpforms_panel_field(
				'text',
				'settings',
				'form_locker_schedule_start_time',
				$instance->form_data,
				esc_html__( 'Start Time', 'wpforms-form-locker' ),
				[
					'class'       => 'wpforms-panel-field-datetime wpforms-panel-field-half',
					'input_class' => 'wpforms-datepair-time wpforms-datepair-start',
					'after'       => '<button type="button" class="wpforms-clear-datetime-field" title="' . esc_attr__( 'Clear Start Time', 'wpforms-form-locker' ) . '"><i class="fa fa-times-circle fa-lg"></i></button>',
				]
			);

			echo '</div><div class="wpforms-flex-container wpforms-flex-container-end">';

			wpforms_panel_field(
				'text',
				'settings',
				'form_locker_schedule_end_date',
				$instance->form_data,
				esc_html__( 'End Date', 'wpforms-form-locker' ),
				[
					'class'       => 'wpforms-panel-field-datetime wpforms-panel-field-half',
					'input_class' => 'readonly-active wpforms-datepair-date wpforms-datepair-end',
					'after'       => '<button type="button" class="wpforms-clear-datetime-field" title="' . esc_attr__( 'Clear End Date', 'wpforms-form-locker' ) . '"><i class="fa fa-times-circle fa-lg"></i></button>',
				]
			);

			wpforms_panel_field(
				'text',
				'settings',
				'form_locker_schedule_end_time',
				$instance->form_data,
				esc_html__( 'End Time', 'wpforms-form-locker' ),
				[
					'class'       => 'wpforms-panel-field-datetime wpforms-panel-field-half',
					'input_class' => 'wpforms-datepair-time wpforms-datepair-end',
					'after'       => '<button type="button" class="wpforms-clear-datetime-field" title="' . esc_attr__( 'Clear End Time', 'wpforms-form-locker' ) . '"><i class="fa fa-times-circle fa-lg"></i></button>',
				]
			);

			echo '</div></div><!-- End of #wpforms-form-locker-schedule-datetime-block -->';

			wpforms_panel_field(
				'tinymce',
				'settings',
				'form_locker_schedule_message',
				$instance->form_data,
				esc_html__( 'Closed Message', 'wpforms-form-locker' ),
				[
					'tinymce' => [
						'editor_height' => 175,
					],
					'tooltip' => esc_html__( 'This message is displayed to visitors when the form is closed.', 'wpforms-form-locker' ),
				]
			);

			// User locker block.
			$logged = wpforms_panel_field(
				'toggle',
				'settings',
				'form_locker_user_enable',
				$instance->form_data,
				esc_html__( 'Logged in users only', 'wpforms-form-locker' ),
				[],
				false
			);

			wpforms_panel_fields_group(
				$logged,
				[
					'description' => esc_html__( 'Restrict form access to only those who meet your criteria.', 'wpforms-form-locker' ),
					'title'       => esc_html__( 'Form Restrictions', 'wpforms-form-locker' ),
				]
			);

			wpforms_panel_field(
				'tinymce',
				'settings',
				'form_locker_user_message',
				$instance->form_data,
				esc_html__( 'Message', 'wpforms-form-locker' ),
				[
					'tinymce' => [
						'editor_height' => 175,
					],
					'tooltip' => esc_html__( 'This message is displayed to logged out visitors in place of the form.', 'wpforms-form-locker' ),
				]
			);

			// Entry limit locker block.
			$limit = wpforms_panel_field(
				'toggle',
				'settings',
				'form_locker_entry_limit_enable',
				$instance->form_data,
				esc_html__( 'Enable total entry limit', 'wpforms-form-locker' ),
				[],
				false
			);

			wpforms_panel_fields_group(
				$limit,
				[
					'description' => esc_html__( 'Limit the number of entries and restrict by email or IP address.', 'wpforms-form-locker' ),
					'title'       => esc_html__( 'Entry Limits & Restrictions', 'wpforms-form-locker' ),
				]
			);

			wpforms_panel_field(
				'text',
				'settings',
				'form_locker_entry_limit',
				$instance->form_data,
				esc_html__( 'Limit', 'wpforms-form-locker' ),
				[
					'type' => 'number',
					'min'  => 1,
				]
			);

			wpforms_panel_field(
				'tinymce',
				'settings',
				'form_locker_entry_limit_message',
				$instance->form_data,
				esc_html__( 'Closed Message', 'wpforms-form-locker' ),
				[
					'tinymce' => [
						'editor_height' => 175,
					],
					'tooltip' => esc_html__( 'This message is displayed to visitors when the form is closed.', 'wpforms-form-locker' ),
				]
			);

			wpforms_panel_field(
				'toggle',
				'settings',
				'form_locker_user_entry_limit_enable',
				$instance->form_data,
				esc_html__( 'Enable user entry limit', 'wpforms-form-locker' ),
				[
					'tooltip' => esc_html__( 'Limit the number of entries each user can submit. Choose the method of restriction using the checkboxes below.', 'wpforms-form-locker' ),
				]
			);

			$ip_not_allowed = [
				'tooltip'     => esc_html__( 'This option is disabled because due to your GDPR settings you opted to not store IP addresses.', 'wpforms-form-locker' ),
				'input_class' => 'wpforms-disabled',
				'value'       => '0',
			];

			wpforms_panel_field(
				'checkbox',
				'settings',
				'form_locker_user_entry_restrict_by_ip',
				$instance->form_data,
				esc_html__( 'Restrict by IP address', 'wpforms-form-locker' ),
				! wpforms_is_collecting_ip_allowed() ? $ip_not_allowed : []
			);

			wpforms_panel_field(
				'checkbox',
				'settings',
				'form_locker_user_entry_restrict_by_email',
				$instance->form_data,
				esc_html__( 'Restrict by email address', 'wpforms-form-locker' )
			);

			wpforms_panel_field(
				'select',
				'settings',
				'form_locker_user_entry_email_field',
				$instance->form_data,
				esc_html__( 'Email Field', 'wpforms-form-locker' ),
				[
					'options'     => $email_fields,
					'input_class' => 'wpforms-field-map-select',
					'data'        => [
						'field-map-allowed' => 'email',
					],
				]
			);

			echo '<div class="wpforms-flex-container wpforms-flex-container-end wpforms-form-locker-user-entry-container">';

			wpforms_panel_field(
				'text',
				'settings',
				'form_locker_user_entry_limit',
				$instance->form_data,
				esc_html__( 'Limit', 'wpforms-form-locker' ),
				[
					'type'  => 'number',
					'class' => 'wpforms-panel-field-half ',
					'min'   => 1,
				]
			);

			wpforms_panel_field(
				'select',
				'settings',
				'form_locker_user_entry_email_duration',
				$instance->form_data,
				'',
				[
					'options' => [
						'day_start'   => esc_html__( 'till the end of the day', 'wpforms-form-locker' ),
						'week_start'  => esc_html__( 'till the end of the week', 'wpforms-form-locker' ),
						'month_start' => esc_html__( 'till the end of the month', 'wpforms-form-locker' ),
						'year_start'  => esc_html__( 'till the end of the year', 'wpforms-form-locker' ),
					   'last_thursday'  => esc_html__( 'till the last thursday of the month', 'wpforms-form-locker' ),						
						'day'         => esc_html__( 'per 24 hours', 'wpforms-form-locker' ),
						'week'        => esc_html__( 'per 7 days', 'wpforms-form-locker' ),
						'month'       => esc_html__( 'per ~30 days', 'wpforms-form-locker' ),
						'year'        => esc_html__( 'per ~365 days', 'wpforms-form-locker' ),
					],
					'class'   => 'wpforms-panel-field-half ',
				]
			);

			echo '</div>';

			wpforms_panel_field(
				'tinymce',
				'settings',
				'form_locker_user_entry_message_limited',
				$instance->form_data,
				esc_html__( 'Display Message', 'wpforms-form-locker' ),
				[
					'tinymce' => [
						'editor_height' => 175,
					],
					'tooltip' => esc_html__( 'This message is displayed to visitors when the form is closed.', 'wpforms-form-locker' ),
				]
			);

		echo '</div>';
	}

	/**
	 * Get date format for Flatpickr.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	private function get_date_format() {

		$supported_formats = [ 'F j, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y' ];
		$current_format    = get_option( 'date_format' );

		if ( ! in_array( $current_format, $supported_formats, true ) ) {
			return $supported_formats[0];
		}

		return $current_format;
	}

	/**
	 * Get time format for Flatpickr.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	private function get_time_format() {

		$supported_formats = [ 'g:i a', 'g:i A', 'H:i' ];
		$current_format    = get_option( 'time_format' );

		if ( ! in_array( $current_format, $supported_formats, true ) ) {
			return $supported_formats[0];
		}

		return $current_format;
	}
}
