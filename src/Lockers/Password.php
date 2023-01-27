<?php

namespace WPFormsLocker\Lockers;

/**
 * Lock a form using an admin-defined password.
 *
 * @since 1.0.0
 */
class Password extends Locker {

	/**
	 * Locker type.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	protected $type = 'password';

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

		if ( ! $this->is_locked() && $this->get_unlocking_value() ) {
			add_action( 'wpforms_display_submit_before', [ $this, 'add_password_field' ] );
		}

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

			$errors[ $form_id ]['form_locker'] = 'password';
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

		$locked_id       = 'locked-' . $this->form_data['id'];
		$action          = esc_url( remove_query_arg( 'wpforms' ) );
		$unlocking_value = $this->get_unlocking_value();

		// Add the password form to the frontend forms to make 'Submit' button JS work correctly.
		wpforms()->get( 'frontend' )->forms[ $locked_id ]       = $this->form_data;
		wpforms()->get( 'frontend' )->forms[ $locked_id ]['id'] = $locked_id;

		$this->print_form_wrapper_open();
		?>

			<?php if ( $message ) : ?>
				<div class="form-locked-message"><?php echo wp_kses_post( wpautop( $message ) ); ?></div>
			<?php endif; ?>

			<form id="wpforms-form-<?php echo esc_attr( $locked_id ); ?>" class="wpforms-validate wpforms-form" data-formid="<?php echo esc_attr( $locked_id ); ?>" method="post" enctype="multipart/form-data" action="<?php echo esc_attr( $action ); ?>">

				<div class="wpforms-field-container">
					<div id="wpforms-<?php echo esc_attr( $locked_id ); ?>-field_form_locker_password-container" class="wpforms-field wpforms-field-password" data-field-id="form_locker_password" data-field-type="form_locker_password">
						<label class="wpforms-field-label" for="wpforms-<?php echo esc_attr( $locked_id ); ?>-field_form_locker_password">
							<?php esc_html_e( 'Password', 'wpforms-form-locker' ); ?>
							<span class="wpforms-required-label">*</span>
						</label>
						<input type="password" id="wpforms-<?php echo esc_attr( $locked_id ); ?>-field_form_locker_password" class="wpforms-field-medium wpforms-field-required" name="wpforms[form_locker_password]" required>
						<?php if ( ! empty( $unlocking_value ) ) : ?>
							<label class="wpforms-error" for="wpforms-<?php echo esc_attr( $locked_id ); ?>-field_form_locker_password"><?php esc_html_e( 'The password is incorrect.', 'wpforms-form-locker' ); ?></label>
						<?php endif; ?>
					</div>
				</div>

				<input type="hidden" name="wpforms[form_locker_form_id]" value="<?php echo absint( $this->form_data['id'] ); ?>">

				<div class="wpforms-submit-container">
					<button type="submit" name="wpforms[submit]" class="wpforms-submit" id="wpforms-submit-<?php echo esc_attr( $locked_id ); ?>" value="wpforms-submit" data-alt-text="<?php esc_html_e( 'Sending...', 'wpforms-form-locker' ); ?>">
						<?php echo esc_html( apply_filters( 'wpforms_form_locker_submit_label', __( 'Submit', 'wpforms-form-locker' ), $this->form_data['id'], $this->form_data ) ); ?>
					</button>
				</div>

			</form>
		<?php

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

		return ! empty( $this->form_data['settings']['form_locker_password_message'] ) ? $this->form_data['settings']['form_locker_password_message'] : '';
	}

	/**
	 * Check if the form has a locker configured.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function has_locker() {

		// Backward compatibility for v1.2.3.
		if ( ! empty( $this->form_data['settings']['form_locker_password_enable'] ) ) {
			return true;
		}

		if ( empty( $this->form_data['settings']['form_locker_verification'] ) ) {
			return false;
		}
		if ( $this->form_data['settings']['form_locker_verification_type'] !== 'password' ) {
			return false;
		}
		if ( empty( $this->form_data['settings']['form_locker_password'] ) ) {
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

		$password  = $this->get_unlocking_value();
		$reference = ! empty( $this->form_data['settings']['form_locker_password'] ) ? $this->form_data['settings']['form_locker_password'] : '';

		if ( $reference === $password ) {
			return false;
		}

		if ( wp_create_nonce( $reference ) === $password ) {
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

		$form_id = ! empty( $_POST['wpforms']['form_locker_form_id'] ) ? absint( $_POST['wpforms']['form_locker_form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( empty( $form_id ) ) {
			$form_id = ! empty( $_POST['wpforms']['id'] ) ? absint( $_POST['wpforms']['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( absint( $this->form_data['id'] ) !== $form_id ) {
			return '';
		}

		return $this->get_unsanitized_password();
	}

	/**
	 * Get a non-sanitized submitted form password.
	 * Use with caution.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_unsanitized_password() {

		return ! empty( $_POST['wpforms']['form_locker_password'] ) ? $_POST['wpforms']['form_locker_password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
	}

	/**
	 * Add a password field to the form to process locked form as normal.
	 *
	 * @since 1.0.0
	 */
	public function add_password_field() {

		$password = wp_create_nonce( $this->get_unlocking_value() );

		echo '<input type="hidden" name="wpforms[form_locker_password]" value="' . esc_attr( $password ) . '">';
	}
}
