/*
 * The cookie notice, and the frames it keeps out.
 *
 * The notice asks nothing, because this site stores nothing optional:
 * everything in its table is required for the page to work or is the result of
 * something the reader did — signing in, ticking «ricorda», switching the
 * theme. So it states, and takes an acknowledgement. The panel behind «Vedi
 * quali» has one row that cannot be switched off, and exists for the table
 * inside it.
 *
 * Third-party frames are not a category. PHP has already replaced each one
 * with a placeholder before this file runs; this module only restores the one
 * a reader asks for, and remembers nothing about it.
 */

import * as CookieConsent from '../vendor/cookieconsent.js';

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

	CookieConsent.run({
		autoShow: true,
		disablePageInteraction: false,
		cookie: { name: 'l3_consent', expiresAfterDays: 180, sameSite: 'Lax' },
		guiOptions: {
			/* Full width along the bottom: a statement belongs across the page,
			 * not in a corner where it reads as something to dismiss. `inline`
			 * puts the two buttons beside the text instead of under it. */
			consentModal: { layout: 'bar inline', position: 'bottom', equalWeightButtons: true, flipButtons: false },
			preferencesModal: { layout: 'box', equalWeightButtons: true, flipButtons: false },
		},
		categories: {
			necessary: { enabled: true, readOnly: true },
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
