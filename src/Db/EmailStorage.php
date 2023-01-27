<?php

namespace WPFormsLocker\Db;

use WPForms_DB;

/**
 * The Email Locker stores records in a custom database.
 *
 * @since 2.0.0
 */
class EmailStorage extends WPForms_DB {

	/**
	 * Primary class constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'wpforms_form_locker_email_verification';
		$this->primary_key = 'id';
		$this->type        = 'form_locker';
	}

	/**
	 * Get table columns.
	 *
	 * @since 2.0.0
	 */
	public function get_columns() {

		return [
			'id'         => '%d',
			'form_id'    => '%d',
			'email'      => '%s',
			'created_at' => '%s',
			'date_used'  => '%s',
		];
	}

	/**
	 * Default column values.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_column_defaults() {

		return [
			'id'         => '%d',
			'form_id'    => '%d',
			'email'      => '%s',
			'created_at' => gmdate( 'Y-m-d H:i:s' ),
			'date_used'  => '%s',
		];
	}

	/**
	 * Get rows from the database.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args  Optional args.
	 * @param bool  $count Flag to return count instead of results.
	 *
	 * @return object|int|null
	 */
	public function get_row( $args = [], $count = false ) {

		global $wpdb;

		$defaults = [
			'number'  => 1,
			'offset'  => 0,
			'id'      => 0,
			'form_id' => 0,
			'orderby' => 'id',
			'order'   => 'ASC',
		];

		$args = wp_parse_args( $args, $defaults );

		$where = $this->build_where(
			$args,
			[ 'id', 'form_id', 'email' ],
			[ '%d', '%d', '%s' ]
		);

		if ( $count === true ) {
			return absint( $wpdb->get_var( "SELECT COUNT({$this->primary_key}) FROM {$this->table_name} {$where};" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM {$this->table_name} {$where} ORDER BY {$args['orderby']} {$args['order']} LIMIT {$args['offset']}, {$args['number']}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Create custom verification email table. Used on plugin activation or on migration.
	 *
	 * @since 2.0.0
	 */
	public function create_table() {

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			form_id bigint(20) NOT NULL,
			email varchar(64) NOT NULL,
			created_at datetime NOT NULL,
			date_used datetime NULL,
			PRIMARY KEY (id),
			KEY email (email),
			KEY form_id (form_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
