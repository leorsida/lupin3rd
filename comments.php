<?php
/**
 * The discussion under a piece.
 *
 * Pre-moderated, no external anti-spam service: the queue is small and the desk
 * reads it by hand.
 *
 * The fields are supplied rather than left to core, so they carry the design
 * system's markup — and so the labels tell the truth about what this site
 * wants: a pseudonym, not a name.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Comment_Walker;
use Lupin3rd\Marks;

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}

$l3_commenter = wp_get_current_commenter();
$l3_required  = (bool) get_option( 'require_name_email' );

/*
 * Core's "Logged in as …" line is a run-on sentence of three links. A reader
 * who is signed in needs to know which identity is about to be attached to
 * what they write, and how to stop being that identity: name, and a way out.
 */
$l3_logged_in_as = '';

if ( is_user_logged_in() ) {
	$l3_user = wp_get_current_user();
	$l3_name = (string) $l3_user->display_name;

	ob_start();
	Marks::render_avatar( $l3_name, mb_strtoupper( mb_substr( $l3_name, 0, 2 ) ), 'avatar-self' );
	$l3_avatar = (string) ob_get_clean();

	$l3_logged_in_as = sprintf(
		'<div class="compose-as">%1$s<div><span class="member-name">%2$s</span><p class="member-note"><a href="%3$s">%4$s</a></p></div></div>',
		$l3_avatar,
		sprintf(
			/* translators: %s: display name of the signed-in user. */
			esc_html__( 'Stai scrivendo come %s', 'lupin3rd' ),
			esc_html( $l3_name )
		),
		esc_url( wp_logout_url( (string) get_permalink() ) ),
		esc_html__( 'esci', 'lupin3rd' )
	);
}

/**
 * Build one form field in the design system's markup.
 *
 * @param string $id       Field id and name.
 * @param string $label    Visible label.
 * @param string $value    Current value.
 * @param string $type     Input type.
 * @param bool   $required Whether the field is required.
 * @param string $hint     Optional help text below the field.
 */
$l3_field = static function ( string $id, string $label, string $value, string $type = 'text', bool $required = false, string $hint = '' ): string {
	return sprintf(
		'<div class="field"><label for="%1$s">%2$s</label><input class="input" id="%1$s" name="%1$s" type="%3$s" value="%4$s"%5$s>%6$s</div>',
		esc_attr( $id ),
		esc_html( $label ),
		esc_attr( $type ),
		esc_attr( $value ),
		$required ? ' required' : '',
		$hint ? '<p class="hint">' . esc_html( $hint ) . '</p>' : ''
	);
};
?>
<section id="comments" class="stack">
	<h2><?php esc_html_e( 'Discussione', 'lupin3rd' ); ?></h2>

	<?php if ( have_comments() ) : ?>
		<div>
			<?php
			wp_list_comments(
				array(
					'walker'      => new Comment_Walker(),
					'style'       => 'div',
					'short_ping'  => true,
					'avatar_size' => 44,
				)
			);
			?>
		</div>

		<?php the_comments_pagination( array( 'class' => 'pagination' ) ); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() ) : ?>
		<p class="meta"><?php esc_html_e( 'La discussione su questo articolo è chiusa.', 'lupin3rd' ); ?></p>
	<?php else : ?>
		<?php
		comment_form(
			array(
				'class_form'           => 'stack stack-md',
				'class_submit'         => 'btn btn-primary',
				'title_reply'          => __( 'Lascia un commento', 'lupin3rd' ),
				/* translators: %s: pseudonym of the person being replied to. */
				'title_reply_to'       => __( 'Rispondi a %s', 'lupin3rd' ),
				'cancel_reply_link'    => __( 'Annulla', 'lupin3rd' ),
				'label_submit'         => __( 'Invia', 'lupin3rd' ),
				'comment_notes_before' => '',
				'comment_notes_after'  => '',
				'logged_in_as'         => $l3_logged_in_as,
				'comment_field'        => sprintf(
					'<p class="meta">%1$s</p><div class="field"><label for="comment">%2$s</label><textarea class="textarea" id="comment" name="comment" rows="5" required></textarea></div>',
					esc_html__( 'Ogni commento è moderato prima di essere pubblicato. Non usare i commenti per materiale sensibile: scrivi una mail con pgp.', 'lupin3rd' ),
					esc_html__( 'Il tuo commento', 'lupin3rd' )
				),
				'fields'               => array(
					'author'  => $l3_field(
						'author',
						__( 'Pseudonimo', 'lupin3rd' ),
						(string) $l3_commenter['comment_author'],
						'text',
						$l3_required,
						__( 'Inserisci il tuo nickname', 'lupin3rd' )
					),
					'email'   => $l3_field(
						'email',
						__( 'Email', 'lupin3rd' ),
						(string) $l3_commenter['comment_author_email'],
						'email',
						$l3_required,
						__( 'Serve solo per la moderazione. Non viene pubblicata, non finisce in una lista, non la diamo a nessuno.', 'lupin3rd' )
					),
					'url'     => $l3_field(
						'url',
						__( 'Sito', 'lupin3rd' ),
						(string) $l3_commenter['comment_author_url'],
						'url',
						false
					),
					'cookies' => sprintf(
						'<label class="check"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"%s><span>%s</span></label>',
						empty( $l3_commenter['comment_author_email'] ) ? '' : ' checked',
						esc_html__( 'Ricorda pseudonimo ed email in questo browser.', 'lupin3rd' )
					),
				),
			)
		);
		?>
	<?php endif; ?>
</section>
