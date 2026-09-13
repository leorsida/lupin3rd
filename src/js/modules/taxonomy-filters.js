/*
 * Archive filters.
 *
 * Every chip is already a link to a URL that runs the query on the server.
 * This module intercepts the click and does the same thing in the page, which
 * is faster and keeps the scroll position. If it never loads, the links work.
 */

/** Read the currently pressed value of a filter group. */
const activeValue = (group) => {
	const pressed = group.querySelector('[aria-pressed="true"]');
	return pressed ? pressed.dataset.value || '' : '';
};

/** Apply every group's selection to the cards in its scope. */
const apply = (target) => {
	const scope =
		document.querySelector(`[data-filter-scope="${target}"]`) ||
		document.querySelector('[data-filter-scope]');

	if (!scope) {
		return;
	}

	const groups = Array.from(
		document.querySelectorAll(`[data-filter-group][data-filter-target="${target}"]`)
	);

	let shown = 0;

	scope.querySelectorAll('[data-post]').forEach((card) => {
		const match = groups.every((group) => {
			const wanted = activeValue(group);
			if (!wanted) {
				return true;
			}
			const name = group.dataset.filterGroup;
			const key = name === 'format' ? 'type' : name;
			return (card.dataset[key] || '') === wanted;
		});

		card.classList.toggle('hidden', !match);
		if (match) {
			shown += 1;
		}
	});

	scope.querySelectorAll('[data-filter-empty]').forEach((element) => {
		element.classList.toggle('hidden', shown > 0);
	});
};

export default function initTaxonomyFilters() {
	const groups = document.querySelectorAll('[data-filter-group]');

	if (!groups.length) {
		return;
	}

	groups.forEach((group) => {
		group.addEventListener('click', (event) => {
			const chip = event.target.closest('.chip');

			if (!chip || !group.contains(chip)) {
				return;
			}

			/* The chip is a real link; only now do we take over from it. */
			event.preventDefault();

			group.querySelectorAll('.chip').forEach((other) => {
				other.setAttribute('aria-pressed', String(other === chip));
				other.classList.toggle('is-active', other === chip);
			});

			apply(group.dataset.filterTarget || '');
		});
	});

	document.querySelectorAll('[data-reset-filters]').forEach((button) => {
		button.addEventListener('click', () => {
			document.querySelectorAll('[data-filter-group]').forEach((group) => {
				const chips = group.querySelectorAll('.chip');
				chips.forEach((chip, index) => {
					chip.setAttribute('aria-pressed', String(index === 0));
					chip.classList.toggle('is-active', index === 0);
				});
				apply(group.dataset.filterTarget || '');
			});
		});
	});
}
