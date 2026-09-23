/*
 * Keep --header-height true.
 *
 * The header has no height of its own: it is as tall as the wordmark, the
 * motto and the padding make it, which changes with the breakpoint and would
 * change again the day somebody adds a line to the masthead. Three things sit
 * below it and need that figure — the sticky side column, its maximum height,
 * and the scroll-margin of any heading reached from a link — and CSS cannot
 * measure an element, so the stylesheet has to state a number.
 *
 * A stated number is a copy, and a copy drifts: it said 92px while the header
 * measured 70, and the index highlighted the wrong entry for months. So the
 * stylesheet keeps a sensible default for a reader without JavaScript, and
 * this module replaces it with the measurement as soon as it can — and again
 * whenever the header changes size.
 */

export default function initHeaderHeight() {
	const header = document.querySelector('.topnav');

	if (!header) {
		return;
	}

	const publish = () => {
		const height = header.getBoundingClientRect().height;

		if (height > 0) {
			document.documentElement.style.setProperty('--header-height', `${Math.round(height * 10) / 10}px`);
		}
	};

	publish();

	if (typeof ResizeObserver === 'function') {
		new ResizeObserver(publish).observe(header);
		return;
	}

	window.addEventListener('resize', publish, { passive: true });
}
