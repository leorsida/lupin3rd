<?php
/**
 * A member profile: /membri/<pseudonimo>/.
 *
 * What this page shows is a promise the site makes in public: pseudonym, role
 * and join date, and never the email, the country or the address last used to
 * sign in. There is no field here that could hold any of those, and a test in
 * tools/e2e/community.test.js checks the rendered page rather than trusting
 * this comment.
 *
 * The private half of an account — bookmarks, notifications, devices — is not
 * here. It lives under /account/, which is never cached and never public.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Community;
use Lupin3rd\I18n;
use Lupin3rd\Marks;
use Lupin3rd\Profile_Fields;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

$l3_user = get_queried_object();

if ( ! $l3_user instanceof WP_User ) {
	get_template_part( 'template-parts/content/empty-state' );
	get_footer();
	return;
}

$l3_pseudonym = Profile_Fields::pseudonym( $l3_user );
$l3_stats     = Community::profile_stats( $l3_user->ID );
$l3_joined    = Profile_Fields::joined( $l3_user );
$l3_languages = Profile_Fields::languages( $l3_user );
$l3_key       = Profile_Fields::fingerprint( $l3_user );
?>

<section class="section" data-section="identity">
	<div class="container stack">
		<?php
		Template_Tags::breadcrumb(
			array(
				array(
					'label' => __( 'Home', 'lupin3rd' ),
					'url'   => home_url( '/' ),
				),
				array(
					'label' => __( 'Comunità', 'lupin3rd' ),
					'url'   => home_url( '/comunita/' ),
				),
				array(
					'label' => $l3_pseudonym,
					'url'   => '',
				),
			)
		);
		?>

		<div class="identity">
			<?php Marks::render_avatar( Profile_Fields::avatar_seed( $l3_user ), Profile_Fields::initials( $l3_user ), 'avatar-lg' ); ?>

			<div class="stack">
				<div class="row identity-name">
					<h1><?php echo esc_html( $l3_pseudonym ); ?></h1>
					<span class="badge"><?php echo esc_html( Community::role_label( $l3_user ) ); ?></span>
					<?php if ( Profile_Fields::key_is_verified( $l3_user ) ) : ?>
						<span class="badge badge-strong" title="<?php esc_attr_e( 'Chiave PGP controfirmata dalla redazione', 'lupin3rd' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M4 12.5l5 5L20 6.5"></path></svg>
							<?php esc_html_e( 'chiave verificata', 'lupin3rd' ); ?>
						</span>
					<?php endif; ?>
				</div>

				<?php if ( Profile_Fields::bio( $l3_user ) ) : ?>
					<p class="lead"><?php echo esc_html( Profile_Fields::bio( $l3_user ) ); ?></p>
				<?php endif; ?>

				<p class="meta">
					<?php
					$l3_facts = array();

					if ( $l3_joined > 0 ) {
						/* translators: %s: join date. */
						$l3_facts[] = sprintf( __( 'Iscritto il %s', 'lupin3rd' ), I18n::instance()->date( $l3_joined ) );
					}

					if ( $l3_languages ) {
						/* translators: %s: comma-separated language codes. */
						$l3_facts[] = sprintf( __( 'scrive in %s', 'lupin3rd' ), implode( ', ', $l3_languages ) );
					}

					echo esc_html( implode( ' · ', $l3_facts ) );
					?>
				</p>

				<div class="row identity-tools">
					<a class="btn btn-secondary btn-sm" href="<?php echo esc_url( home_url( '/cassetta-sicura/' ) ); ?>">
						<?php esc_html_e( 'Scrivigli in privato', 'lupin3rd' ); ?>
					</a>
					<span class="meta" data-when-signed-out>
						<?php
						printf(
							/* translators: %s: link to the sign-in page. */
							esc_html__( 'Profilo pubblico. %s per rispondere ai suoi interventi.', 'lupin3rd' ),
							sprintf(
								'<a href="%s">%s</a>',
								esc_url( home_url( '/accesso/' ) ),
								esc_html__( 'Accedi', 'lupin3rd' )
							)
						);
						?>
					</span>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="section section-tight">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/community/stat-grid',
			null,
			array(
				'stats' => array(
					array(
						'num'   => number_format_i18n( $l3_stats['contributions'] ),
						'label' => __( 'interventi pubblicati', 'lupin3rd' ),
					),
					array(
						'num'   => number_format_i18n( $l3_stats['threads'] ),
						'label' => __( 'articoli commentati', 'lupin3rd' ),
					),
					array(
						'num'   => number_format_i18n( $l3_stats['corrections'] ),
						'label' => __( 'correzioni accettate', 'lupin3rd' ),
					),
					array(
						'num'   => number_format_i18n( $l3_stats['published'] ),
						'label' => __( 'writeup pubblicati', 'lupin3rd' ),
					),
				),
			)
		);
		?>
	</div>
</section>

