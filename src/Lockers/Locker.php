<?php

namespace WPFormsLocker\Lockers;

/**
 * Locker class.
 *
 * @since 2.3.0
 */
abstract class Locker {

	/**
	 * Current form information.
	 *
	 * @since 2.3.0
	 *
	 * @var array $form_data Form data.
	 */
	public $form_data;

	/**
	 * Locker type.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Init.
	 *
	 * @since 2.3.0
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Locker hooks.
	 *
	 * @since 2.3.0
	 */
	abstract public function hooks();

	/**
	 * Set current form information for internal use.
	 *
	 * @since 2.3.0
	 *
	 * @param array $form_data Form information.
	 */
	protected function set_form_data( $form_data ) {

		$this->form_data = $form_data;
	}

	/**
	 * Get form classes.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	private function get_form_classes() {

		/**
		 * Modify locker container classes.
		 *
		 * @since 2.3.0
		 *
		 * @param array $classes   Array of classes.
		 * @param array $form_data Form data.
		 */
		return (array) apply_filters( 'wpforms_locker_lockers_locker_get_form_classes', [ sprintf( 'wpforms-%s-locked', $this->type ) ], $this->form_data );
	}

	/**
	 * Print opening tag for the form wrapper.
	 *
	 * @since 2.3.0
	 */
	protected function print_form_wrapper_open() {

		printf(
			'<div class="%s" id="wpforms-locked-%d">',
			wpforms_sanitize_classes( $this->get_form_classes(), true ),
			absint( $this->form_data['id'] )
		);
	}

	/**
	 * Print ending tag for the form wrapper.
	 *
	 * @since 2.3.0
	 */
	protected function print_form_wrapper_close() {

		echo '</div>';
	}
}
