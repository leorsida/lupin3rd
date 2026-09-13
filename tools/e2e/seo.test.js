/*
 * What the site tells machines about itself.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { get, page, externalHosts, siteHost } from './helpers.js';

const article = await (async () => {
	const sitemap = await page('/wp-sitemap-posts-tutorial-1.xml');

	return new URL(/<loc>([^<]+)<\/loc>/.exec(sitemap)[1]).pathname;
})();

test('the head carries canonical, description and Open Graph', async () => {
	const html = await page(article);

	assert.match(html, /<link rel="canonical" href="[^"]+"/, 'no canonical');
	assert.match(html, /<meta name="description" content="[^"]+"/, 'no description');
	assert.match(html, /<meta property="og:title"/, 'no og:title');
	assert.match(html, /<meta property="og:image" content="[^"]+"/, 'no og:image');
});

test('the Open Graph image is a file that exists', async () => {
	const html = await page(article);
	const image = /<meta property="og:image" content="([^"]+)"/.exec(html)[1];
	const response = await get(new URL(image).pathname);

	assert.equal(response.status, 200, `${image} is not served`);
});

test('the JSON-LD parses and describes the piece', async () => {
	const html = await page(article);
	const scripts = [...html.matchAll(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/g)];

	assert.ok(scripts.length > 0, 'no JSON-LD');

	const graph = scripts.flatMap((match) => {
		const data = JSON.parse(match[1]); // Throws, loudly, if the theme emitted broken JSON.

		return data['@graph'] || [data];
	});

	const types = graph.map((node) => node['@type']);

	assert.ok(types.includes('Organization'), 'no Organization');
	assert.ok(types.some((type) => /Article|HowTo/.test(String(type))), 'nothing describing the piece itself');
});

test('data documents carry text, not HTML entities', async () => {
	/*
	 * JSON-LD and llms.txt are read by machines that do not decode entities:
	 * &#8217; in a summary is a typo to them.
	 */
	const html = await page(article);
	const scripts = [...html.matchAll(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/g)];

	for (const match of scripts) {
		assert.doesNotMatch(match[1], /&#\d+;|&[a-z]+;/, 'the JSON-LD contains HTML entities');
	}

	const llms = await page('/llms.txt');

	assert.doesNotMatch(llms, /&#\d+;|&(amp|quot|lt|gt|nbsp|rsquo|laquo|raquo);/, 'llms.txt contains HTML entities');
});

test('the documents machines look for are served', async () => {
	for (const path of ['/llms.txt', '/robots.txt', '/feed/', '/wp-sitemap.xml']) {
		const response = await get(path);

		assert.equal(response.status, 200, `${path} answered ${response.status}`);
		assert.ok(response.body.length > 50, `${path} is empty`);
	}
});

test('robots.txt points at the sitemap and states the crawler policy', async () => {
	const robots = await page('/robots.txt');

	assert.match(robots, /Sitemap:/i, 'no sitemap line');
	assert.match(robots, /User-agent:/i, 'no user-agent line');
});

test('no third party is asked for anything', async () => {
	/*
	 * "Nessuna terza parte in pagina" is in the footer of every page. This is
	 * the line that keeps it true.
	 */
	const own = await siteHost();

	for (const path of ['/', article, '/archivio/']) {
		const html = await page(path);

		assert.deepEqual(externalHosts(html, [own]), [], `${path} reaches outside the site`);
	}
});
