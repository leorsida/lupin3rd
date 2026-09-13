/* lupin3rd.org — compiled from src/js. Do not edit. */
(function () {
	'use strict';

	/*
	 * The header, corrected.
	 *
	 * Every page ships its header in the signed-out state, because every page is
	 * the same page for everybody — that is what makes it cacheable (D-017). This
	 * reads the small display cookie PHP left beside it and swaps the two states.
	 *
	 * The cookie is not a credential. The reader can edit it, and editing it
	 * changes what their own header says and nothing else. It is therefore written
	 * into the page with textContent and never with innerHTML: reach for innerHTML
	 * here and you hand the reader a cross-site scripting hole aimed at themselves.
	 */

	const HINT = 'l3_hint';

	const readHint = () => {
		const found = document.cookie
			.split('; ')
			.find((pair) => pair.startsWith(`${HINT}=`));

		if (!found) {
			return null;
		}

		try {
			/*
			 * PHP's setcookie() encodes the value with urlencode(), which writes a
			 * space as `+`. decodeURIComponent does not know that convention, so
			 * the plus is put back first. No field carries a space today; this is
			 * here so that the day one does, it does not fail silently.
			 */
			const raw = found.slice(HINT.length + 1).replace(/\+/g, '%20');
			const value = JSON.parse(decodeURIComponent(raw));

			return value && typeof value.name === 'string' ? value : null;
		} catch (error) {
			/* A cookie the reader mangled is a cookie we do not have. */
			return null;
		}
	};

	const show = (element, on) => {
		element.classList.toggle('hidden', !on);
	};

	function initSession() {
		const hint = readHint();

		if (!hint) {
			// The page is already correct: it was rendered for a stranger.
			return;
		}

		document.querySelectorAll('[data-when-signed-out]').forEach((element) => show(element, false));
		document.querySelectorAll('[data-when-signed-in]').forEach((element) => show(element, true));

		document.querySelectorAll('[data-account-name]').forEach((element) => {
			element.textContent = hint.name;
		});

		document.querySelectorAll('[data-account-public]').forEach((link) => {
			if (typeof hint.profile === 'string' && hint.profile.startsWith('/')) {
				link.setAttribute('href', hint.profile);
			}
		});

		const chip = document.querySelector('[data-account-chip]');
		const panel = document.querySelector('[data-account-panel]');

		if (!chip || !panel) {
			return;
		}

		const avatar = chip.querySelector('.avatar');

		if (avatar) {
			chip.setAttribute('aria-label', hint.name);
			chip.setAttribute('title', hint.name);

			const mark = avatar.querySelector('b');

			if (mark) {
				mark.textContent = typeof hint.mark === 'string' ? hint.mark.slice(0, 2) : '';
			}

			/*
			 * The pattern is one of five names the stylesheet defines. Anything
			 * else is somebody editing their own cookie, and gets no class at all
			 * rather than an attribute built from their string.
			 */
			const patterns = ['scan', 'bars', 'grid', 'dots', 'diag'];

			if (patterns.includes(hint.pattern)) {
				avatar.classList.add(`av--${hint.pattern}`);
			}
		}

		const close = () => {
			show(panel, false);
			chip.setAttribute('aria-expanded', 'false');
		};

		chip.addEventListener('click', (event) => {
			event.stopPropagation();

			const open = 'true' === chip.getAttribute('aria-expanded');

			show(panel, !open);
			chip.setAttribute('aria-expanded', String(!open));
		});

		document.addEventListener('click', (event) => {
			if (!panel.contains(event.target)) {
				close();
			}
		});

		document.addEventListener('keydown', (event) => {
			if ('Escape' === event.key) {
				close();
			}
		});
	}

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

	function initTabs() {
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

	/*
	 * Password strength.
	 *
	 * Four bars and one line of advice. It is an opinion, not a gate: the server
	 * enforces one rule, a minimum length, and this never blocks a submission.
	 *
	 * The opinion is deliberately about length first. A twelve-character
	 * passphrase of ordinary words beats a mangled eight-character word, and a
	 * meter that rewards punctuation over length teaches the opposite.
	 */

	const score = (value) => {
		if (!value) {
			return 0;
		}

		const length = value.length;
		const variety =
			(/[a-z]/.test(value) ? 1 : 0) +
			(/[A-Z]/.test(value) ? 1 : 0) +
			(/[0-9]/.test(value) ? 1 : 0) +
			(/[^A-Za-z0-9]/.test(value) ? 1 : 0);

		if (length < 10) {
			return 1;
		}

		if (length >= 20) {
			return 4;
		}

		if (length >= 16) {
			return variety > 1 ? 4 : 3;
		}

		return variety > 2 ? 3 : 2;
	};

	function initPasswordStrength() {
		const fields = document.querySelectorAll('[data-password-strength]');

		if (!fields.length) {
			return;
		}

		fields.forEach((field) => {
			const meter = document.querySelector(field.dataset.passwordStrength);
			const echo = field.closest('.field')?.querySelector('[data-password-strength-echo]');

			if (!meter) {
				return;
			}

			const bars = Array.from(meter.querySelectorAll('i'));

			field.addEventListener('input', () => {
				const level = score(field.value);

				bars.forEach((bar, index) => {
					bar.classList.toggle('on', index < level);
				});

				if (echo) {
					const said = echo.dataset[`level${level}`];

					if (said) {
						echo.textContent = said;
					}
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
		/* First: it corrects what the server could not know. */
		initSession();
		initThemeSwitch();
		initMobileNav();
		initSearchOverlay();
		initTaxonomyFilters();
		initCodeCopy();
		initTableOfContents();
		initFormValidation();
		initTabs();
		initPasswordStrength();
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start, { once: true });
	} else {
		start();
	}

})();
