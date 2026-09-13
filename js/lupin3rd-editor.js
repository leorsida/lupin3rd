/* lupin3rd.org — compiled from src/js. Do not edit. */
(function () {
	'use strict';

	/*
	 * A one-line stand-in for JSX.
	 *
	 * The theme has no Babel step and does not want one for four editor controls.
	 * `h('div', { className: 'x' }, child)` reads closely enough to the markup it
	 * produces.
	 */

	const h = (...args) => wp.element.createElement(...args);
	const { __ } = wp.i18n;

	/*
	 * l3/code — editing.
	 *
	 * The editor shows the source as plain text. There is no preview of the
	 * highlighting because the highlighting happens on the server: showing an
	 * approximation here would be a second implementation to keep in step with the
	 * first, and the first is the one readers see.
	 */


	function registerCodeBlock() {
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

	/*
	 * l3/notice — editing.
	 *
	 * Three variants. "Perimetro legale" is the one a tutorial cannot be published
	 * without, so the variant control says so rather than leaving the author to
	 * discover it when the publish button refuses.
	 */


	const VARIANTS = [
		{ value: 'method', label: 'Nota di metodo' },
		{ value: 'legal', label: 'Perimetro legale' },
		{ value: 'correction', label: 'Correzione' },
	];

	function registerNoticeBlock() {
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

	/*
	 * A tutorial cannot be published without a legal-scope notice.
	 *
	 * This is the friendly half of the rule: it locks the publish button and says
	 * why, while the author is still writing. The binding half is on the server —
	 * the REST gate and the insert filter in Blocks — because WP-CLI, an import
	 * and a REST client never see this file.
	 */


	const LOCK = 'l3-legal-notice';
	const NOTICE_ID = 'l3-legal-notice';

	/** Whether a block tree contains a legal-scope notice at any depth. */
	const hasLegalNotice = (blocks) =>
		blocks.some(
			(block) =>
				(block.name === 'l3/notice' && block.attributes?.variant === 'legal') ||
				hasLegalNotice(block.innerBlocks || [])
		);

	function registerLegalGate() {
		const { select, subscribe, dispatch } = wp.data;
		let locked = null;

		subscribe(() => {
			const editor = select('core/editor');
			const blockEditor = select('core/block-editor');

			if (!editor || !blockEditor || !editor.getCurrentPostType) {
				return;
			}

			if (editor.getCurrentPostType() !== 'tutorial') {
				return;
			}

			const missing = !hasLegalNotice(blockEditor.getBlocks());

			if (missing === locked) {
				return;
			}

			locked = missing;

			if (missing) {
				dispatch('core/editor').lockPostSaving(LOCK);
				dispatch('core/notices').createNotice(
					'warning',
					__(
						'Questo tutorial non ha il riquadro «perimetro legale». Aggiungi un blocco Avviso con quella variante: senza, non si pubblica.',
						'lupin3rd'
					),
					{ id: NOTICE_ID, isDismissible: false }
				);
			} else {
				dispatch('core/editor').unlockPostSaving(LOCK);
				dispatch('core/notices').removeNotice(NOTICE_ID);
			}
		});
	}

	/*
	 * The tutorial's own fields, in the document sidebar.
	 *
	 * register_post_meta() stores these; nothing draws them. Without this panel the
	 * desk would have to use the raw custom-fields table, which is how a required
	 * field quietly stops being filled in.
	 */


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

	function registerTutorialPanel() {
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

	/*
	 * lupin3rd.org — editor bundle.
	 *
	 * Loaded only in the block editor, never on a public page, so the front end's
	 * one-bundle rule is untouched.
	 *
	 * Everything here talks to WordPress through the `wp.*` globals rather than
	 * bundling @wordpress packages: the packages are already on the page, and
	 * bundling a second copy of React into a theme is how editors get slow.
	 */


	registerCodeBlock();
	registerNoticeBlock();
	registerLegalGate();
	registerTutorialPanel();

})();
