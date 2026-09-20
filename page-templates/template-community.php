<?php
/**
 * Template Name: Comunità
 *
 * The index of the community: who is here, what they have been saying, and
 * what the site keeps about them.
 *
 * Every number on this page is counted from something the site already has —
 * approved comments, registrations, the moment the desk let a contribution
 * through. Nothing here is counted from a record of who read what or of who
 * signed in when, because there is no such record. That is why «account
 * attivi» means «has published something» and not «signed in this year»
 * (D-025).
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

/*
 * One post, set up once: the title, the permalink the filter chips are built
 * from and the desk's own introduction all come from it, and they are needed
 * before the first section rather than inside a loop.
 */
the_post();

$l3_stats   = Community::stats();
$l3_role    = (string) get_query_var( Community::ROLE_VAR );
$l3_members = Community::recent_members( 8, $l3_role );
$l3_url     = static fn ( string $role ): string => '' === $role
	? remove_query_arg( Community::ROLE_VAR, get_permalink() )
	: add_query_arg( Community::ROLE_VAR, $role, get_permalink() );

$l3_roles = array(
	''            => __( 'Tutti', 'lupin3rd' ),
	'member'      => __( 'Membri', 'lupin3rd' ),
	'contributor' => __( 'Collaboratori', 'lupin3rd' ),
);
?>

<section class="section">
	<div class="container stack">
		<?php
		Template_Tags::breadcrumb(
			array(
				array(
					'label' => __( 'Home', 'lupin3rd' ),
					'url'   => home_url( '/' ),
				),
				array(
					'label' => get_the_title(),
					'url'   => '',
				),
			)
		);
		?>

		<p class="eyebrow">
			<?php
			printf(
				/* translators: %s: number of accounts with at least one published contribution. */
				esc_html__( 'Comunità · %s account, nessun nome reale', 'lupin3rd' ),
				esc_html( number_format_i18n( $l3_stats['members'] ) )
			);
			?>
		</p>

		<h1><?php the_title(); ?></h1>

		<div class="grid-2-1">
			<div class="prose">
				<?php the_content(); ?>
			</div>

			<div class="row row-wrap">
				<a class="btn btn-primary btn-arrow" href="<?php echo esc_url( home_url( '/accesso/' ) ); ?>" data-when-signed-out>
					<?php esc_html_e( 'Crea un account', 'lupin3rd' ); ?>
				</a>
				<a class="btn btn-primary btn-arrow hidden" href="<?php echo esc_url( home_url( '/account/' ) ); ?>" data-when-signed-in>
					<?php esc_html_e( 'Vai al tuo profilo', 'lupin3rd' ); ?>
				</a>
				<a class="btn btn-secondary" href="<?php echo esc_url( home_url( '/linee-guida/' ) ); ?>">
					<?php esc_html_e( 'Come si modera', 'lupin3rd' ); ?>
				</a>
			</div>
		</div>
	</div>
</section>

<section class="section section-tight">
	<div class="container stack">
		<?php
		get_template_part(
			'template-parts/community/stat-grid',
			null,
			array(
				'stats' => array(
					array(
						'num'   => number_format_i18n( $l3_stats['members'] ),
						'label' => __( 'account attivi', 'lupin3rd' ),
					),
					array(
						'num'   => number_format_i18n( $l3_stats['recent'] ),
						'label' => __( 'commenti in 30 giorni', 'lupin3rd' ),
					),
					array(
						'num'   => number_format_i18n( $l3_stats['corrections'] ),
						/* translators: %d: current year. */
						'label' => sprintf( __( 'correzioni accettate nel %d', 'lupin3rd' ), (int) gmdate( 'Y' ) ),
					),
					array(
						/* translators: %d: hours. */
						'num'   => $l3_stats['wait'] > 0 ? sprintf( __( '%d h', 'lupin3rd' ), (int) $l3_stats['wait'] ) : '—',
						'label' => __( 'attesa media in moderazione', 'lupin3rd' ),
					),
				),
			)
		);
		?>

		<p class="meta">
			<?php esc_html_e( 'È attivo un account che ha pubblicato almeno un commento: non contiamo le iscrizioni dormienti per gonfiare il numero, e non teniamo traccia di chi è entrato e quando.', 'lupin3rd' ); ?>
		</p>
	</div>
</section>

<section class="section">
	<div class="container stack">
		<div class="row-between">
			<div class="mw-46">
				<p class="kicker"><?php esc_html_e( 'Ultimi iscritti', 'lupin3rd' ); ?></p>
				<h2><?php esc_html_e( 'Chi è arrivato di recente', 'lupin3rd' ); ?></h2>
			</div>
		</div>

		<div class="filterbar" role="group" aria-label="<?php esc_attr_e( 'Filtra per ruolo', 'lupin3rd' ); ?>"
			data-filter-group="role" data-filter-target="members">
			<?php foreach ( $l3_roles as $l3_value => $l3_label ) : ?>
				<a
					class="chip<?php echo $l3_value === $l3_role ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( $l3_url( (string) $l3_value ) ); ?>"
					data-value="<?php echo esc_attr( (string) $l3_value ); ?>"
					aria-pressed="<?php echo $l3_value === $l3_role ? 'true' : 'false'; ?>">
					<?php echo esc_html( $l3_label ); ?>
				</a>
			<?php endforeach; ?>
		</div>

		<?php if ( $l3_members ) : ?>
			<div class="grid-2" data-filter-scope="members">
				<?php
				foreach ( $l3_members as $l3_member ) {
					get_template_part( 'template-parts/community/member-row', null, array( 'user' => $l3_member ) );
				}

				get_template_part(
					'template-parts/content/empty-state',
					null,
					array(
						'hidden'  => true,
						'title'   => __( 'Nessun account con questo ruolo.', 'lupin3rd' ),
						'message' => __( 'I collaboratori si contano sulle dita: diventa tale chi manda tre contributi accettati.', 'lupin3rd' ),
					)
				);
				?>
			</div>
		<?php else : ?>
			<?php
			get_template_part(
				'template-parts/content/empty-state',
				null,
				array(
					'title'   => __( 'Nessun account con questo ruolo.', 'lupin3rd' ),
					'message' => __( 'I collaboratori si contano sulle dita: diventa tale chi manda tre contributi accettati.', 'lupin3rd' ),
				)
			);
			?>
		<?php endif; ?>

		<p class="meta">
			<?php esc_html_e( 'Pubblichiamo pseudonimo, ruolo e data di iscrizione. Mai l\'email, mai il paese, mai l\'ultimo indirizzo usato per entrare.', 'lupin3rd' ); ?>
		</p>
	</div>
