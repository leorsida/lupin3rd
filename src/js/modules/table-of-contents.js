/*
 * Side index.
 *
 * Highlights the section being read and fills a progress bar. The index itself
 * is server-rendered from the piece's own headings, so it is complete and
 * linkable with or without this module.
 */

export default function initTableOfContents() {
	const toc = document.querySelector('[data-toc]');

	if (!toc) {
		return;
	}

	const links = Array.from(toc.querySelectorAll('a[href^="#"]'));
	const targets = links.map((link) => document.getElementById(link.getAttribute('href').slice(1)));
	const progress = toc.querySelector('[data-progress]');

	if (!links.length) {
		return;
	}

	let ticking = false;

	/*
	 * The line a heading has to cross to count as the one being read.
	 *
	 * It is not the height of the header, and not a constant: it is the exact
	 * distance at which the browser parks a heading reached from this index,
	 * which the stylesheet states as `scroll-margin-top` on the heading
	 * itself. Reading it from the target is the only way the two cannot drift
	 * apart, and they had: the module compared against a flat 120, the CSS
	 * parks at `calc(var(--admin-bar) + var(--header-height))` — 92px, 124px
	 * with the admin bar — and the real header is neither, because its height
	 * comes from its content and measures about 70. Whichever pair of numbers
	 * you picked, clicking an entry highlighted the one above it.
	 *
	 * Every target carries the same rule, so the first one answers for all.
	 */
	const readingLine = () => {
		const first = targets.find(Boolean);
		const parked = first ? parseFloat(window.getComputedStyle(first).scrollMarginTop) : 0;

		return (Number.isFinite(parked) ? parked : 0) + 4;
	};

	const update = () => {
		ticking = false;
		const limit = readingLine();
		let current = 0;

		targets.forEach((target, index) => {
			if (target && target.getBoundingClientRect().top <= limit) {
				current = index;
			}
		});

		/*
		 * At the foot of the document the last headings may never reach that
		 * line — there is nothing left to scroll. Whoever is down there is
		 * reading the last section, whatever the arithmetic says.
		 */
		if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 2) {
			current = links.length - 1;
		}

		links.forEach((link, index) => {
			link.classList.toggle('is-active', index === current);
		});

		if (progress) {
			const height = document.body.scrollHeight - window.innerHeight;
			const ratio = height > 0 ? Math.min(1, Math.max(0, window.scrollY / height)) : 0;
			progress.style.width = `${(ratio * 100).toFixed(1)}%`;
		}
	};

	window.addEventListener(
		'scroll',
		() => {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(update);
			}
		},
		{ passive: true }
	);

	update();
}
