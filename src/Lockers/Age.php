<?php

namespace WPFormsLocker\Lockers;

use DateTime;

/**
 * Lock a form using an admin-defined age criteria.
 *
 * @since 2.0.0
 */
class Age extends Locker {

	/**
	 * Locker type.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	protected $type = 'age';

	/**
	 * Locker hooks.
	 *
	 * @since 2.0.0
	 */
	public function hooks() {

		add_filter( 'wpforms_frontend_load', [ $this, 'display_form' ], 10, 2 );
		add_filter( 'wpforms_process_initial_errors', [ $this, 'submit_form' ], 10, 2 );
		add_filter( 'wpforms_conversational_forms_start_button_disabled', [ $this, 'is_locked_filter' ], 10, 2 );
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

		if ( ! $this->is_locked() && $this->get_unlocking_value() > 0 ) {
			add_action( 'wpforms_display_submit_before', [ $this, 'add_age_field' ] );
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
	 * @since 2.0.0
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

			$errors[ $form_id ]['form_locker'] = 'age';
		}

		return $errors;
	}

	/**
	 * Check if the form meets a condition to be locked.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	private function is_locked() {

		if ( ! $this->has_locker() ) {
			return false;
		}

		$age = $this->get_unlocking_value();

		if ( $age < 0 ) {
			return true;
		}

		$reference_age = ! empty( $this->form_data['settings']['form_locker_age'] ) ? absint( $this->form_data['settings']['form_locker_age'] ) : 0;
		$criteria      = ! empty( $this->form_data['settings']['form_locker_age_criteria'] ) ? $this->form_data['settings']['form_locker_age_criteria'] : '';

		// Compare user age with set criteria.
		return ! version_compare( $age, $reference_age, $criteria );
	}

	/**
	 * Check if the form has a locker configured.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	private function has_locker() {

		if ( empty( $this->form_data['settings']['form_locker_verification'] ) ) {
			return false;
		}

		if ( ! empty( $this->form_data['settings']['form_locker_verification_type'] ) && $this->form_data['settings']['form_locker_verification_type'] !== 'age' ) {
			return false;
		}

		if ( ! isset( $this->form_data['settings']['form_locker_age'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the value unlocking the form.
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	public function get_unlocking_value() {

		// phpcs:disable WordPress.Security.NonceVerification
		// In case if we submit the main form with already known age, return the age.
		if ( ! empty( $_POST['wpforms']['form_locker_age'] ) ) {
			return absint( $_POST['wpforms']['form_locker_age'] );
		}

		$date  = '';
		$month = ! empty( $_POST['wpforms']['form_locker_age_month'] ) ? absint( $_POST['wpforms']['form_locker_age_month'] ) : 1;
		$day   = ! empty( $_POST['wpforms']['form_locker_age_day'] ) ? absint( $_POST['wpforms']['form_locker_age_day'] ) : 1;
		$year  = ! empty( $_POST['wpforms']['form_locker_age_year'] ) ? absint( $_POST['wpforms']['form_locker_age_year'] ) : '';
		// phpcs:enable WordPress.Security.NonceVerification

		if ( $month && $day && $year ) {
			$date = $year . '-' . $month . '-' . $day;
			$date = DateTime::createFromFormat( 'Y-m-d', $date );
		}

		return ! empty( $date ) ? absint( $this->count_age( $date ) ) : -1;
	}

	/**
	 * Count birthday from given date.
	 *
	 * @param DateTime $date_of_birth Birthday date.
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	private function count_age( $date_of_birth ) {

		return $date_of_birth->diff( new DateTime() )->y;
	}


	/**
	 * Add a age field to the form to process locked form as normal.
	 *
	 * @since 2.0.0
	 */
	public function add_age_field() {

		$age = $this->get_unlocking_value();

		echo '<input type="hidden" name="wpforms[form_locker_age]" value="' . esc_attr( $age ) . '">';
	}