</section>

<section class="section">
	<div class="container grid-main-side">
		<div class="stack">
			<div class="mw-46">
				<p class="kicker"><?php esc_html_e( 'Ultimi commenti', 'lupin3rd' ); ?></p>
				<h2><?php esc_html_e( 'Chi ha commentato, e sotto quale articolo', 'lupin3rd' ); ?></h2>
			</div>

			<?php
			$l3_contributions = Community::recent_contributions( 6 );

			if ( $l3_contributions ) {
				foreach ( $l3_contributions as $l3_contribution ) {
					get_template_part(
						'template-parts/community/activity-row',
						null,
						array(
							'comment' => $l3_contribution,
							'byline'  => true,
						)
					);
				}
				?>
				<p class="meta"><?php esc_html_e( 'Compaiono solo i commenti già passati dalla moderazione.', 'lupin3rd' ); ?></p>
				<?php
			} else {
				?>
				<p class="meta"><?php esc_html_e( 'Nessun commento pubblicato, per ora.', 'lupin3rd' ); ?></p>
				<?php
			}
			?>
		</div>

		<aside>
			<?php $l3_lively = Community::liveliest_posts( 4 ); ?>

			<?php if ( $l3_lively ) : ?>
				<div class="widget">
					<p class="widget-title"><?php esc_html_e( 'Discussioni più vive', 'lupin3rd' ); ?></p>
					<ul>
						<?php foreach ( $l3_lively as $l3_post ) : ?>
							<li>
								<a href="<?php echo esc_url( (string) get_permalink( $l3_post ) ); ?>">
									<?php echo esc_html( get_the_title( $l3_post ) ); ?>
									<span><?php echo esc_html( number_format_i18n( (int) $l3_post->comment_count ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Da membro a collaboratore', 'lupin3rd' ); ?></p>
				<p class="widget-note">
					<?php esc_html_e( 'Tre contributi accettati — correzioni documentate, traduzioni, writeup — e la redazione propone il passaggio. Da collaboratore si firma in prima pagina e si accede alle bozze prima della pubblicazione.', 'lupin3rd' ); ?>
				</p>
				<p>
					<a class="btn btn-secondary btn-sm" href="<?php echo esc_url( home_url( '/linee-guida/' ) ); ?>">
						<?php esc_html_e( 'Linee guida', 'lupin3rd' ); ?>
					</a>
				</p>
			</div>
		</aside>
	</div>
</section>

<?php $l3_active = Community::most_active( 5 ); ?>

<?php if ( $l3_active ) : ?>
	<section class="section">
		<div class="container stack">
			<div class="mw-46">
				<p class="kicker"><?php esc_html_e( 'Contributi', 'lupin3rd' ); ?></p>
				<h2><?php esc_html_e( 'Chi tiene viva la discussione', 'lupin3rd' ); ?></h2>
			</div>

			<div class="table-wrap">
				<table class="ds-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Membro', 'lupin3rd' ); ?></th>
							<th class="num-col"><?php esc_html_e( 'Commenti', 'lupin3rd' ); ?></th>
							<th class="num-col"><?php esc_html_e( 'Correzioni accettate', 'lupin3rd' ); ?></th>
							<th><?php esc_html_e( 'Iscritto', 'lupin3rd' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $l3_active as $l3_member ) :
							$l3_member_stats = Community::profile_stats( $l3_member->ID );
							$l3_member_since = Profile_Fields::joined( $l3_member );
							?>
							<tr>
								<td>
									<span class="row row-tight">
										<?php
										Marks::render_avatar(
											Profile_Fields::avatar_seed( $l3_member ),
											Profile_Fields::initials( $l3_member ),
											'avatar-sm'
										);
										?>
										<a class="member-name" href="<?php echo esc_url( Community::profile_url( $l3_member ) ); ?>">
											<?php echo esc_html( Profile_Fields::pseudonym( $l3_member ) ); ?>
										</a>
									</span>
								</td>
								<td class="num-col"><?php echo esc_html( number_format_i18n( $l3_member_stats['contributions'] ) ); ?></td>
								<td class="num-col"><?php echo esc_html( number_format_i18n( $l3_member_stats['corrections'] ) ); ?></td>
								<td><?php echo esc_html( $l3_member_since > 0 ? I18n::instance()->date( $l3_member_since ) : '—' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<p class="meta">
				<?php esc_html_e( 'Nessun punteggio, nessuna classifica pubblica oltre questa: contiamo commenti passati in moderazione e correzioni finite davvero in pagina.', 'lupin3rd' ); ?>
			</p>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();
