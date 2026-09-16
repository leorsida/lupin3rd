<?php
/**
 * Every message this theme sends, through one door.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * The theme sends three kinds of email and each one is a sentence somebody may
 * want to rewrite without a deploy. They all pass through here, so that there
 * is one filter shape to learn instead of three, and one place that decides
 * what happens when a filter returns nonsense.
 *
 * The wording stays in the code as the default, and deliberately: it is a
 * `__()` string, so the English build gets it from the translation file when
 * the multilingual work lands. A copy stored in the database would be one
 * language pretending to be all of them.
 *
 * Plain text, always. The colophon promises «nessun tracciamento nelle email:
 * niente pixel, niente link riscritti», and plain text is what makes that true
 * by construction rather than by vigilance — a tracking pixel needs an <img>,
 * and there is nowhere to put one.
 *
 * Not a module: it has no hooks of its own and nothing to keep. Like the
 * walkers, it is a plain class the rest of the theme calls.
 */
final class Mail {

	/**
	 * Send one of the theme's messages.
	 *
	 * @param string                $key     Short name of the message: what the
	 *                                       filter is called after.
	 * @param string                $to      Recipient address.
	 * @param string                $subject Subject line.
	 * @param string                $body    Plain text body.
	 * @param array<int, string>    $headers Extra headers.
	 * @param array<string, mixed>  $context What the message is about, for
	 *                                       whoever filters it.
	 */
	public static function send( string $key, string $to, string $subject, string $body, array $headers = array(), array $context = array() ): bool {
		$message = array(
			'to'      => $to,
			'subject' => $subject,
			'body'    => $body,
			'headers' => $headers,
		);

		/**
		 * Filters one of the theme's outgoing messages.
		 *
		 * The dynamic part of the name is the message key: lupin3rd_mail_new_contribution,
		 * lupin3rd_mail_dispatch_confirmation, lupin3rd_mail_comment_reported.
		 * See docs/llmwiki/16-emails.md for what each carries.
		 *
		 * @param array<string, mixed> $message Recipient, subject, body and headers.
		 * @param array<string, mixed> $context What the message is about.
		 */
		$filtered = apply_filters( "lupin3rd_mail_{$key}", $message, $context );

		/*
		 * A filter that returns the wrong shape gets ignored rather than
		 * obeyed. Sending an empty subject to an empty address because
		 * somebody's plugin returned null is worse than sending the default.
		 */
		if ( is_array( $filtered ) ) {
			foreach ( array( 'to', 'subject', 'body' ) as $part ) {
				if ( isset( $filtered[ $part ] ) && is_string( $filtered[ $part ] ) && '' !== $filtered[ $part ] ) {
					$message[ $part ] = $filtered[ $part ];
				}
			}

			if ( isset( $filtered['headers'] ) && is_array( $filtered['headers'] ) ) {
				$message['headers'] = $filtered['headers'];
			}
		}

		if ( ! is_email( $message['to'] ) ) {
			return false;
		}

		return wp_mail(
			$message['to'],
			$message['subject'],
			$message['body'],
			$message['headers']
		);
	}
}
