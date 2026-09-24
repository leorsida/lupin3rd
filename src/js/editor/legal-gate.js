/*
 * A tutorial cannot be published without a legal-scope notice.
 *
 * This is the friendly half of the rule: it locks the publish button and says
 * why, while the author is still writing. The binding half is on the server —
 * the REST gate and the insert filter in Blocks — because WP-CLI, an import
 * and a REST client never see this file.
 */

import { __ } from './dom.js';

const LOCK = 'l3-legal-notice';
const NOTICE_ID = 'l3-legal-notice';

/** Every notice in a block tree, at any depth. */
const notices = (blocks) =>
	blocks.flatMap((block) => [
		...(block.name === 'l3/notice' ? [block] : []),
		...notices(block.innerBlocks || []),
	]);

/**
 * What to say, given the notices the piece actually has.
 *
 * «Add a notice» is the wrong sentence to read while looking at the notice you
 * just added. The block ships with the method variant selected, so the usual
 * mistake is not a missing box: it is a box set to the wrong thing, and the
 * fix is a control in the sidebar rather than another block.
 */
const complaint = (blocks) => {
	const found = notices(blocks);

	if (found.some((block) => block.attributes?.variant === 'legal')) {
		return null;
	}

	if (found.length) {
		return __(
			'Questo tutorial ha un Avviso, ma non è l’informativa legale. Selezionalo e imposta «Avviso → Variante → Informativa legale» nella barra laterale.',
			'lupin3rd'
		);
	}

	return __(
		'Questo tutorial non ha il riquadro informativa legale. Aggiungi un blocco Avviso e imposta «Variante → Informativa legale» nella barra laterale.',
		'lupin3rd'
	);
};

export default function registerLegalGate() {
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

		const missing = complaint(blockEditor.getBlocks());

		/* The message itself is the state: it changes when the mistake does. */
		if (missing === locked) {
			return;
		}

		locked = missing;

		if (missing) {
			dispatch('core/editor').lockPostSaving(LOCK);
			dispatch('core/notices').removeNotice(NOTICE_ID);
			dispatch('core/notices').createNotice('warning', missing, {
				id: NOTICE_ID,
				isDismissible: false,
			});
		} else {
			dispatch('core/editor').unlockPostSaving(LOCK);
			dispatch('core/notices').removeNotice(NOTICE_ID);
		}
	});
}
