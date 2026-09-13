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

	const update = () => {
		ticking = false;
		const y = window.scrollY + 120;
		let current = 0;

		targets.forEach((target, index) => {
			if (target && target.offsetTop <= y) {
				current = index;
			}
		});

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
