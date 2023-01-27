<?php

namespace WPFormsLocker\Tasks;

use WPForms\Tasks\Meta;
use WPForms\Tasks\Task;
use WPForms\Tasks\Tasks;

/**
 * Class PasswordOptionUpgradeTask.
 *
 * @since 2.3.0
 */
class PasswordOptionUpgradeTask extends Task {

	/**
	 * Async task action.
	 *
	 * @since 2.3.0
	 */
	const ACTION = 'wpforms_form_locker_process_password_option_upgrade';

	/**
	 * Status option name.
	 *
	 * @since 2.3.0
	 */
	const STATUS = 'wpforms_form_locker_process_password_option_upgrade_status';

	/**
	 * Start status.
	 *
	 * @since 2.3.0
	 */
	const START = 'start';

	/**
	 * In progress status.
	 *
	 * @since 2.3.0
	 */
	const IN_PROGRESS = 'in progress';

	/**
	 * Completed status.
	 *
	 * @since 2.3.0
	 */
	const COMPLETED = 'completed';

	/**
	 * Chunk size to use.
	 *
	 * Specifies how many forms to process on each request.
	 *
	 * @since 2.3.0
	 */
	const CHUNK_SIZE = 50;

	/**
	 * Class constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Initialize the task with all the proper checks.
	 *
	 * @since 2.3.0
	 */
	public function init() {

		$status = get_option( self::STATUS );

		if ( ! $status || $status === self::COMPLETED ) {
			return;
		}

		$this->hooks();

		// Add the migration tasks..
		$tasks = wpforms()->get( 'tasks' );

		if ( $status === self::IN_PROGRESS || $tasks->is_scheduled( self::ACTION ) !== false ) {
			return;
		}

		$this->init_migration( $tasks );

		// Mark that migration is in progress.
		update_option( self::STATUS, self::IN_PROGRESS );
	}

	/**
	 * Add hooks.
	 *
	 * @since 2.3.0
	 */
	private function hooks() {

		add_action( self::ACTION, [ $this, 'migrate' ] );
		add_action( 'action_scheduler_after_process_queue', [ $this, 'after_process_queue' ] );
	}

	/**
	 * Migrate a form.
	 *
	 * @since 2.3.0
	 *
	 * @param int $meta_id Action meta id.
	 */
	public function migrate( $meta_id ) {

		$params = ( new Meta() )->get( $meta_id );

		if ( ! $params ) {
			return;
		}

		list( $chunk ) = $params->data;

		if ( empty( $chunk ) ) {
			return;
		}

		foreach ( $chunk as $form_id ) {
			$this->password_option_upgrade( $form_id );
		}
	}

	/**
	 * Upgrade password option of a form.
	 *
	 * @since 2.3.0
	 *
	 * @param int $form_id Form ID.
	 */
	private function password_option_upgrade( $form_id ) {

		$form = wpforms()->get( 'form' )->get( $form_id, [ 'cap' => false ] );

		if ( empty( $form ) ) {
			return;
		}

		$form_data = wpforms_decode( $form->post_content );

		if ( empty( $form_data['settings']['form_locker_password_enable'] ) ) {
			return;
		}

		$form_data['settings'] = array_replace_recursive(
			$form_data['settings'],
			[
				'form_locker_verification'      => 1,
				'form_locker_verification_type' => 'password',
				'form_locker_password_enable'   => '',
			]
		);

		wpforms()->get( 'form' )->update( $form->ID, $form_data, [ 'cap' => false ] );
	}

	/**
	 * Init migration.
	 *
	 * @since 2.3.0
	 *
	 * @param Tasks $tasks Tasks class instance.
	 */
	private function init_migration( $tasks ) {

		$forms = $this->get_forms_to_migrate();

		if ( empty( $forms ) ) {
			return;
		}

		$form_chunks = array_chunk( $forms, self::CHUNK_SIZE, true );
		$count       = count( $form_chunks );

		foreach ( $form_chunks as $index => $chunk ) {
			$tasks->create( self::ACTION )->async()->params( $chunk, $index, $count )->register();
		}
	}

	/**
	 * Get the IDs of the forms to be migrated.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	private function get_forms_to_migrate() {

		global $wpdb;

		$ids = $wpdb->get_col(
			"SELECT ID FROM `wp_posts` WHERE `post_content` LIKE '%form_locker_password%' AND `post_type` = 'wpforms'"
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * After process queue action.
	 *
	 * Update the task status to completed when all tasks are done.
	 *
	 * @since 2.3.0
	 */
	public function after_process_queue() {

		$tasks = wpforms()->get( 'tasks' );

		if ( $tasks->is_scheduled( self::ACTION ) || get_option( self::STATUS ) !== self::IN_PROGRESS ) {
			return;
		}

		update_option( self::STATUS, self::COMPLETED );
	}
}
