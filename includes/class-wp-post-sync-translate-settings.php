<?php
/**
 * Settings manager for Host/Target configuration
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/includes
 */

class Wp_Post_Sync_Translate_Settings {

	const OPTION_MODE = 'wp_post_sync_translate_mode';
	const OPTION_TARGETS = 'wp_post_sync_translate_targets';
	const OPTION_TARGET_KEY = 'wp_post_sync_translate_target_key';
	const OPTION_TARGET_LANGUAGE = 'wp_post_sync_translate_target_language';
	const OPTION_CHATGPT_KEY = 'wp_post_sync_translate_chatgpt_key';

	/**
	 * Get the current mode (host or target).
	 *
	 * @return string 'host' or 'target'.
	 * @since 1.0.0
	 */
	public static function get_mode() {
		return get_option( self::OPTION_MODE, 'host' );
	}

	/**
	 * Set the mode.
	 *
	 * @param string $mode 'host' or 'target'.
	 * @since 1.0.0
	 */
	public static function set_mode( $mode ) {
		if ( in_array( $mode, array( 'host', 'target' ), true ) ) {
			update_option( self::OPTION_MODE, $mode );
		}
	}

	/**
	 * Get all target configurations.
	 *
	 * @return array Array of targets with url and key.
	 * @since 1.0.0
	 */
	public static function get_targets() {
		$targets = get_option( self::OPTION_TARGETS, array() );
		return is_array( $targets ) ? $targets : array();
	}

	/**
	 * Add a target.
	 *
	 * @param string $url Target site URL.
	 * @param string $key Unique key for target.
	 * @return bool True on success.
	 * @since 1.0.0
	 */
	public static function add_target( $url, $key = null ) {
		$url = untrailingslashit( esc_url_raw( $url ) );

		if ( ! $url ) {
			return false;
		}

		if ( ! $key ) {
			$key = self::generate_key();
		}

		// Check if target already exists.
		$targets = self::get_targets();

		foreach ( $targets as $target ) {
			if ( $target['url'] === $url ) {
				return false;
			}
		}

		$targets[] = array(
			'url' => $url,
			'key' => $key,
			'added_at' => current_time( 'timestamp' ),
		);

		update_option( self::OPTION_TARGETS, $targets );
		return true;
	}

	/**
	 * Remove a target.
	 *
	 * @param string $url Target site URL.
	 * @return bool True on success.
	 * @since 1.0.0
	 */
	public static function remove_target( $url ) {
		$url = untrailingslashit( esc_url_raw( $url ) );
		$targets = self::get_targets();

		$filtered = array_filter(
			$targets,
			function( $target ) use ( $url ) {
				return $target['url'] !== $url;
			}
		);

		if ( count( $filtered ) < count( $targets ) ) {
			update_option( self::OPTION_TARGETS, array_values( $filtered ) );
			return true;
		}

		return false;
	}

	/**
	 * Get a target by URL.
	 *
	 * @param string $url Target site URL.
	 * @return array|null Target config or null.
	 * @since 1.0.0
	 */
	public static function get_target( $url ) {
		$url = untrailingslashit( esc_url_raw( $url ) );
		$targets = self::get_targets();

		foreach ( $targets as $target ) {
			if ( $target['url'] === $url ) {
				return $target;
			}
		}

		return null;
	}

	/**
	 * Get the target key (for target site).
	 *
	 * @return string The key.
	 * @since 1.0.0
	 */
	public static function get_target_key() {
		return get_option( self::OPTION_TARGET_KEY, '' );
	}

	/**
	 * Set the target key (for target site).
	 *
	 * @param string $key The key from host.
	 * @since 1.0.0
	 */
	public static function set_target_key( $key ) {
		update_option( self::OPTION_TARGET_KEY, sanitize_text_field( $key ) );
	}

	/**
	 * Get the target language.
	 *
	 * @return string Language code.
	 * @since 1.0.0
	 */
	public static function get_target_language() {
		return get_option( self::OPTION_TARGET_LANGUAGE, 'fr' );
	}

	/**
	 * Set the target language.
	 *
	 * @param string $language Language code.
	 * @since 1.0.0
	 */
	public static function set_target_language( $language ) {
		$valid = array( 'fr', 'es', 'hi' );
		if ( in_array( $language, $valid, true ) ) {
			update_option( self::OPTION_TARGET_LANGUAGE, $language );
		}
	}

	/**
	 * Get ChatGPT API key (encrypted).
	 *
	 * @return string The API key.
	 * @since 1.0.0
	 */
	public static function get_chatgpt_key() {
		return get_option( self::OPTION_CHATGPT_KEY, '' );
	}

	/**
	 * Set ChatGPT API key.
	 *
	 * @param string $key API key.
	 * @since 1.0.0
	 */
	public static function set_chatgpt_key( $key ) {
		update_option( self::OPTION_CHATGPT_KEY, sanitize_text_field( $key ) );
	}

	/**
	 * Generate a unique key (≥16 chars).
	 *
	 * @return string Generated key.
	 * @since 1.0.0
	 */
	public static function generate_key() {
		return bin2hex( random_bytes( 24 ) ); // 48 hex characters.
	}

}
