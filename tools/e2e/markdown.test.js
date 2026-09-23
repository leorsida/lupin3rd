/*
 * The Markdown twin of a page, and the ways it is announced.
 *
 * The rules that matter here are not about formatting. They are about what
 * must never come out of this endpoint: an unpublished piece, a page somebody
 * put a password on, a comment, or an iframe — the page holds that one back
 * behind the consent gate, and a text file has no gate to hold it with.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { get, page } from './helpers.js';

const paths = await (async () => {
	const posts = await page('/wp-sitemap-posts-post-1.xml');
	const tutorials = await page('/wp-sitemap-posts-tutorial-1.xml');
	const first = (xml) => [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => new URL(m[1]).pathname)[0];

	return { news: first(posts), tutorial: first(tutorials) };
})();

const markdown = (path) => path.replace(/\/$/, '') + '.md';

test('a piece answers at its .md address, as text and not as a page', async () => {
	const response = await get(markdown(paths.tutorial));

	assert.equal(response.status, 200, 'no .md: the rewrite rules may not have been flushed');
	assert.match(response.headers.get('content-type') || '', /^text\/markdown/, 'served as something other than Markdown');
	assert.match(response.headers.get('x-robots-tag') || '', /noindex/, 'a second copy of the piece, offered for indexing');
	assert.match(response.headers.get('link') || '', /rel="canonical"/, 'no canonical pointing back at the page');
});

test('the file says what it is, who wrote it and under what licence', async () => {
	const body = (await get(markdown(paths.tutorial))).body;

	assert.match(body, /^---\n/, 'no front matter');
	assert.match(body, /\nurl: "https?:\/\//, 'the canonical address is missing from the front matter');
	assert.match(body, /\nlicense: "CC BY-SA 4\.0"/, 'the licence does not travel with the text');
	assert.match(body, /\n# /, 'no title');
});

test('no markup survives into the file', async () => {
	const body = (await get(markdown(paths.tutorial))).body;

	assert.doesNotMatch(body, /<iframe/i, 'an iframe reached the file, where no consent gate can hold it');
	assert.doesNotMatch(body, /<(p|div|h[1-6]|span)\b/i, 'HTML reached the file');
});

test('the comments are not in the file', async () => {
	const html = await page(paths.news);
	const body = (await get(markdown(paths.news))).body;

	const comment = (html.match(/<article[^>]*class="[^"]*\bcomment\b[^"]*"[\s\S]{0,400}?<p>([^<]{40,})</) || [])[1];

	if (!comment) {
		return; // No comment in the demo content to look for.
	}

	assert.ok(!body.includes(comment.slice(0, 40)), 'a comment was copied into the Markdown');
});

test('what is not published has no .md either', async () => {
	assert.equal((await get('/questa-pagina-non-esiste-affatto.md')).status, 404);
});

test('the page announces its twin, in the markup and in the headers', async () => {
	const response = await get(paths.tutorial);

	assert.match(response.body, /<link rel="alternate" type="text\/markdown" href="[^"]+\.md">/, 'no alternate link in the head');
	assert.match(response.headers.get('link') || '', /type="text\/markdown"/, 'no alternate link in the headers');
	assert.match(response.body, /class="mdlink"[^>]*href="[^"]+\.md"/, 'no link beside the title — the button must be a link first');
});

test('the home page offers no twin: it has no path to hang one on', async () => {
	const body = await page('/');

	assert.doesNotMatch(body, /type="text\/markdown"/, 'the front page advertised a .md, which would be <host>.md');
});

test('llms.txt lists the pieces, beside the policy it already carried', async () => {
	/*
	 * /llms.txt is Geo's document and was here first, with the licence and the
	 * crawler policy. The Markdown work adds a section to it — it does not get
	 * a second document, which is how this endpoint briefly came to have two
	 * implementations, one of them quietly winning on hook priority.
	 */
	const index = await get('/llms.txt');

	assert.equal(index.status, 200, 'no /llms.txt');
	assert.match(index.headers.get('content-type') || '', /^text\/plain/, 'served as something other than plain text');
	assert.match(index.body, /CC BY-SA 4\.0/, 'the index lost the licence it used to state');
	assert.match(index.body, /Cosa è ammesso/, 'the index lost the crawler policy it used to state');
	assert.match(index.body, /^- \[.+\]\(https?:\/\/.+\.md\)/m, 'the index lists nothing, or lists pages rather than Markdown');

	assert.match(await page('/robots.txt'), /llms\.txt/, 'robots.txt does not point at the index');
});
