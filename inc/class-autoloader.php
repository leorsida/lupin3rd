<?php
/**
 * Class autoloader.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Maps class names in the Lupin3rd namespace to files under inc/.
 *
 * Lupin3rd\Content_Types  ->  inc/class-content-types.php
 * Lupin3rd\Singleton      ->  inc/trait-singleton.php
 *
 * This class cannot use the Singleton trait: the trait is what it loads.
 */
final class Autoloader {

	/**
	 * The single instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Directory holding the theme's classes, with a trailing slash.
	 *
	 * @var string
	 */
	private string $directory;

	/**
	 * Return the single instance, creating it on first call.
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Resolve the class directory once.
	 */
	private function __construct() {
		$this->directory = get_template_directory() . '/inc/';
	}

	/**
	 * Cloning a singleton would defeat its purpose.
	 */
	private function __clone() {}

	/**
	 * Register the autoloader with PHP.
	 */
	public function register(): void {
		spl_autoload_register( array( $this, 'load' ) );
	}

	/**
	 * Load the file defining the given class, if this theme owns it.
	 *
	 * @param string $class_name Fully qualified class name.
	 */
	public function load( string $class_name ): void {
		if ( ! str_starts_with( $class_name, __NAMESPACE__ . '\\' ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( __NAMESPACE__ ) + 1 );
		$slug     = strtolower( str_replace( array( '\\', '_' ), array( '/', '-' ), $relative ) );

		foreach ( array( 'class', 'trait', 'interface' ) as $kind ) {
			$path = $this->directory . $kind . '-' . $slug . '.php';

			if ( is_readable( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
