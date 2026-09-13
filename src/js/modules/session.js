/*
 * The header, corrected.
 *
 * Every page ships its header in the signed-out state, because every page is
 * the same page for everybody — that is what makes it cacheable (D-017). This
 * reads the small display cookie PHP left beside it and swaps the two states.
 *
 * The cookie is not a credential. The reader can edit it, and editing it
 * changes what their own header says and nothing else. It is therefore written
 * into the page with textContent and never with innerHTML: reach for innerHTML
 * here and you hand the reader a cross-site scripting hole aimed at themselves.
 */

const HINT = 'l3_hint';

const readHint = () => {
	const found = document.cookie
		.split('; ')
		.find((pair) => pair.startsWith(`${HINT}=`));

	if (!found) {
		return null;
	}

	try {
		const value = JSON.parse(decodeURIComponent(found.slice(HINT.length + 1)));

		return value && typeof value.name === 'string' ? value : null;
	} catch (error) {
		/* A cookie the reader mangled is a cookie we do not have. */
		return null;
	}
};

const show = (element, on) => {
	element.classList.toggle('hidden', !on);
};

export default function initSession() {
	const hint = readHint();

	if (!hint) {
		// The page is already correct: it was rendered for a stranger.
		return;
	}

	document.querySelectorAll('[data-when-signed-out]').forEach((element) => show(element, false));
	document.querySelectorAll('[data-when-signed-in]').forEach((element) => show(element, true));

	document.querySelectorAll('[data-account-name]').forEach((element) => {
		element.textContent = hint.name;
	});

	document.querySelectorAll('[data-account-profile]').forEach((link) => {
		if (typeof hint.profile === 'string' && hint.profile.startsWith('/')) {
			link.setAttribute('href', hint.profile);
		}
	});

	const chip = document.querySelector('[data-account-chip]');
	const panel = document.querySelector('[data-account-panel]');

	if (!chip || !panel) {
		return;
	}

	const avatar = chip.querySelector('.avatar');

	if (avatar) {
		chip.setAttribute('aria-label', hint.name);
		chip.setAttribute('title', hint.name);

		const mark = avatar.querySelector('b');

		if (mark) {
			mark.textContent = typeof hint.mark === 'string' ? hint.mark.slice(0, 2) : '';
		}

		/*
		 * The pattern is one of five names the stylesheet defines. Anything
		 * else is somebody editing their own cookie, and gets no class at all
		 * rather than an attribute built from their string.
		 */
		const patterns = ['scan', 'bars', 'grid', 'dots', 'diag'];

		if (patterns.includes(hint.pattern)) {
			avatar.classList.add(`av--${hint.pattern}`);
		}
	}

	const close = () => {
		show(panel, false);
		chip.setAttribute('aria-expanded', 'false');
	};

	chip.addEventListener('click', (event) => {
		event.stopPropagation();

		const open = 'true' === chip.getAttribute('aria-expanded');

		show(panel, open);
		chip.setAttribute('aria-expanded', String(!open));
	});

	document.addEventListener('click', (event) => {
		if (!panel.contains(event.target)) {
			close();
		}
	});

	document.addEventListener('keydown', (event) => {
		if ('Escape' === event.key) {
			close();
		}
	});
}