<section class="section">
	<div class="container grid-main-side">
		<div class="stack">
			<?php
			/*
			 * The tab strip is hidden until the script that drives it runs, and
			 * each panel carries its own heading. Without JavaScript the page is
			 * two labelled lists, one after the other; with it, the prototype's
			 * tabs. Neither state hides anything from the reader.
			 */
			?>
			<div class="tabs" role="tablist" aria-label="<?php esc_attr_e( 'Attività del profilo', 'lupin3rd' ); ?>" data-tabs hidden>
				<button class="tab" type="button" role="tab" id="tab-contributions" aria-controls="pan-contributions" aria-selected="true">
					<?php esc_html_e( 'Interventi', 'lupin3rd' ); ?>
					<span class="num"><?php echo esc_html( number_format_i18n( $l3_stats['contributions'] ) ); ?></span>
				</button>
				<button class="tab" type="button" role="tab" id="tab-published" aria-controls="pan-published" aria-selected="false" tabindex="-1">
					<?php esc_html_e( 'Contributi', 'lupin3rd' ); ?>
					<span class="num"><?php echo esc_html( number_format_i18n( $l3_stats['published'] ) ); ?></span>
				</button>
			</div>

			<div class="tabpanel" id="pan-contributions" role="tabpanel" aria-labelledby="tab-contributions">
				<p class="kicker" data-tab-heading><?php esc_html_e( 'Interventi', 'lupin3rd' ); ?></p>

				<?php
				$l3_contributions = Community::contributions_by( $l3_user->ID );

				if ( $l3_contributions ) {
					foreach ( $l3_contributions as $l3_contribution ) {
						get_template_part(
							'template-parts/community/activity-row',
							null,
							array( 'comment' => $l3_contribution )
						);
					}
					?>
					<p class="meta"><?php esc_html_e( 'Gli interventi restano pubblici finché resta l\'articolo.', 'lupin3rd' ); ?></p>
					<?php
				} else {
					?>
					<p class="meta"><?php esc_html_e( 'Nessun intervento pubblicato, per ora.', 'lupin3rd' ); ?></p>
					<?php
				}
				?>
			</div>

			<div class="tabpanel" id="pan-published" role="tabpanel" aria-labelledby="tab-published">
				<p class="kicker" data-tab-heading><?php esc_html_e( 'Contributi', 'lupin3rd' ); ?></p>

				<?php if ( have_posts() ) : ?>
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content/log-row' );
					endwhile;
					?>
					<?php Template_Tags::pagination(); ?>
				<?php else : ?>
					<p class="meta"><?php esc_html_e( 'Nessun pezzo firmato da questo account.', 'lupin3rd' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<aside>
			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Scheda', 'lupin3rd' ); ?></p>
				<table class="ds-table">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Ruolo', 'lupin3rd' ); ?></td>
							<td><?php echo esc_html( Community::role_label( $l3_user ) ); ?></td>
						</tr>
						<?php if ( $l3_joined > 0 ) : ?>
							<tr>
								<td><?php esc_html_e( 'Iscritto', 'lupin3rd' ); ?></td>
								<td><?php echo esc_html( I18n::instance()->date( $l3_joined ) ); ?></td>
							</tr>
						<?php endif; ?>
						<tr>
							<td><?php esc_html_e( 'Interventi', 'lupin3rd' ); ?></td>
							<td class="num-col"><?php echo esc_html( number_format_i18n( $l3_stats['contributions'] ) ); ?></td>
						</tr>
						<?php if ( $l3_languages ) : ?>
							<tr>
								<td><?php esc_html_e( 'Lingue', 'lupin3rd' ); ?></td>
								<td><?php echo esc_html( implode( ', ', $l3_languages ) ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<?php if ( '' !== $l3_key ) : ?>
				<div class="widget">
					<p class="widget-title"><?php esc_html_e( 'Chiave dichiarata', 'lupin3rd' ); ?></p>
					<div class="codeblock">
						<div class="codeblock-head">
							<span><?php esc_html_e( 'impronta pgp', 'lupin3rd' ); ?></span>
							<button
								class="copy-btn"
								type="button"
								data-copy
								data-copied-label="<?php esc_attr_e( 'copiato', 'lupin3rd' ); ?>"
								data-failed-label="<?php esc_attr_e( 'seleziona e copia', 'lupin3rd' ); ?>"><?php esc_html_e( 'Copia', 'lupin3rd' ); ?></button>
						</div>
						<div class="codeblock-body">
							<pre><code><?php echo esc_html( $l3_key ); ?></code></pre>
						</div>
					</div>
					<?php if ( ! Profile_Fields::key_is_verified( $l3_user ) ) : ?>
						<p class="meta"><?php esc_html_e( 'Dichiarata dall\'account, non ancora controfirmata dalla redazione.', 'lupin3rd' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Moderazione', 'lupin3rd' ); ?></p>
				<p class="widget-note">
					<?php
					if ( Community::is_queued( $l3_user->ID ) ) {
						printf(
							/* translators: %d: number of contributions read before an account leaves the queue. */
							esc_html__( 'I primi %d interventi passano dalla moderazione. Questo account è ancora in coda.', 'lupin3rd' ),
							(int) Community::MODERATION_THRESHOLD
						);
					} else {
						esc_html_e( 'Superati i primi tre interventi, i suoi commenti compaiono senza attesa. Due segnalazioni respinte riportano l\'account in coda.', 'lupin3rd' );
					}
					?>
				</p>
			</div>
		</aside>
	</div>
</section>

<?php
get_footer();
