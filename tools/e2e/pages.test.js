/*
 * Every kind of page renders, and carries what it is supposed to carry.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { get, page, element, count } from './helpers.js';

const paths = await (async () => {
	const sitemap = await page('/wp-sitemap-posts-post-1.xml');
	const tutorials = await page('/wp-sitemap-posts-tutorial-1.xml');
	const dossiers = await page('/wp-sitemap-posts-dossier-1.xml');
	const first = (xml, skip = 0) => [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => new URL(m[1]).pathname)[skip];

	return {
		news: first(sitemap, 1) || first(sitemap),
		tutorial: first(tutorials),
		dossier: first(dossiers),
	};
})();

test('the home page renders its sections', async () => {
	const html = await page('/');

	assert.match(html, /class="[^"]*\bhero\b/, 'no hero');
	assert.match(html, /class="[^"]*\bpost\b/, 'no cards');
	assert.ok(count(html, /class="[^"]*\bsection\b/) >= 4, 'fewer sections than expected');
});

test('the feature card is rendered once, not three times', async () => {
	/*
	 * It was rendered three times: post_meta() printed a category <a> inside
	 * .post, which is itself an <a>, and the parser split the outer element at
	 * every nested anchor.
	 */
	const html = await page('/');
	const feature = element(html, /<a[^>]*class="[^"]*\bsplit-feature\b/);

	assert.ok(feature, 'no feature card on the home page');
	assert.equal(count(feature, /<a\b/), 1, 'the feature card contains a nested anchor');
});

test('the dispatch form is on the home page and posts somewhere real', async () => {
	const html = await page('/');
	const form = element(html, /<form[^>]*action="[^"]*admin-post\.php"/);

	assert.ok(form, 'no newsletter form');
	assert.match(form, /name="action" value="l3_subscribe"/);
	assert.match(form, /name="_wpnonce"/);
	assert.match(form, /type="email"/);
});

test('a news piece carries its furniture', async () => {
	const html = await page(paths.news);

	assert.match(html, /<h1[^>]*>/, 'no title');
	assert.match(html, /class="[^"]*\bpost-meta\b/, 'no byline row');
	assert.match(html, /class="[^"]*\btag tag-strong\b/, 'no section chip in the byline');
	assert.doesNotMatch(html, /<p class="kicker">/, 'the kicker is back above the title');
	assert.match(html, /id="comments"/, 'no discussion');
	assert.match(html, /Continua da qui/, 'no related pieces');
});

test('the related row is a row, not a single card', async () => {
	const html = await page(paths.news);
	const related = element(html, /<section[^>]*aria-labelledby="l3-related"/);

	assert.ok(related, 'no related section');
	assert.ok(count(related, /class="post"/) >= 2, 'the related row has fewer than two cards');
});

test('a tutorial carries an index, a code block and the legal notice', async () => {
	const html = await page(paths.tutorial);

	assert.match(html, /class="toc widget"/, 'no index');
	assert.match(html, /class="[^"]*\bcodeblock\b/, 'no code block: the blocks may not be registered');
	assert.match(html, /data-copy/, 'the code block has no copy button');
	assert.match(html, /class="[^"]*\bcallout--legal\b|perimetro legale/i, 'no legal notice');
});

test('the line numbers sit outside <code>, so copying gives code alone', async () => {
	const html = await page(paths.tutorial);
	const numbered = element(html, /<div[^>]*class="[^"]*\bcodeblock--numbered\b/);

	if (!numbered) {
		return; // No numbered block in the demo content.
	}

	const code = element(numbered, /<code\b/);

	assert.ok(code, 'a code block with no <code>');
	assert.doesNotMatch(code, /codeblock-lines/, 'the gutter is inside <code> and would be copied with it');
});

test('a dossier index lists its instalments', async () => {
	const html = await page(paths.dossier);

	assert.match(html, /Le puntate|In questo dossier/, 'no instalment navigation');
});

test('the archive filters run on the server, not only in the browser', async () => {
	const all = await page('/archivio/');
	const tutorials = await page('/archivio/?l3_format=tutorial');

	const rows = (html) => count(html, /class="[^"]*\blog-row\b/);

	assert.ok(rows(all) > 0, 'the archive is empty');
	assert.ok(rows(tutorials) < rows(all), 'the filter returned the whole archive');
	assert.ok(rows(tutorials) > 0, 'the filter returned nothing');
});

test('search answers without JavaScript', async () => {
	const html = await page('/?s=router');

	assert.match(html, /<h1[^>]*>[\s\S]*Risultati per/, 'no results heading');
});

test('a wrong tag gives a 404 that suggests the right one', async () => {
	const missing = await get('/tag/forensee/');

	assert.equal(missing.status, 404);
	assert.match(missing.body, /forense/, 'no suggestion for a near-miss slug');
});

test('a tag page does not wear its own name as a layout class', async () => {
	/*
	 * body_class() adds `tag`, and `.tag` in this design system is an
	 * inline-flex pill. The body became a pill.
	 */
	const html = await page('/tag/forense/');
	const body = /<body[^>]*class="([^"]*)"/.exec(html);

	assert.ok(body, 'no body class attribute');

	const classes = body[1].split(/\s+/);

	for (const colliding of ['tag', 'post', 'card', 'row', 'meta', 'mark', 'section', 'container']) {
		assert.ok(!classes.includes(colliding), `body carries the component class "${colliding}"`);
	}
});
