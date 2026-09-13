<?php
/**
 * Asset loading.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the theme's one stylesheet and one script, and prints the inline
 * snippet that sets the colour scheme before first paint.
 *
 * Nothing else is loaded on a public page. No jQuery, no block library CSS, no
 * emoji script, no external request of any kind.
 */
final class Assets {

	use Singleton;

	/**
	 * The inline snippet that applies the stored colour scheme.
	 *
	 * It has to run before the first paint, or the page flashes light before
	 * turning dark. Keep it on one line and free of trailing whitespace: its
	 * SHA-256 hash is what the Content-Security-Policy allows, and one changed
	 * byte makes the browser refuse it. Recompute with tools/csp-hash.php.
	 */
	private const THEME_SNIPPET = 'try{var t=localStorage.getItem("l3-theme");if(t==="light"||t==="dark"){document.documentElement.setAttribute("data-theme",t)}}catch(e){}';

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public' ) );
		add_action( 'wp_head', array( $this, 'print_theme_snippet' ), 1 );
		add_filter( 'script_loader_tag', array( $this, 'defer_theme_script' ), 10, 3 );
	}

	/**
	 * Enqueue the stylesheet and the bundle.
	 */
	public function enqueue_public(): void {
		$suffix = $this->use_unminified() ? '' : '.min';

		wp_enqueue_style(
			'lupin3rd',
			Theme::url( "css/lupin3rd{$suffix}.css" ),
			array(),
			$this->version( "css/lupin3rd{$suffix}.css" )
		);

		wp_enqueue_script(
			'lupin3rd',
			Theme::url( "js/lupin3rd{$suffix}.js" ),
			array(),
			$this->version( "js/lupin3rd{$suffix}.js" ),
			true
		);

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Print the colour-scheme snippet as the first thing in the head.
	 */
	public function print_theme_snippet(): void {
		printf( "<script>%s</script>\n", self::THEME_SNIPPET ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A fixed literal, allowed by CSP hash.
	}

	/**
	 * Return the snippet, so the build tool can hash exactly what ships.
	 */
	public static function theme_snippet(): string {
		return self::THEME_SNIPPET;
	}

	/**
	 * Add defer to the theme's own script tag.
	 *
	 * @param string $tag    The script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 */
	public function defer_theme_script( string $tag, string $handle, string $src ): string {
		if ( 'lupin3rd' !== $handle ) {
			return $tag;
		}

		return str_replace( ' src=', ' defer src=', $tag );
	}

	/**
	 * Whether to serve unminified assets.
	 */
	private function use_unminified(): bool {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}

	/**
	 * Version string for an asset: theme version plus file modification time.
	 *
	 * @param string $relative Path relative to the theme root.
	 */
	private function version( string $relative ): string {
		$path = Theme::path( $relative );

		return file_exists( $path )
			? Theme::VERSION . '.' . filemtime( $path )
			: Theme::VERSION;
	}
}
