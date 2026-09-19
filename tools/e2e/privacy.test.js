/*
 * The consent notice, the embed gate, and the page that describes both.
 *
 * The gate is the only one of these that is a promise rather than a feature:
 * it has to hold for a reader who never runs the bundle, which is exactly what
 * a browser-side implementation cannot do and what these assertions check.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { get, page, element, externalHosts } from './helpers.js';

const GATED = '/tutorial/quattro-pin-sul-pcb-la-seriale-di-un-router/';

test('the privacy page answers, and lists what the code writes', async () => {
	const html = await page('/privacy/');

	for (const name of ['wordpress_logged_in_', 'l3_hint', 'comment_author_', 'l3_consent', 'l3-theme']) {
		assert.match(html, new RegExp(`<code>${name.replace(/[*]/g, '')}`), `${name} is not in the cookie table`);
	}
});

test('the notice ships its wording as data, not as code', async () => {
	/*
	 * A `type="application/json"` block is not executed, so a hash-based
	 * script-src never has to account for it. Turning this into an inline
	 * script would be invisible until a policy was added months later.
	 */
	const html = await page('/');
	const block = element(html, /<script[^>]*id="l3-consent-config"/);

	assert.ok(block, 'no consent configuration on the page');
	assert.match(block, /type="application\/json"/, 'the configuration is executable');

	const json = JSON.parse(block.replace(/^<script[^>]*>/, '').replace(/<\/script>$/, ''));

	assert.ok(json.notice.consentModal.title, 'the notice has no title');
	assert.ok(json.notice.consentModal.acceptNecessaryBtn, 'the notice has no way to refuse');
});

test('reopening the notice works without the bundle, and the button does not pretend to', async () => {
	const footer = await page('/');
	const link = element(footer, /<a[^>]*data-consent-open/);

	assert.ok(link, 'nothing in the footer reopens the notice');
	assert.match(link, /href="[^"]*\/privacy\/"/, 'the footer control is inert without JavaScript');

	const privacy = await page('/privacy/');

	assert.match(privacy, /<button[^>]*data-consent-open[^>]*\bhidden\b/, 'a script-only button ships visible');
});

test('a third-party frame is replaced before it is sent, not hidden afterwards', async () => {
	const html = await page(GATED);
	const gate = element(html, /<div[^>]*class="embed-gate"/);

	assert.ok(gate, 'the seeded embed was not gated');
	assert.match(gate, /data-embed-host="www\.youtube-nocookie\.com"/, 'the gate does not name the host');
	assert.match(gate, /data-embed-show/, 'the gate offers no way to load the frame');
	assert.doesNotMatch(html, /<iframe[^>]*youtube/i, 'the iframe survived in the HTML');
});

test('no article sends the browser to another host', async () => {
	/*
	 * The colophon's claim, checked rather than believed — and now also the
	 * reason the notice can have a single category.
	 */
	for (const path of ['/', GATED, '/privacy/', '/comunita/']) {
		const html = await page(path);

		assert.deepEqual(externalHosts(html), [], `${path} contacts a third party`);
	}
});

test('the consent cookie is not written before an answer', async () => {
	const response = await get('/');
	const cookies = response.headers.get('set-cookie') || '';

	assert.doesNotMatch(cookies, /l3_consent/, 'the site answered for the reader');
});
