/*
 * The navigation on a narrow screen: a drawer.
 *
 * Two things about the order of events here matter more than the animation.
 *
 * The server renders the menu as a stacked list in normal flow and the button
 * that opens it as `hidden`. This module is what turns the list into a panel
 * (`is-drawer`) and reveals the button. So a reader whose script never arrives
 * gets a long menu under the bar — every link reachable, no control that
 * shrugs — instead of a button that does nothing.
 *
 * And a drawer is a trap for the keyboard if nobody keeps focus inside it: the
 * links behind it are still in the tab order, so Tab walks out of the panel and
 * into a page the reader cannot see. That is what the focus handling below is
 * for, and it is not decoration.
 */

const FOCUSABLE = 'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

export default function initMobileNav() {
	const button = document.querySelector('[data-nav-toggle]');
	const nav = document.querySelector('.topnav nav');
	const scrim = document.querySelector('[data-nav-scrim]');

	if (!button || !nav) {
		return;
	}

	/* From here on there is something that can open the panel, so it may close. */
	nav.classList.add('is-drawer');
	button.hidden = false;
	nav.querySelectorAll('[data-nav-close]').forEach((close) => {
		close.hidden = false;
	});

	let lastFocus = null;

	const focusable = () => Array.from(nav.querySelectorAll(FOCUSABLE)).filter((el) => !el.hidden);

	const open = () => {
		lastFocus = document.activeElement;

		if (scrim) {
			scrim.hidden = false;
			/* A reflow between `hidden` and the class, or the fade never runs. */
			void scrim.offsetWidth;
			scrim.classList.add('is-open');
		}

		nav.classList.add('is-open');
		button.setAttribute('aria-expanded', 'true');
		document.documentElement.classList.add('nav-locked');

		const first = focusable()[0];

		if (first) {
			first.focus();
		}
	};

	const close = () => {
		nav.classList.remove('is-open');
		button.setAttribute('aria-expanded', 'false');
		document.documentElement.classList.remove('nav-locked');

		if (scrim) {
			scrim.classList.remove('is-open');
			scrim.hidden = true;
		}

		if (lastFocus instanceof HTMLElement) {
			lastFocus.focus();
		}
	};

	const isOpen = () => nav.classList.contains('is-open');

	button.addEventListener('click', () => {
		if (isOpen()) {
			close();
		} else {
			open();
		}
	});

	nav.addEventListener('click', (event) => {
		if (event.target.closest('[data-nav-close]')) {
			close();
			return;
		}

		/* Following a link inside the drawer should not leave it open behind. */
		if (event.target.closest('a[href]')) {
			close();
		}
	});

	if (scrim) {
		scrim.addEventListener('click', close);
	}

	document.addEventListener('keydown', (event) => {
		if (!isOpen()) {
			return;
		}

		if ('Escape' === event.key) {
			close();
			return;
		}

		if ('Tab' !== event.key) {
			return;
		}

		const stops = focusable();

		if (!stops.length) {
			return;
		}

		const first = stops[0];
		const last = stops[stops.length - 1];

		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		} else if (!nav.contains(document.activeElement)) {
			event.preventDefault();
			first.focus();
		}
	});
}
