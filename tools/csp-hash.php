<?php
/**
 * Print the Content-Security-Policy source hash for the inline theme snippet.
 *
 * The theme sets data-theme on <html> before first paint, which needs an
 * inline <script>. The policy allows exactly that one script, by hash, rather
 * than opening with 'unsafe-inline' or issuing a per-request nonce — a nonce
 * would make every response unique and defeat the static page cache.
 *
 * The hash covers the exact bytes between <script> and </script>. One changed
 * character and the browser silently refuses to run the snippet, so run this
 * after any edit to Assets::THEME_SNIPPET and update the web-server config.
 *
 * Usage:
 *     php tools/csp-hash.php
 *
 * @package Lupin3rd
 */

$snippet = null;
$source  = file_get_contents( __DIR__ . '/../inc/class-assets.php' );

if ( false !== $source && preg_match( "/THEME_SNIPPET\s*=\s*'(.*?)';/s", $source, $match ) ) {
	$snippet = stripcslashes( $match[1] );
}

if ( null === $snippet ) {
	fwrite( STDERR, "Could not read Assets::THEME_SNIPPET.\n" );
	exit( 1 );
}

printf( "sha256-%s\n", base64_encode( hash( 'sha256', $snippet, true ) ) );
fprintf( STDERR, "(%d bytes)\n", strlen( $snippet ) );
