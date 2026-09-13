/**
 * PostCSS configuration.
 *
 * Autoprefixer only. The design system is written against modern CSS on
 * purpose — OKLCH, color-mix, container-free grids — so there is nothing to
 * transpile and nothing to polyfill.
 */
module.exports = {
	plugins: {
		autoprefixer: {},
	},
};
