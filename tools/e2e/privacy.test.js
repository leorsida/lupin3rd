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
	assert.ok(json.notice.consentModal.acceptNecessaryBtn, 'the notice cannot be dismissed');
	assert.ok(json.notice.consentModal.showPreferencesBtn, 'the notice has no way into the table');
});

test('the notice claims no optional storage', async () => {
	/*
	 * The day something optional is stored, this fails — and it should, because
	 * on that day the notice has to start asking instead of stating.
	 */
	const html = await page('/');
	const block = element(html, /<script[^>]*id="l3-consent-config"/);
	const json = JSON.parse(block.replace(/^<script[^>]*>/, '').replace(/<\/script>$/, ''));
	const linked = json.notice.preferencesModal.sections.map((s) => s.linkedCategory).filter(Boolean);

	assert.deepEqual(linked, ['necessary'], `the panel offers a choice: ${linked.join(', ')}`);
	assert.ok(!json.notice.consentModal.acceptAllBtn, 'the notice offers to accept something');
});

test('the panel carries the cookie table, and it matches the privacy page', async () => {
	/*
	 * The panel's rows and the page's table are written in two places and kept
	 * in step by hand. This is the only thing that notices when they drift.
	 */
	const html = await page('/');
	const block = element(html, /<script[^>]*id="l3-consent-config"/);
	const json = JSON.parse(block.replace(/^<script[^>]*>/, '').replace(/<\/script>$/, ''));
	const sections = json.notice.preferencesModal.sections;
	const necessary = sections.find((s) => s.linkedCategory === 'necessary');

	assert.ok(necessary, 'the panel has no necessary row');
	assert.ok(necessary.cookieTable.body.length > 0, 'the necessary row lists no cookies');

	const privacy = await page('/privacy/');

	for (const row of necessary.cookieTable.body) {
		assert.match(privacy, new RegExp(`<code>${row.name.replace('*', '')}`), `${row.name} is in the panel and not on /privacy/`);
	}
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
	for (const path of ['/', GATED, '/privacy/', '/community/']) {
		const html = await page(path);

		assert.deepEqual(externalHosts(html), [], `${path} contacts a third party`);
	}
});

test('the consent cookie is not written before an answer', async () => {
	const response = await get('/');
	const cookies = response.headers.get('set-cookie') || '';

	assert.doesNotMatch(cookies, /l3_consent/, 'the site answered for the reader');
});
