/*
 * The design system's structural contracts.
 *
 * Three of this theme's defects came from breaking one of these: a grid that
 * takes exactly two children given three, an element WordPress moves that was
 * not the one carrying the frame, a sticky box with no background of its own.
 * None of them raised an error; all of them broke the page.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { get, page, element, childTags, count } from './helpers.js';

const commented = await (async () => {
	const sitemap = await page('/wp-sitemap-posts-post-1.xml');
	const paths = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map((match) => new URL(match[1]).pathname);

	for (const path of paths) {
		const html = await page(path);

		if (/class="[^"]*\bcomment\b[^"]*"/.test(html)) {
			return { path, html };
		}
	}

	return null;
})();

test('a comment is two children: the avatar and everything else', { skip: !commented && 'no published comments' }, () => {
	const comment = element(commented.html, /<article[^>]*class="[^"]*\bcomment\b/);

	assert.ok(comment, 'no comment element');

	const children = childTags(comment);

	assert.equal(children.length, 2, `.comment has ${children.length} children; the 44px column takes exactly two`);
	assert.equal(children[0], 'span', 'the first child should be the avatar');
});

test('both comment tools sit in the tools row', { skip: !commented && 'no published comments' }, () => {
	const tools = element(commented.html, /<div[^>]*class="comment-tools"/);

	assert.ok(tools, 'no tools row');
	assert.match(tools, /comment-reply-link|comment-tool/, 'no reply tool');
	assert.match(tools, /name="action" value="l3_report_comment"/, 'no report tool');
	assert.match(tools, /name="_wpnonce"/, 'the report form has no nonce');
});

test('the reply form is the element WordPress moves, frame and all', async () => {
	/*
	 * comment-reply.js moves #respond and nothing else. A frame on a wrapper
	 * stays behind, and the reply form comes out of its box.
	 */
	const html = commented ? commented.html : await page('/');
	const respond = element(html, /<div[^>]*id="respond"/);

	assert.ok(respond, 'no #respond');
	assert.match(respond, /class="[^"]*comment-respond/, '#respond lost its class');
	assert.match(respond, /<form/, '#respond does not contain the form');
	assert.doesNotMatch(html, /class="formcard"[^>]*>\s*<[^>]*>\s*<div[^>]*id="respond"/, 'the card is a wrapper again');
});

test('the side column is one block, not a stack of sticky boxes', async () => {
	const sitemap = await page('/wp-sitemap-posts-tutorial-1.xml');
	const path = new URL(/<loc>([^<]+)<\/loc>/.exec(sitemap)[1]).pathname;
	const html = await page(path);
	const grid = element(html, /<div[^>]*class="grid-main-side"/);

	assert.ok(grid, 'no main/side grid');

	const children = childTags(grid);

	assert.deepEqual(children, ['div', 'aside'], 'the grid should hold the main column and the side column, in that order');

	const aside = element(grid, /<aside/);

	assert.match(aside, /class="toc widget"/, 'the index is not in the side column');
});

test('the main column holds the whole piece, so the side column can follow it', async () => {
	const sitemap = await page('/wp-sitemap-posts-tutorial-1.xml');
	const path = new URL(/<loc>([^<]+)<\/loc>/.exec(sitemap)[1]).pathname;
	const html = await page(path);
	const grid = element(html, /<div[^>]*class="grid-main-side"/);

	assert.match(grid, /id="comments"/, 'the discussion is outside the grid: the column stops halfway down the page');
});

test('cards contain no nested anchors', async () => {
	for (const path of ['/', '/archivio/']) {
		const html = await page(path);
		const walker = /<a[^>]*class="post[^"]*"/g;
		let match;

		while ((match = walker.exec(html)) !== null) {
			const card = element(html.slice(match.index), /<a\b/);

			assert.equal(count(card, /<a\b/), 1, `a card on ${path} contains a nested anchor`);
		}
	}
});

test('the search overlay is in the page and uses the state the stylesheet defines', async () => {
	const html = await page('/');
	const overlay = element(html, /<div[^>]*class="[^"]*\boverlay\b/);

	assert.ok(overlay, 'no search overlay');
	assert.match(overlay, /<form/, 'the overlay has no form to submit without JavaScript');

	/*
	 * The overlay's own class attribute, not a descendant's: the
	 * empty-results message inside it is supposed to carry .hidden.
	 */
	const ownClasses = /^<div[^>]*\sclass="([^"]*)"/.exec(overlay);

	assert.ok(ownClasses, 'the overlay has no class attribute');
	assert.ok(
		!ownClasses[1].split(/\s+/).includes('hidden'),
		'the overlay is hidden with .hidden, which is !important and beats .is-open'
	);
	assert.match(overlay, /^<div[^>]*\shidden\b/, 'the overlay is not hidden from assistive technology when closed');
});

test('the menu is a list of links, not a list of bullets', async () => {
	const html = await page('/');
	const nav = element(html, /<nav[^>]*class="[^"]*\bnav\b|<nav\b/);

	assert.ok(nav, 'no navigation');
	assert.doesNotMatch(nav, /<li\b/, 'the menu emits list items outside a list');
});

test('an empty state spans the whole list it replaces', async () => {
	/*
	 * It ships inside the members grid, hidden, so the filter module can reveal
	 * it without a round trip. A grid item occupies one cell unless it is told
	 * otherwise, and a «no results» notice sitting in one of two columns reads
	 * as a result rather than as the absence of them.
	 */
	const html = await page('/comunita/');
	const grid = element(html, /<div[^>]*data-filter-scope="members"/);

	assert.ok(grid, 'no members grid on /comunita/');
	assert.match(grid, /class="emptystate hidden"/, 'the grid carries no empty state to reveal');
});
