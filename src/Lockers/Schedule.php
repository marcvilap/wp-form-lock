<?php

namespace WPFormsLocker\Lockers;

/**
 * Lock a form if a user is not logged in.
 *
 * @since 1.0.0
 */
class Schedule extends Locker {

	/**
	 * Locker type.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	protected $type = 'schedule';

	/**
	 * Locker hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		add_filter( 'wpforms_frontend_load', [ $this, 'display_form' ], 10, 2 );
		add_filter( 'wpforms_process_initial_errors', [ $this, 'submit_form' ], 10, 2 );
		add_filter( 'wpforms_conversational_forms_start_button_disabled', [ $this, 'is_locked_filter' ], 10, 2 );
	}

	/**
	 * On form display actions.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $load_form Indicates whether a form should be loaded.
	 * @param array $form_data Form information.
	 *
	 * @return bool
	 */
	public function display_form( $load_form, $form_data ) {

		$this->set_form_data( $form_data );

		if ( ! $this->is_locked() ) {
			return $load_form;
		}

		\add_action( 'wpforms_frontend_not_loaded', array( $this, 'locked_html' ), 10, 2 );

		return false;
	}

	/**
	 * On form submit actions.
	 *
	 * @since 1.0.0
	 *
	 * @param array $errors Form submit errors.
	 * @param array $form_data Form information.
	 *
	 * @return array
	 */
	public function submit_form( $errors, $form_data ) {

		$this->set_form_data( $form_data );

		if ( $this->is_locked() ) {
			$form_id = ! empty( $this->form_data['id'] ) ? $this->form_data['id'] : 0;

			$errors[ $form_id ]['form_locker'] = 'schedule';
		}

		return $errors;
	}

	/**
	 * Locked form HTML.
	 *
	 * @since 1.0.0
	 * @since 2.3.0 Added form wrapper.
	 */
	public function locked_html() {

		$message = $this->get_locked_message();

		if ( ! $message ) {
			return;
		}

		$this->print_form_wrapper_open();
		printf( '<div class="form-locked-message">%s</div>', wp_kses_post( wpautop( $message ) ) );
		$this->print_form_wrapper_close();
	}


	/**
	 * Get locked form message from an admin area.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_locked_message() {

		return ! empty( $this->form_data['settings']['form_locker_schedule_message'] ) ? $this->form_data['settings']['form_locker_schedule_message'] : '';
	}

	/**
	 * Check if the form has a locker configured.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function has_locker() {

		if ( empty( $this->form_data['settings']['form_locker_schedule_enable'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the form meets a condition to be locked.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_locked() {

		if ( ! $this->has_locker() ) {
			return false;
		}

		$start_date = ! empty( $this->form_data['settings']['form_locker_schedule_start_date'] ) ? $this->form_data['settings']['form_locker_schedule_start_date'] : '';
		$end_date   = ! empty( $this->form_data['settings']['form_locker_schedule_end_date'] ) ? $this->form_data['settings']['form_locker_schedule_end_date'] : '';

		// Flatpickr is set to store the date in a hardcoded 'Y-m-d' format
		// jQuery timepicker fetches a time format from WP settings.
		$format  = 'Y-m-d ';
		$format .= get_option( 'time_format' ) ? get_option( 'time_format' ) : 'g:i a';

		if ( $start_date ) {
			$start_time = ! empty( $this->form_data['settings']['form_locker_schedule_start_time'] ) ? $this->form_data['settings']['form_locker_schedule_start_time'] : '';
			$start      = date_create_from_format( $format, gmdate( $format, strtotime( $start_date . $start_time ) ) );
		}

		if ( $end_date ) {
			$end_time = ! empty( $this->form_data['settings']['form_locker_schedule_end_time'] ) ? $this->form_data['settings']['form_locker_schedule_end_time'] : '';
			$end      = date_create_from_format( $format, gmdate( $format, strtotime( $end_date . $end_time ) ) );
		}

		if ( empty( $start ) && empty( $end ) ) {
			return false;
		}

		$current = \date_create( $this->get_unlocking_value() );

		if ( ! empty( $start ) && empty( $end ) && $start < $current ) {
			return false;
		}

		if ( ! empty( $end ) && empty( $start ) && $end > $current ) {
			return false;
		}

		if ( ! empty( $start ) && ! empty( $end ) && $start < $current && $end > $current ) {
			return false;
		}

		return true;
	}

	/**
	 * Filter locked state.
	 *
	 * @since 2.0.0
	 *
	 * @param bool  $locked    Locked state.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function is_locked_filter( $locked, $form_data ) {

		$this->set_form_data( $form_data );

		return $this->is_locked() ? true : $locked;
	}

	/**
	 * Get the value unlocking the form.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_unlocking_value() {

		return \current_time( 'mysql' );
	}
}
