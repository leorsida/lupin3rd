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

	/*
	 * `.hero` is no longer here: it is the fallback for a site with nothing to
	 * lead with. What the page must always have is a lead and the rows under
	 * it — asserting the fallback was asserting the exception.
	 */
	assert.match(html, /class="post lead-cover"/, 'no lead');
	assert.match(html, /class="[^"]*\bpost\b/, 'no cards');
	assert.ok(count(html, /class="[^"]*\bsection\b/) >= 4, 'fewer sections than expected');
});

test('the lead is rendered once, not three times', async () => {
	/*
	 * It was rendered three times: post_meta() printed a category <a> inside
	 * .post, which is itself an <a>, and the parser split the outer element at
	 * every nested anchor. The lead changed shape since; what it must not have
	 * changed is this.
	 */
	const html = await page('/');
	const feature = element(html, /<a[^>]*class="[^"]*\blead-cover\b/);

	assert.ok(feature, 'no lead on the home page');
	assert.equal(count(feature, /<a\b/), 1, 'the lead contains a nested anchor');
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
	assert.match(html, /class="[^"]*\bcallout--legal\b|informativa legale/i, 'no legal notice');
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

test('the code block starts at the first column, on the page and on the clipboard', async () => {
	/*
	 * Inside <pre> the template's own indentation is content. Written across
	 * several lines, blocks/code/render.php printed a newline and two tabs
	 * before the first character: the first line of every block was pushed to
	 * the right, and the copy button put that whitespace on the clipboard —
	 * which, for a block whose content is a command to paste into a shell, is
	 * the part that matters.
	 */
	const html = await page(paths.tutorial);
	const block = element(html, /<div[^>]*class="[^"]*\bcodeblock\b/);
	const code = block && element(block, /<code\b/);

	if (!code) {
		return; // No code block in the demo content.
	}

	const inner = code.replace(/^<code[^>]*>/, '').replace(/<\/code>$/, '');

	assert.doesNotMatch(inner, /^\s/, 'the code block opens on whitespace from the template');
	assert.doesNotMatch(inner, /\s$/, 'the code block closes on whitespace from the template');
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

test('the home page leads with a piece and gives it the h1', async () => {
	/*
	 * A front page that opens with a claim about the publication has its h1 on
	 * the claim; this one opens with the lead story, so the h1 is the lead's
	 * headline. Either is defensible — having neither, or two, is not.
	 */
	const html = await page('/');
	const headings = html.match(/<h1[^>]*>/g) || [];

	assert.equal(headings.length, 1, `the home page has ${headings.length} first-level headings`);

	const lead = element(html, /<a class="post lead-cover"/);

	assert.ok(lead, 'the lead is not the cover-and-words block');
	assert.match(lead, /<h1/, 'the lead does not carry the h1');
	assert.doesNotMatch(lead, /class="mark-tag"/, 'the corner label is back, on top of the words');
});

test('the cover behind the lead is fetched at once, not lazily', async () => {
	const html = await page('/');
	const lead = element(html, /<a class="post lead-cover"/);
	const img = lead && /<img[^>]*>/.exec(lead);

	if (!img) {
		return; // A generated mark, not a photograph: nothing to fetch.
	}

	assert.doesNotMatch(img[0], /loading="lazy"/, 'the largest image above the fold is lazy-loaded');
});

test('the menu is reachable without the script that turns it into a drawer', async () => {
	/*
	 * Which shape the navigation takes is decided by `no-js` on <html>, which
	 * the snippet in the head removes before the first paint — so a reader with
	 * a script gets the panel laid out once, and a reader without one gets the
	 * list. It used to be decided by the module, and the page was laid out
	 * twice: the list, then the panel, and everything below jumped. That was
	 * the whole of a Cumulative Layout Shift of 0.4 on a phone.
	 *
	 * What must not come back is the other arrangement — panel off-screen in
	 * the markup, revealed by script — which hides the navigation behind a file
	 * that may never arrive.
	 */
	const html = await page('/');
	const nav = element(html, /<nav[^>]*id="primary-nav"/);

	assert.ok(nav, 'no primary navigation');
	assert.match(html, /<html[^>]*class="[^"]*\bno-js\b/, 'the markup does not ship no-js, so the drawer cannot be styled before paint');
	assert.match(html, /<script>document\.documentElement\.classList\.remove\("no-js"\)/, 'nothing removes no-js before the first paint');
	assert.doesNotMatch(html, /is-drawer/, 'the drawer is a class again, which means the layout is decided after paint');
	assert.doesNotMatch(html, /<button[^>]*data-nav-toggle[^>]*\bhidden\b/, 'the burger ships hidden, which is a second layout waiting to happen');
	assert.match(html, /<div class="nav-scrim"[^>]*\bhidden\b/, 'the backdrop ships visible');
});

test('the menu says which section you are in, on a single piece too', async () => {
	/*
	 * Every entry in this menu is a custom link, and WordPress marks a menu
	 * item current by matching the request against the object it points at —
	 * which a custom link does not have. So on /tutorial/<slug>/ nothing was
	 * marked and the navigation stopped saying where the reader was.
	 */
	const check = async (path, expected) => {
		const html = await page(path);
		const nav = element(html, /<nav[^>]*id="primary-nav"/);
		const marked = [...nav.matchAll(/<a[^>]*aria-current="page"[^>]*>([^<]*)</g)].map((m) => m[1].trim());

		assert.equal(marked.length, 1, `${path} marks ${marked.length} entries: ${marked.join(', ')}`);
		assert.match(marked[0], expected, `${path} marks «${marked[0]}»`);
	};

	await check(paths.tutorial, /tutorial/i);
	await check(paths.dossier, /dossier/i);

	/*
	 * A category archive, because a category entry is a custom link too and
	 * takes a different route through section_paths() than a post type does.
	 */
	await check('/categoria/crypto/', /crypto/i);
});

test('the archive is in the footer, and the menu does not pretend otherwise', async () => {
	/*
	 * The archive left the top navigation for the footer. This used to assert
	 * that /archivio/ lit an entry up there, and kept asserting it after the
	 * move: the menu was right and the test was describing a site that no
	 * longer existed. What has to stay true is the pair — nothing marked in a
	 * menu the page is not in, and a way to reach it from the foot of the page.
	 */
	const html = await page('/archivio/');
	const nav = element(html, /<nav[^>]*id="primary-nav"/);
	const footer = element(html, /<footer/);

	assert.equal(count(nav, /aria-current="page"/), 0, 'the top navigation marks a section the archive is not in');
	assert.match(footer, /href="[^"]*\/archivio\/?"/, 'the archive is reachable from nowhere');
});
