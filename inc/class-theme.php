<?php
/**
 * Theme registry.
 *
 * @package Lupin3rd
 */

namespace Lupin3rd;

defined( 'ABSPATH' ) || exit;

/**
 * The only class that knows which modules exist and in what order they start.
 *
 * Boot order is the order of the array below and it matters: Setup declares
 * theme support before Content_Types registers anything, and Content_Types
 * registers the post types before Meta_Fields attaches fields to them.
 */
final class Theme {

	use Singleton;

	/**
	 * Theme version, used to bust asset caches.
	 */
	public const VERSION = '0.1.0';

	/**
	 * Text domain. Repeated as a literal in every translation call, because
	 * the string extractors cannot read a constant.
	 */
	public const TEXT_DOMAIN = 'lupin3rd';

	/**
	 * Modules, in boot order.
	 *
	 * @var array<int, class-string>
	 */
	private const MODULES = array(
		Setup::class,
		I18n::class,
		Assets::class,
		Content_Types::class,
		Meta_Fields::class,
		Profile_Fields::class,
		Community::class,
		Authentication::class,
		Session_Hint::class,
		Account::class,
		Retirement::class,
		Reply_Notifications::class,
		Consent::class,
		Blocks::class,
		Patterns::class,
		Marks::class,
		Template_Tags::class,
		Archive_Filters::class,
		Search::class,
		Markdown::class,
		Seo::class,
		Schema::class,
		Geo::class,
		Feeds::class,
		Comment_Reports::class,
		Newsletter::class,
		Security::class,
		Performance::class,
	);

	/**
	 * Started modules, keyed by class name.
	 *
	 * @var array<class-string, object>
	 */
	private array $modules = array();

	/**
	 * Theme has no hooks of its own; the modules carry them.
	 */
	protected function register_hooks(): void {}

	/**
	 * Start every module exactly once.
	 */
	public function boot(): void {
		foreach ( self::MODULES as $module ) {
			if ( ! isset( $this->modules[ $module ] ) && method_exists( $module, 'instance' ) ) {
				$this->modules[ $module ] = $module::instance();
			}
		}
	}

	/**
	 * Return a started module, or null when it is not part of this build.
	 *
	 * @param class-string $module Module class name.
	 */
	public function module( string $module ): ?object {
		return $this->modules[ $module ] ?? null;
	}

	/**
	 * Absolute path to a file inside the theme.
	 *
	 * @param string $relative Path relative to the theme root, no leading slash.
	 */
	public static function path( string $relative = '' ): string {
		return get_template_directory() . '/' . ltrim( $relative, '/' );
	}

	/**
	 * Public URL of a file inside the theme.
	 *
	 * @param string $relative Path relative to the theme root, no leading slash.
	 */
	public static function url( string $relative = '' ): string {
		return get_template_directory_uri() . '/' . ltrim( $relative, '/' );
	}
}
