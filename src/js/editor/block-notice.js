/*
 * l3/notice — editing.
 *
 * Three variants. "Perimetro legale" is the one a tutorial cannot be published
 * without, so the variant control says so rather than leaving the author to
 * discover it when the publish button refuses.
 */

import { h, __ } from './dom.js';

export const VARIANTS = [
	{ value: 'method', label: 'Nota di metodo' },
	{ value: 'legal', label: 'Perimetro legale' },
	{ value: 'correction', label: 'Correzione' },
];

export default function registerNoticeBlock() {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps, InnerBlocks } = wp.blockEditor;
	const { PanelBody, SelectControl, Notice } = wp.components;

	registerBlockType('l3/notice', {
		edit: ({ attributes, setAttributes }) => {
			const { variant } = attributes;
			const current = VARIANTS.find((v) => v.value === variant) || VARIANTS[0];

			return h(
				'div',
				useBlockProps({
					className: variant === 'legal' ? 'callout callout--legal' : 'callout',
				}),
				h(
					InspectorControls,
					null,
					h(
						PanelBody,
						{ title: __('Avviso', 'lupin3rd') },
						h(SelectControl, {
							label: __('Variante', 'lupin3rd'),
							value: variant,
							options: VARIANTS.map((v) => ({ value: v.value, label: v.label })),
							onChange: (value) => setAttributes({ variant: value }),
						}),
						variant === 'legal'
							? h(
									Notice,
									{ status: 'info', isDismissible: false },
									__(
										'Questo è il riquadro obbligatorio nei tutorial: senza, il pezzo non si pubblica.',
										'lupin3rd'
									)
							  )
							: null
					)
				),
				h('p', { className: 'callout-title' }, current.label),
				h(InnerBlocks, {
					template: [['core/paragraph', { placeholder: __('Scrivi l’avviso…', 'lupin3rd') }]],
					templateLock: false,
				})
			);
		},

		save: () => h(InnerBlocks.Content),
	});
}
