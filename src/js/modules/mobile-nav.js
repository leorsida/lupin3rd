/*
 * The navigation on a narrow screen.
 *
 * Below the breakpoint the menu collapses behind a button. The markup is the
 * same either way: only a class changes, so nothing is duplicated and nothing
 * is hidden from a crawler.
 */

export default function initMobileNav() {
	const button = document.querySelector('[data-nav-toggle]');
	const nav = document.querySelector('.topnav nav');

	if (!button || !nav) {
		return;
	}

	button.addEventListener('click', () => {
		const open = nav.classList.toggle('is-open');
		button.setAttribute('aria-expanded', String(open));
	});

	/* Escape closes it, the way every other overlay on the site behaves. */
	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && nav.classList.contains('is-open')) {
			nav.classList.remove('is-open');
			button.setAttribute('aria-expanded', 'false');
			button.focus();
		}
	});
}
