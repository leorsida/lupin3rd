/*
 * The tutorial's own fields, in the document sidebar.
 *
 * register_post_meta() stores these; nothing draws them. Without this panel the
 * desk would have to use the raw custom-fields table, which is how a required
 * field quietly stops being filled in.
 */

import { h, __ } from './dom.js';

const FIELDS = [
	{
		key: 'l3_estimated_time',
		label: 'Tempo stimato (minuti)',
		type: 'number',
		help: 'Finisce nei dati strutturati come HowTo.totalTime.',
	},
	{
		key: 'l3_prerequisites',
		label: 'Prerequisiti',
		type: 'textarea',
		help: 'Uno per riga. Finiscono nei dati strutturati come HowTo.supply.',
	},
	{ key: 'l3_lab_repository', label: 'Repository del laboratorio', type: 'url' },
	{ key: 'l3_sample_hash', label: 'SHA-256 del campione', type: 'text' },
];

export default function registerTutorialPanel() {
	const { registerPlugin } = wp.plugins;
	const { PanelRow, TextControl, TextareaControl } = wp.components;
	const { useSelect, useDispatch } = wp.data;

	// The panel component moved from wp.editPost to wp.editor; support both so
	// the theme does not break on either side of that change.
	const PluginDocumentSettingPanel =
		wp.editor?.PluginDocumentSettingPanel || wp.editPost?.PluginDocumentSettingPanel;

	if (!registerPlugin || !PluginDocumentSettingPanel) {
		return;
	}

	const Panel = () => {
		const { postType, meta } = useSelect(
			(s) => ({
				postType: s('core/editor').getCurrentPostType(),
				meta: s('core/editor').getEditedPostAttribute('meta') || {},
			}),
			[]
		);
		const { editPost } = useDispatch('core/editor');

		if (postType !== 'tutorial') {
			return null;
		}

		const set = (key, value) => editPost({ meta: { ...meta, [key]: value } });

		return h(
			PluginDocumentSettingPanel,
			{ name: 'l3-tutorial', title: __('Tutorial', 'lupin3rd'), className: 'l3-tutorial-panel' },
			...FIELDS.map((field) =>
				h(
					PanelRow,
					{ key: field.key },
					field.type === 'textarea'
						? h(TextareaControl, {
								label: __(field.label, 'lupin3rd'),
								help: field.help ? __(field.help, 'lupin3rd') : undefined,
								value: meta[field.key] || '',
								onChange: (value) => set(field.key, value),
						  })
						: h(TextControl, {
								label: __(field.label, 'lupin3rd'),
								help: field.help ? __(field.help, 'lupin3rd') : undefined,
								type: field.type === 'number' ? 'number' : 'text',
								value: meta[field.key] ?? '',
								onChange: (value) =>
									set(field.key, field.type === 'number' ? parseInt(value, 10) || 0 : value),
						  })
				)
			)
		);
	};

	registerPlugin('lupin3rd-tutorial', { render: Panel, icon: null });
}
