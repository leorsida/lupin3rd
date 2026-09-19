<?php
/**
 * The cookie notice, and the one thing it actually governs.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * A notice that asks about nothing is theatre, and on a site whose subject is
 * surveillance it is the worst kind: it performs a concern the rest of the
 * page is built to make unnecessary.
 *
 * So this one governs something real. There is exactly one optional category —
 * third-party embeds — and it is enforced rather than declared: an `<iframe>`
 * pointing somewhere else is replaced, server-side, with a placeholder that
 * names the host it would have contacted. Nothing is fetched until the reader
 * says so. Everything else the site stores — the session, the comment fields a
 * reader asked to be remembered, the theme switch — is strictly necessary or
 * set by an explicit action, and is described on /privacy/ rather than put
 * behind a button that only ever gets one answer.
 *
 * One optional category also means no preferences panel: two buttons say what
 * a page of checkboxes would, and the panel's stylesheet is 16KB this site
 * does not send.
 */
final class Consent {

	use Singleton;

	/**
	 * Category the embeds belong to. Matches the name used in the bundle.
	 */
	public const CATEGORY_EMBEDS = 'embeds';

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_filter( 'the_content', array( $this, 'gate_embeds' ), 20 );
		add_filter( 'embed_oembed_html', array( $this, 'gate_oembed' ), 20, 2 );
		add_action( 'wp_footer', array( $this, 'print_config' ) );
	}

	/**
	 * Hand the notice its wording, as data rather than as code.
	 *
	 * A `type="application/json"` block is not executed, so a
	 * `script-src` policy built on hashes does not have to account for it —
	 * which an inline `wp_add_inline_script()` would, and its hash would change
	 * with every translated string. The bundle reads this and nothing else.
	 */
	public function print_config(): void {
		$config = array(
			'locale' => substr( I18n::instance()->language(), 0, 2 ),
			'notice' => array(
				'consentModal' => array(
					'title'              => __( 'Un riquadro ospitato altrove', 'lupin3rd' ),
					'description'        => __( 'Questo sito non profila e non ha terze parti nelle sue pagine. L\'unica eccezione possibile è un riquadro incorporato dentro un articolo — un video, una mappa — che, per caricarsi, fa sapere a chi lo ospita che sei qui. Puoi permetterlo una volta per tutte, o lasciarlo bloccato e decidere riquadro per riquadro.', 'lupin3rd' ),
					'acceptAllBtn'       => __( 'Permetti i riquadri', 'lupin3rd' ),
					'acceptNecessaryBtn' => __( 'Lasciali bloccati', 'lupin3rd' ),
					'footer'             => sprintf(
						'<a href="%s">%s</a>',
						esc_url( home_url( '/privacy/' ) ),
						esc_html__( 'Cosa conserviamo, e cosa no', 'lupin3rd' )
					),
				),
			),
		);

		printf(
			'<script type="application/json" id="l3-consent-config">%s</script>' . "\n",
			wp_json_encode( $config ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, in a block that is read and never executed.
		);
	}

	/**
	 * Replace third-party iframes in a piece with a placeholder.
	 *
	 * Runs at priority 20, after the blocks have rendered and after WordPress
	 * has turned its own oEmbeds into markup, so that what is scanned is the
	 * HTML a reader would otherwise have received.
	 *
	 * @param string $content Rendered content.
	 */
	public function gate_embeds( $content ): string {
		$content = (string) $content;

		if ( ! str_contains( $content, '<iframe' ) ) {
			return $content;
		}

		return (string) preg_replace_callback(
			'#<iframe\b[^>]*>.*?</iframe>|<iframe\b[^>]*/?>#is',
			array( $this, 'replace_iframe' ),
			$content
		);
	}

	/**
	 * Same for anything oEmbed produced on its own.
	 *
	 * @param string $html The embed markup.
	 * @param string $url  The URL that was embedded.
	 */
	public function gate_oembed( $html, $url ): string {
		return $this->gate_embeds( (string) $html );
	}

	/**
	 * Turn one iframe into a placeholder, unless it points at this site.
	 *
	 * @param array<int, string> $match Regular expression match.
	 */
	private function replace_iframe( array $match ): string {
		$iframe = $match[0];
		$src    = array();

		if ( ! preg_match( '#\ssrc=["\']([^"\']+)["\']#i', $iframe, $src ) ) {
			// No source is nothing to fetch, and nothing to ask about.
			return $iframe;
		}

		$host = (string) wp_parse_url( $src[1], PHP_URL_HOST );

		if ( '' === $host || $this->is_own( $host ) ) {
			return $iframe;
		}

		return sprintf(
			'<div class="embed-gate" data-embed-gate data-embed-host="%1$s" data-embed="%2$s">' .
				'<p>%3$s</p>' .
				'<button class="btn btn-secondary btn-sm" type="button" data-embed-show>%4$s</button>' .
			'</div>',
			esc_attr( $host ),
			esc_attr( base64_encode( $iframe ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Carrying markup through an attribute, not hiding it.
			sprintf(
				/* translators: %s: hostname the embed would contact. */
				esc_html__( 'Questo riquadro è ospitato da %s. Caricarlo significa che quel sito vede che sei qui: non lo facciamo senza chiedertelo.', 'lupin3rd' ),
				'<span class="embed-gate-host">' . esc_html( $host ) . '</span>'
			),
			esc_html__( 'Carica questo riquadro', 'lupin3rd' )
		);
	}

	/**
	 * Whether a hostname is this site's own.
	 *
	 * @param string $host Hostname from the embed.
	 */
	private function is_own( string $host ): bool {
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		return strtolower( $host ) === strtolower( $home );
	}
}
