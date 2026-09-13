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
	}

	/**
	 * Load translations from the theme's languages directory.
	 */
	public function load_text_domain(): void {
		load_theme_textdomain( 'lupin3rd', Theme::path( 'languages' ) );
	}

	/**
	 * Return the current language code, two letters, lowercase.
	 *
	 * Phase 1 is Italian only. Phase 2 replaces the body of this method with a
	 * call to the multilingual plugin; nothing else has to change.
	 */
	public function language(): string {
		/**
		 * Filters the current language code.
		 *
		 * @param string $code Two-letter language code.
		 */
		return (string) apply_filters( 'lupin3rd_current_language', substr( get_locale(), 0, 2 ) );
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
