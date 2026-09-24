<?php
/**
 * Proprietary blocks.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the two blocks the desk writes with, and enforces the one
 * editorial rule that is not a matter of taste.
 *
 * Both blocks render server-side. Neither ships a front-end script: the copy
 * button is wired by the theme's single bundle, through a data attribute.
 */
final class Blocks {

	use Singleton;

	/**
	 * The notice variant a tutorial cannot be published without.
	 */
	public const LEGAL_VARIANT = 'legal';

	/**
	 * The heading a notice carries, by variant.
	 *
	 * It lives here rather than in blocks/notice/render.php because the
	 * Markdown twin of a page has to print the same words: a notice that reads
	 * «Informativa legale» on the page and «Avviso» in the file would be two
	 * descriptions of one thing, kept in step by nobody.
	 *
	 * @param string $variant Notice variant.
	 */
	public static function notice_title( string $variant ): string {
		$titles = array(
			'method'            => __( 'Nota di metodo', 'lupin3rd' ),
			self::LEGAL_VARIANT => __( 'Informativa legale', 'lupin3rd' ),
			'correction'        => __( 'Correzione', 'lupin3rd' ),
		);

		return $titles[ $variant ] ?? $titles['method'];
	}

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'block_categories_all', array( $this, 'add_category' ), 10, 1 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );

		// Two gates, because one is not enough: the editor, and everything else.
		add_filter( 'rest_pre_insert_' . Content_Types::TUTORIAL, array( $this, 'gate_rest_publish' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( $this, 'gate_insert' ), 10, 2 );
	}

	/**
	 * Register the blocks from their block.json manifests.
	 */
	public function register(): void {
		foreach ( array( 'code', 'notice' ) as $block ) {
			$path = Theme::path( 'blocks/' . $block );

			if ( file_exists( $path . '/block.json' ) ) {
				register_block_type( $path );
			}
		}
	}

	/**
	 * Give the blocks a category of their own in the inserter.
	 *
	 * @param array<int, array<string, mixed>> $categories Block categories.
	 * @return array<int, array<string, mixed>>
	 */
	public function add_category( array $categories ): array {
		array_unshift(
			$categories,
			array(
				'slug'  => 'lupin3rd',
				'title' => __( 'lupin3rd', 'lupin3rd' ),
			)
		);

		return $categories;
	}

	/**
	 * Load the editor bundle: block controls and the publish gate's warning.
	 */
	public function enqueue_editor_assets(): void {
		$relative = 'js/lupin3rd-editor.js';
		$path     = Theme::path( $relative );

		if ( ! file_exists( $path ) ) {
			return;
		}

		wp_enqueue_script(
			'lupin3rd-editor',
			Theme::url( $relative ),
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-plugins', 'wp-edit-post' ),
			Theme::VERSION . '.' . filemtime( $path ),
			true
		);

		wp_set_script_translations( 'lupin3rd-editor', 'lupin3rd', Theme::path( 'languages' ) );
	}

	// --- The legal-scope rule ---

	/**
	 * Whether a piece of content carries a legal-scope notice.
	 *
	 * Parses the blocks rather than searching the string: a comment, a quoted
	 * example or an attribute mentioning "legal" must not satisfy the rule.
	 *
	 * Expects UNSLASHED content. Block attributes are JSON inside the
	 * delimiter, and slashed JSON does not parse — callers reading from
	 * wp_insert_post_data must wp_unslash() first. The REST prepared object is
	 * already unslashed at rest_pre_insert_*, so that path passes it straight
	 * through.
	 *
	 * @param string $content Post content, unslashed.
	 */
	public static function has_legal_notice( string $content ): bool {
		if ( ! str_contains( $content, 'l3/notice' ) ) {
			return false;
		}

		foreach ( self::flatten( parse_blocks( $content ) ) as $block ) {
			if ( 'l3/notice' === ( $block['blockName'] ?? '' )
				&& self::LEGAL_VARIANT === ( $block['attrs']['variant'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Flatten a block tree, so a notice nested in a column still counts.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return array<int, array<string, mixed>>
	 */
	private static function flatten( array $blocks ): array {
		$flat = array();

		foreach ( $blocks as $block ) {
			$flat[] = $block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				$flat = array_merge( $flat, self::flatten( $block['innerBlocks'] ) );
			}
		}

		return $flat;
	}

	/**
	 * Whether a status means the piece is publicly readable.
	 *
	 * @param string $status Post status.
	 */
	private static function is_public_status( string $status ): bool {
		return in_array( $status, array( 'publish', 'future', 'private' ), true );
	}

	/**
	 * Refuse to publish a tutorial through the REST API without a legal notice.
	 *
	 * This is the gate the block editor hits, so the author sees why the button
	 * refused rather than watching a save fail silently.
	 *
	 * @param \stdClass        $prepared Post object about to be inserted.
	 * @param \WP_REST_Request $request  The request.
	 * @return \stdClass|\WP_Error
	 */
	public function gate_rest_publish( $prepared, $request ) {
		$status = (string) ( $prepared->post_status ?? $request['status'] ?? '' );

		if ( ! self::is_public_status( $status ) ) {
			return $prepared;
		}

		$content = (string) ( $prepared->post_content ?? '' );

		if ( '' === $content && ! empty( $prepared->ID ) ) {
			$existing = get_post( $prepared->ID );
			$content  = $existing ? (string) $existing->post_content : '';
		}

		if ( self::has_legal_notice( $content ) ) {
			return $prepared;
		}

		return new \WP_Error(
			'l3_missing_legal_notice',
			__( 'Un tutorial non viene pubblicato senza il riquadro con l\'informativa legale. Se l\'Avviso c\'è già, controlla la variante: la barra laterale del blocco ha «Avviso → Variante», e quella predefinita è «Nota di metodo».', 'lupin3rd' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Demote a tutorial that reaches publication by any other route.
	 *
	 * WP-CLI, an import and a direct wp_insert_post() all pass through here and
	 * none of them sees the editor's warning. The piece is kept as a draft
	 * rather than rejected, so no work is lost.
	 *
	 * @param array<string, mixed> $data    Sanitised post data.
	 * @param array<string, mixed> $postarr Raw post data.
	 * @return array<string, mixed>
	 */
	public function gate_insert( array $data, array $postarr ): array {
		if ( Content_Types::TUTORIAL !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}

		if ( ! self::is_public_status( (string) ( $data['post_status'] ?? '' ) ) ) {
			return $data;
		}

		/*
		 * wp_insert_post_data receives slashed data. Left as it arrives, the
		 * block delimiter reads {\"variant\":\"legal\"} — not valid JSON, so
		 * parse_blocks yields no attributes, the variant looks absent and a
		 * perfectly good tutorial gets demoted. Unslash before parsing.
		 */
		if ( self::has_legal_notice( wp_unslash( (string) ( $data['post_content'] ?? '' ) ) ) ) {
			return $data;
		}

		$data['post_status'] = 'draft';

		/*
		 * Say so. wp_insert_post_data cannot show the caller a message, so a
		 * demotion here is invisible: WP-CLI reports success and the piece
		 * quietly stops being published. A line in the log is the difference
		 * between a five-minute diagnosis and an afternoon.
		 */
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					'lupin3rd: tutorial "%s" kept as a draft — no l3/notice with variant "%s" in its content.',
					$postarr['post_title'] ?? $data['post_title'] ?? '(senza titolo)',
					self::LEGAL_VARIANT
				)
			);
		}

		return $data;
	}

	// --- Monochrome highlighting ---

	/**
	 * Keywords worth marking, by language.
	 *
	 * Deliberately short. The point is to make structure legible, not to
	 * reimplement a grammar: a long list produces a speckled block that is
	 * harder to read than plain text.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const KEYWORDS = array(
		'bash'   => array( 'if', 'then', 'else', 'fi', 'for', 'in', 'do', 'done', 'while', 'case', 'esac', 'function', 'return', 'export', 'local', 'sudo' ),
		'php'    => array( 'function', 'class', 'return', 'if', 'else', 'elseif', 'foreach', 'for', 'while', 'new', 'use', 'namespace', 'public', 'private', 'protected', 'static', 'const', 'echo', 'true', 'false', 'null' ),
		'js'     => array( 'const', 'let', 'var', 'function', 'return', 'if', 'else', 'for', 'while', 'class', 'import', 'export', 'default', 'await', 'async', 'new', 'true', 'false', 'null' ),
		'python' => array( 'def', 'class', 'return', 'if', 'elif', 'else', 'for', 'while', 'import', 'from', 'with', 'as', 'try', 'except', 'raise', 'True', 'False', 'None' ),
	);

	/**
	 * Highlight source monochromatically, on the server.
	 *
	 * Three token classes, no library, nothing shipped to the browser. The
	 * colouring carries weight and shade rather than hue, so it survives a
	 * monochrome screen and colour blindness — and so that a reader cannot tell
	 * a keyword from a string by hue alone.
	 *
	 * @param string $code     Raw source.
	 * @param string $language Language hint.
	 */
	public static function highlight( string $code, string $language = '' ): string {
		$language = strtolower( trim( $language ) );
		$keywords = self::KEYWORDS[ $language ] ?? array();

		// Comment and string spans are found first: a keyword inside either is
		// not a keyword.
		$pattern = '/(?P<comment>#[^\n]*|\/\/[^\n]*|\/\*.*?\*\/)|(?P<string>"(?:[^"\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\n]|\\\\.)*\')/s';

		$out    = '';
		$offset = 0;

		while ( preg_match( $pattern, $code, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
			// A group that did not take part in the match reports offset -1.
			$is_comment = $match['comment'][1] >= 0;
			$token      = $is_comment ? $match['comment'] : $match['string'];

			$start = (int) $token[1];
			$text  = (string) $token[0];
			$class = $is_comment ? 'tok-com' : 'tok-str';

			$out   .= self::mark_keywords( substr( $code, $offset, $start - $offset ), $keywords );
			$out   .= sprintf( '<span class="%s">%s</span>', $class, esc_html( $text ) );
			$offset = $start + strlen( $text );
		}

		return $out . self::mark_keywords( substr( $code, $offset ), $keywords );
	}

	/**
	 * Escape a plain span of code and wrap its keywords.
	 *
	 * @param string             $text     Source fragment.
	 * @param array<int, string> $keywords Keywords for the language.
	 */
	private static function mark_keywords( string $text, array $keywords ): string {
		$escaped = esc_html( $text );

		if ( ! $keywords ) {
			return $escaped;
		}

		$alternatives = implode( '|', array_map( 'preg_quote', $keywords ) );

		return (string) preg_replace(
			'/\b(' . $alternatives . ')\b/',
			'<span class="tok-kw">$1</span>',
			$escaped
		);
	}
}
