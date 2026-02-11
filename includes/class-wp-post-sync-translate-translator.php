<?php
/**
 * Translator - runs on Target to translate Gutenberg post content using ChatGPT
 *
 * @package Wp_Post_Sync_Translate
 */

class Wp_Post_Sync_Translate_Translator {

	const TEXT_CHUNK_SIZE = 200;

	/* ===========================
	 * ENTRY POINT
	 * =========================== */
	public static function translate_post( $post_id ) {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-sync-translate-logger.php';

		$logger = new Wp_Post_Sync_Translate_Logger();
		$logger->start_timer();

		$key  = Wp_Post_Sync_Translate_Settings::get_chatgpt_key();
		$lang = Wp_Post_Sync_Translate_Settings::get_target_language();
		$map  = array( 'fr' => 'French', 'es' => 'Spanish', 'hi' => 'Hindi' );

		if ( empty( $key ) || ! isset( $map[ $lang ] ) ) {
			$logger->log_error( 0, '', 'Translation skipped: missing config', 'translate' );
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			$logger->log_error( 0, '', 'Post not found', 'translate' );
			return;
		}

		$update  = array( 'ID' => $post_id );
		$changed = false;

		$title = self::translate_plain_text( $post->post_title, $key, $map[ $lang ] );
		if ( $title && $title !== $post->post_title ) {
			$update['post_title'] = wp_strip_all_tags( $title );
			$changed = true;
		}

		$content = self::translate_gutenberg_content( $post->post_content, $key, $map[ $lang ] );
		if ( $content && $content !== $post->post_content ) {
			$update['post_content'] = $content;
			$changed = true;
		}

		$excerpt = self::translate_plain_text( $post->post_excerpt, $key, $map[ $lang ] );
		if ( $excerpt && $excerpt !== $post->post_excerpt ) {
			$update['post_excerpt'] = wp_strip_all_tags( $excerpt );
			$changed = true;
		}

		if ( $changed ) {
			$res = wp_update_post( $update, true );
			if ( is_wp_error( $res ) ) {
				$logger->log_error( $post_id, '', $res->get_error_message(), 'translate' );
				return;
			}
		}

		$logger->log_success( $post_id, null, '', 'translate' );
	}

	/* ===========================
	 * GUTENBERG TRANSLATION
	 * =========================== */

	private static function translate_gutenberg_content( $content, $key, $language ) {
		if ( trim( $content ) === '' ) {
			return '';
		}

		$blocks = parse_blocks( $content );
		if ( empty( $blocks ) ) {
			return $content;
		}

		$blocks = self::translate_blocks( $blocks, $key, $language );
		return serialize_blocks( $blocks );
	}

	private static function translate_blocks( array $blocks, $key, $language ) {
		foreach ( $blocks as &$block ) {

			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			// LIST BLOCK (translate each <li>)
			if ( $block['blockName'] === 'core/list' && ! empty( $block['innerHTML'] ) ) {

				preg_match_all( '/<li>(.*?)<\/li>/s', $block['innerHTML'], $items );

				foreach ( $items[1] as $item ) {
					$plain = wp_strip_all_tags( $item );
					if ( trim( $plain ) === '' ) {
						continue;
					}

					$translated = self::translate_text_with_chunking( $plain, $key, $language );
					$block['innerHTML'] = str_replace(
						$item,
						esc_html( $translated ),
						$block['innerHTML']
					);
				}

				$block['innerContent'] = array( $block['innerHTML'] );
			}

			// SIMPLE TEXT BLOCKS
			elseif ( self::should_translate_block( $block['blockName'] ) && ! empty( $block['innerHTML'] ) ) {

				$plain = self::extract_plain_text( $block['innerHTML'] );
				if ( $plain !== '' ) {
					$translated = self::translate_text_with_chunking( $plain, $key, $language );
					$html       = self::replace_block_text( $block['innerHTML'], $translated );

					$block['innerHTML']    = $html;
					$block['innerContent'] = array( $html );
				}
			}

			// INNER BLOCKS (columns, group, etc.)
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::translate_blocks(
					$block['innerBlocks'],
					$key,
					$language
				);
			}
		}

		return $blocks;
	}

	private static function should_translate_block( $name ) {
		return in_array(
			$name,
			array(
				'core/paragraph',
				'core/heading',
				'core/quote',
				'core/button',
			),
			true
		);
	}

	/* ===========================
	 * TEXT + HTML HELPERS
	 * =========================== */

	private static function extract_plain_text( $html ) {
		$html = preg_replace( '/<br\s*\/?>/i', "\n", $html );
		$text = wp_strip_all_tags( $html );
		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}

	private static function replace_block_text( $html, $translation ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );

		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );

		if ( $body ) {
			$body->nodeValue = $translation;
		}

		libxml_clear_errors();
		return wp_kses_post( $dom->saveHTML() );
	}

	/* ===========================
	 * TRANSLATION CORE
	 * =========================== */

	private static function translate_text_with_chunking( $text, $key, $language ) {
		$chunks = self::chunk_by_sentence( $text );
		$out    = array();

		foreach ( $chunks as $chunk ) {
			$res = self::translate_batch( array( $chunk ), $key, $language );
			if ( ! isset( $res[0] ) ) {
				return false;
			}
			$out[] = $res[0];
		}

		return trim( implode( ' ', $out ) );
	}

	private static function chunk_by_sentence( $text ) {
		$sentences = preg_split( '/(?<=[.!?])\s+/', $text );
		$out       = array();
		$current   = '';

		foreach ( $sentences as $s ) {
			$test = $current ? "$current $s" : $s;
			if ( mb_strlen( $test ) <= self::TEXT_CHUNK_SIZE ) {
				$current = $test;
			} else {
				$out[]   = $current;
				$current = $s;
			}
		}

		if ( $current ) {
			$out[] = $current;
		}

		return $out;
	}

	private static function translate_plain_text( $text, $key, $language ) {
		$res = self::translate_batch( array( trim( $text ) ), $key, $language );
		return $res[0] ?? false;
	}

	private static function translate_batch( array $texts, $key, $language ) {

		$delimiter = "\n<<<###>>>\n";

		$prompt = <<<PROMPT
Translate the following PLAIN TEXT into {$language}.
Keep meaning and tone.
Return text segments in the same order separated by {$delimiter}.
Return ONLY the translations.
PROMPT;

		$body = array(
			'model'       => 'gpt-4o-mini',
			'temperature' => 0.2,
			'messages'    => array(
				array( 'role' => 'system', 'content' => 'Professional translator.' ),
				array( 'role' => 'user', 'content' => $prompt . "\n\n" . implode( $delimiter, $texts ) ),
			),
		);

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['choices'][0]['message']['content'] ) ) {
			return false;
		}

		return array_map( 'trim', explode( $delimiter, $data['choices'][0]['message']['content'] ) );
	}
}
