/*
 * l3/code — editing.
 *
 * The editor shows the source as plain text. There is no preview of the
 * highlighting because the highlighting happens on the server: showing an
 * approximation here would be a second implementation to keep in step with the
 * first, and the first is the one readers see.
 */

import { h, __ } from './dom.js';

export default function registerCodeBlock() {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps, PlainText } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl, SelectControl } = wp.components;

	registerBlockType('l3/code', {
		edit: ({ attributes, setAttributes }) => {
			const { content, language, filename, showLineNumbers } = attributes;
			const label = [language, filename].filter(Boolean).join(' · ');

			return h(
				'div',
				useBlockProps({ className: 'codeblock' }),
				h(
					InspectorControls,
					null,
					h(
						PanelBody,
						{ title: __('Blocco codice', 'lupin3rd') },
						h(SelectControl, {
							label: __('Linguaggio', 'lupin3rd'),
							value: language,
							options: [
								{ label: __('nessuno', 'lupin3rd'), value: '' },
								{ label: 'bash', value: 'bash' },
								{ label: 'php', value: 'php' },
								{ label: 'js', value: 'js' },
								{ label: 'python', value: 'python' },
							],
							onChange: (value) => setAttributes({ language: value }),
							help: __(
								'Decide quali parole chiave vengono marcate. La colorazione è monocromatica e la genera il server.',
								'lupin3rd'
							),
						}),
						h(TextControl, {
							label: __('Nome del file', 'lupin3rd'),
							value: filename,
							onChange: (value) => setAttributes({ filename: value }),
							help: __('Compare nell’intestazione, accanto al linguaggio.', 'lupin3rd'),
						}),
						h(ToggleControl, {
							label: __('Numeri di riga', 'lupin3rd'),
							checked: !!showLineNumbers,
							onChange: (value) => setAttributes({ showLineNumbers: value }),
						})
					)
				),
				h(
					'div',
					{ className: 'codeblock-head' },
					h('span', null, label || __('codice', 'lupin3rd')),
					h('span', { className: 'copy-btn' }, __('Copia', 'lupin3rd'))
				),
				h(
					'pre',
					null,
					h(PlainText, {
						value: content,
						onChange: (value) => setAttributes({ content: value }),
						placeholder: __('Incolla qui il codice…', 'lupin3rd'),
						'aria-label': __('Codice', 'lupin3rd'),
					})
				)
			);
		},

		// A dynamic block: the markup readers get is built by render.php.
		save: () => null,
	});
}
