<?php
/**
 * Block patterns.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * The layouts the desk starts from.
 *
 * A pattern is a starting point, not a template: once inserted it is ordinary
 * blocks and the author may do as they like. The tutorial header exists so
 * that the parts a tutorial always has — standfirst, legal scope, prerequisites
 * — are present from the first keystroke rather than remembered at the end.
 */
final class Patterns {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ), 30 );
	}

	/**
	 * Register the pattern category and the patterns.
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		register_block_pattern_category(
			'lupin3rd',
			array( 'label' => __( 'lupin3rd', 'lupin3rd' ) )
		);

		register_block_pattern(
			'l3/tutorial-header',
			array(
				'title'       => __( 'Testata tutorial', 'lupin3rd' ),
				'description' => __( 'Sommario, informativa legale e prerequisiti: le tre cose che un tutorial deve sempre avere.', 'lupin3rd' ),
				'categories'  => array( 'lupin3rd' ),
				'postTypes'   => array( Content_Types::TUTORIAL ),
				'content'     => $this->tutorial_header(),
			)
		);

		register_block_pattern(
			'l3/lab-block',
			array(
				'title'       => __( 'Blocco di laboratorio', 'lupin3rd' ),
				'description' => __( 'Un passo con il suo comando e la nota di metodo accanto.', 'lupin3rd' ),
				'categories'  => array( 'lupin3rd' ),
				'postTypes'   => array( Content_Types::TUTORIAL, Content_Types::DOSSIER ),
				'content'     => $this->lab_block(),
			)
		);
	}

	/**
	 * The tutorial header pattern's block markup.
	 */
	private function tutorial_header(): string {
		$standfirst = __( 'Due o tre frasi che stanno in piedi da sole, fuori dal contesto: sono quelle che un motore cita e che un lettore legge prima di decidere se continuare.', 'lupin3rd' );
		$legal      = __( 'Questo tutorial riguarda sistemi che possiedi o che sei autorizzato a trattare. Provare una di queste tecniche su sistemi altrui, senza un incarico scritto, è un reato in quasi ogni giurisdizione.', 'lupin3rd' );
		$prereq     = __( 'Cosa serve avere davanti prima di cominciare.', 'lupin3rd' );

		return sprintf(
			'<!-- wp:paragraph {"className":"lead"} --><p class="lead">%1$s</p><!-- /wp:paragraph -->'
			. '<!-- wp:l3/notice {"variant":"legal"} --><!-- wp:paragraph --><p>%2$s</p><!-- /wp:paragraph --><!-- /wp:l3/notice -->'
			. '<!-- wp:heading --><h2>%3$s</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>%4$s</p><!-- /wp:paragraph -->',
			esc_html( $standfirst ),
			esc_html( $legal ),
			esc_html__( 'Prerequisiti', 'lupin3rd' ),
			esc_html( $prereq )
		);
	}

	/**
	 * The lab-step pattern's block markup.
	 */
	private function lab_block(): string {
		return sprintf(
			'<!-- wp:heading --><h2>%1$s</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>%2$s</p><!-- /wp:paragraph -->'
			. '<!-- wp:l3/code {"language":"bash","filename":"%3$s"} /-->'
			. '<!-- wp:l3/notice {"variant":"method"} --><!-- wp:paragraph --><p>%4$s</p><!-- /wp:paragraph --><!-- /wp:l3/notice -->',
			esc_html__( 'Il passo', 'lupin3rd' ),
			esc_html__( 'Cosa fa questo passo e perché, in una frase.', 'lupin3rd' ),
			esc_attr__( 'comando', 'lupin3rd' ),
			esc_html__( 'Come verificare di aver fatto la cosa giusta, e cosa significa se il risultato è diverso.', 'lupin3rd' )
		);
	}
}
