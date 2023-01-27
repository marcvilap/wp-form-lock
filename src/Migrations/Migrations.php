<?php

namespace WPFormsLocker\Migrations;

use WPForms\Migrations\Base;

/**
 * Class Migrations handles addon upgrade routines.
 *
 * @since 2.1.0
 */
class Migrations extends Base {

	/**
	 * WP option name to store the migration versions.
	 *
	 * @since 2.1.0
	 */
	const MIGRATED_OPTION_NAME = 'wpforms_form_locker_versions';

	/**
	 * Current plugin version.
	 *
	 * @since 2.1.0
	 */
	const CURRENT_VERSION = WPFORMS_FORM_LOCKER_VERSION;

	/**
	 * Name of plugin used in log messages.
	 *
	 * @since 2.1.0
	 */
	const PLUGIN_NAME = 'WPForms Form Locker';

	/**
	 * Upgrade classes.
	 *
	 * @since 2.1.0
	 */
	const UPGRADE_CLASSES = [
		'Upgrade200',
	];
}