	/**
	 * Locked form HTML.
	 *
	 * @since 2.0.0
	 * @since 2.3.0 Added form wrapper.
	 */
	public function locked_html() {

		$locked_id     = 'locked-' . $this->form_data['id'];
		$action        = esc_url( remove_query_arg( 'wpforms' ) );
		$message       = $this->get_locked_message();
		$message_error = $this->get_error_message();

		// Add the select birthday form to the frontend forms to make 'Submit' button JS work correctly.
		wpforms()->get( 'frontend' )->forms[ $locked_id ]       = $this->form_data;
		wpforms()->get( 'frontend' )->forms[ $locked_id ]['id'] = $locked_id;

		$this->print_form_wrapper_open();
		?>

			<?php if ( $message ) : ?>
				<div class="form-locked-message"><?php echo wp_kses_post( wpautop( $message ) ); ?></div>
			<?php endif; ?>

			<form id="wpforms-form-<?php echo esc_attr( $locked_id ); ?>" class="wpforms-validate wpforms-form" data-formid="<?php echo esc_attr( $locked_id ); ?>" method="post" enctype="multipart/form-data" action="<?php echo esc_attr( $action ); ?>">
				<div class="wpforms-field-container">
					<div id="wpforms-<?php echo esc_attr( $locked_id ); ?>-age-container" class="wpforms-field wpforms-field-age" data-field-id="form_locker_age" data-field-type="form_locker_age">
						<label class="wpforms-field-label" for="wpforms-<?php echo esc_attr( $locked_id ); ?>-field_form_locker_age">
							<?php esc_html_e( 'Age', 'wpforms-form-locker' ); ?>
							<span class="wpforms-required-label">*</span>
						</label>
						<div class="wpforms-field-row wpforms-field-select">
							<?php $this->date_selectors_html(); ?>
						</div>
						<?php if ( ! empty( $message_error ) ) : ?>
							<label class="wpforms-error"><?php echo wp_kses_post( $message_error ); ?></label>
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
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_locked_message() {

		return ! empty( $this->form_data['settings']['form_locker_age_message'] ) ? $this->form_data['settings']['form_locker_age_message'] : '';
	}

	/**
	 * Display error message if age does not meet the condition.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_error_message() {

		$locker_form_id = ! empty( $_POST['wpforms']['form_locker_form_id'] ) ? absint( $_POST['wpforms']['form_locker_form_id'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( $locker_form_id !== (int) $this->form_data['id'] ) {
			return false;
		}

		return apply_filters( 'wpforms_form_locker_age_get_error_message', esc_html__( 'Your age does not meet the criteria.', 'wpforms-form-locker' ), $this->form_data['id'], $this->form_data );
	}

	/**
	 * Generate date selectors from determined date format.
	 *
	 * @since 2.0.0
	 */
	private function date_selectors_html() {

		$output               = '';
		$default_date_formats = [
			'month' => [ 'F', 'm' ],
			'day'   => [ 'j', 'd' ],
			'year'  => [ 'Y', 'y' ],
		];
		$default_format       = get_option( 'date_format' ) ? get_option( 'date_format' ) : 'F j, Y';

		// Remove dividers form date format string.
		$format = str_split( preg_replace( '/\W/', '', $default_format ) );

		$ranges = $this->get_date_ranges();

		foreach ( $format as $key => $value ) {
			$key = $key === 0 ? 'wpforms-first' : '';

			if ( in_array( $value, $default_date_formats['year'], true ) ) {
				$output .= $this->option_template_html( $ranges['year'], $key, __( 'Year', 'wpforms-form-locker' ) );
			} elseif ( in_array( $value, $default_date_formats['month'], true ) ) {
				$output .= $this->option_template_html( $ranges['month'], $key, __( 'Month', 'wpforms-form-locker' ) );
			} elseif ( in_array( $value, $default_date_formats['day'] , true ) ) {
				$output .= $this->option_template_html( $ranges['day'], $key, __( 'Day', 'wpforms-form-locker' ) );
			}
		}

		printf( $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * HTML template for date selectors rendering.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $ranges Option's value ranges.
	 * @param string $key    CSS class for the first select of range.
	 * @param string $label  Option placeholder value.
	 *
	 * @return string
	 */
	private function option_template_html( $ranges, $key, $label ) {

		$output  = sprintf( '<div class="wpforms-field-row-block wpforms-one-fifth %s"><select class="wpforms-field-required" name="wpforms[form_locker_age_%s]" required>', $key, strtolower( $label ) );
		$output .= sprintf( '<option value="" class="placeholder" selected disabled>%s</option>', $label );

		foreach ( $ranges as $item ) {
			$output .= sprintf(
				'<option value="%d">%d</option>',
				$item,
				$item
			);
		}
		$output .= '</select></div>';

		return $output;
	}

	/**
	 * Return date ranges by month, days, years.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function get_date_ranges() {

		return [
			'month' => range( 1, 12 ),
			'day'   => range( 1, 31 ),
			'year'  => range( gmdate( 'Y' ), 1920 ),
		];
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
}
