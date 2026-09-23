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

		/*
		 * The sign-out link ships without its nonce, because a nonce is different
		 * for every reader and the header is in every cached page. This is where it
		 * gets one. The server verifies it against the session, so a reader who
		 * edits the cookie only breaks their own sign-out.
		 */
		if ('string' === typeof hint.out && /^[a-z0-9]+$/i.test(hint.out)) {
			document.querySelectorAll('[data-account-signout]').forEach((link) => {
				const url = new URL(link.href, window.location.origin);

				url.searchParams.set('_wpnonce', hint.out);
				link.setAttribute('href', url.pathname + url.search);
			});
		}

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

	function initMobileNav() {
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
	 * Closing an account: keep the button inert until the pseudonym matches.
	 *
	 * Comfort only. The server checks the same thing and refuses without it, so
	 * the form works exactly as well with this file missing — which is why the
	 * button ships enabled and is disabled here, rather than the other way round.
	 * A control that arrives disabled and waits for a script is a control that
	 * stays disabled when the script does not arrive.
	 */

	function initAccountClose() {
		const form = document.querySelector('[data-close-account]');

		if (!form) {
			return;
		}

		const field = form.querySelector('[data-close-confirm]');
		const button = form.querySelector('[data-close-submit]');

		if (!field || !button) {
			return;
		}

		const expected = (field.getAttribute('data-close-confirm') || '').trim().toLowerCase();

		const check = () => {
			button.disabled = field.value.trim().toLowerCase() !== expected;
		};

		field.addEventListener('input', check);
		check();
	}

	/*
	 * vanilla-cookieconsent 3.1.0 — https://github.com/orestbida/cookieconsent
	 * MIT, © 2020-2025 Orest Bida. Licence in cookieconsent.LICENSE.txt.
	 *
	 * Vendored rather than resolved from node_modules at build time, on purpose.
	 * This is the only code in the front-end bundle that this project did not
	 * write, and it belongs in a file that can be read, diffed and audited in the
	 * repository like everything else the reader's browser executes. Updating it
	 * means replacing this file and saying so in a commit.
	 */
	/*!
	* CookieConsent 3.1.0
	* https://github.com/orestbida/cookieconsent
	* Author Orest Bida
	* Released under the MIT License
	*/
	const e='opt-in',t='opt-out',o='show--consent',n='show--preferences',a='disable--interaction',s='data-category',c='div',r='button',i='aria-hidden',l='btn-group',d='click',f='data-role',_='consentModal',u='preferencesModal';class p{constructor(){this.t={mode:e,revision:0,autoShow:true,lazyHtmlGeneration:true,autoClearCookies:true,manageScriptTags:true,hideFromBots:true,cookie:{name:'cc_cookie',expiresAfterDays:182,domain:'',path:'/',secure:true,sameSite:'Lax'}},this.o={i:{},l:'',_:{},u:{},p:{},m:[],v:false,h:null,C:null,S:null,M:'',D:true,T:false,k:false,A:false,N:false,H:[],V:false,I:true,L:[],j:false,F:'',P:false,O:[],R:[],B:[],$:[],G:false,J:false,U:false,q:[],K:[],W:[],X:{},Y:{},Z:{},ee:{},te:{},oe:[]},this.ne={ae:{},se:{}},this.ce={},this.re={ie:'cc:onFirstConsent',le:'cc:onConsent',de:'cc:onChange',fe:'cc:onModalShow',_e:'cc:onModalHide',ue:'cc:onModalReady'};}}const g=new p,m=(e,t)=>e.indexOf(t),b=(e,t)=>-1!==m(e,t),v=e=>Array.isArray(e),y=e=>'string'==typeof e,h=e=>!!e&&'object'==typeof e&&!v(e),C=e=>'function'==typeof e,w=e=>Object.keys(e),S=e=>Array.from(new Set(e)),x=()=>document.activeElement,M=e=>e.preventDefault(),D=(e,t)=>e.querySelectorAll(t),k=e=>{const t=document.createElement(e);return e===r&&(t.type=e),t},E=(e,t,o)=>e.setAttribute(t,o),A=(e,t,o)=>{e.removeAttribute(o?'data-'+t:t);},N=(e,t,o)=>e.getAttribute(o?'data-'+t:t),H=(e,t)=>e.appendChild(t),V=(e,t)=>e.classList.add(t),I=(e,t)=>V(e,'cm__'+t),L=(e,t)=>V(e,'pm__'+t),j=(e,t)=>e.classList.remove(t),F=e=>{if('object'!=typeof e)return e;if(e instanceof Date)return new Date(e.getTime());let t=Array.isArray(e)?[]:{};for(let o in e){let n=e[o];t[o]=F(n);}return t},O=(e,t)=>dispatchEvent(new CustomEvent(e,{detail:t})),R=(e,t,o,n)=>{e.addEventListener(t,o),n&&g.o.m.push({pe:e,ge:t,me:o});},B=()=>{const e=g.t.cookie.expiresAfterDays;return C(e)?e(g.o.F):e},$=(e,t)=>{const o=e||[],n=t||[];return o.filter((e=>!b(n,e))).concat(n.filter((e=>!b(o,e))))},G=e=>{g.o.R=S(e),g.o.F=(()=>{let e='custom';const{R:t,O:o,B:n}=g.o,a=t.length;return a===o.length?e='all':a===n.length&&(e='necessary'),e})();},J=(e,t,o,n)=>{const a='accept-',{show:s,showPreferences:c,hide:r,hidePreferences:i,acceptCategory:l}=t,f=e||document,_=e=>D(f,`[data-cc="${e}"]`),u=(e,t)=>{M(e),l(t),i(),r();},p=_('show-preferencesModal'),m=_('show-consentModal'),b=_(a+'all'),v=_(a+'necessary'),y=_(a+'custom'),h=g.t.lazyHtmlGeneration;for(const e of p)E(e,'aria-haspopup','dialog'),R(e,d,(e=>{M(e),c();})),h&&(R(e,'mouseenter',(e=>{M(e),g.o.N||o(t,n);}),true),R(e,'focus',(()=>{g.o.N||o(t,n);})));for(let e of m)E(e,'aria-haspopup','dialog'),R(e,d,(e=>{M(e),s(true);}),true);for(let e of b)R(e,d,(e=>{u(e,'all');}),true);for(let e of y)R(e,d,(e=>{u(e);}),true);for(let e of v)R(e,d,(e=>{u(e,[]);}),true);},U=(e,t)=>{e&&(t&&(e.tabIndex=-1),e.focus(),t&&e.removeAttribute('tabindex'));},z=(e,t)=>{const o=n=>{n.target.removeEventListener('transitionend',o),'opacity'===n.propertyName&&'1'===getComputedStyle(e).opacity&&U((e=>1===e?g.ne.be:g.ne.ve)(t));};R(e,'transitionend',o);};let q;const K=e=>{clearTimeout(q),e?V(g.ne.ye,a):q=setTimeout((()=>{j(g.ne.ye,a);}),500);},Q=['M 19.5 4.5 L 4.5 19.5 M 4.5 4.501 L 19.5 19.5','M 3.572 13.406 L 8.281 18.115 L 20.428 5.885','M 21.999 6.94 L 11.639 17.18 L 2.001 6.82 '],W=(e=0,t=1.5)=>`<svg viewBox="0 0 24 24" stroke-width="${t}"><path d="${Q[e]}"/></svg>`,X=e=>{const t=g.ne,o=g.o;(e=>{const n=e===t.he,a=o.i.disablePageInteraction?t.ye:n?t.Ce:t.ye;R(a,'keydown',(t=>{if('Tab'!==t.key||!(n?o.k&&!o.A:o.A))return;const a=x(),s=n?o.q:o.K;0!==s.length&&(t.shiftKey?a!==s[0]&&e.contains(a)||(M(t),U(s[1])):a!==s[1]&&e.contains(a)||(M(t),U(s[0])));}),true);})(e);},Y=['[href]',r,'input','details','[tabindex]'].map((e=>e+':not([tabindex="-1"])')).join(','),Z=e=>{const{o:t,ne:o}=g,n=(e,t)=>{const o=D(e,Y);t[0]=o[0],t[1]=o[o.length-1];};1===e&&t.T&&n(o.he,t.q),2===e&&t.N&&n(o.we,t.K);},ee=(e,t,o)=>{const{de:n,le:a,ie:s,_e:c,ue:r,fe:i}=g.ce,l=g.re;if(t){const n={modalName:t};return e===l.fe?C(i)&&i(n):e===l._e?C(c)&&c(n):(n.modal=o,C(r)&&r(n)),O(e,n)}const d={cookie:g.o.p};e===l.ie?C(s)&&s(F(d)):e===l.le?C(a)&&a(F(d)):(d.changedCategories=g.o.L,d.changedServices=g.o.ee,C(n)&&n(F(d))),O(e,F(d));},te=(e,t)=>{try{return e()}catch(e){return !t&&console.warn('CookieConsent:',e),false}},oe=e=>{const{Y:t,ee:o,O:n,X:a,oe:c,p:r,L:i}=g.o;for(const e of n){const n=o[e]||t[e]||[];for(const o of n){const n=a[e][o];if(!n)continue;const{onAccept:s,onReject:c}=n;!n.Se&&b(t[e],o)?(n.Se=true,C(s)&&s()):n.Se&&!b(t[e],o)&&(n.Se=false,C(c)&&c());}}if(!g.t.manageScriptTags)return;const l=c,d=e||r.categories||[],f=(e,n)=>{if(n>=e.length)return;const a=c[n];if(a.xe)return f(e,n+1);const r=a.Me,l=a.De,_=a.Te,u=b(d,l),p=!!_&&b(t[l],_);if(!_&&!a.ke&&u||!_&&a.ke&&!u&&b(i,l)||_&&!a.ke&&p||_&&a.ke&&!p&&b(o[l]||[],_)){a.xe=true;const t=N(r,'type',true);A(r,'type',!!t),A(r,s);let o=N(r,'src',true);o&&A(r,'src',true);const c=k('script');c.textContent=r.innerHTML;for(const{nodeName:e}of r.attributes)E(c,e,r[e]||N(r,e));t&&(c.type=t),o?c.src=o:o=r.src;const i=!!o&&(!t||['text/javascript','module'].includes(t));if(i&&(c.onload=c.onerror=()=>{f(e,++n);}),r.replaceWith(c),i)return}f(e,++n);};f(l,0);},ne='bottom',ae='left',se='center',ce='right',re='inline',ie='wide',le='pm--',de=['middle','top',ne],fe=[ae,se,ce],_e={box:{Ee:[ie,re],Ae:de,Ne:fe,He:ne,Ve:ce},cloud:{Ee:[re],Ae:de,Ne:fe,He:ne,Ve:se},bar:{Ee:[re],Ae:de.slice(1),Ne:[],He:ne,Ve:''}},ue={box:{Ee:[],Ae:[],Ne:[],He:'',Ve:''},bar:{Ee:[ie],Ae:[],Ne:[ae,ce],He:'',Ve:ae}},pe=e=>{const t=g.o.i.guiOptions,o=t&&t.consentModal,n=t&&t.preferencesModal;0===e&&ge(g.ne.he,_e,o,'cm--','box','cm'),1===e&&ge(g.ne.we,ue,n,le,'box','pm');},ge=(e,t,o,n,a,s)=>{e.className=s;const c=o&&o.layout,r=o&&o.position,i=o&&o.flipButtons,l=!o||false!==o.equalWeightButtons,d=c&&c.split(' ')||[],f=d[0],_=d[1],u=f in t?f:a,p=t[u],m=b(p.Ee,_)&&_,v=r&&r.split(' ')||[],y=v[0],h=n===le?v[0]:v[1],C=b(p.Ae,y)?y:p.He,w=b(p.Ne,h)?h:p.Ve,S=t=>{t&&V(e,n+t);};S(u),S(m),S(C),S(w),i&&S('flip');const x=s+'__btn--secondary';if('cm'===s){const{Ie:e,Le:t}=g.ne;e&&(l?j(e,x):V(e,x)),t&&(l?j(t,x):V(t,x));}else {const{je:e}=g.ne;e&&(l?j(e,x):V(e,x));}},me=(e,t)=>{const o=g.o,n=g.ne,{hide:a,hidePreferences:s,acceptCategory:_}=e,p=e=>{_(e),s(),a();},m=o.u&&o.u.preferencesModal;if(!m)return;const b=m.title,v=m.closeIconLabel,C=m.acceptAllBtn,S=m.acceptNecessaryBtn,x=m.savePreferencesBtn,M=m.sections||[],D=C||S||x;if(n.Fe)n.Pe=k(c),L(n.Pe,'body');else {n.Fe=k(c),V(n.Fe,'pm-wrapper');const e=k('div');V(e,'pm-overlay'),H(n.Fe,e),R(e,d,s),n.we=k(c),V(n.we,'pm'),E(n.we,'role','dialog'),E(n.we,i,true),E(n.we,'aria-modal',true),E(n.we,'aria-labelledby','pm__title'),R(n.ye,'keydown',(e=>{27===e.keyCode&&s();}),true),n.Oe=k(c),L(n.Oe,'header'),n.Re=k('h2'),L(n.Re,'title'),n.Re.id='pm__title',n.Be=k(r),L(n.Be,'close-btn'),E(n.Be,'aria-label',m.closeIconLabel||''),R(n.Be,d,s),n.$e=k('span'),n.$e.innerHTML=W(),H(n.Be,n.$e),n.Ge=k(c),L(n.Ge,'body'),n.Je=k(c),L(n.Je,'footer');var T=k(c);V(T,'btns');var A=k(c),N=k(c);L(A,l),L(N,l),H(n.Je,A),H(n.Je,N),H(n.Oe,n.Re),H(n.Oe,n.Be),n.ve=k(c),E(n.ve,'tabIndex',-1),H(n.we,n.ve),H(n.we,n.Oe),H(n.we,n.Ge),D&&H(n.we,n.Je),H(n.Fe,n.we);}let I;b&&(n.Re.innerHTML=b,v&&E(n.Be,'aria-label',v)),M.forEach(((e,t)=>{const a=e.title,s=e.description,l=e.linkedCategory,f=l&&o.P[l],_=e.cookieTable,u=_&&_.body,p=_&&_.caption,g=u&&u.length>0,b=!!f,v=b&&o.X[l],C=h(v)&&w(v)||[],S=b&&(!!s||!!g||w(v).length>0);var x=k(c);if(L(x,'section'),S||s){var M=k(c);L(M,'section-desc-wrapper');}let D=C.length;if(S&&D>0){const e=k(c);L(e,'section-services');for(const t of C){const o=v[t],n=o&&o.label||t,a=k(c),s=k(c),r=k(c),i=k(c);L(a,'service'),L(i,'service-title'),L(s,'service-header'),L(r,'service-icon');const d=be(n,t,f,true,l);i.innerHTML=n,H(s,r),H(s,i),H(a,s),H(a,d),H(e,a);}H(M,e);}if(a){var T=k(c),A=k(b?r:c);if(L(T,'section-title-wrapper'),L(A,'section-title'),A.innerHTML=a,H(T,A),b){const e=k('span');e.innerHTML=W(2,3.5),L(e,'section-arrow'),H(T,e),x.className+='--toggle';const t=be(a,l,f);let o=m.serviceCounterLabel;if(D>0&&y(o)){let e=k('span');L(e,'badge'),L(e,'service-counter'),E(e,i,true),E(e,'data-servicecounter',D),o&&(o=o.split('|'),o=o.length>1&&D>1?o[1]:o[0],E(e,'data-counterlabel',o)),e.innerHTML=D+(o?' '+o:''),H(A,e);}if(S){L(x,'section--expandable');var N=l+'-desc';E(A,'aria-expanded',false),E(A,'aria-controls',N);}H(T,t);}else E(A,'role','heading'),E(A,'aria-level','3');H(x,T);}if(s){var F=k('p');L(F,'section-desc'),F.innerHTML=s,H(M,F);}if(S&&(E(M,i,'true'),M.id=N,((e,t,o)=>{R(A,d,(()=>{t.classList.contains('is-expanded')?(j(t,'is-expanded'),E(o,'aria-expanded','false'),E(e,i,'true')):(V(t,'is-expanded'),E(o,'aria-expanded','true'),E(e,i,'false'));}));})(M,x,A),g)){const e=k('table'),o=k('thead'),a=k('tbody');if(p){const t=k('caption');L(t,'table-caption'),t.innerHTML=p,e.appendChild(t);}L(e,'section-table'),L(o,'table-head'),L(a,'table-body');const s=_.headers,r=w(s),i=n.Ue.createDocumentFragment(),l=k('tr');for(const e of r){const o=s[e],n=k('th');n.id='cc__row-'+o+t,E(n,'scope','col'),L(n,'table-th'),n.innerHTML=o,H(i,n);}H(l,i),H(o,l);const d=n.Ue.createDocumentFragment();for(const e of u){const o=k('tr');L(o,'table-tr');for(const n of r){const a=s[n],r=e[n],i=k('td'),l=k(c);L(i,'table-td'),E(i,'data-column',a),E(i,'headers','cc__row-'+a+t),l.insertAdjacentHTML('beforeend',r),H(i,l),H(o,i);}H(d,o);}H(a,d),H(e,o),H(e,a),H(M,e);}(S||s)&&H(x,M);const P=n.Pe||n.Ge;b?(I||(I=k(c),L(I,'section-toggles')),I.appendChild(x)):I=null,H(P,I||x);})),C&&(n.ze||(n.ze=k(r),L(n.ze,'btn'),E(n.ze,f,'all'),H(A,n.ze),R(n.ze,d,(()=>p('all')))),n.ze.innerHTML=C),S&&(n.je||(n.je=k(r),L(n.je,'btn'),E(n.je,f,'necessary'),H(A,n.je),R(n.je,d,(()=>p([])))),n.je.innerHTML=S),x&&(n.qe||(n.qe=k(r),L(n.qe,'btn'),L(n.qe,'btn--secondary'),E(n.qe,f,'save'),H(N,n.qe),R(n.qe,d,(()=>p()))),n.qe.innerHTML=x),n.Pe&&(n.we.replaceChild(n.Pe,n.Ge),n.Ge=n.Pe),pe(1),o.N||(o.N=true,ee(g.re.ue,u,n.we),t(e),H(n.Ce,n.Fe),X(n.we),setTimeout((()=>V(n.Fe,'cc--anim')),100)),Z(2);};function be(e,t,o,n,a){const c=g.o,r=g.ne,l=k('label'),f=k('input'),_=k('span'),u=k('span'),p=k('span'),m=k('span'),v=k('span');if(m.innerHTML=W(1,3),v.innerHTML=W(0,3),f.type='checkbox',V(l,'section__toggle-wrapper'),V(f,'section__toggle'),V(m,'toggle__icon-on'),V(v,'toggle__icon-off'),V(_,'toggle__icon'),V(u,'toggle__icon-circle'),V(p,'toggle__label'),E(_,i,'true'),n?(V(l,'toggle-service'),E(f,s,a),r.se[a][t]=f):r.ae[t]=f,n?(e=>{R(f,'change',(()=>{const t=r.se[e],o=r.ae[e];c.Z[e]=[];for(let o in t){const n=t[o];n.checked&&c.Z[e].push(n.value);}o.checked=c.Z[e].length>0;}));})(a):(e=>{R(f,d,(()=>{const t=r.se[e],o=f.checked;c.Z[e]=[];for(let n in t)t[n].checked=o,o&&c.Z[e].push(n);}));})(t),f.value=t,p.textContent=e.replace(/<.*>.*<\/.*>/gm,''),H(u,v),H(u,m),H(_,u),c.D)(o.readOnly||o.enabled)&&(f.checked=true);else if(n){const e=c.Y[a];f.checked=o.readOnly||b(e,t);}else b(c.R,t)&&(f.checked=true);return o.readOnly&&(f.disabled=true),H(l,f),H(l,_),H(l,p),l}const ve=()=>{const e=k('span');return g.ne.Ke||(g.ne.Ke=e),e},ye=(e,t)=>{const o=g.o,n=g.ne,{hide:a,showPreferences:s,acceptCategory:u}=e,p=o.u&&o.u.consentModal;if(!p)return;const m=p.acceptAllBtn,b=p.acceptNecessaryBtn,v=p.showPreferencesBtn,y=p.closeIconLabel,h=p.footer,C=p.label,w=p.title,S=e=>{a(),u(e);};if(!n.Qe){n.Qe=k(c),n.he=k(c),n.We=k(c),n.Xe=k(c),n.Ye=k(c),V(n.Qe,'cm-wrapper'),V(n.he,'cm'),I(n.We,'body'),I(n.Xe,'texts'),I(n.Ye,'btns'),E(n.he,'role','dialog'),E(n.he,'aria-modal','true'),E(n.he,i,'false'),E(n.he,'aria-describedby','cm__desc'),C?E(n.he,'aria-label',C):w&&E(n.he,'aria-labelledby','cm__title');const e='box',t=o.i.guiOptions,a=t&&t.consentModal,s=(a&&a.layout||e).split(' ')[0]===e;w&&y&&s&&(n.Le||(n.Le=k(r),n.Le.innerHTML=W(),I(n.Le,'btn'),I(n.Le,'btn--close'),R(n.Le,d,(()=>{S([]);})),H(n.We,n.Le)),E(n.Le,'aria-label',y)),H(n.We,n.Xe),(m||b||v)&&H(n.We,n.Ye),n.be=k(c),E(n.be,'tabIndex',-1),H(n.he,n.be),H(n.he,n.We),H(n.Qe,n.he);}w&&(n.Ze||(n.Ze=k('h2'),n.Ze.className=n.Ze.id='cm__title',H(n.Xe,n.Ze)),n.Ze.innerHTML=w);let x=p.description;if(x&&(o.V&&(x=x.replace('{{revisionMessage}}',o.I?'':p.revisionMessage||'')),n.et||(n.et=k('p'),n.et.className=n.et.id='cm__desc',H(n.Xe,n.et)),n.et.innerHTML=x),m&&(n.tt||(n.tt=k(r),H(n.tt,ve()),I(n.tt,'btn'),E(n.tt,f,'all'),R(n.tt,d,(()=>{S('all');}))),n.tt.firstElementChild.innerHTML=m),b&&(n.Ie||(n.Ie=k(r),H(n.Ie,ve()),I(n.Ie,'btn'),E(n.Ie,f,'necessary'),R(n.Ie,d,(()=>{S([]);}))),n.Ie.firstElementChild.innerHTML=b),v&&(n.ot||(n.ot=k(r),H(n.ot,ve()),I(n.ot,'btn'),I(n.ot,'btn--secondary'),E(n.ot,f,'show'),R(n.ot,'mouseenter',(()=>{o.N||me(e,t);})),R(n.ot,d,s)),n.ot.firstElementChild.innerHTML=v),n.nt||(n.nt=k(c),I(n.nt,l),m&&H(n.nt,n.tt),b&&H(n.nt,n.Ie),(m||b)&&H(n.We,n.nt),H(n.Ye,n.nt)),n.ot&&!n.st&&(n.st=k(c),n.Ie&&n.tt?(I(n.st,l),H(n.st,n.ot),H(n.Ye,n.st)):(H(n.nt,n.ot),I(n.nt,l+'--uneven'))),h){if(!n.ct){let e=k(c),t=k(c);n.ct=k(c),I(e,'footer'),I(t,'links'),I(n.ct,'link-group'),H(t,n.ct),H(e,t),H(n.he,e);}n.ct.innerHTML=h;}pe(0),o.T||(o.T=true,ee(g.re.ue,_,n.he),t(e),H(n.Ce,n.Qe),X(n.he),setTimeout((()=>V(n.Qe,'cc--anim')),100)),Z(1),J(n.We,e,me,t);},he=e=>{if(!y(e))return null;if(e in g.o._)return e;let t=e.slice(0,2);return t in g.o._?t:null},Ce=()=>g.o.l||g.o.i.language.default,we=e=>{e&&(g.o.l=e);},Se=async e=>{const t=g.o;let o=he(e)?e:Ce(),n=t._[o];if(y(n)?n=await(async e=>{try{const t=await fetch(e);return await t.json()}catch(e){return console.error(e),false}})(n):C(n)&&(n=await n()),!n)throw `Could not load translation for the '${o}' language`;return t.u=n,we(o),true},xe=()=>{let e=g.o.i.language.rtl,t=g.ne.Ce;e&&t&&(v(e)||(e=[e]),b(e,g.o.l)?V(t,'cc--rtl'):j(t,'cc--rtl'));},Me=()=>{const e=g.ne;if(e.Ce)return;e.Ce=k(c),e.Ce.id='cc-main',e.Ce.setAttribute('data-nosnippet',''),xe();let t=g.o.i.root;t&&y(t)&&(t=document.querySelector(t)),(t||e.Ue.body).appendChild(e.Ce);},De=e=>te((()=>localStorage.removeItem(e))),Te=(e,t)=>{if(t instanceof RegExp)return e.filter((e=>t.test(e)));{const o=m(e,t);return o>-1?[e[o]]:[]}},ke=e=>{const{hostname:t,protocol:o}=location,{name:n,path:a,domain:s,sameSite:c,useLocalStorage:r,secure:i}=g.t.cookie,l=864e5*B(),d=new Date;d.setTime(d.getTime()+l),g.o.p.expirationTime=d.getTime();const f=JSON.stringify(g.o.p);let _=n+'='+encodeURIComponent(f)+(0!==l?'; expires='+d.toUTCString():'')+'; Path='+a+'; SameSite='+c;b(t,'.')&&(_+='; Domain='+s),i&&'https:'===o&&(_+='; Secure'),r?((e,t)=>{te((()=>localStorage.setItem(e,t)));})(n,f):document.cookie=_,g.o.p;},Ee=(e,t,o)=>{if(0===e.length)return;const n=o||g.t.cookie.domain,a=t||g.t.cookie.path,s='www.'===n.slice(0,4),c=s&&n.substring(4),r=(e,t)=>{t&&'.'!==t.slice(0,1)&&(t='.'+t),document.cookie=e+'=; path='+a+(t?'; domain='+t:'')+'; expires=Thu, 01 Jan 1970 00:00:01 GMT;';};for(const t of e)r(t,o),o||r(t,n),s&&r(t,c);},Ae=e=>{const t=g.t.cookie.name,o=g.t.cookie.useLocalStorage;return ((e,t)=>{let o;return o=te((()=>JSON.parse(t?e:decodeURIComponent(e))),true)||{},o})(o?(n=t,te((()=>localStorage.getItem(n)))||''):Ne(t),o);var n;},Ne=(e,t)=>{const o=document.cookie.match('(^|;)\\s*'+e+'\\s*=\\s*([^;]+)');return o?o.pop():''},He=e=>{const t=document.cookie.split(/;\s*/),o=[];for(const n of t){let t=n.split('=')[0];o.push(t);}return o},Ve=(o,n=[])=>{((e,t)=>{const{O:o,R:n,B:a,N:s,Z:c,$:r,X:i}=g.o;let l=[];if(e){v(e)?l.push(...e):y(e)&&(l='all'===e?o:[e]);for(const e of o)c[e]=b(l,e)?w(i[e]):[];}else l=[...n,...r],s&&(l=(()=>{const e=g.ne.ae;if(!e)return [];let t=[];for(let o in e)e[o].checked&&t.push(o);return t})());l=l.filter((e=>!b(o,e)||!b(t,e))),l.push(...a),G(l);})(o,n),(()=>{const e=g.o,{Z:t,B:o,Y:n,X:a,O:s}=e,c=s;e.te=F(n);for(const s of c){const c=a[s],r=w(c),i=t[s]&&t[s].length>0,l=b(o,s);if(0!==r.length){if(n[s]=[],l)n[s].push(...r);else if(i){const e=t[s];n[s].push(...e);}else n[s]=e.Z[s];n[s]=S(n[s]);}}})(),(()=>{const o=g.o;o.L=g.t.mode===t&&o.D?$(o.$,o.R):$(o.R,o.p.categories);let n=o.L.length>0,a=false;for(const e of o.O)o.ee[e]=$(o.Y[e],o.te[e]),o.ee[e].length>0&&(a=true);const s=g.ne.ae;for(const e in s)s[e].checked=b(o.R,e);for(const e of o.O){const t=g.ne.se[e],n=o.Y[e];for(const e in t)t[e].checked=b(n,e);}o.C||(o.C=new Date),o.M||(o.M=([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g,(e=>(e^crypto.getRandomValues(new Uint8Array(1))[0]&15>>e/4).toString(16)))),o.p={categories:F(o.R),revision:g.t.revision,data:o.h,consentTimestamp:o.C.toISOString(),consentId:o.M,services:F(o.Y),languageCode:g.o.l},o.S&&(o.p.lastConsentTimestamp=o.S.toISOString());let c=false;const r=n||a;(o.D||r)&&(o.D&&(o.D=false,c=true),o.S=o.S?new Date:o.C,o.p.lastConsentTimestamp=o.S.toISOString(),ke(),g.t.autoClearCookies&&(c||r)&&(e=>{const t=g.o,o=He(),n=(e=>{const t=g.o;return (e?t.O:t.L).filter((e=>{const o=t.P[e];return !!o&&!o.readOnly&&!!o.autoClear}))})(e);for(const e in t.ee)for(const n of t.ee[e]){const a=t.X[e][n].cookies;if(!b(t.Y[e],n)&&a)for(const e of a){const t=Te(o,e.name);Ee(t,e.path,e.domain);}}for(const a of n){const n=t.P[a].autoClear,s=n&&n.cookies||[],c=b(t.L,a),r=!b(t.R,a),i=c&&r;if(e?r:i){n.reloadPage&&i&&(t.j=true);for(const e of s){const t=Te(o,e.name);Ee(t,e.path,e.domain);}}}})(c),oe()),c&&(ee(g.re.ie),ee(g.re.le),g.t.mode===e)||(r&&ee(g.re.de),o.j&&(o.j=false,location.reload()));})();},Ie=e=>{const t=g.o.D?[]:g.o.R;return b(t,e)},je=(e,t)=>{const o=g.o.D?[]:g.o.Y[t]||[];return b(o,e)},Oe=e=>{const{ne:t,o:n}=g;if(!n.k){if(!n.T){if(!e)return;ye(Ge,Me);}n.k=true,n.J=x(),n.v&&K(true),z(t.he,1),V(t.ye,o),E(t.he,i,'false'),setTimeout((()=>{U(g.ne.be);}),100),ee(g.re.fe,_);}},Re=()=>{const{ne:e,o:t,re:n}=g;t.k&&(t.k=false,t.v&&K(),U(e.Ke,true),j(e.ye,o),E(e.he,i,'true'),U(t.J),t.J=null,ee(n._e,_));},Be=()=>{const e=g.o;e.A||(e.N||me(Ge,Me),e.A=true,e.k?e.U=x():e.J=x(),z(g.ne.we,2),V(g.ne.ye,n),E(g.ne.we,i,'false'),setTimeout((()=>{U(g.ne.ve);}),100),ee(g.re.fe,u));},$e=()=>{const e=g.o;e.A&&(e.A=false,(()=>{const e=We(),t=g.o.P,o=g.ne.ae,n=g.ne.se,a=e=>b(g.o.$,e);for(const s in o){const c=!!t[s].readOnly;o[s].checked=c||(e?Ie(s):a(s));for(const t in n[s])n[s][t].checked=c||(e?je(t,s):a(s));}})(),U(g.ne.$e,true),j(g.ne.ye,n),E(g.ne.we,i,'true'),e.k?(U(e.U),e.U=null):(U(e.J),e.J=null),ee(g.re._e,u));};var Ge={show:Oe,hide:Re,showPreferences:Be,hidePreferences:$e,acceptCategory:Ve};const We=()=>!g.o.D,Xe=async e=>{const{o:o,t:n,re:a}=g,c=window;if(!c._ccRun){if(c._ccRun=true,(e=>{const{ne:o,t:n,o:a}=g,c=n,r=a,{cookie:i}=c,l=g.ce,d=e.cookie,f=e.categories,_=w(f)||[],u=navigator,p=document;o.Ue=p,o.ye=p.documentElement,i.domain=location.hostname,r.i=e,r.P=f,r.O=_,r._=e.language.translations,r.v=!!e.disablePageInteraction,l.ie=e.onFirstConsent,l.le=e.onConsent,l.de=e.onChange,l._e=e.onModalHide,l.fe=e.onModalShow,l.ue=e.onModalReady;const{mode:m,autoShow:v,lazyHtmlGeneration:y,autoClearCookies:C,revision:S,manageScriptTags:x,hideFromBots:M}=e;m===t&&(c.mode=m),'boolean'==typeof C&&(c.autoClearCookies=C),'boolean'==typeof x&&(c.manageScriptTags=x),'number'==typeof S&&S>=0&&(c.revision=S,r.V=true),'boolean'==typeof v&&(c.autoShow=v),'boolean'==typeof y&&(c.lazyHtmlGeneration=y),false===M&&(c.hideFromBots=false),true===c.hideFromBots&&u&&(r.G=u.userAgent&&/bot|crawl|spider|slurp|teoma/i.test(u.userAgent)||u.webdriver),h(d)&&(c.cookie={...i,...d}),c.autoClearCookies,r.V,c.manageScriptTags,(e=>{const{P:t,X:o,Y:n,Z:a,B:s}=g.o;for(let c of e){const e=t[c],r=e.services||{},i=h(r)&&w(r)||[];o[c]={},n[c]=[],a[c]=[],e.readOnly&&(s.push(c),n[c]=i),g.ne.se[c]={};for(let e of i){const t=r[e];t.Se=false,o[c][e]=t;}}})(_),(()=>{if(!g.t.manageScriptTags)return;const e=g.o,t=D(document,'script['+s+']');for(const o of t){let t=N(o,s),n=o.dataset.service||'',a=false;if(t&&'!'===t.charAt(0)&&(t=t.slice(1),a=true),'!'===n.charAt(0)&&(n=n.slice(1),a=true),b(e.O,t)&&(e.oe.push({Me:o,xe:false,ke:a,De:t,Te:n}),n)){const o=e.X[t];o[n]||(o[n]={Se:false});}}})(),we((()=>{const e=g.o.i.language.autoDetect;if(e){const t={browser:navigator.language,document:document.documentElement.lang},o=he(t[e]);if(o)return o}return Ce()})());})(e),o.G)return;(()=>{const e=g.o,o=g.t,n=Ae(),{categories:a,services:s,consentId:c,consentTimestamp:r,lastConsentTimestamp:i,data:l,revision:d}=n,f=v(a);e.p=n,e.M=c;const _=!!c&&y(c);e.C=r,e.C&&(e.C=new Date(r)),e.S=i,e.S&&(e.S=new Date(i)),e.h=void 0!==l?l:null,e.V&&_&&d!==o.revision&&(e.I=false),e.D=!(_&&e.I&&e.C&&e.S&&f),o.cookie.useLocalStorage&&!e.D&&(e.D=(new Date).getTime()>(n.expirationTime||0),e.D&&De(o.cookie.name)),e.D,(()=>{const e=g.o;for(const o of e.O){const n=e.P[o];if(n.readOnly||n.enabled){e.$.push(o);const n=e.X[o]||{};for(let a in n)e.Z[o].push(a),e.i.mode===t&&e.Y[o].push(a);}}})(),e.D?o.mode===t&&(e.R=[...e.$]):(e.Y={...e.Y,...s},e.Z={...e.Y},G([...e.B,...a]));})();const i=We();if(!await Se())return  false;if(J(null,r=Ge,me,Me),g.o.D&&ye(r,Me),g.t.lazyHtmlGeneration||me(r,Me),n.autoShow&&!i&&Oe(true),i)return oe(),ee(a.le);n.mode===t&&oe(o.$);}var r;};

	/*
	 * The cookie notice, and the frames it keeps out.
	 *
	 * The notice asks nothing, because this site stores nothing optional:
	 * everything in its table is required for the page to work or is the result of
	 * something the reader did — signing in, ticking «ricorda», switching the
	 * theme. So it states, and takes an acknowledgement. The panel behind «Vedi
	 * quali» has one row that cannot be switched off, and exists for the table
	 * inside it.
	 *
	 * Third-party frames are not a category. PHP has already replaced each one
	 * with a placeholder before this file runs; this module only restores the one
	 * a reader asks for, and remembers nothing about it.
	 */


	/** Put one gated embed back, from the markup PHP parked in the attribute. */
	const reveal = (gate) => {
		const encoded = gate.getAttribute('data-embed');

		if (!encoded) {
			return;
		}

		const frame = document.createElement('div');

		/*
		 * The markup came from the server, where it was already the post's own
		 * rendered content — it is being restored, not introduced. The attribute is
		 * base64 only so that quotes survive the round trip.
		 */
		frame.innerHTML = atob(encoded);

		const iframe = frame.querySelector('iframe');

		if (iframe) {
			gate.replaceWith(iframe);
		}
	};

	const readConfig = () => {
		const block = document.getElementById('l3-consent-config');

		if (!block) {
			return null;
		}

		try {
			return JSON.parse(block.textContent || '');
		} catch (error) {
			/* Wording we cannot read is a notice we should not show half of. */
			return null;
		}
	};

	function initConsent() {
		const config = readConfig();

		if (!config) {
			return;
		}

		/* One embed, shown because this reader asked for this one. Nothing is
		 * remembered: the next page starts from the same question. */
		document.addEventListener('click', (event) => {
			const button = event.target.closest('[data-embed-show]');

			if (button) {
				const gate = button.closest('[data-embed-gate]');

				if (gate) {
					reveal(gate);
				}
			}
		});

		Xe({
			autoShow: true,
			disablePageInteraction: false,
			cookie: { name: 'l3_consent', expiresAfterDays: 180, sameSite: 'Lax' },
			guiOptions: {
				/* Full width along the bottom: a statement belongs across the page,
				 * not in a corner where it reads as something to dismiss. `inline`
				 * puts the two buttons beside the text instead of under it. */
				consentModal: { layout: 'bar inline', position: 'bottom', equalWeightButtons: true, flipButtons: false },
				preferencesModal: { layout: 'box', equalWeightButtons: true, flipButtons: false },
			},
			categories: {
				necessary: { enabled: true, readOnly: true },
			},
			language: {
				default: config.locale || 'it',
				translations: { [config.locale || 'it']: config.notice },
			},
		});

		/*
		 * Anything marked as reopening the notice does so. `true` forces it even
		 * once an answer has been given, which is the whole point of the control.
		 *
		 * A button ships `hidden` and is revealed here, because without this module
		 * it would do nothing; the footer's is a real link to /privacy/, so it
		 * ships visible and is only intercepted.
		 */
		document.querySelectorAll('[data-consent-open]').forEach((control) => {
			control.hidden = false;

			control.addEventListener('click', (event) => {
				event.preventDefault();
				Oe(true);
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
		initAccountClose();
		initConsent();
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start, { once: true });
	} else {
		start();
	}

})();
