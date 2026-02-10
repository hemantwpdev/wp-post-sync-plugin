<?php
/**
 * Database setup for audit logs
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

class Wp_Post_Sync_Translate_Database {

	/**
	 * Create the audit logs table.
	 *
	 * @since 1.0.0
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name     = $wpdb->prefix . 'post_sync_translate_logs';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			host_post_id BIGINT UNSIGNED NOT NULL,
			target_post_id BIGINT UNSIGNED,
			source_site_url VARCHAR(255) NOT NULL,
			target_site_url VARCHAR(255) NOT NULL,
			action VARCHAR(50) NOT NULL,
			status VARCHAR(20) NOT NULL,
			message LONGTEXT,
			user_role VARCHAR(50),
			duration_ms INT UNSIGNED,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY host_post_id (host_post_id),
			KEY target_post_id (target_post_id),
			KEY created_at (created_at),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a log entry.
	 *
	 * @param array $data Log data.
	 * @return int|false The number of rows inserted, or false on error.
	 * @since 1.0.0
	 */
	public static function insert_log( $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'post_sync_translate_logs';

		$defaults = array(
			'host_post_id'   => 0,
			'target_post_id' => null,
			'source_site_url' => get_site_url(),
			'target_site_url' => '',
			'action'         => '',
			'status'         => 'pending',
			'message'        => '',
			'user_role'      => '',
			'duration_ms'    => 0,
			'created_at'     => current_time( 'mysql' ),
		);

		$data = array_merge( $defaults, $data );

		$format = array(
			'%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s'
		);

		return $wpdb->insert( $table_name, $data, $format );
	}

	/**
	 * Get logs by criteria.
	 *
	 * @param array $args Query arguments.
	 * @return array Logs.
	 * @since 1.0.0
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'post_sync_translate_logs';

		$defaults = array(
			'host_post_id'   => null,
			'target_post_id' => null,
			'status'         => null,
			'limit'          => 50,
			'offset'         => 0,
			'orderby'        => 'created_at',
			'order'          => 'DESC',
		);

		$args = array_merge( $defaults, $args );

		$where = array( '1=1' );

		if ( $args['host_post_id'] ) {
			$where[] = $wpdb->prepare( 'host_post_id = %d', $args['host_post_id'] );
		}

		if ( $args['target_post_id'] ) {
			$where[] = $wpdb->prepare( 'target_post_id = %d', $args['target_post_id'] );
		}

		if ( $args['status'] ) {
			$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
		}

		$where_clause = implode( ' AND ', $where );

		$sql = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE $where_clause ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
			$args['limit'],
			$args['offset']
		);

		return $wpdb->get_results( $sql );
	}
}
