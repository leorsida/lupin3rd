<?php
/**
 * Constants PHPStan cannot see.
 *
 * The theme guards every file with `defined( 'ABSPATH' ) || exit;` and reads a
 * couple of constants WordPress defines at runtime. Declaring them here keeps
 * the analysis about the theme's own logic.
 *
 * @package Lupin3rd
 */

defined( 'ABSPATH' ) || define( 'ABSPATH', '/app/public/' );
defined( 'WP_DEBUG' ) || define( 'WP_DEBUG', false );
defined( 'WP_ENVIRONMENT_TYPE' ) || define( 'WP_ENVIRONMENT_TYPE', 'local' );
