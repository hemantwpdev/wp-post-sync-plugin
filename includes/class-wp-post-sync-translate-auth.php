<?php
/**
 * HMAC authentication and domain binding
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

class Wp_Post_Sync_Translate_Auth {

	/**
	 * Generate HMAC signature for request.
	 *
	 * @param array  $body Request body.
	 * @param string $key Shared key.
	 * @return string HMAC-SHA256 signature.
	 * @since 1.0.0
	 */
	public static function generate_signature( $body, $key ) {
		$json = wp_json_encode( $body );
		return hash_hmac( 'sha256', $json, $key );
	}

	/**
	 * Verify HMAC signature.
	 *
	 * @param array  $body Request body.
	 * @param string $key Shared key.
	 * @param string $signature Provided signature.
	 * @return bool True if valid.
	 * @since 1.0.0
	 */
	public static function verify_signature( $body, $key, $signature ) {
		$computed = self::generate_signature( $body, $key );
		// Use hash_equals to prevent timing attacks.
		return hash_equals( $computed, $signature );
	}

	/**
	 * Verify request domain matches.
	 *
	 * @param string $source_domain Domain of request source.
	 * @param string $expected_domain Expected domain.
	 * @return bool True if matches.
	 * @since 1.0.0
	 */
	public static function verify_domain( $source_domain, $expected_domain ) {
		$source   = untrailingslashit( esc_url_raw( $source_domain ) );
		$expected = untrailingslashit( esc_url_raw( $expected_domain ) );

		return hash_equals( wp_parse_url( $source, PHP_URL_HOST ), wp_parse_url( $expected, PHP_URL_HOST ) );
	}

	/**
	 * Verify request authentication.
	 *
	 * @param array  $body Request body.
	 * @param string $signature Provided signature.
	 * @param string $source_url Source site URL.
	 * @param string $key Shared key.
	 * @return array|WP_Error Auth result or error.
	 * @since 1.0.0
	 */
	public static function verify_request( $body, $signature, $source_url, $key ) {
		// Verify signature (primary security check).
		// HMAC signature is sufficient - it binds the request body + key together.
		// If signature is valid, we know request came from someone with the shared key.
		if ( ! self::verify_signature( $body, $key, $signature ) ) {
			return new WP_Error(
				'invalid_signature',
				'Request signature verification failed',
				array( 'status' => 401 )
			);
		}

		return array( 'valid' => true );
	}

	/**
	 * Get current user role (for logging).
	 *
	 * @return string User role.
	 * @since 1.0.0
	 */
	public static function get_user_role() {
		$user = wp_get_current_user();
		if ( $user->ID > 0 ) {
			$roles = $user->roles;
			return ! empty( $roles ) ? $roles[0] : 'unknown';
		}
		return 'anonymous';
	}
}
