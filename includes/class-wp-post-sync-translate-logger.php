<?php
/**
 * Audit logging system
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

class Wp_Post_Sync_Translate_Logger {

	private $start_time = 0;

	/**
	 * Start timing for a log entry.
	 *
	 * @since 1.0.0
	 */
	public function start_timer() {
		$this->start_time = microtime( true );
	}

	/**
	 * Get elapsed time in milliseconds.
	 *
	 * @return int Elapsed milliseconds.
	 * @since 1.0.0
	 */
	public function get_elapsed_ms() {
		if ( $this->start_time === 0 ) {
			return 0;
		}
		$elapsed = microtime( true ) - $this->start_time;
		return intval( $elapsed * 1000 );
	}

	/**
	 * Log a sync action.
	 *
	 * @param array $args Log arguments.
	 * @return int|false Log ID or false on error.
	 * @since 1.0.0
	 */
	public function log( $args ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-database.php';

		$defaults = array(
			'host_post_id'    => 0,
			'target_post_id'  => null,
			'source_site_url' => get_site_url(),
			'target_site_url' => '',
			'action'          => 'sync',
			'status'          => 'success',
			'message'         => '',
			'user_role'       => Wp_Post_Sync_Translate_Auth::get_user_role(),
			'duration_ms'     => $this->get_elapsed_ms(),
		);

		$data = array_merge( $defaults, $args );

		return Wp_Post_Sync_Translate_Database::insert_log( $data );
	}

	/**
	 * Log a sync success.
	 *
	 * @param int    $host_post_id Host post ID.
	 * @param int    $target_post_id Target post ID.
	 * @param string $target_url Target site URL.
	 * @param string $action Action name.
	 * @return int|false Log ID or false.
	 * @since 1.0.0
	 */
	public function log_success( $host_post_id, $target_post_id, $target_url, $action = 'sync' ) {
		return $this->log(
			array(
				'host_post_id'    => $host_post_id,
				'target_post_id'  => $target_post_id,
				'target_site_url' => $target_url,
				'action'          => $action,
				'status'          => 'success',
				'message'         => 'Post synced and translated successfully',
			)
		);
	}

	/**
	 * Log a sync error.
	 *
	 * @param int    $host_post_id Host post ID.
	 * @param string $target_url Target site URL.
	 * @param string $error_message Error message.
	 * @param string $action Action name.
	 * @param int    $target_post_id Target post ID (optional).
	 * @return int|false Log ID or false.
	 * @since 1.0.0
	 */
	public function log_error( $host_post_id, $target_url, $error_message, $action = 'sync', $target_post_id = null ) {
		return $this->log(
			array(
				'host_post_id'    => $host_post_id,
				'target_post_id'  => $target_post_id,
				'target_site_url' => $target_url,
				'action'          => $action,
				'status'          => 'error',
				'message'         => $error_message,
			)
		);
	}
}
