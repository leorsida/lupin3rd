/*
 * Tab strips.
 *
 * The server renders every panel open, each with its own heading, and the tab
 * strip itself hidden. Without this script the page is a sequence of labelled
 * lists — everything is reachable, nothing is behind a control that does not
 * work. This script turns that into the prototype's tabs: it reveals the strip,
 * folds the headings away and leaves one panel showing.
 */

const panelsOf = (strip) =>
	Array.from(strip.querySelectorAll('[role="tab"]'))
		.map((tab) => ({
			tab,
			panel: document.getElementById(tab.getAttribute('aria-controls') || ''),
		}))
		.filter((pair) => pair.panel);

const select = (pairs, chosen) => {
	pairs.forEach(({ tab, panel }) => {
		const on = tab === chosen;

		tab.setAttribute('aria-selected', on ? 'true' : 'false');
		tab.tabIndex = on ? 0 : -1;
		panel.hidden = !on;
	});
};

export default function initTabs() {
	const strips = document.querySelectorAll('[data-tabs]');

	if (!strips.length) {
		return;
	}

	strips.forEach((strip) => {
		const pairs = panelsOf(strip);

		if (pairs.length < 2) {
			// One panel is not a tab strip; leave the page as the server sent it.
			return;
		}

		pairs.forEach(({ panel }) => {
			panel.querySelectorAll('[data-tab-heading]').forEach((heading) => {
				heading.hidden = true;
			});
		});

		const initial = pairs.find(({ tab }) => tab.getAttribute('aria-selected') === 'true') || pairs[0];

		strip.hidden = false;
		select(pairs, initial.tab);

		strip.addEventListener('click', (event) => {
			const tab = event.target.closest('[role="tab"]');

			if (tab && strip.contains(tab)) {
				select(pairs, tab);
			}
		});

		strip.addEventListener('keydown', (event) => {
			const keys = { ArrowRight: 1, ArrowLeft: -1, Home: 'first', End: 'last' };
			const step = keys[event.key];

			if (step === undefined) {
				return;
			}

			event.preventDefault();

			const current = pairs.findIndex(({ tab }) => tab.getAttribute('aria-selected') === 'true');
			let next = current;

			if (step === 'first') {
				next = 0;
			} else if (step === 'last') {
				next = pairs.length - 1;
			} else {
				next = (current + step + pairs.length) % pairs.length;
			}

			select(pairs, pairs[next].tab);
			pairs[next].tab.focus();
		});
	});
}
