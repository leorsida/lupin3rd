<?php
/**
 * Translation and date formatting.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the text domain and centralises date formats.
 *
 * Dates are never formatted inline in a template. Italian uses d.m.Y, English
 * uses M j, Y, and a single method decides which applies, so that adding the
 * second language in phase 2 touches one file.
 */
final class I18n {

	use Singleton;

	/**
	 * Attach hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'after_setup_theme', array( $this, 'load_text_domain' ) );
		add_filter( 'language_attributes', array( $this, 'declare_the_published_language' ) );
	}

	/**
	 * Make the `<html>` tag declare the language the page is actually written in.
	 *
	 * Core builds this string from `get_locale()`, which answers for the
	 * WordPress installation. When the two disagree — a site setting changed by
	 * hand or by an update — the markup tells a reader, a screen reader and a
	 * crawler that Italian prose is English. Here the content decides.
	 *
	 * @param mixed $attributes The attribute string core assembled.
	 * @return mixed
	 */
	public function declare_the_published_language( $attributes ) {
		if ( ! is_string( $attributes ) || is_admin() ) {
			return $attributes;
		}

		$lang = $this->language();

		if ( preg_match( '/lang="[^"]*"/', $attributes ) ) {
			return preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $lang ) . '"', $attributes );
		}

		return trim( $attributes . ' lang="' . esc_attr( $lang ) . '"' );
	}

	/**
	 * Load translations from the theme's languages directory.
	 */
	public function load_text_domain(): void {
		load_theme_textdomain( 'lupin3rd', Theme::path( 'languages' ) );
	}

	/**
	 * The languages this site actually publishes in, best first.
	 *
	 * Not a list of languages WordPress can be set to: a list of languages that
	 * have content, URLs and feeds here. Today that is one. Adding `'en'` is
	 * what turns the second half of the language switch from a link to a 404
	 * into a link to a site.
	 *
	 * @return array<int, string>
	 */
	public static function published(): array {
		/**
		 * Filters the languages the site publishes in.
		 *
		 * @param array<int, string> $codes Two-letter language codes, best first.
		 */
		return (array) apply_filters( 'lupin3rd_published_languages', array( 'it' ) );
	}

	/**
	 * Return the current language code, two letters, lowercase.
	 *
	 * Read from `get_locale()`, but only accepted when the site publishes that
	 * language. `get_locale()` answers for WordPress, not for the content: it
	 * follows the site setting, and in the admin it follows the signed-in
	 * user's own profile language. Trusting it directly meant a setting changed
	 * by accident — a core update did exactly this on 20.09.2026 — could put
	 * the public header into a language with nothing behind it, announcing EN
	 * on an Italian site and linking to an /it/ that does not exist.
	 *
	 * So the locale proposes and this list decides. When the English build
	 * lands, `published()` gains `'en'` and the switch starts working on its
	 * own.
	 */
	public function language(): string {
		$published = self::published();
		$locale    = substr( get_locale(), 0, 2 );
		$code      = in_array( $locale, $published, true ) ? $locale : (string) ( $published[0] ?? 'it' );

		/**
		 * Filters the current language code.
		 *
		 * @param string $code Two-letter language code.
		 */
		return (string) apply_filters( 'lupin3rd_current_language', $code );
	}

	/**
	 * Return the date format for the current language.
	 */
	public function date_format(): string {
		return 'en' === $this->language() ? 'M j, Y' : 'd.m.Y';
	}

	/**
	 * Format a post's publication date for display.
	 *
	 * @param int|\WP_Post|null $post Post to read the date from.
	 */
	public function post_date( $post = null ): string {
		return (string) get_the_date( $this->date_format(), $post );
	}

	/**
	 * Format an arbitrary timestamp for display.
	 *
	 * @param int $timestamp Unix timestamp.
	 */
	public function date( int $timestamp ): string {
		return wp_date( $this->date_format(), $timestamp );
	}

	/**
	 * Format a moment the way the prototype does: near things in words, far
	 * things by date.
	 *
	 * Deliberately coarse. «3 ore fa» is what a reader needs; the minute an
	 * account was last seen is not, and is exactly the kind of detail the
	 * colophon promises not to publish.
	 *
	 * @param int|string $when Unix timestamp, or a GMT datetime string.
	 */
	public function relative_date( $when ): string {
		$timestamp = is_numeric( $when ) ? (int) $when : (int) strtotime( (string) $when . ' UTC' );

		if ( $timestamp <= 0 ) {
			return '';
		}

		$days  = (int) floor( ( (int) wp_date( 'U' ) - $timestamp ) / DAY_IN_SECONDS );
		$time  = wp_date( 'H:i', $timestamp );
		$today = wp_date( 'Y-m-d' );
		$then  = wp_date( 'Y-m-d', $timestamp );

		if ( $then === $today ) {
			/* translators: %s: time of day, e.g. 11:02. */
			return sprintf( __( 'oggi, %s', 'lupin3rd' ), $time );
		}

		if ( wp_date( 'Y-m-d', (int) wp_date( 'U' ) - DAY_IN_SECONDS ) === $then ) {
			/* translators: %s: time of day, e.g. 21:40. */
			return sprintf( __( 'ieri, %s', 'lupin3rd' ), $time );
		}

		if ( $days < 7 ) {
			/* translators: %d: number of days. */
			return sprintf( _n( '%d giorno fa', '%d giorni fa', $days, 'lupin3rd' ), $days );
		}

		return $this->date( $timestamp ) . ', ' . $time;
	}
}
