<?php

namespace WPFormsLocker\Lockers;

/**
 * Lock a form if a new entry exceeds an admin-defined entry limit.
 *
 * @since 1.0.0
 */
class EntryLimit extends Locker {

	/**
	 * Locker type.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	protected $type = 'entry_limit';

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

		add_action( 'wpforms_frontend_not_loaded', [ $this, 'locked_html' ], 10, 2 );

		return false;
	}

	/**
	 * On form submit actions.
	 *
	 * @since 1.0.0
	 *
	 * @param array $errors    Form submit errors.
	 * @param array $form_data Form information.
	 *
	 * @return array
	 */
	public function submit_form( $errors, $form_data ) {

		$this->set_form_data( $form_data );

		if ( $this->is_locked() ) {
			$form_id = ! empty( $this->form_data['id'] ) ? $this->form_data['id'] : 0;

			$errors[ $form_id ]['form_locker'] = 'entry_limit';
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

		return ! empty( $this->form_data['settings']['form_locker_entry_limit_message'] ) ? $this->form_data['settings']['form_locker_entry_limit_message'] : '';
	}

	/**
	 * Check if the form has a locker configured.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function has_locker() {

		if ( empty( $this->form_data['settings']['form_locker_entry_limit_enable'] ) ) {
			return false;
		}
		if ( empty( $this->form_data['settings']['form_locker_entry_limit'] ) ) {
			return false;
		}
		if ( (int) $this->form_data['settings']['form_locker_entry_limit'] <= 0 ) {
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

		$reference = ! empty( $this->form_data['settings']['form_locker_entry_limit'] ) ? (int) $this->form_data['settings']['form_locker_entry_limit'] : 0;

		if ( 0 >= $reference ) {
			return false;
		}

		$entries_count = $this->get_unlocking_value();

		if ( $entries_count < $reference ) {
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

		add_filter( 'wpforms_entry_handler_get_entries_where', [ $this, 'exclude_not_allowed_entries' ] );

		$entries = wpforms()->entry->get_entries( [ 'form_id' => $this->form_data['id'] ], true );

		remove_filter( 'wpforms_entry_handler_get_entries_where', [ $this, 'exclude_not_allowed_entries' ] );

		return $entries;
	}

	/**
	 * Exclude abandonment and partial statuses for locked form.
	 *
	 * @since 2.0.0
	 *
	 * @param array $where List of WHERE conditions for entries query.
	 *
	 * @return array
	 */
	public function exclude_not_allowed_entries( $where ) {

		/**
		 * Excluded entry statuses from entry limit locker.
		 *
		 * @since 2.3.0
		 *
		 * @param array<string> $excluded Excluded statuses.
		 *
		 * @return array<string>
		 */
		$excluded = (array) apply_filters( 'wpforms_locker_lockers_entry_limit_exclude_not_allowed_entries_excluded_statuses', [ 'abandoned', 'partial' ] );
		$excluded = array_map( 'esc_sql', $excluded );
		$excluded = implode( "','", $excluded );

		if ( $excluded ) {
			$where[] = "status NOT IN ( '$excluded' )";
		}

		return $where;
	}
}
