<?php
/**
 * REST API endpoints for sync and translation
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

class Wp_Post_Sync_Translate_REST {

	const REST_NAMESPACE = 'wp-post-sync-translate/v1';

	/**
	 * Register REST routes.
	 *
	 * @since 1.0.0
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/sync',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_sync' ),
				'permission_callback' => '__return_true', // Auth handled in callback.
				'args'                => array(
					'host_post_id'  => array(
						'type'     => 'integer',
						'required' => true,
					),
					'title'         => array(
						'type'     => 'string',
						'required' => true,
					),
					'content'       => array(
						'type'     => 'string',
						'required' => true,
					),
					'excerpt'       => array(
						'type' => 'string',
					),
					'categories'    => array(
						'type' => 'array',
					),
					'tags'          => array(
						'type' => 'array',
					),
					'featured_image_url' => array(
						'type' => 'string',
					),
					'source_url'    => array(
						'type'     => 'string',
						'required' => true,
					),
					'signature'     => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/auth-test',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_auth_test' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'source_url' => array(
						'type'     => 'string',
						'required' => true,
					),
					'signature'  => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/translate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_translate' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Handle sync endpoint.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error Response.
	 * @since 1.0.0
	 */
	public static function handle_sync( WP_REST_Request $request ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-auth.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-mapper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-logger.php';

		// Check if this is a target site.
		if ( Wp_Post_Sync_Translate_Settings::get_mode() !== 'target' ) {
			return new WP_Error(
				'not_target',
				'This site is not configured as a target',
				array( 'status' => 403 )
			);
		}

		$params = $request->get_json_params();
		$logger = new Wp_Post_Sync_Translate_Logger();
		$logger->start_timer();

		// Get stored key.
		$stored_key = Wp_Post_Sync_Translate_Settings::get_target_key();

		if ( empty( $stored_key ) ) {
			return new WP_Error(
				'no_key',
				'Target key not configured',
				array( 'status' => 403 )
			);
		}

		// Extract signature and remove from body before verification.
		$signature = $params['signature'] ?? '';
		unset( $params['signature'] );

		// Verify authentication.
		$auth_result = Wp_Post_Sync_Translate_Auth::verify_request(
			$params,
			$signature,
			$params['source_url'] ?? '',
			$stored_key
		);

		if ( is_wp_error( $auth_result ) ) {
			$logger->log_error(
				$params['host_post_id'] ?? 0,
				$params['source_url'] ?? '',
				'Authentication failed: ' . $auth_result->get_error_message(),
				'sync'
			);
			return $auth_result;
		}
		
		// Extract data.
		$host_post_id       = intval( $params['host_post_id'] ?? 0 );
		$title              = sanitize_text_field( $params['title'] ?? '' );
		$content            = wp_kses_post( $params['content'] ?? '' );
		$excerpt            = sanitize_text_field( $params['excerpt'] ?? '' );
		$categories         = isset( $params['categories'] ) ? (array) $params['categories'] : array();
		$tags               = isset( $params['tags'] ) ? (array) $params['tags'] : array();
		$featured_image_url = isset( $params['featured_image_url'] ) ? esc_url_raw( $params['featured_image_url'] ) : '';
		$source_url         = esc_url_raw( $params['source_url'] );

		if ( ! $host_post_id || ! $title ) {
			return new WP_Error(
				'invalid_data',
				'Missing required fields',
				array( 'status' => 400 )
			);
		}

		// Check if this post already exists (by host_post_id and source URL).
		$target_post_id = Wp_Post_Sync_Translate_Mapper::get_target_post_id( $host_post_id, $source_url );

		// Create or update post.
		$post_data = array(
			'post_type'    => 'post',
			'post_title'   => $title,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'post_status'  => 'publish',
		);

		if ( $target_post_id ) {
			$post_data['ID'] = $target_post_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			$logger->log_error(
				$host_post_id,
				$source_url,
				'Failed to save post: ' . $result->get_error_message(),
				'sync',
				$target_post_id
			);
			return $result;
		}

		$target_post_id = $result;

		// Store mapping.
		Wp_Post_Sync_Translate_Mapper::set_mapping( $target_post_id, $host_post_id, $source_url );

		// Handle categories.
		self::sync_categories( $target_post_id, $categories );

		// Handle tags.
		self::sync_tags( $target_post_id, $tags );

		// Handle featured image.
		if ( $featured_image_url ) {
			self::sync_featured_image( $target_post_id, $featured_image_url );
		}

		// Log success.
		$logger->log_success( $host_post_id, $target_post_id, $source_url, 'sync' );

		// Trigger async translation on target if ChatGPT key and language are configured.
		$chatgpt_key = Wp_Post_Sync_Translate_Settings::get_chatgpt_key();
		$target_lang = Wp_Post_Sync_Translate_Settings::get_target_language();
		$allowed = array( 'fr', 'es', 'hi' );
		if ( ! empty( $chatgpt_key ) && in_array( $target_lang, $allowed, true ) ) {
			// Make async call to translation endpoint (non-blocking).
			$translate_endpoint = trailingslashit( get_site_url() ) . 'wp-json/wp-post-sync-translate/v1/translate';
			wp_remote_post(
				$translate_endpoint,
				array(
					'headers'   => array(
						'Content-Type' => 'application/json',
					),
					'body'      => wp_json_encode( array( 'post_id' => $target_post_id ) ),
					'timeout'   => 0.01, // Minimal timeout to avoid blocking.
					'blocking'  => false, // Non-blocking async call.
					'sslverify' => true,
				)
			);
		}

		return new WP_REST_Response(
			array(
				'success'        => true,
				'target_post_id' => $target_post_id,
				'message'        => 'Post synced and translated successfully',
			),
			200
		);
	}

	/**
	 * Handle translate endpoint (async background translation).
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error Response.
	 * @since 1.0.0
	 */
	public static function handle_translate( WP_REST_Request $request ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-translator.php';

		// Check if this is a target site.
		if ( Wp_Post_Sync_Translate_Settings::get_mode() !== 'target' ) {
			return new WP_Error(
				'not_target',
				'This site is not configured as a target',
				array( 'status' => 403 )
			);
		}

		$params = $request->get_json_params();
		$post_id = intval( $params['post_id'] ?? 0 );

		if ( ! $post_id ) {
			return new WP_Error(
				'invalid_post',
				'Post ID required',
				array( 'status' => 400 )
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				'Post not found',
				array( 'status' => 404 )
			);
		}

		// Run translation (returns immediately, actual translation happens in background).
		Wp_Post_Sync_Translate_Translator::translate_post( $post_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'post_id' => $post_id,
				'message' => 'Translation queued',
			),
			200
		);
	}

	/**
	 * Handle auth test endpoint.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error Response.
	 * @since 1.0.0
	 */
	public static function handle_auth_test( WP_REST_Request $request ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-auth.php';

		if ( Wp_Post_Sync_Translate_Settings::get_mode() !== 'target' ) {
			return new WP_Error(
				'not_target',
				'This site is not configured as a target',
				array( 'status' => 403 )
			);
		}

		$params      = $request->get_json_params();
		$stored_key  = Wp_Post_Sync_Translate_Settings::get_target_key();
		$source_url  = esc_url_raw( $params['source_url'] ?? '' );
		$signature   = sanitize_text_field( $params['signature'] ?? '' );

		if ( empty( $stored_key ) ) {
			return new WP_Error(
				'no_key',
				'Target key not configured',
				array( 'status' => 403 )
			);
		}

		// Remove signature from params before verification.
		unset( $params['signature'] );

		// Verify signature.
		$auth_result = Wp_Post_Sync_Translate_Auth::verify_request(
			$params,
			$signature,
			$source_url,
			$stored_key
		);

		if ( is_wp_error( $auth_result ) ) {
			return $auth_result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Authentication successful',
			),
			200
		);
	}

	/**
	 * Sync categories.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $categories Categories to sync.
	 * @since 1.0.0
	 */
	private static function sync_categories( $post_id, $categories ) {
		if ( empty( $categories ) ) {
			return;
		}

		$category_ids = array();

		foreach ( $categories as $cat_name ) {
			$cat_name = sanitize_text_field( $cat_name );

			// Get or create category.
			$term = get_term_by( 'name', $cat_name, 'category' );

			if ( ! $term ) {
				$new_term = wp_insert_term( $cat_name, 'category' );

				if ( is_wp_error( $new_term ) ) {
					continue;
				}

				$category_ids[] = $new_term['term_id'];
			} else {
				$category_ids[] = $term->term_id;
			}
		}

		if ( ! empty( $category_ids ) ) {
			wp_set_post_terms( $post_id, $category_ids, 'category' );
		}
	}

	/**
	 * Sync tags.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $tags Tags to sync.
	 * @since 1.0.0
	 */
	private static function sync_tags( $post_id, $tags ) {
		if ( empty( $tags ) ) {
			return;
		}

		$tag_ids = array();

		foreach ( $tags as $tag_name ) {
			$tag_name = sanitize_text_field( $tag_name );

			// Get or create tag.
			$term = get_term_by( 'name', $tag_name, 'post_tag' );

			if ( ! $term ) {
				$new_term = wp_insert_term( $tag_name, 'post_tag' );

				if ( is_wp_error( $new_term ) ) {
					continue;
				}

				$tag_ids[] = $new_term['term_id'];
			} else {
				$tag_ids[] = $term->term_id;
			}
		}

		if ( ! empty( $tag_ids ) ) {
			wp_set_post_terms( $post_id, $tag_ids, 'post_tag' );
		}
	}

	/**
	 * Sync featured image.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $image_url Image URL.
	 * @since 1.0.0
	 */
	private static function sync_featured_image( $post_id, $image_url ) {
		// Sanitize image URL.
		$image_url = esc_url_raw( $image_url );

		// Get image filename from URL.
		$filename = basename( $image_url );

		if ( ! $filename ) {
			$filename = 'featured-image-' . $post_id;
		}

		// Check if file already exists in media library.
		// $existing_attachment_id = self::get_attachment_by_filename( $filename );

		// if ( $existing_attachment_id ) {
		// 	// Attachment already exists, just set it as featured image.
		// 	set_post_thumbnail( $post_id, $existing_attachment_id );
		// 	return;
		// }

		// Download image.
		$response = wp_remote_get(
			$image_url,
			array(
				'timeout'   => 300,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		$image_data = wp_remote_retrieve_body( $response );

		if ( empty( $image_data ) ) {
			return;
		}

		// Upload to media library.
		$upload = wp_upload_bits( $filename, null, $image_data );

		if ( isset( $upload['error'] ) && $upload['error'] ) {
			return;
		}

		// Create attachment.
		$attachment = array(
			'post_mime_type' => $upload['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return;
		}

		// Generate attachment metadata.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		// Set as featured image.
		set_post_thumbnail( $post_id, $attachment_id );
	}

	/**
	 * Get attachment ID by filename.
	 *
	 * @param string $filename Filename to search for.
	 * @return int|false Attachment ID or false if not found.
	 * @since 1.0.0
	 */
	private static function get_attachment_by_filename( $filename ) {
		// Remove extension for title comparison.
		$title = preg_replace( '/\.[^.]+$/', '', $filename );

		$args = array(
			'post_type'      => 'attachment',
			'posts_per_page' => 1,
			's'              => $title,
			'post_status'    => 'inherit',
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			return $query->posts[0]->ID;
		}

		return false;
	}
}
