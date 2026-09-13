<?php
/**
 * Template Name: Accesso
 *
 * Registration and sign-in, in the theme. A reader never sees wp-login.php:
 * that page still works, but only for the desk, and it says so.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Authentication;
use Lupin3rd\Community;
use Lupin3rd\Marks;
use Lupin3rd\Profile_Fields;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

the_post();

$l3_notice = Authentication::notice();
$l3_panel  = Authentication::panel();
$l3_user   = wp_get_current_user();
$l3_inside = $l3_user instanceof WP_User && $l3_user->exists();
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
					'label' => __( 'Comunità', 'lupin3rd' ),
					'url'   => home_url( '/comunita/' ),
				),
				array(
					'label' => __( 'Account', 'lupin3rd' ),
					'url'   => '',
				),
			)
		);
		?>

		<p class="eyebrow"><?php esc_html_e( 'Account · pseudonimo obbligatorio, nome reale mai', 'lupin3rd' ); ?></p>
		<h1><?php the_title(); ?></h1>

		<div class="prose">
			<?php the_content(); ?>
		</div>
	</div>
</section>

<section class="section section-tight">
	<div class="container grid-main-side">
		<div class="stack">

			<?php if ( $l3_notice ) : ?>
				<p class="form-note<?php echo 'error' === $l3_notice['tone'] ? ' form-note--error' : ''; ?>" role="status">
					<?php echo esc_html( $l3_notice['text'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $l3_inside ) : ?>
				<div class="gate">
					<span class="row row-tight">
						<?php Marks::render_avatar( Profile_Fields::avatar_seed( $l3_user ), Profile_Fields::initials( $l3_user ), 'avatar-self' ); ?>
						<span>
							<span class="member-name">
								<?php
								printf(
									/* translators: %s: the reader's pseudonym. */
									esc_html__( 'Sei già dentro come %s', 'lupin3rd' ),
									esc_html( Profile_Fields::pseudonym( $l3_user ) )
								);
								?>
							</span>
							<span class="member-note"><?php esc_html_e( 'Sessione aperta su questo browser.', 'lupin3rd' ); ?></span>
						</span>
					</span>
					<span class="row row-tight row-wrap">
						<a class="btn btn-secondary btn-sm" href="<?php echo esc_url( Community::profile_url( $l3_user ) ); ?>">
							<?php esc_html_e( 'Il tuo profilo', 'lupin3rd' ); ?>
						</a>
						<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( Authentication::sign_out_url( home_url( '/' ) ) ); ?>">
							<?php esc_html_e( 'Esci', 'lupin3rd' ); ?>
						</a>
					</span>
				</div>
			<?php else : ?>

				<div class="tabs" role="tablist" aria-label="<?php esc_attr_e( 'Accesso e registrazione', 'lupin3rd' ); ?>" data-tabs hidden>
					<button class="tab" type="button" role="tab" id="tab-register" aria-controls="pan-register"
						aria-selected="<?php echo 'registrazione' === $l3_panel ? 'true' : 'false'; ?>"
						<?php echo 'registrazione' === $l3_panel ? '' : 'tabindex="-1"'; ?>>
						<?php esc_html_e( 'Crea un account', 'lupin3rd' ); ?>
					</button>
					<button class="tab" type="button" role="tab" id="tab-sign-in" aria-controls="pan-sign-in"
						aria-selected="<?php echo 'accesso' === $l3_panel ? 'true' : 'false'; ?>"
						<?php echo 'accesso' === $l3_panel ? '' : 'tabindex="-1"'; ?>>
						<?php esc_html_e( 'Accedi', 'lupin3rd' ); ?>
					</button>
				</div>

				<div class="tabpanel" id="pan-register" role="tabpanel" aria-labelledby="tab-register">
					<h2 data-tab-heading><?php esc_html_e( 'Crea un account', 'lupin3rd' ); ?></h2>
					<?php
					if ( Authentication::registration_is_open() ) {
						get_template_part( 'template-parts/auth/form-register' );
					} else {
						?>
						<p class="meta"><?php esc_html_e( 'Le registrazioni sono chiuse in questo momento.', 'lupin3rd' ); ?></p>
						<?php
					}
					?>
				</div>

				<div class="tabpanel" id="pan-sign-in" role="tabpanel" aria-labelledby="tab-sign-in">
					<h2 data-tab-heading><?php esc_html_e( 'Accedi', 'lupin3rd' ); ?></h2>
					<?php get_template_part( 'template-parts/auth/form-sign-in' ); ?>
				</div>

			<?php endif; ?>
		</div>

		<aside>
			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Cosa sblocca l\'account', 'lupin3rd' ); ?></p>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/archivio/' ) ); ?>"><?php esc_html_e( 'Intervenire sotto gli articoli', 'lupin3rd' ); ?> <span>01</span></a></li>
					<li><a href="<?php echo esc_url( home_url( '/comunita/' ) ); ?>"><?php esc_html_e( 'Un profilo pubblico con i tuoi interventi', 'lupin3rd' ); ?> <span>02</span></a></li>
					<li><a href="<?php echo esc_url( home_url( '/linee-guida/' ) ); ?>"><?php esc_html_e( 'Proporre correzioni e writeup', 'lupin3rd' ); ?> <span>03</span></a></li>
				</ul>
			</div>

			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Cosa non chiediamo', 'lupin3rd' ); ?></p>
				<p class="widget-note"><?php esc_html_e( 'Nome reale, documento, numero di telefono, foto, data di nascita, azienda. Se un giorno ci servissero, la risposta sarebbe: chiudiamo la registrazione.', 'lupin3rd' ); ?></p>
			</div>

			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'L\'avatar non esce dal server', 'lupin3rd' ); ?></p>
				<p class="widget-note"><?php esc_html_e( 'Niente Gravatar. L\'immagine di profilo è un motivo generato dal seme dell\'account: nessun servizio esterno riceve l\'hash della tua email.', 'lupin3rd' ); ?></p>
				<div class="avatar-stack" aria-hidden="true">
					<?php
					foreach ( array( 'seme-uno', 'seme-due', 'seme-tre', 'seme-quattro' ) as $l3_seed ) {
						Marks::render_avatar( $l3_seed, '' );
					}
					?>
				</div>
			</div>
		</aside>
	</div>
</section>

<?php
get_footer();
