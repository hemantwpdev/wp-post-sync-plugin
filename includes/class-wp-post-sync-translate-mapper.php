<?php
/**
 * Post mapper - tracks Host post ID to Target post ID relationships
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

class Wp_Post_Sync_Translate_Mapper {

	const META_KEY_HOST_ID = '_wp_post_sync_translate_host_id';
	const META_KEY_TARGET_URL = '_wp_post_sync_translate_target_url';

	/**
	 * Store mapping between host and target post.
	 *
	 * @param int    $target_post_id Target post ID.
	 * @param int    $host_post_id Host post ID.
	 * @param string $target_url Target site URL.
	 * @since 1.0.0
	 */
	public static function set_mapping( $target_post_id, $host_post_id, $target_url ) {
		$target_url = untrailingslashit( esc_url_raw( $target_url ) );
		update_post_meta( $target_post_id, self::META_KEY_HOST_ID, intval( $host_post_id ) );
		update_post_meta( $target_post_id, self::META_KEY_TARGET_URL, $target_url );
	}

	/**
	 * Get target post ID from host post ID and target URL.
	 *
	 * @param int    $host_post_id Host post ID.
	 * @param string $target_url Target site URL.
	 * @return int|null Target post ID or null.
	 * @since 1.0.0
	 */
	public static function get_target_post_id( $host_post_id, $target_url ) {
		global $wpdb;

		$target_url = untrailingslashit( esc_url_raw( $target_url ) );

		$query = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} 
			WHERE meta_key = %s AND meta_value = %d
			LIMIT 1",
			self::META_KEY_HOST_ID,
			intval( $host_post_id )
		);

		$post_id = $wpdb->get_var( $query );

		if ( ! $post_id ) {
			return null;
		}

		// Verify target URL matches.
		$target_url_meta = get_post_meta( $post_id, self::META_KEY_TARGET_URL, true );
		if ( $target_url_meta === $target_url ) {
			return intval( $post_id );
		}

		return null;
	}

	/**
	 * Get host post ID from target post ID.
	 *
	 * @param int $target_post_id Target post ID.
	 * @return int|null Host post ID or null.
	 * @since 1.0.0
	 */
	public static function get_host_post_id( $target_post_id ) {
		$host_id = get_post_meta( $target_post_id, self::META_KEY_HOST_ID, true );
		return ! empty( $host_id ) ? intval( $host_id ) : null;
	}

	/**
	 * Get target URL from target post ID.
	 *
	 * @param int $target_post_id Target post ID.
	 * @return string|null Target URL or null.
	 * @since 1.0.0
	 */
	public static function get_target_url( $target_post_id ) {
		return get_post_meta( $target_post_id, self::META_KEY_TARGET_URL, true );
	}

	/**
	 * Delete mapping.
	 *
	 * @param int $target_post_id Target post ID.
	 * @since 1.0.0
	 */
	public static function delete_mapping( $target_post_id ) {
		delete_post_meta( $target_post_id, self::META_KEY_HOST_ID );
		delete_post_meta( $target_post_id, self::META_KEY_TARGET_URL );
	}
}
