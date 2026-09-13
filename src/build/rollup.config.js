/**
 * Rollup configuration.
 *
 * Two bundles, for two audiences. The front-end bundle is the one the rule
 * about a single file governs; the editor bundle is never served to a reader.
 * Neither has a dependency: every line the browser runs is written here, and
 * the editor one talks to WordPress through the globals already on the page.
 */
const banner = '/* lupin3rd.org — compiled from src/js. Do not edit. */';

export default [
	{
		input: 'src/js/lupin3rd.js',
		output: { file: 'js/lupin3rd.js', format: 'iife', name: 'lupin3rd', sourcemap: false, banner },
	},
	{
		input: 'src/js/editor.js',
		output: { file: 'js/lupin3rd-editor.js', format: 'iife', name: 'lupin3rdEditor', sourcemap: false, banner },
	},
];
