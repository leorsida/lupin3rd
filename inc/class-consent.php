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
 * This site writes nothing optional. Everything in the cookie table is either
 * required for the page to work or the direct result of an action the reader
 * took — signing in, ticking «ricorda», switching the theme. There is nothing
 * to consent to, so the notice does not pretend to ask: it states what is
 * stored, offers the table behind a button, and takes an acknowledgement.
 *
 * Third-party frames are handled without a category, because a category would
 * imply the alternative is loading them. `gate_embeds()` replaces every
 * `<iframe>` pointing at another host with a placeholder naming that host,
 * server-side, before the HTML is sent. A reader who wants one loads that one,
 * and nothing is remembered: the next page asks again. That is stricter than
 * any answer a banner could have collected, and it costs the reader one click
 * on the rare page that has an embed at all.
 *
 * @package Lupin3rd
 */
final class Consent {

	use Singleton;

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
	 * The notice itself: a statement and two controls.
	 *
	 * There is no «accept» because there is nothing to accept — only one button
	 * that dismisses the notice, and one that opens the table. A banner that
	 * offered a refusal here would be offering to stop doing something the site
	 * is not doing.
	 *
	 * @return array<string, string>
	 */
	private function consent_modal(): array {
		return array(
			'title'              => __( 'Gestione dei cookie', 'lupin3rd' ),
			'description'        => __( 'Questo sito non fa profilazione, non misura e non ha script di terze parti nelle sue pagine. Gli unici dati che restano nel tuo browser servono a farlo funzionare, farti ricordare il nome nei commenti oppure scegliere il tema scuro. Non c\'è niente da accettare e niente da rifiutare — se vuoi, puoi vedere l\'elenco completo cliccando su approfondisci.', 'lupin3rd' ),
			'acceptNecessaryBtn' => __( 'Ho capito', 'lupin3rd' ),
			'showPreferencesBtn' => __( 'Approfindisci', 'lupin3rd' ),
			'footer'             => sprintf(
				'<a href="%s">%s</a>',
				esc_url( home_url( '/privacy/' ) ),
				esc_html__( 'Cosa conserviamo, e cosa no', 'lupin3rd' )
			),
		);
	}

	/**
	 * The panel behind «Vedi quali».
	 *
	 * One row, and its switch cannot move: that is the honest shape of a site
	 * with no optional storage. What the panel is really for is the table
	 * underneath it, which is the same one printed on /privacy/ — shown here
	 * because this is where somebody is looking.
	 *
	 * @return array<string, mixed>
	 */
	private function preferences_modal(): array {
		return array(
			'title'              => __( 'Cosa resta nel tuo browser', 'lupin3rd' ),
			'acceptNecessaryBtn' => __( 'Chiudi', 'lupin3rd' ),
			'closeIconLabel'     => __( 'Chiudi', 'lupin3rd' ),
			'sections'           => array(
				array(
					'title'       => __( 'In poche parole', 'lupin3rd' ),
					'description' => sprintf(
						/* translators: %s: link to the privacy page. */
						__( 'Niente analytics, niente pubblicità, nessuno script di terze parti: c\'è solo lo stretto necessario. Per i dettagli, vai in %s.', 'lupin3rd' ),
						sprintf(
							'<a href="%s" class="cc__link">%s</a>',
							esc_url( home_url( '/privacy/' ) ),
							esc_html__( 'pagina privacy', 'lupin3rd' )
						)
					),
				),
				array(
					'title'          => __( 'Cookie strettamente necessari', 'lupin3rd' ),
					'description'    => __( 'Senza questi il sito non funziona. Nessuno di loro serve a profilare.', 'lupin3rd' ),
					'linkedCategory' => 'necessary',
					'cookieTable'    => $this->cookie_table(),
				),
				array(
					'title'       => __( 'Inclusione di parti di pagine', 'lupin3rd' ),
					'description' => __( 'Quando un articolo incorpora un video o una mappa ospitati da un altro sito, non li carichiamo: al loro posto trovi un segnaposto che dice quale dominio verrebbe contattato. Se vuoi vederlo lo carichi tu, quella volta soltanto.', 'lupin3rd' ),
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
					'desc' => __( 'Ricorda che hai già visto questo avviso, per non riproportelo a ogni pagina.', 'lupin3rd' ),
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
