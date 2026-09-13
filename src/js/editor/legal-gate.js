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

/** Whether a block tree contains a legal-scope notice at any depth. */
const hasLegalNotice = (blocks) =>
	blocks.some(
		(block) =>
			(block.name === 'l3/notice' && block.attributes?.variant === 'legal') ||
			hasLegalNotice(block.innerBlocks || [])
	);

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
