<?php

namespace WPFormsLocker;

use WPFormsLocker\Migrations\Migrations;

/**
 * Form Locker addon install.
 *
 * @since 2.0.0
 */
class Install {

	/**
	 * Initialize.
	 *
	 * @since 2.0.0
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Install hooks.
	 *
	 * @since 2.0.0
	 */
	public function hooks() {

		add_action( 'wpmu_new_blog', [ $this, 'new_multisite_blog' ] );
	}

	/**
	 * Perform certain actions on plugin activation.
	 *
	 * @since 2.0.0
	 * @deprecated 2.2.0
	 *
	 * @param bool $network_wide Whether to enable the plugin for all sites in the network
	 *                           or just the current site. Multisite only. Default is false.
	 */
	public function install( $network_wide = false ) {

		_deprecated_function( __CLASS__ . '::' . __METHOD__, '2.2.0' );

		// Check if we are on multisite and network activating.
		if ( is_multisite() && $network_wide ) {

			// Multisite - go through each subsite and run the installer.
			$sites = get_sites(
				[
					'fields' => 'ids',
					'number' => 0,
				]
			);

			foreach ( $sites as $blog_id ) {
				switch_to_blog( $blog_id );
				$this->run();
				restore_current_blog();
			}
		} else {

			// Normal single site.
			$this->run();
		}
	}

	/**
	 * Run the actual installer.
	 *
	 * @since 2.0.0
	 */
	private function run() {

		( new Migrations() )->init();
	}

	/**
	 * When a new site is created in multisite, see if we are network activated,
	 * and if so run the installer.
	 *
	 * @since 2.0.0
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function new_multisite_blog( $blog_id ) {

		if ( is_plugin_active_for_network( plugin_basename( WPFORMS_FORM_LOCKER_FILE ) ) ) {
			switch_to_blog( $blog_id );
			$this->run();
			restore_current_blog();
		}
	}
}
