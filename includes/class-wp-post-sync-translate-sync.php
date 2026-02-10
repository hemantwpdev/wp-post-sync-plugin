<?php
/**
 * Sync mechanism - push posts from Host to Targets
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

class Wp_Post_Sync_Translate_Sync {

	/**
	 * Push a post to all targets.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action Action (publish, update, delete).
	 * @since 1.0.0
	 */
	public static function push_post( $post_id, $action = 'publish' ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-auth.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-logger.php';

		// Only push if this is a host site.
		if ( Wp_Post_Sync_Translate_Settings::get_mode() !== 'host' ) {
			return;
		}

		// Get post.
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'post' ) {
			return;
		}

		// Skip drafts and other non-published statuses for publish action.
		if ( 'publish' === $action && 'publish' !== $post->post_status ) {
			return;
		}

		// Get all targets.
		$targets = Wp_Post_Sync_Translate_Settings::get_targets();

		if ( empty( $targets ) ) {
			return;
		}

		// Push to each target.
		foreach ( $targets as $target ) {
			self::push_to_target( $post_id, $target, $action );
		}
	}

	/**
	 * Push a post to a specific target.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $target Target config with url and key.
	 * @param string $action Action (publish, update, delete).
	 * @since 1.0.0
	 */
	private static function push_to_target( $post_id, $target, $action = 'publish' ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-auth.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-logger.php';

		$logger = new Wp_Post_Sync_Translate_Logger();
		$logger->start_timer();

		$post = get_post( $post_id );
		$target_url = $target['url'];
		$key        = $target['key'];

		// Build request body.
		if ( 'delete' === $action ) {
			$body = array(
				'action'        => 'delete',
				'host_post_id'  => $post_id,
				'source_url'    => get_site_url(),
			);
		} else {
			// Get post data.
			$categories = self::get_post_categories( $post_id );
			$tags       = self::get_post_tags( $post_id );
			$image_url  = self::get_featured_image_url( $post_id );

			$body = array(
				'host_post_id'        => $post_id,
				'title'               => $post->post_title,
				'content'             => $post->post_content,
				'excerpt'             => $post->post_excerpt,
				'categories'          => $categories,
				'tags'                => $tags,
				'featured_image_url'  => $image_url,
				'source_url'          => get_site_url(),
			);
		}

		// Generate signature.
		$signature = Wp_Post_Sync_Translate_Auth::generate_signature( $body, $key );
		$body['signature'] = $signature;

		// Send request.
		$endpoint = trailingslashit( $target_url ) . 'wp-json/wp-post-sync-translate/v1/sync';
		error_log( 'Pushing post ID ' . $post_id . ' to ' . $endpoint . ' with action ' . $action );
		error_log( 'Request body: ' . print_r( $body, true ) );
		$response = wp_remote_post(
			$endpoint,
			array(
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
				'body'      => wp_json_encode( $body ),
				'timeout'   => 30,
				'sslverify' => true,
			)
		);

		// Log response.
		$status = 'error';
		$message = '';

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			error_log( 'Error pushing to ' . $endpoint . ': ' . $message );
		} else {
			$http_code = wp_remote_retrieve_response_code( $response );
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
			error_log( 'HTTP code: ' . $http_code );
			error_log( 'Response from ' . $endpoint . ': ' . print_r( $response_body, true ) );
			if ( 200 === $http_code && isset( $response_body['success'] ) && $response_body['success'] ) {
				$status = 'success';
				$message = $response_body['message'] ?? 'Synced successfully';
			} else {
				$message = $response_body['message'] ?? 'Unexpected response';
			}
		}

		// Log.
		if ( 'success' === $status ) {
			$logger->log_success( $post_id, null, $target_url, $action );
		} else {
			$logger->log_error( $post_id, $target_url, $message, $action );
		}
	}

	/**
	 * Get post categories as names.
	 *
	 * @param int $post_id Post ID.
	 * @return array Category names.
	 * @since 1.0.0
	 */
	private static function get_post_categories( $post_id ) {
		$categories = get_the_category( $post_id );

		if ( empty( $categories ) ) {
			return array();
		}

		return wp_list_pluck( $categories, 'name' );
	}

	/**
	 * Get post tags as names.
	 *
	 * @param int $post_id Post ID.
	 * @return array Tag names.
	 * @since 1.0.0
	 */
	private static function get_post_tags( $post_id ) {
		$tags = get_the_tags( $post_id );

		if ( empty( $tags ) ) {
			return array();
		}

		return wp_list_pluck( $tags, 'name' );
	}

	/**
	 * Get featured image URL.
	 *
	 * @param int $post_id Post ID.
	 * @return string|null Image URL or null.
	 * @since 1.0.0
	 */
	private static function get_featured_image_url( $post_id ) {
		$image_id = get_post_thumbnail_id( $post_id );

		if ( ! $image_id ) {
			return null;
		}

		$image = wp_get_attachment_url( $image_id );

		return $image ? $image : null;
	}
}
