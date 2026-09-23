<?php
/**
 * Server render for l3/code.
 *
 * @package Lupin3rd
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

use Lupin3rd\Blocks;

defined( 'ABSPATH' ) || exit;

/*
 * Trimmed of leading and trailing newlines only, never of spaces: an empty
 * first or last line is an artefact of how the block was typed, while an
 * indented first line may well be the point.
 */
$l3_code = trim( (string) ( $attributes['content'] ?? '' ), "\n" );

if ( '' === trim( $l3_code ) ) {
	return;
}

$l3_language = (string) ( $attributes['language'] ?? '' );
$l3_filename = (string) ( $attributes['filename'] ?? '' );
$l3_numbered = ! empty( $attributes['showLineNumbers'] );

$l3_label = trim( implode( ' · ', array_filter( array( $l3_language, $l3_filename ) ) ) );
$l3_lines = $l3_numbered ? substr_count( $l3_code, "\n" ) + 1 : 0;
$l3_class = $l3_numbered ? 'codeblock codeblock--numbered' : 'codeblock';
$l3_attrs = get_block_wrapper_attributes( array( 'class' => $l3_class ) );
?>
<div <?php echo wp_kses_data( $l3_attrs ); ?>>
	<div class="codeblock-head">
		<span><?php echo esc_html( $l3_label ?: __( 'codice', 'lupin3rd' ) ); ?></span>
		<button
			class="copy-btn"
			type="button"
			data-copy
			data-copied-label="<?php esc_attr_e( 'copiato', 'lupin3rd' ); ?>"
			data-failed-label="<?php esc_attr_e( 'seleziona e copia', 'lupin3rd' ); ?>"><?php esc_html_e( 'Copia', 'lupin3rd' ); ?></button>
	</div>

	<div class="codeblock-body">
		<?php if ( $l3_numbered ) : ?>
			<?php
			/*
			 * The numbers sit outside <code>, in their own column. Inside it
			 * they would be part of the text, which means part of what the copy
			 * button copies and part of what a reader drags a selection over.
			 *
			 * Printed in one statement for the same reason as the <pre> below:
			 * this column is whitespace-significant too, and the template's own
			 * indentation would push the first number out of line with the
			 * first line of code.
			 */
			$l3_numbers = array();

			for ( $l3_i = 1; $l3_i <= $l3_lines; $l3_i++ ) {
				$l3_numbers[] = (string) $l3_i;
			}

			printf(
				'<span class="codeblock-lines" aria-hidden="true">%s</span>',
				esc_html( implode( "\n", $l3_numbers ) )
			);
			?>
		<?php endif; ?>

		<?php
		/*
		 * Everything from <pre> to </pre> on one statement, with no newline and
		 * no indentation of its own. Inside <pre> the template's own whitespace
		 * is content: written across several lines, this printed a blank first
		 * line and two tabs before the first character of the code, and put
		 * both on the clipboard as well.
		 */
		printf(
			'<pre><code%1$s>%2$s</code></pre>',
			$l3_language ? ' class="language-' . esc_attr( sanitize_html_class( $l3_language ) ) . '"' : '',
			Blocks::highlight( $l3_code, $l3_language ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped token by token inside highlight().
		);
		?>
	</div>
</div>
