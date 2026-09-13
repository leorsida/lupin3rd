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

$l3_code = (string) ( $attributes['content'] ?? '' );

if ( '' === trim( $l3_code ) ) {
	return;
}

$l3_language = (string) ( $attributes['language'] ?? '' );
$l3_filename = (string) ( $attributes['filename'] ?? '' );
$l3_numbered = ! empty( $attributes['showLineNumbers'] );

$l3_label = trim( implode( ' · ', array_filter( array( $l3_language, $l3_filename ) ) ) );
$l3_lines = $l3_numbered ? substr_count( rtrim( $l3_code, "\n" ), "\n" ) + 1 : 0;
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
			 */
			?>
			<span class="codeblock-lines" aria-hidden="true">
			<?php
			for ( $l3_i = 1; $l3_i <= $l3_lines; $l3_i++ ) {
				echo esc_html( (string) $l3_i ) . ( $l3_i < $l3_lines ? "\n" : '' );
			}
			?>
			</span>
		<?php endif; ?>

		<pre><code<?php echo $l3_language ? ' class="language-' . esc_attr( sanitize_html_class( $l3_language ) ) . '"' : ''; ?>>
		<?php
			// Escaped token by token in highlight().
			echo Blocks::highlight( $l3_code, $l3_language ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		</code></pre>
	</div>
</div>
