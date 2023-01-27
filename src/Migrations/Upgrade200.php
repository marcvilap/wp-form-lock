<?php

namespace WPFormsLocker\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPFormsLocker\Tasks\PasswordOptionUpgradeTask;

/**
 * Class Form Locker addon v2.0.0 upgrade.
 *
 * @since 2.2.0
 *
 * @noinspection PhpUnused
 */
class Upgrade200 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * @since 2.2.0
	 * @since 2.3.0 Use AS task for the migration.
	 *
	 * @return bool|null Upgrade result:
	 *                   true  - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null  - upgrade started but not yet finished (background task).
	 */
	public function run() {

		if ( ! wpforms_form_locker()->email_storage->table_exists() ) {
			wpforms_form_locker()->email_storage->create_table();
		}

		return $this->run_async( PasswordOptionUpgradeTask::class );
	}
}
