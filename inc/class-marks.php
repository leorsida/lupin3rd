<?php
/**
 * Generative imagery.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * The site publishes no photographs.
 *
 * An article's image is a mark: a dotted ground, a diagonal or scanline
 * pattern, and a monospace glyph, all drawn in CSS. An account's avatar is the
 * same idea at 44 pixels, derived from a seed so that Gravatar never receives
 * a hash of anybody's email address.
 *
 * Everything here is deterministic: the same post always produces the same
 * mark, on every server, forever. That matters because the mark is also what
 * social platforms show, and an image that changes on every deploy looks like
 * a broken site.
 */
final class Marks {

	use Singleton;

	/**
	 * Pattern variants for an article mark.
	 *
	 * @var array<int, string>
	 */
	private const VARIANTS = array( 'scan', 'bars', 'grid' );

	/**
	 * Pattern variants for an avatar.
	 *
	 * @var array<int, string>
	 */
	private const AVATAR_VARIANTS = array( 'scan', 'bars', 'grid', 'dots', 'diag' );

	/**
	 * Glyphs a mark can carry. Monospace, present in every system font, and
	 * meaningless on purpose: a mark labels, it does not illustrate.
	 *
	 * @var array<int, string>
	 */
	private const GLYPHS = array( '//', '::', '><', '[]', '{}', '##', '&&', '~~', '%%', '^^', '\\\\', '||' );

	/**
	 * Marks has no hooks: it is a library the templates call.
	 */
	protected function register_hooks(): void {}

	/**
	 * Return a stable integer for any seed string.
	 *
	 * @param string $seed Seed, usually a post slug or an account seed.
	 */
	private static function hash( string $seed ): int {
		return (int) hexdec( substr( md5( $seed ), 0, 8 ) );
	}

	/**
	 * Pattern variant for a post.
	 *
	 * @param int|\WP_Post|null $post Post to derive it from.
	 */
	public static function variant( $post = null ): string {
		$post = get_post( $post );
		$seed = $post ? $post->post_name . '|' . $post->ID : 'lupin3rd';

		return self::VARIANTS[ self::hash( $seed ) % count( self::VARIANTS ) ];
	}

	/**
	 * Glyph for a post.
	 *
	 * @param int|\WP_Post|null $post Post to derive it from.
	 */
	public static function glyph( $post = null ): string {
		$post = get_post( $post );
		$seed = $post ? 'g|' . $post->post_name : 'lupin3rd';

		return self::GLYPHS[ self::hash( $seed ) % count( self::GLYPHS ) ];
	}

	/**
	 * Pattern variant for an avatar seed.
	 *
	 * @param string $seed Account seed.
	 */
	public static function avatar_variant( string $seed ): string {
		return self::AVATAR_VARIANTS[ self::hash( 'a|' . $seed ) % count( self::AVATAR_VARIANTS ) ];
	}

