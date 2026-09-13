/*
 * The community's promises, checked on the rendered page.
 *
 * /comunita/ says it in one sentence: «Pubblichiamo pseudonimo, ruolo e data di
 * iscrizione. Mai l'email, mai il paese, mai l'ultimo indirizzo usato per
 * entrare.» A promise nobody measures is a preference. These are the
 * measurements.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { get, page, element } from './helpers.js';

const profile = await (async () => {
	const index = await page('/wp-sitemap.xml');

	if (!/wp-sitemap-users/.test(index)) {
		return null;
	}

	const users = await page('/wp-sitemap-users-1.xml');
	const found = /<loc>([^<]+)<\/loc>/.exec(users);

	if (!found) {
		return null;
	}

	const path = new URL(found[1]).pathname;

	return { path, html: await page(path) };
})();

const skip = !profile && 'no member profile published';

test('a profile lives under /membri/, not /author/', { skip }, () => {
	assert.match(profile.path, /^\/membri\//, `the profile is at ${profile.path}`);
});

test('a profile never shows an email address', { skip }, () => {
	/*
	 * Not "does not show the email of this account" — the page has no field
	 * that could hold an address at all, so any address-shaped string on it is
	 * a leak whoever it belongs to.
	 */
	const addresses = profile.html.match(/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/g) || [];

	assert.deepEqual(addresses, [], `the profile page carries ${addresses.length} email address(es)`);
});

test('a profile never shows when the account was last seen', { skip }, () => {
	assert.doesNotMatch(profile.html, /ultimo accesso|last (sign-?in|login|seen)/i, 'the profile publishes a last access');
	assert.doesNotMatch(profile.html, /\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/, 'the profile publishes an address');
});

test('a profile shows the pseudonym and the role, which is what it is for', { skip }, () => {
	const identity = element(profile.html, /<div[^>]*class="identity"/);

	assert.ok(identity, 'no identity block');
	assert.match(identity, /<h1[^>]*>[\s\S]*?<\/h1>/, 'no pseudonym');
	assert.match(identity, /class="badge"/, 'no role badge');
});

test('the profile tabs work without JavaScript', { skip }, () => {
	/*
	 * The server renders every panel open and hides the strip that needs a
	 * script. A panel shipped with `hidden` would be unreachable to a reader
	 * whose script did not load.
	 */
	const panels = profile.html.match(/<div class="tabpanel"[^>]*>/g) || [];

	assert.ok(panels.length >= 2, 'no tab panels');
	panels.forEach((panel) => {
		assert.doesNotMatch(panel, /\bhidden\b/, 'a tab panel ships hidden');
	});
	assert.match(profile.html, /<div class="tabs"[^>]*\bhidden\b/, 'the tab strip ships visible but inert');
});

test('/author/ is not a second door to the same page', { skip }, async () => {
	const slug = profile.path.replace(/^\/membri\//, '').replace(/\/$/, '');
	const old = await get(`/author/${slug}/`);

	assert.notEqual(old.status, 200, '/author/<slug>/ still answers');
});
