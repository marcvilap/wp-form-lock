<?php

namespace WPFormsLocker\Lockers;

use WPForms\Emails\Mailer;
use WPForms\Helpers\Crypto;
use WPForms\Emails\Templates\General;

/**
 * Lock a form with email verification.
 *
 * @since 2.0.0
 */
class Email extends Locker {

	/**
	 * Locker type.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	protected $type = 'email';

	/**
	 * Identify where form is locked/unlocked.
	 *
	 * @since 2.0.0
	 *
	 * @var bool $locked True/False switch.
	 */
	private $locked = true;

	/**
	 * Email hooks.
	 *
	 * @since 2.0.0
	 */
	public function hooks() {

		add_action( 'wpforms_frontend_output_before', [ $this, 'get_email_verified' ] );
		add_filter( 'wpforms_frontend_load', [ $this, 'display_form' ], 10, 2 );
		add_filter( 'wpforms_process_initial_errors', [ $this, 'submit_form' ], 10, 2 );
		add_filter( 'wpforms_conversational_forms_start_button_disabled', [ $this, 'is_locked_filter' ], 10, 2 );

		// Clean email locker entries if form is deleted.
		add_action( 'wpforms_delete_form', [ $this, 'delete_form_related_records' ] );
	}

	/**
	 * On form submit actions.
	 *
	 * @since 2.0.0
	 *
	 * @param array $errors    Form submit errors.
	 * @param array $form_data Form information.
	 *
	 * @return array
	 */
	public function submit_form( $errors, $form_data ) {

		$this->set_form_data( $form_data );

		// Check if entry has email locker key identifier.
		$locked = isset( $_POST['wpforms']['form_locker_email'] ) ? absint( $_POST['wpforms']['form_locker_email'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $locked && $this->is_locked() ) {
			$form_id = ! empty( $this->form_data['id'] ) ? $this->form_data['id'] : 0;

			$errors[ $form_id ]['form_locker'] = 'email';
		}

		return $errors;
	}

	/**
	 * On form display actions.
	 *
	 * @since 2.0.0
	 *
	 * @param bool  $load_form Indicates whether a form should be loaded.
	 * @param array $form_data Form information.
	 *
	 * @return bool
	 */
	public function display_form( $load_form, $form_data ) {

		$this->set_form_data( $form_data );

		if ( ! $this->is_locked() && $this->has_locker() ) {
			add_action( 'wpforms_display_submit_before', [ $this, 'add_email_field' ] );

			return $load_form;
		}

		if ( ! $this->is_locked() ) {
			return $load_form;
		}

		add_action( 'wpforms_frontend_not_loaded', [ $this, 'locked_html' ], 10, 2 );
		add_action( 'wpforms_frontend_not_loaded', [ $this, 'add_entry' ], 10, 2 );

		return false;
	}

	/**
	 * Check if the form has a locker configured.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	protected function has_locker() {

		if ( empty( $this->form_data['settings']['form_locker_verification'] ) ) {
			return false;
		}

		if ( $this->form_data['settings']['form_locker_verification_type'] !== 'email' ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if form is locked by conditions.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	private function is_locked() {

		if ( ! $this->has_locker() ) {
			return false;
		}

		if ( ! $this->locked ) {
			return false;
		}

		return true;
	}

	/**
	 * Locked form HTML.
	 *
	 * @since 2.0.0
	 * @since 2.3.0 Added form wrapper.
	 */
	public function locked_html() {

		$locked_id          = 'locked-' . $this->form_data['id'];
		$action             = esc_url( remove_query_arg( 'wpforms' ) );
		$message            = $this->get_locked_message();
		$email_sent_message = $this->get_email_sent_message();

		// Add the email form to the frontend forms to make 'Submit' button JS work correctly.
		wpforms()->get( 'frontend' )->forms[ $locked_id ]       = $this->form_data;
		wpforms()->get( 'frontend' )->forms[ $locked_id ]['id'] = $locked_id;

		$this->print_form_wrapper_open();
		?>

			<?php
			// Displayed in case of successfully sent confirmation message.
			if ( ! empty( $email_sent_message ) ) {
				$this->print_confirmation_message( $email_sent_message );
			} else {
				if ( $message ) {
					?>
					<div class="form-locked-message"><?php echo wp_kses_post( wpautop( $message ) ); ?></div>
					<?php
				}
				// phpcs:disable WPForms.PHP.ValidateHooks.InvalidHookName
				/**
				 * Allow modifying submit button text.
				 *
				 * @since 2.0.0
				 *
				 * @param string $button_text Submit button text.
				 * @param int    $form_id     Form ID.
				 * @param array  $form_data   Form data and settings.
				 */
				$button_text = (string) apply_filters( 'wpforms_form_locker_submit_label', __( 'Submit', 'wpforms-form-locker' ), $this->form_data['id'], $this->form_data );
				// phpcs:enable WPForms.PHP.ValidateHooks.InvalidHookName
				?>
				<form id="wpforms-form-<?php echo esc_attr( $locked_id ); ?>" class="wpforms-validate wpforms-form" data-formid="<?php echo esc_attr( $locked_id ); ?>" method="post" enctype="multipart/form-data" action="<?php echo esc_attr( $action ); ?>">
					<div class="wpforms-field-container">
						<div id="wpforms-<?php echo esc_attr( $locked_id ); ?>-field_form_locker_email-container" class="wpforms-field wpforms-field-email" data-field-id="form_locker_email" data-field-type="form_locker_email">
							<label class="wpforms-field-label" for="wpforms-<?php echo esc_attr( $locked_id ); ?>-field_form_locker_email">
								<?php esc_html_e( 'Email', 'wpforms-form-locker' ); ?>
								<span class="wpforms-required-label">*</span>
							</label>
							<input type="email" pattern="/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/" id="wpforms-<?php echo esc_attr( $locked_id ); ?>-field_form_locker_email" class="wpforms-field-medium wpforms-field-required" name="wpforms[form_locker_email]" required>
						</div>
					</div>

					<input type="hidden" name="wpforms[form_locker_form_id]" value="<?php echo absint( $this->form_data['id'] ); ?>">

					<div class="wpforms-submit-container">
						<button type="submit" name="wpforms[submit]" class="wpforms-submit" id="wpforms-submit-<?php echo esc_attr( $locked_id ); ?>" value="wpforms-submit" data-alt-text="<?php esc_html_e( 'Sending...', 'wpforms-form-locker' ); ?>">
							<?php echo esc_html( $button_text ); ?>
						</button>
					</div>
				</form>
			<?php
			}
		$this->print_form_wrapper_close();
	}

	/**
	 * Get locked form message from an admin area.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_locked_message() {

		return ! empty( $this->form_data['settings']['form_locker_email_message'] ) ? $this->form_data['settings']['form_locker_email_message'] : '';
	}

	/**
	 * Display successfully sent message after email verification link is sent.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_email_sent_message() {

		// Check for email and form id.
		$email          = ! empty( $_POST['wpforms']['form_locker_email'] ) ? sanitize_email( wp_unslash( $_POST['wpforms']['form_locker_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$locker_form_id = ! empty( $_POST['wpforms']['form_locker_form_id'] ) ? absint( $_POST['wpforms']['form_locker_form_id'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( $locker_form_id !== (int) $this->form_data['id'] ) {
			return false;
		}

		if ( ! $email ) {
			return false;
		}

		return apply_filters( 'wpforms_form_locker_email_sent_message', esc_html__( 'Please check your email inbox for the verification link.', 'wpforms-form-locker' ) );
	}

	/**
	 * Add new email verification entry.
	 *
	 * @since 2.0.0
	 */
	public function add_entry() {

		if ( empty( $_POST['wpforms']['form_locker_email'] ) && empty( $_POST['wpforms']['form_locker_form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		if ( $_POST['wpforms']['form_locker_form_id'] !== $this->form_data['id'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$email = sanitize_email( wp_unslash( $_POST['wpforms']['form_locker_email'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! is_email( $email ) ) {
			return;
		}

		$form_id = absint( $_POST['wpforms']['form_locker_form_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$row     = wpforms_form_locker()->email_storage->get_row(
			[
				'form_id' => $form_id,
				'email'   => $email,
			]
		);

		$args = [
			'form_id'    => $form_id,
			'email'      => $email,
			'created_at' => gmdate( 'Y-m-d H:i:s' ),
			'date_used'  => '',
		];

		if ( ! empty( $row ) ) {
			wpforms_form_locker()->email_storage->update(
				$row->id,
				$args
			);
		} else {
			wpforms_form_locker()->email_storage->add( $args );
		}

		// Sent email to user.
		$this->send_email( $email , $form_id );
	}

	/**
	 * Send verification email.
	 *
	 * @since 2.0.0
	 *
	 * @param string $email   Email for verification.
	 * @param int    $form_id Form ID.
	 */
	private function send_email( $email, $form_id ) {

		$hash              = $this->generate_hash( $form_id, $email );
		$refer_link        = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$refer_link        = remove_query_arg( 'wpforms_locker_email_verification_token' , $refer_link );
		$verification_link = esc_url( add_query_arg( 'wpforms_locker_email_verification_token', $hash, $refer_link ) );
		$subject           = apply_filters( 'wpforms_form_locker_email_send_email_subject', esc_html__( 'Email address confirmation', 'wpforms-form-locker' ) );
		$text              = sprintf(
			'<p> %1$s <a href="' . $refer_link . '">' . $refer_link . '</a></p><br/>'
			. '<p> %2$s <a href="' . $verification_link . '"> %3$s </a> %4$s </p><br/>'
			. '<p> %5$s ' . $verification_link . '</p>',
			esc_html__( 'Email address confirmation is required to access the form at', 'wpforms-form-locker' ),
			esc_html__( 'To confirm your email address and access the form, please ', 'wpforms-form-locker' ),
			esc_html__( 'visit this link to', 'wpforms-form-locker' ),
			esc_html__( 'verify your email address', 'wpforms-form-locker' ),
			esc_html__( 'Alternately, you can copy and paste the following URL in your browser: ', 'wpforms-form-locker' )
		);
		$message           = apply_filters( 'wpforms_form_locker_email_send_email_message', $text );

		// Use default email template.
		$template = new General( $message );

		( new Mailer() )
			->subject( $subject )
			->template( $template )
			->to_email( $email )
			->send();
	}

	/**
	 * Generate verification token.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id Form ID.
	 * @param string $email   Email for verification.
	 *
	 * @return string
	 */
	private function generate_hash( $form_id, $email ) {

		return Crypto::encrypt( $form_id . '###' . $email );
	}

	/**
	 * Process email verification.
	 *
	 * @since 2.0.0
	 */
	public function get_email_verified() {

		if ( ! isset( $_GET['wpforms_locker_email_verification_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$has_record = false;
		$token      = ! empty( $_GET['wpforms_locker_email_verification_token'] ) ? $_GET['wpforms_locker_email_verification_token'] : ''; // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		$token      = str_replace( ' ', '+', $token );
		$token      = explode( '###', Crypto::decrypt( $token ) );

		// Check if email exists.
		if ( is_array( $token ) && count( $token ) === 2 ) {
			$has_record = wpforms_form_locker()->email_storage->get_row(
				[
					'form_id' => $token[0],
					'email'   => $token[1],
				]
			);
		}
		if ( $has_record ) {
			wpforms_form_locker()->email_storage->update( $has_record->id, [ 'date_used' => gmdate( 'Y-m-d H:i:s' ) ] );
			$this->locked = false;
		}
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
	 * Add a email field to the form to process locked form as normal.
	 *
	 * @since 2.0.0
	 */
	public function add_email_field() {

		echo '<input type="hidden" name="wpforms[form_locker_email]" value="1">';
	}

	/**
	 * Clean records that related to the deleted form.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form_ids The collection with form IDs, which were deleted.
	 */
	public function delete_form_related_records( $form_ids ) {

		foreach ( $form_ids as $form_id ) {

			// If the user doesn't have permissions - the form wasn't deleted. So, nothing to do.
			if ( wpforms_current_user_can( 'delete_form_single', $form_id ) ) {
				wpforms_form_locker()->email_storage->delete_by( 'form_id', $form_id );
			}
		}
	}

	/**
	 * Successfully sent confirmation message.
	 *
	 * @since 2.3.0
	 *
	 * @param string $email_sent_message Successful sent message text.
	 */
	private function print_confirmation_message( $email_sent_message ) {

		$class = (int) wpforms_setting( 'disable-css', '1' ) === 1 ? 'wpforms-confirmation-container-full' : 'wpforms-confirmation-container';

		printf(
			'<div class="%s" id="wpforms-confirmation-%d">%s</div>',
			wpforms_sanitize_classes( $class ),
			absint( $this->form_data['id'] ),
			wp_kses_post( $email_sent_message )
		);
	}
}