	/**
	 * Render the mark for a post.
	 *
	 * When the post has a featured image, that image is used instead: the desk
	 * can always override the generated mark, it just never has to.
	 *
	 * @param int|\WP_Post|null $post  Post to render the mark for.
	 * @param string            $shape One of wide, square, tall, or an empty string for the default 16:10.
	 * @param string            $tag   Optional label printed in the corner.
	 */
	public static function render( $post = null, string $shape = '', string $tag = '' ): void {
		$post = get_post( $post );

		if ( $post && has_post_thumbnail( $post ) ) {
			$classes = 'mark' . ( $shape ? ' ' . $shape : '' );

			/*
			 * `wide` means the lead, and the lead is the first thing on the
			 * page: it gets the large size and is fetched at once. Everything
			 * else is a card further down and is fetched when it is needed.
			 * Lazy-loading the largest image above the fold is the classic way
			 * to make a page feel slower than it is.
			 */
			$is_lead = 'wide' === $shape;

			printf( '<div class="%s">', esc_attr( $classes ) );
			echo get_the_post_thumbnail(
				$post,
				$is_lead ? 'l3-feature' : 'l3-card',
				array(
					'loading'       => $is_lead ? 'eager' : 'lazy',
					'fetchpriority' => $is_lead ? 'high' : 'auto',
					'alt'           => '',
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this.
			if ( $tag ) {
				printf( '<span class="mark-tag">%s</span>', esc_html( $tag ) );
			}
			echo '</div>';
			return;
		}

		$classes = trim( 'mark mark--' . self::variant( $post ) . ' ' . $shape );

		printf( '<div class="%s" aria-hidden="true">', esc_attr( $classes ) );
		printf( '<span class="mark-glyph">%s</span>', esc_html( self::glyph( $post ) ) );
		if ( $tag ) {
			printf( '<span class="mark-tag">%s</span>', esc_html( $tag ) );
		}
		echo '</div>';
	}

	/**
	 * Render an avatar.
	 *
	 * @param string $seed      Account seed; the pseudonym is a fine default.
	 * @param string $initial   Character shown in the middle.
	 * @param string $modifiers Space separated avatar modifiers: a size
	 *                          (avatar-sm, avatar-lg), avatar-self for the
	 *                          signed-in reader, or an empty string.
	 */
	public static function render_avatar( string $seed, string $initial = '', string $modifiers = '' ): void {
		$classes = trim( 'avatar av--' . self::avatar_variant( $seed ) . ' ' . $modifiers );
		$initial = $initial ?: strtoupper( substr( $seed, 0, 2 ) );

		printf(
			'<span class="%s" aria-hidden="true"><b>%s</b></span>',
			esc_attr( $classes ),
			esc_html( $initial )
		);
	}

	/**
	 * Return the URL of a PNG rendering of a post's mark, for Open Graph.
	 *
	 * Social platforms need a real file, so the mark is drawn once with GD and
	 * cached in the uploads directory. A featured image wins when there is one.
	 *
	 * @param int|\WP_Post|null $post Post to render.
	 */
	public static function social_image( $post = null ): string {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		if ( has_post_thumbnail( $post ) ) {
			return (string) get_the_post_thumbnail_url( $post, 'l3-feature' );
		}

		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return '';
		}

		$variant  = self::variant( $post );
		$name     = sprintf( 'mark-%s-%s.png', $post->ID, $variant );
		$uploads  = wp_upload_dir();
		$dir      = trailingslashit( $uploads['basedir'] ) . 'l3-marks';
		$file     = $dir . '/' . $name;
		$file_url = trailingslashit( $uploads['baseurl'] ) . 'l3-marks/' . $name;

		if ( file_exists( $file ) ) {
			return $file_url;
		}

		if ( ! wp_mkdir_p( $dir ) ) {
			return '';
		}

		self::draw( $file, $variant );

		return file_exists( $file ) ? $file_url : '';
	}

	/**
	 * Draw a mark to a PNG file.
	 *
	 * Deliberately typographic-free: GD's bundled fonts would not match the
	 * site's type, and loading a font file to draw two characters is not worth
	 * the dependency. The pattern alone is recognisable.
	 *
	 * @param string $file    Absolute path to write to.
	 * @param string $variant One of the VARIANTS.
	 */
	private static function draw( string $file, string $variant ): void {
		$width  = 1200;
		$height = 630;
		$image  = imagecreatetruecolor( $width, $height );

		$background = imagecolorallocate( $image, 18, 19, 22 );
		$ink        = imagecolorallocate( $image, 232, 231, 227 );
		$faint      = imagecolorallocatealpha( $image, 232, 231, 227, 110 );

		imagefilledrectangle( $image, 0, 0, $width, $height, $background );

		// The dotted ground, present on every mark.
		for ( $x = 0; $x < $width; $x += 14 ) {
			for ( $y = 0; $y < $height; $y += 14 ) {
				imagesetpixel( $image, $x, $y, $faint );
			}
		}

		switch ( $variant ) {
			case 'scan':
				for ( $y = 0; $y < $height; $y += 14 ) {
					imagefilledrectangle( $image, 0, $y, $width, $y + 3, $faint );
				}
				break;

			case 'bars':
				for ( $x = -$height; $x < $width; $x += 68 ) {
					imagefilledpolygon(
						$image,
						array( $x, $height, $x + 28, $height, $x + 28 + $height, 0, $x + $height, 0 ),
						$faint
					);
				}
				break;

			case 'grid':
			default:
				for ( $x = 0; $x < $width; $x += 36 ) {
					imagefilledrectangle( $image, $x, 0, $x + 1, $height, $faint );
				}
				for ( $y = 0; $y < $height; $y += 36 ) {
					imagefilledrectangle( $image, 0, $y, $width, $y + 1, $faint );
				}
				break;
		}

		// A rule along the bottom, the way the site rules its sections.
		imagefilledrectangle( $image, 0, $height - 8, $width, $height, $ink );

		imagepng( $image, $file, 9 );
		imagedestroy( $image );
	}
}
