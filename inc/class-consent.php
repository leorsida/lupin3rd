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
 * There is still a preferences panel, and it is not there for the look of it:
 * it is where the cookie table lives. A reader who wants to know what is
 * stored before answering should not have to leave the notice to find out, and
 * a panel listing a category without saying what it writes would be the
 * checkbox theatre described above with extra steps.
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
				'consentModal'     => $this->consent_modal(),
				'preferencesModal' => $this->preferences_modal(),
			),
		);

		printf(
			'<script type="application/json" id="l3-consent-config">%s</script>' . "\n",
			wp_json_encode( $config ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, in a block that is read and never executed.
		);
	}

	/**
	 * The notice itself: three buttons, because the third one is the only
	 * honest way to offer the middle answer.
	 *
	 * Accepting and refusing are both one click. «Personalizza» opens the panel
	 * below, which is where the cookie table lives — and a reader who wants to
	 * know what is stored before answering should not have to leave the notice
	 * to find out.
	 *
	 * @return array<string, string>
	 */
	private function consent_modal(): array {
		return array(
			'title'              => __( 'Un riquadro ospitato altrove', 'lupin3rd' ),
			'description'        => __( 'Questo sito non profila e non ha terze parti nelle sue pagine. L\'unica eccezione possibile è un riquadro incorporato dentro un articolo — un video, una mappa — che, per caricarsi, fa sapere a chi lo ospita che sei qui. Puoi permetterlo una volta per tutte, o lasciarlo bloccato e decidere riquadro per riquadro.', 'lupin3rd' ),
			'acceptAllBtn'       => __( 'Permetti i riquadri', 'lupin3rd' ),
			'acceptNecessaryBtn' => __( 'Lasciali bloccati', 'lupin3rd' ),
			'showPreferencesBtn' => __( 'Personalizza', 'lupin3rd' ),
			'footer'             => sprintf(
				'<a href="%s">%s</a>',
				esc_url( home_url( '/privacy/' ) ),
				esc_html__( 'Cosa conserviamo, e cosa no', 'lupin3rd' )
			),
		);
	}

	/**
	 * The panel behind «Personalizza».
	 *
	 * Two rows, because the site has two categories and one of them cannot be
	 * switched off. The table under the first row is the same one printed on
	 * /privacy/: shown here because this is where somebody is deciding, and
	 * because a panel that lists a category without saying what it stores is
	 * the checkbox theatre this notice is meant to avoid.
	 *
	 * @return array<string, mixed>
	 */
	private function preferences_modal(): array {
		return array(
			'title'               => __( 'Cosa resta nel tuo browser', 'lupin3rd' ),
			'acceptAllBtn'        => __( 'Permetti tutto', 'lupin3rd' ),
			'acceptNecessaryBtn'  => __( 'Solo il necessario', 'lupin3rd' ),
			'savePreferencesBtn'  => __( 'Salva le mie scelte', 'lupin3rd' ),
			'closeIconLabel'      => __( 'Chiudi', 'lupin3rd' ),
			'serviceCounterLabel' => __( 'servizio|servizi', 'lupin3rd' ),
			'sections'            => array(
				array(
					'title'       => __( 'In due righe', 'lupin3rd' ),
					'description' => sprintf(
						/* translators: %s: link to the privacy page. */
						__( 'Niente analitica, niente pubblicità, nessuno script di terze parti. C\'è una sola cosa da decidere, ed è qui sotto. Il resto è spiegato per esteso in %s.', 'lupin3rd' ),
						sprintf(
							'<a href="%s" class="cc__link">%s</a>',
							esc_url( home_url( '/privacy/' ) ),
							esc_html__( 'pagina privacy', 'lupin3rd' )
						)
					),
				),
				array(
					'title'          => __( 'Strettamente necessari', 'lupin3rd' ),
					'description'    => __( 'Senza questi il sito non funziona, o dimentica qualcosa che gli hai chiesto di ricordare. Nessuno di loro serve a profilare, e nessuno appartiene a un altro dominio.', 'lupin3rd' ),
					'linkedCategory' => 'necessary',
					'cookieTable'    => $this->cookie_table(),
				),
				array(
					'title'          => __( 'Riquadri incorporati', 'lupin3rd' ),
					'description'    => __( 'Ogni tanto un articolo incorpora un video o una mappa che sta altrove. Quel riquadro, per caricarsi, fa sapere a chi lo ospita che sei su questa pagina. Se lasci questo interruttore spento, al suo posto trovi un segnaposto che dice quale dominio verrebbe contattato, e decidi tu riquadro per riquadro.', 'lupin3rd' ),
					'linkedCategory' => self::CATEGORY_EMBEDS,
				),
				array(
					'title'       => __( 'Ripensarci', 'lupin3rd' ),
					'description' => __( 'Questo pannello si riapre dal fondo di ogni pagina, e la risposta che dai vale da subito. Cancellare i cookie del sito dal tuo browser riporta tutto al punto di partenza.', 'lupin3rd' ),
				),
			),
		);
	}

	/**
	 * What the necessary category actually writes.
	 *
	 * Kept in step with the table on /privacy/ by hand: see
	 * `docs/llmwiki/17-privacy.md`, which lists both places.
	 *
	 * @return array<string, mixed>
	 */
	private function cookie_table(): array {
		return array(
			'headers' => array(
				'name' => __( 'Nome', 'lupin3rd' ),
				'desc' => __( 'A cosa serve', 'lupin3rd' ),
				'dur'  => __( 'Durata', 'lupin3rd' ),
			),
			'body'    => array(
				array(
					'name' => 'wordpress_logged_in_*',
					'desc' => __( 'Ti tiene connesso. Scritto solo se accedi.', 'lupin3rd' ),
					'dur'  => __( 'fino a 14 giorni', 'lupin3rd' ),
				),
				array(
					'name' => 'l3_hint',
					'desc' => __( 'Dice alla testata come chiamarti e quale avatar disegnare. Non autorizza niente.', 'lupin3rd' ),
					'dur'  => __( '14 giorni', 'lupin3rd' ),
				),
				array(
					'name' => 'comment_author_*',
					'desc' => __( 'Ricorda pseudonimo ed email nel modulo dei commenti, se spunti quella casella.', 'lupin3rd' ),
					'dur'  => __( 'circa un anno', 'lupin3rd' ),
				),
				array(
					'name' => 'l3_consent',
					'desc' => __( 'Ricorda la risposta che stai dando adesso.', 'lupin3rd' ),
					'dur'  => __( '180 giorni', 'lupin3rd' ),
				),
				array(
					'name' => 'l3-theme',
					'desc' => __( 'Il tema chiaro o scuro che hai scelto. Resta nel tuo browser e non viene mai spedito al server.', 'lupin3rd' ),
					'dur'  => __( 'finché non lo cancelli', 'lupin3rd' ),
				),
			),
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
