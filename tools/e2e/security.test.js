/*
 * The promises in the colophon, checked rather than believed.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { get, page } from './helpers.js';

test('the REST API does not hand out the list of people', async () => {
	const users = await get('/wp-json/wp/v2/users');

	assert.equal(users.status, 401, `/wp/v2/users answered ${users.status}`);
});

test('?author=1 does not reveal a pseudonym', async () => {
	const probe = await get('/?author=1');

	assert.notEqual(probe.status, 301, 'author enumeration still redirects to the author archive');
	assert.notEqual(probe.status, 302, 'author enumeration still redirects to the author archive');
});

test('XML-RPC is shut', async () => {
	const xmlrpc = await get('/xmlrpc.php', { method: 'POST', body: '<methodCall></methodCall>' });

	assert.ok([403, 404, 405].includes(xmlrpc.status), `xmlrpc.php answered ${xmlrpc.status}`);
});

test('the version of WordPress is not advertised', async () => {
	const html = await page('/');

	assert.doesNotMatch(html, /<meta name="generator"/, 'the generator tag is back');
});

test('the security headers are set', async () => {
	const home = await get('/');

	assert.equal(home.headers.get('x-content-type-options'), 'nosniff');
	assert.ok(home.headers.get('referrer-policy'), 'no referrer policy');
});

test('files that should never be served are not', async () => {
	for (const path of ['/wp-config.php', '/readme.html', '/license.txt']) {
		const response = await get(path);

		assert.ok(response.status >= 400, `${path} answered ${response.status}`);
	}
});

test('the confirmation link cannot be guessed into a subscription', async () => {
	const response = await get('/?l3_confirm=obviously-not-a-token');

	assert.ok([301, 302].includes(response.status), `a bogus token answered ${response.status}`);
	assert.match(String(response.headers.get('location')), /l3_newsletter=error/, 'a bogus token was not refused');
});

test('reporting a comment without a nonce writes nothing', async () => {
	const response = await get('/wp-admin/admin-post.php?action=l3_report_comment&comment=1', { method: 'POST' });

	assert.ok([301, 302].includes(response.status), `the report handler answered ${response.status}`);
	assert.doesNotMatch(String(response.headers.get('location')), /l3_reported=/, 'a report without a nonce was accepted');
});
