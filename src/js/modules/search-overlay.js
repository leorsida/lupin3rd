/*
 * Search overlay.
 *
 * Filters a short list of recent pieces as you type, for an instant answer.
 * The form still submits to the real search page, which is what a reader
 * without JavaScript gets and what every crawler sees.
 */

export default function initSearchOverlay() {
	const overlay = document.querySelector('[data-search-overlay]');

	if (!overlay) {
		return;
	}

	const input = overlay.querySelector('[data-search-input]');
	const results = overlay.querySelector('[data-search-results]');
	const empty = overlay.querySelector('[data-search-empty]');
	const rows = Array.from(overlay.querySelectorAll('[data-live-search]'));
	let lastFocused = null;

	/*
	 * Visibility is `.is-open`, which is what the design system defines. The
	 * `hidden` attribute is kept in step with it for a different reason: it
	 * takes the panel's twelve links out of the tab order and hides them from
	 * assistive technology while the overlay is closed. Removing the attribute
	 * has to happen before focus(), because you cannot focus what is not
	 * rendered.
	 */

	/** Show the overlay and put the caret in the field. */
	const open = () => {
		lastFocused = document.activeElement;
		overlay.hidden = false;
		overlay.classList.add('is-open');
		if (input) {
			input.focus();
			input.select();
		}
	};

	/** Hide the overlay and return focus where it came from. */
	const close = () => {
		overlay.classList.remove('is-open');
		overlay.hidden = true;
		if (lastFocused instanceof HTMLElement) {
			lastFocused.focus();
		}
	};

	/** Show only the rows whose keywords contain every word typed. */
	const filter = (query) => {
		const words = query.toLowerCase().split(/\s+/).filter(Boolean);
		let shown = 0;

		rows.forEach((row) => {
			const haystack = row.dataset.keywords || '';
			const match = words.every((word) => haystack.includes(word));
			row.classList.toggle('hidden', !match);
			if (match) {
				shown += 1;
			}
		});

		if (empty) {
			empty.classList.toggle('hidden', shown > 0);
		}
		if (results) {
			results.classList.toggle('hidden', shown === 0);
		}
	};

	document.querySelectorAll('[data-search-open]').forEach((button) => {
		button.addEventListener('click', open);
	});

	overlay.querySelectorAll('[data-search-close]').forEach((button) => {
		button.addEventListener('click', close);
	});

	overlay.addEventListener('click', (event) => {
		if (event.target === overlay) {
			close();
		}
	});

	if (input) {
		input.addEventListener('input', () => filter(input.value));
	}

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && !overlay.hidden) {
			close();
			return;
		}

		/* Slash opens search, unless the reader is already typing somewhere. */
		const typing =
			document.activeElement instanceof HTMLElement &&
			['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);

		if (event.key === '/' && !typing && overlay.hidden) {
			event.preventDefault();
			open();
		}
	});
}
