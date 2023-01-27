<?php

namespace WPFormsLocker;

use WPFormsLocker\Db\EmailStorage;

/**
 * WPForms Form Locker loader class.
 *
 * @since 1.0.0
 */
final class Loader {

	/**
	 * Have the only available instance of the class.
	 *
	 * @var Loader
	 *
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * URL to a plugin directory. Used for assets.
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	public $url = '';

	/**
	 * Database class.
	 *
	 * @since 2.0.0
	 *
	 * @var \WPFormsLocker\Db\EmailStorage
	 */
	public $email_storage;

	/**
	 * Initiate main plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Loader
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) || ! ( self::$instance instanceof self ) ) {
			self::$instance = ( new self() )->init();
		}

		return self::$instance;
	}

	/**
	 * All the actual plugin loading is done here.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->url = plugin_dir_url( __DIR__ );

		( new Install() )->init();

		$this->hooks();

		return $this;
	}

	/**
	 * Plugin hooks.
	 *
	 * @since 2.0.0
	 */
	private function hooks() {

		add_action( 'wpforms_loaded', [ $this, 'setup' ], 15 );
		add_action( 'wpforms_updater', [ $this, 'updater' ] );
	}

	/**
	 * Load database functionality.
	 *
	 * @since 2.0.0
	 */
	private function load_database() {

		$this->email_storage = new EmailStorage();
	}

	/**
	 * All the actual plugin loading is done here.
	 *
	 * @since 1.0.0
	 */
	public function setup() {

		$this->load_database();

		// Run used migrations.
		( new Migrations\Migrations() )->init();

		// Load Lockers classes.
		( new Lockers\Password() )->init();
		( new Lockers\Age() )->init();
		( new Lockers\Email() )->init();
		( new Lockers\Schedule() )->init();
		( new Lockers\EntryLimit() )->init();
		( new Lockers\User() )->init();
		( new Lockers\UserEntryLimit() )->init();
		( new Lockers\UniqueAnswer() )->init();

		if ( wpforms_is_admin_page( 'builder' ) || Lockers\UniqueAnswer::is_unique_answer_enabled_new_field_ajax() ) {
			( new Admin() )->init();
		}
	}

	/**
	 * Load the plugin updater.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key License key.
	 */
	public function updater( $key ) {

		new \WPForms_Updater(
			[
				'plugin_name' => 'WPForms Form Locker',
				'plugin_slug' => 'wpforms-form-locker',
				'plugin_path' => plugin_basename( WPFORMS_FORM_LOCKER_FILE ),
				'plugin_url'  => trailingslashit( $this->url ),
				'remote_url'  => WPFORMS_UPDATER_API,
				'version'     => WPFORMS_FORM_LOCKER_VERSION,
				'key'         => $key,
			]
		);
	}
}
