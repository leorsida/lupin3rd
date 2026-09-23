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
	 * The line a heading has to cross to count as the one being read: the
	 * bottom edge of whatever is pinned above the page. The header is sticky
	 * below the admin bar, so its own bottom already includes it — measuring
	 * it beats repeating here a number that CSS owns.
	 *
	 * It was repeated, as a flat 120. For a signed-out reader that was close
	 * enough to the header's 92px; for a signed-in one the admin bar pushed
	 * the real figure to 124, four pixels past the constant, and clicking an
	 * entry in the index highlighted the entry above it. A bug nobody could
	 * see unless they were logged in, which is to say: only us.
	 */
	const readingLine = () => {
		const header = document.querySelector('.topnav');

		return (header ? header.getBoundingClientRect().bottom : 0) + 4;
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
