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

import initSession from './modules/session.js';
import initHeaderHeight from './modules/header-height.js';
import initThemeSwitch from './modules/theme-switch.js';
import initMobileNav from './modules/mobile-nav.js';
import initSearchOverlay from './modules/search-overlay.js';
import initTaxonomyFilters from './modules/taxonomy-filters.js';
import initCodeCopy from './modules/code-copy.js';
import initMarkdownCopy from './modules/markdown-copy.js';
import initTableOfContents from './modules/table-of-contents.js';
import initFormValidation from './modules/form-validation.js';
import initTabs from './modules/tabs.js';
import initPasswordStrength from './modules/password-strength.js';
import initAccountClose from './modules/account-close.js';
import initConsent from './modules/consent.js';

const start = () => {
	/* First: it corrects what the server could not know. */
	initSession();
	/* Before anything that sticks or scrolls to a position reads the token. */
	initHeaderHeight();
	initThemeSwitch();
	initMobileNav();
	initSearchOverlay();
	initTaxonomyFilters();
	initCodeCopy();
	initMarkdownCopy();
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
