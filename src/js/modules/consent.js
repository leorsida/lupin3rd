/*
 * The cookie notice, and the embeds it governs.
 *
 * The notice exists to answer one question — may a third-party frame be
 * fetched — because that is the only question this site has to ask. Everything
 * else it stores is the session, the comment fields somebody ticked a box to
 * have remembered, and the theme switch: necessary, or chosen by an action.
 * Asking about those would be asking a question with one answer.
 *
 * So the panel behind «Personalizza» has two rows and one usable switch. What
 * earns it its place is the cookie table inside it: the same one on /privacy/,
 * put where somebody is actually deciding.
 */

import * as CookieConsent from '../vendor/cookieconsent.js';

const EMBEDS = 'embeds';

/** Put one gated embed back, from the markup PHP parked in the attribute. */
const reveal = (gate) => {
	const encoded = gate.getAttribute('data-embed');

	if (!encoded) {
		return;
	}

	const frame = document.createElement('div');

	/*
	 * The markup came from the server, where it was already the post's own
	 * rendered content — it is being restored, not introduced. The attribute is
	 * base64 only so that quotes survive the round trip.
	 */
	frame.innerHTML = atob(encoded);

	const iframe = frame.querySelector('iframe');

	if (iframe) {
		gate.replaceWith(iframe);
	}
};

const revealAll = () => {
	document.querySelectorAll('[data-embed-gate]').forEach(reveal);
};

const readConfig = () => {
	const block = document.getElementById('l3-consent-config');

	if (!block) {
		return null;
	}

	try {
		return JSON.parse(block.textContent || '');
	} catch (error) {
		/* Wording we cannot read is a notice we should not show half of. */
		return null;
	}
};

export default function initConsent() {
	const config = readConfig();

	if (!config) {
		return;
	}

	/* One embed, shown because this reader asked for this one. Nothing is
	 * remembered: the next page starts from the same question. */
	document.addEventListener('click', (event) => {
		const button = event.target.closest('[data-embed-show]');

		if (button) {
			const gate = button.closest('[data-embed-gate]');

			if (gate) {
				reveal(gate);
			}
		}
	});

	document.addEventListener('cc:onConsent', () => {
		if (CookieConsent.acceptedCategory(EMBEDS)) {
			revealAll();
		}
	});

	document.addEventListener('cc:onChange', () => {
		if (CookieConsent.acceptedCategory(EMBEDS)) {
			revealAll();
		}
	});

	CookieConsent.run({
		autoShow: true,
		disablePageInteraction: false,
		cookie: { name: 'l3_consent', expiresAfterDays: 180, sameSite: 'Lax' },
		guiOptions: {
			consentModal: { layout: 'box wide', position: 'bottom left', equalWeightButtons: true, flipButtons: false },
			preferencesModal: { layout: 'box', equalWeightButtons: true, flipButtons: false },
		},
		categories: {
			necessary: { enabled: true, readOnly: true },
			[EMBEDS]: { enabled: false },
		},
		language: {
			default: config.locale || 'it',
			translations: { [config.locale || 'it']: config.notice },
		},
	});

	/*
	 * Anything marked as reopening the notice does so. `true` forces it even
	 * once an answer has been given, which is the whole point of the control.
	 *
	 * A button ships `hidden` and is revealed here, because without this module
	 * it would do nothing; the footer's is a real link to /privacy/, so it
	 * ships visible and is only intercepted.
	 */
	document.querySelectorAll('[data-consent-open]').forEach((control) => {
		control.hidden = false;

		control.addEventListener('click', (event) => {
			event.preventDefault();
			CookieConsent.show(true);
		});
	});
}
