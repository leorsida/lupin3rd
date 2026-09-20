/*
 * Closing an account.
 *
 * Nothing is overwritten when an account is closed, so every one of these
 * assertions is about a surface that would otherwise go on printing a real
 * pseudonym. That is the whole difference between this design and one that
 * scrubs the row: there, a forgotten place shows a blank; here it shows the
 * person who asked to disappear.
 *
 * What cannot be checked from outside is the act itself — closing one requires
 * a session and a password. These tests cover the doors instead: that the
 * handler refuses everything it should, and that nothing anonymous can reach
 * it. The rest is covered by the WP-CLI check in
 * docs/PIANO-CANCELLAZIONE-ACCOUNT.md §9.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { get, page, element } from './helpers.js';

const post = (path, fields) => {
	const body = new URLSearchParams(fields).toString();

	return get(path, {
		method: 'POST',
		body,
		headers: {
			'content-type': 'application/x-www-form-urlencoded',
			'content-length': String(Buffer.byteLength(body)),
		},
	});
};

test('the handler takes nothing from an anonymous caller', async () => {
	/*
	 * No `admin_post_nopriv_` twin is registered, so WordPress answers 400
	 * rather than running the handler. Asserting «not 302» would pass on an
	 * error page; asserting the status says which defence is holding.
	 */
	const response = await post('/wp-admin/admin-post.php', {
		action: 'l3_close_account',
		l3_current_password: 'whatever',
		l3_confirm_pseudonym: 'whoever',
	});

	assert.equal(response.status, 400, `the closing handler answered ${response.status}`);
});

test('/account/ sends a stranger to the front door, closing form included', async () => {
	const response = await get('/account/');

	assert.equal(response.status, 302, `/account/ answered ${response.status} to a stranger`);
	assert.match(response.headers.get('location') || '', /\/accesso\//);
});

test('the sign-in page can say an account was closed', async () => {
	/*
	 * The message a reader lands on after closing. It is read from the URL, so
	 * it can be checked without holding a session — and it has to exist,
	 * because the alternative is being signed out onto a page that explains
	 * nothing.
	 */
	const html = await page('/accesso/?l3_auth=closed');

	assert.match(html, /account rimosso|Account chiuso/i, 'closing leaves the reader without a word');
});

test('no closed account is named anywhere a reader can look', async () => {
	/*
	 * Cheap and blunt on purpose: if the exclusion in Retirement ever stops
	 * working, the pseudonym of a closed account turns up in one of these.
	 * With no closed account seeded this passes trivially, which is why the
	 * assertion is written against the role slug — that one appears in the
	 * markup only if something is printing a closed account's furniture.
	 */
	for (const path of ['/community/', '/']) {
		const html = await page(path);

		assert.doesNotMatch(html, /l3_closed/, `${path} names the closed role`);
	}
});

test('the community page still stands on its own', async () => {
	/*
	 * A guard against the exclusion being implemented as a filter that empties
	 * the page for everybody: the lists have to keep listing.
	 */
	const html = await page('/community/');
	const main = element(html, /<section[^>]*class="[^"]*section/);

	assert.ok(main, 'no section on /community/');
	assert.match(html, /\/membri\//, 'the community page lists nobody at all');
});
