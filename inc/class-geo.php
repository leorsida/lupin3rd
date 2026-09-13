<?php
/**
 * Generative engine surface.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * What a generative engine needs in order to cite the site correctly.
 *
 * Serves /llms.txt, and states the crawler policy in robots.txt. The policy
 * follows from the licence rather than from a mood about AI: the texts are
 * CC BY-SA 4.0, so reuse is welcome on the licence's terms, and the terms
 * include share-alike.
 */
final class Geo {

	use Singleton;

	/**
	 * Crawlers that fetch in order to answer and cite. Allowed.
	 *
	 * @var array<int, string>
	 */
	private const CITING = array( 'OAI-SearchBot', 'Claude-SearchBot', 'PerplexityBot', 'ChatGPT-User', 'Claude-User' );

	/**
	 * Collectors building training corpora. Not allowed.
	 *
	 * @var array<int, string>
	 */
	private const COLLECTING = array( 'GPTBot', 'ClaudeBot', 'meta-externalagent', 'CCBot', 'Google-Extended' );

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'add_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'serve_llms_txt' ) );
		add_filter( 'robots_txt', array( $this, 'robots_txt' ), 10, 2 );
	}

	/**
	 * Route /llms.txt.
	 */
	public function add_rewrite(): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?l3_llms=1', 'top' );
	}

	/**
	 * Register the query variable the rewrite sets.
	 *
	 * @param array<int, string> $vars Public query variables.
	 * @return array<int, string>
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = 'l3_llms';

		return $vars;
	}

	/**
	 * Serve /llms.txt.
	 */
	public function serve_llms_txt(): void {
		if ( ! get_query_var( 'l3_llms' ) ) {
			return;
		}

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );

		echo $this->llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text document, escaped as it is built.
		exit;
	}

	/**
	 * Build the body of /llms.txt.
	 */
	private function llms_txt(): string {
		$home = home_url( '/' );

		/*
		 * This is a plain-text document: an HTML entity in it is simply wrong.
		 * get_bloginfo() escapes for display, so the apostrophe in the tagline
		 * would arrive as &#039;.
		 */
		$name        = Template_Tags::to_plain_text( get_bloginfo( 'name' ) );
		$description = Template_Tags::to_plain_text( get_bloginfo( 'description' ) );

		$lines = array(
			'# ' . $name,
			'',
			'> ' . $description,
			'',
			'Rivista indipendente di cultura tecnica ai margini, online dal 1999.',
			'Redazione distribuita, nessun investitore.',
			'',
			'## Licenza e citazione',
			'',
			'- Testi: CC BY-SA 4.0 (https://creativecommons.org/licenses/by-sa/4.0/)',
			'- Codice: MIT',
			'- Per citare: titolo del pezzo, ' . $name . ', data di pubblicazione, URL.',
			'- Le correzioni restano in fondo al pezzo e non vengono mai cancellate:',
			'  la data di ultima modifica è quella che conta.',
			'',
			'## Cosa è ammesso',
			'',
			'Sono ammessi i crawler che leggono per rispondere citando la fonte, e i',
			'recuperi avviati da una persona. Non sono ammessi i raccoglitori che',
			'costruiscono corpora di addestramento: non perché addestrare sia',
			'illegittimo, ma perché lo share-alike di CC BY-SA non è onorabile da un',
			'insieme di pesi. La condizione della licenza è inadempibile, quindi la',
			'licenza non è concessa. Se questo cambia, cambia anche questa riga.',
			'',
			'## Sezioni',
			'',
			'- Notizie: ' . $home,
			'- Tutorial: ' . get_post_type_archive_link( Content_Types::TUTORIAL ),
			'- Dossier: ' . get_post_type_archive_link( Content_Types::DOSSIER ),
			'- Chi siamo: ' . $home . 'chi-siamo/',
			'- Linee guida: ' . $home . 'linee-guida/',
			'',
			'## Contatti',
			'',
			'Materiale sensibile: solo tramite SecureDrop sul mirror .onion o via email',
			'cifrata. Mai dai commenti, mai da un modulo di contatto.',
			'',
		);

		return implode( "\n", $lines );
	}

	/**
	 * Append the crawler policy to robots.txt.
	 *
	 * @param string $output  Current robots.txt body.
	 * @param bool   $is_public Whether the site is public.
	 */
	public function robots_txt( string $output, $is_public ): string {
		if ( ! $is_public ) {
			return $output;
		}

		$lines = array(
			'',
			'# Crawler generativi.',
			'# La politica segue dalla licenza: i testi sono CC BY-SA 4.0.',
			'# Motivazione estesa in /llms.txt',
			'',
			'# Ammessi: leggono per rispondere citando la fonte.',
		);

		foreach ( self::CITING as $agent ) {
			$lines[] = 'User-agent: ' . $agent;
			$lines[] = 'Allow: /';
			$lines[] = '';
		}

		$lines[] = '# Non ammessi: raccolgono per corpora di addestramento.';
		$lines[] = '# Lo share-alike non è onorabile da un insieme di pesi.';

		foreach ( self::COLLECTING as $agent ) {
			$lines[] = 'User-agent: ' . $agent;
			$lines[] = 'Disallow: /';
			$lines[] = '';
		}

		$lines[] = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );

		return $output . implode( "\n", $lines ) . "\n";
	}
}
