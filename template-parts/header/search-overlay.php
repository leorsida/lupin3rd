<?php
/**
 * Search overlay.
 *
 * Filters a short list of recent pieces in the browser for an instant answer.
 * Unlike the prototype, which has a bare input, this is a real form: pressing
 * Enter reaches the search page, so the overlay degrades to a working search
 * when the script does not load.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Content_Types;
use Lupin3rd\I18n;
use Lupin3rd\Search;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

$l3_recent = Search::recent( 12 );
?>
<div
	class="overlay"
	data-search-overlay
	role="dialog"
	aria-modal="true"
	aria-label="<?php esc_attr_e( 'Cerca nel sito', 'lupin3rd' ); ?>"
	hidden>
	<div class="overlay-panel">
		<form class="overlay-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="sr-only" for="l3-search"><?php esc_html_e( 'Termine di ricerca', 'lupin3rd' ); ?></label>
			<input
				class="input"
				id="l3-search"
				type="search"
				name="s"
				data-search-input
				autocomplete="off"
				placeholder="<?php esc_attr_e( 'Cerca notizie, tutorial, dossier, tag…', 'lupin3rd' ); ?>">
			<button class="btn btn-secondary" type="button" data-search-close>
				<?php esc_html_e( 'Chiudi', 'lupin3rd' ); ?>
			</button>
		</form>

		<ul class="overlay-results" data-search-results>
			<?php foreach ( $l3_recent as $l3_post ) : ?>
				<li data-live-search data-keywords="<?php echo esc_attr( Search::keywords( $l3_post ) ); ?>">
					<a href="<?php echo esc_url( (string) get_permalink( $l3_post ) ); ?>">
						<span class="r-title"><?php echo esc_html( get_the_title( $l3_post ) ); ?></span>
						<span class="meta">
							<?php
							$l3_category = Template_Tags::primary_category( $l3_post );
							echo esc_html( $l3_category ? $l3_category->name : Content_Types::label( $l3_post->post_type ) );
							?>
							&middot;
							<?php echo esc_html( I18n::instance()->post_date( $l3_post ) ); ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="overlay-empty hidden" data-search-empty>
			<?php esc_html_e( 'Nessun risultato fra i pezzi recenti. Invio per cercare in tutto l\'archivio.', 'lupin3rd' ); ?>
		</p>

		<p class="overlay-hint meta"><?php esc_html_e( 'esc per chiudere · invio per cercare in archivio', 'lupin3rd' ); ?></p>
	</div>
</div>
