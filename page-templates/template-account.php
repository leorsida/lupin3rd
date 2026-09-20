<?php
/**
 * Template Name: Account
 *
 * The private half of an account: the fields the reader can change, and the
 * button that ends the session. Never cached, never public, and never shown to
 * a stranger — `Account::close_to_strangers()` sends them to /accesso/ first.
 *
 * What is *not* here is as deliberate as what is. No bookmarks yet, no
 * notifications, no device list: those are their own slices, and a page that
 * shows empty boxes for features that do not exist is a page that lies.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Account;
use Lupin3rd\Authentication;
use Lupin3rd\Community;
use Lupin3rd\I18n;
use Lupin3rd\Marks;
use Lupin3rd\Profile_Fields;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

the_post();

$l3_me     = wp_get_current_user();
$l3_notice = Account::notice();
$l3_joined = (int) strtotime( $l3_me->user_registered . ' UTC' );
$l3_stats  = Community::profile_stats( $l3_me->ID );
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
					'label' => __( 'Il tuo profilo', 'lupin3rd' ),
					'url'   => '',
				),
			)
		);
		?>

		<div class="identity">
			<?php Marks::render_avatar( Profile_Fields::avatar_seed( $l3_me ), Profile_Fields::initials( $l3_me ), 'avatar-lg avatar-self' ); ?>

			<div class="stack">
				<div class="row identity-name">
					<h1><?php echo esc_html( Profile_Fields::pseudonym( $l3_me ) ); ?></h1>
					<span class="badge"><?php echo esc_html( Community::role_label( $l3_me ) ); ?></span>
					<?php if ( Profile_Fields::key_is_verified( $l3_me ) ) : ?>
						<span class="badge badge-strong">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M4 12.5l5 5L20 6.5"></path></svg>
							<?php esc_html_e( 'chiave verificata', 'lupin3rd' ); ?>
						</span>
					<?php endif; ?>
				</div>

				<p class="lead"><?php the_title(); ?></p>

				<p class="meta">
					<?php
					printf(
						/* translators: %s: join date. */
						esc_html__( 'Iscritto il %s', 'lupin3rd' ),
						esc_html( I18n::instance()->date( $l3_joined ) )
					);
					?>
				</p>

				<div class="row identity-tools">
					<a class="btn btn-secondary btn-sm" href="<?php echo esc_url( Community::profile_url( $l3_me ) ); ?>">
						<?php esc_html_e( 'Vedi il profilo pubblico', 'lupin3rd' ); ?>
					</a>
					<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( Authentication::sign_out_url( home_url( '/' ) ) ); ?>">
						<?php esc_html_e( 'Esci', 'lupin3rd' ); ?>
					</a>
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
						'label' => __( 'commenti pubblicati', 'lupin3rd' ),
					),
					array(
						'num'   => Community::is_queued( $l3_me->ID ) ? __( 'sì', 'lupin3rd' ) : __( 'no', 'lupin3rd' ),
						'label' => __( 'in coda di moderazione', 'lupin3rd' ),
					),
					array(
						'num'   => number_format_i18n( $l3_stats['corrections'] ),
						'label' => __( 'correzioni accettate', 'lupin3rd' ),
					),
					array(
						'num'   => number_format_i18n( $l3_stats['published'] ),
						'label' => __( 'pezzi firmati', 'lupin3rd' ),
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
			<?php if ( $l3_notice ) : ?>
				<p class="form-note<?php echo 'error' === $l3_notice['tone'] ? ' form-note--error' : ''; ?>" role="status">
					<?php echo esc_html( $l3_notice['text'] ); ?>
				</p>
			<?php endif; ?>

			<div class="mw-46">
				<p class="kicker"><?php esc_html_e( 'Profilo pubblico', 'lupin3rd' ); ?></p>
				<h2><?php esc_html_e( 'Il tuo profilo pubblico', 'lupin3rd' ); ?></h2>
			</div>

			<?php get_template_part( 'template-parts/account/form-profile' ); ?>

			<div class="mw-46">
				<p class="kicker"><?php esc_html_e( 'Accesso', 'lupin3rd' ); ?></p>
				<h2><?php esc_html_e( 'Indirizzo e password', 'lupin3rd' ); ?></h2>
			</div>

			<?php get_template_part( 'template-parts/account/form-credentials' ); ?>

			<div class="mw-46">
				<p class="kicker"><?php esc_html_e( 'Andarsene', 'lupin3rd' ); ?></p>
				<h2><?php esc_html_e( 'Chiudere l\'account', 'lupin3rd' ); ?></h2>
			</div>

			<?php get_template_part( 'template-parts/account/form-close' ); ?>
		</div>

		<aside>
			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Moderazione', 'lupin3rd' ); ?></p>
				<p class="widget-note">
					<?php
					if ( Community::is_queued( $l3_me->ID ) ) {
						printf(
							/* translators: 1: approved contributions so far, 2: how many are needed. */
							esc_html__( 'I tuoi commenti vengono letti prima di essere pubblicati: %1$d su %2$d.', 'lupin3rd' ),
							(int) Community::contributions( $l3_me->ID ),
							(int) Community::MODERATION_THRESHOLD
						);
					} else {
						esc_html_e( 'I tuoi commenti compaiono senza attesa. Due segnalazioni respinte riportano l\'account in coda.', 'lupin3rd' );
					}
					?>
				</p>
			</div>

			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Cosa teniamo', 'lupin3rd' ); ?></p>
				<ul class="plain-list">
					<li><?php esc_html_e( 'Pseudonimo, data di iscrizione, ruolo.', 'lupin3rd' ); ?></li>
					<li><?php esc_html_e( 'Email, visibile solo alla moderazione.', 'lupin3rd' ); ?></li>
					<li><?php esc_html_e( 'Commenti pubblicati.', 'lupin3rd' ); ?></li>
				</ul>
				<p class="widget-note"><?php esc_html_e( 'Niente cronologia di lettura, niente indirizzi oltre le 24 ore di finestra antispam.', 'lupin3rd' ); ?></p>
			</div>
		</aside>
	</div>
</section>

<?php
get_footer();
