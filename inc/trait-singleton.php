<?php
/**
 * Shared singleton behaviour.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * Makes a class instantiable exactly once.
 *
 * The constructor calls register_hooks(), so a module cannot attach its hooks
 * twice however many times it is asked for. Each class using the trait gets its
 * own copy of $instance.
 */
trait Singleton {

	/**
	 * The single instance of the using class.
	 *
	 * @var static|null
	 */
	private static ?self $instance = null;

	/**
	 * Return the single instance, creating it on first call.
	 */
	public static function instance(): static {
		return static::$instance ??= new static();
	}

	/**
	 * Instances are created through instance() only.
	 */
	private function __construct() {
		$this->register_hooks();
	}

	/**
	 * Cloning a singleton would defeat its purpose.
	 */
	private function __clone() {}

	/**
	 * Unserialising a singleton would defeat its purpose.
	 *
	 * @throws \LogicException Always.
	 */
	public function __wakeup(): void {
		throw new \LogicException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Attach this module's WordPress hooks. Called once, from the constructor.
	 */
	abstract protected function register_hooks(): void;
}
