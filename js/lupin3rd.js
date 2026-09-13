/* lupin3rd.org — compiled from src/js. Do not edit. */
(function () {
	'use strict';

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
	const apply$1 = (scheme) => {
		document.documentElement.setAttribute('data-theme', scheme);
		document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
			button.setAttribute('aria-pressed', String(scheme === 'light'));
		});
	};

	function initThemeSwitch() {
		const toggles = document.querySelectorAll('[data-theme-toggle]');

		if (!toggles.length) {
			return;
		}

		apply$1(stored() === 'light' ? 'light' : 'dark');

		toggles.forEach((button) => {
			button.addEventListener('click', () => {
				const next =
					document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
				apply$1(next);
				store(next);
			});
		});
	}

	/*
	 * The navigation on a narrow screen.
	 *
	 * Below the breakpoint the menu collapses behind a button. The markup is the
	 * same either way: only a class changes, so nothing is duplicated and nothing
	 * is hidden from a crawler.
	 */

	function initMobileNav() {
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

	/*
	 * Search overlay.
	 *
	 * Filters a short list of recent pieces as you type, for an instant answer.
	 * The form still submits to the real search page, which is what a reader
	 * without JavaScript gets and what every crawler sees.
	 */

	function initSearchOverlay() {
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

	function initTaxonomyFilters() {
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

	/*
	 * Copy a code block.
	 *
	 * The button is rendered by the code block itself and is absent when there is
	 * nothing to copy, so a page without code pays nothing for this.
	 */

	function initCodeCopy() {
		const buttons = document.querySelectorAll('[data-copy]');

		if (!buttons.length) {
			return;
		}

		buttons.forEach((button) => {
			button.addEventListener('click', async () => {
				// `code`, not `pre`: with line numbers on, the gutter is a sibling
				// of the pre and must not end up on the clipboard.
				const block = button.closest('.codeblock');
				const code = block ? block.querySelector('pre code') || block.querySelector('pre') : null;

				if (!code) {
					return;
				}

				const label = button.textContent;

				try {
					await navigator.clipboard.writeText(code.innerText);
					button.textContent = button.dataset.copiedLabel || 'copiato';
				} catch (error) {
					/*
					 * Clipboard access can be refused — a private window, a denied
					 * permission, an insecure origin. Say so rather than claim a
					 * copy that did not happen, and do the next best thing: select
					 * the code, so the reader's own copy shortcut works.
					 */
					button.textContent = button.dataset.failedLabel || 'seleziona e copia';

					const selection = window.getSelection();
					if (selection) {
						const range = document.createRange();
						range.selectNodeContents(code);
						selection.removeAllRanges();
						selection.addRange(range);
					}
				}

				setTimeout(() => {
					button.textContent = label;
				}, 1600);
			});
		});
	}

	/*
	 * Side index.
	 *
	 * Highlights the section being read and fills a progress bar. The index itself
	 * is server-rendered from the piece's own headings, so it is complete and
	 * linkable with or without this module.
	 */

	function initTableOfContents() {
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

	/*
	 * Client-side form checks.
	 *
	 * Nothing here is a security measure: the server validates everything again.
	 * This only saves a round trip and puts the error next to the field that
	 * caused it, which the browser's own bubble does not.
	 */

	function initFormValidation() {
		const forms = document.querySelectorAll('[data-validate]');

		if (!forms.length) {
			return;
		}

		forms.forEach((form) => {
			form.setAttribute('novalidate', 'novalidate');

			form.addEventListener('submit', (event) => {
				let firstInvalid = null;

				form.querySelectorAll('[required]').forEach((field) => {
					const valid = field.checkValidity() && String(field.value).trim() !== '';
					const error = form.querySelector(`[data-error-for="${field.name}"]`);

					field.setAttribute('aria-invalid', String(!valid));

					if (error) {
						error.classList.toggle('hidden', valid);
					}

					if (!valid && !firstInvalid) {
						firstInvalid = field;
					}
				});

				if (firstInvalid) {
					event.preventDefault();
					firstInvalid.focus();
				}
			});
		});
	}

	/*
	 * lupin3rd.org — the theme's only front-end bundle.
	 *
	 * One entry point, one compiled file. Every module below binds to data
	 * attributes and returns immediately when its hook is absent from the page, so
	 * the cost of shipping all of them everywhere is a handful of failed
	 * querySelector calls.
	 *
	 * Every feature here is an improvement on something that already works without
	 * it. Filters, search and the index are real links and a real form first.
	 */


	const start = () => {
		initThemeSwitch();
		initMobileNav();
		initSearchOverlay();
		initTaxonomyFilters();
		initCodeCopy();
		initTableOfContents();
		initFormValidation();
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start, { once: true });
	} else {
		start();
	}

})();
