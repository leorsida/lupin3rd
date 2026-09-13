/*
 * Colour scheme.
 *
 * The stored preference is applied by an inline snippet in the head, before
 * first paint, so the page never flashes light before turning dark. This
 * module only handles the toggle afterwards.
 */

const STORAGE_KEY = 'l3-theme';

/** Read the stored preference, tolerating a browser that refuses storage. */
const stored = () => {
	try {
		return localStorage.getItem(STORAGE_KEY);
	} catch (error) {
		return null;
	}
};

/** Store the preference, tolerating a browser that refuses storage. */
const store = (value) => {
	try {
		localStorage.setItem(STORAGE_KEY, value);
	} catch (error) {
		/* A private window is allowed to say no. The toggle still works. */
	}
};

/** Apply a scheme to the document and update every toggle's pressed state. */
const apply = (scheme) => {
	document.documentElement.setAttribute('data-theme', scheme);
	document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
		button.setAttribute('aria-pressed', String(scheme === 'light'));
	});
};

export default function initThemeSwitch() {
	const toggles = document.querySelectorAll('[data-theme-toggle]');

	if (!toggles.length) {
		return;
	}

	apply(stored() === 'light' ? 'light' : 'dark');

	toggles.forEach((button) => {
		button.addEventListener('click', () => {
			const next =
				document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
			apply(next);
			store(next);
		});
	});
}
