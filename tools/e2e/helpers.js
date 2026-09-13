/*
 * Enough HTML handling to make assertions about a rendered page, with no
 * dependencies: Node's own test runner and fetch, nothing installed.
 *
 * These tests exist because of what the theme's first fourteen defects had in
 * common — not one of them produced an error anywhere. A card rendered three
 * times, an overlay that never opened, blocks that were never registered: all
 * of them were visible only by loading the page, and all of them would have
 * been caught here.
 */

const BASE = (process.env.BASE_URL || 'http://app').replace(/\/$/, '');

const VOID_ELEMENTS = new Set([
	'area', 'base', 'br', 'col', 'embed', 'hr', 'img',
	'input', 'link', 'meta', 'source', 'track', 'wbr',
]);

/**
 * Fetch a path and return { status, headers, body }.
 */
export async function get(path, options = {}) {
	const response = await fetch(BASE + path, { redirect: 'manual', ...options });
	const body = await response.text();

	return { status: response.status, headers: response.headers, body, url: BASE + path };
}

/**
 * Fetch a path and fail loudly if it is not 200.
 */
export async function page(path) {
	const response = await get(path);

	if (response.status !== 200) {
		throw new Error(`${path} answered ${response.status}, expected 200`);
	}

	return response.body;
}

/**
 * The outer HTML of the first element matching a pattern, tags balanced.
 *
 * The pattern must match the opening tag, e.g. /<article[^>]*class="[^"]*\bcomment\b/.
 */
export function element(html, pattern) {
	const found = new RegExp(pattern.source, pattern.flags.replace('g', '')).exec(html);

	if (!found) {
		return null;
	}

	const start = found.index;
	const name = /^<([a-zA-Z0-9-]+)/.exec(html.slice(start));

	if (!name) {
		return null;
	}

	const tag = name[1].toLowerCase();

	if (VOID_ELEMENTS.has(tag)) {
		return html.slice(start, html.indexOf('>', start) + 1);
	}

	const walker = new RegExp(`<${tag}\\b[^>]*?(/?)>|</${tag}\\s*>`, 'gi');
	walker.lastIndex = start;

	let depth = 0;
	let step;

	while ((step = walker.exec(html)) !== null) {
		if (step[0].startsWith('</')) {
			depth -= 1;

			if (depth === 0) {
				return html.slice(start, step.index + step[0].length);
			}
		} else if (!step[1]) {
			depth += 1;
		}
	}

	return null;
}

/**
 * The tag names of an element's direct children.
 */
export function childTags(outerHtml) {
	const inner = outerHtml.replace(/^<[a-zA-Z0-9-]+\b[^>]*>/, '').replace(/<\/[a-zA-Z0-9-]+\s*>$/, '');
	const walker = /<([a-zA-Z0-9-]+)\b[^>]*?(\/?)>|<\/([a-zA-Z0-9-]+)\s*>/gi;
	const tags = [];

	let depth = 0;
	let step;

	while ((step = walker.exec(inner)) !== null) {
		const closing = Boolean(step[3]);
		const tag = (step[1] || step[3]).toLowerCase();

		if (closing) {
			depth -= 1;
			continue;
		}

		if (depth === 0) {
			tags.push(tag);
		}

		if (!VOID_ELEMENTS.has(tag) && !step[2]) {
			depth += 1;
		}
	}

	return tags;
}

/**
 * How many times a pattern occurs.
 */
export function count(html, pattern) {
	return (html.match(new RegExp(pattern.source, pattern.flags.includes('g') ? pattern.flags : pattern.flags + 'g')) || []).length;
}

/**
 * Every external host the page asks the browser to contact.
 *
 * The colophon promises no third party in the page. This is how that promise
 * is checked rather than believed.
 */
export function externalHosts(html, ownHosts = []) {
	const own = new Set([new URL(BASE).host, ...ownHosts]);
	const hosts = new Set();
	const walker = /(?:src|href)="(https?:\/\/[^"]+)"/gi;

	let step;

	while ((step = walker.exec(html)) !== null) {
		const host = new URL(step[1]).host;

		if (!own.has(host)) {
			hosts.add(host);
		}
	}

	return [...hosts];
}

/**
 * The host the site calls itself, read from its own canonical link.
 *
 * The tests can run against http://app inside the compose network or against
 * http://localhost:8080 from the host; either way the pages link to whatever
 * WP_HOME says, and that is not a third party.
 */
export async function siteHost() {
	const html = await page('/');
	const canonical = /<link rel="canonical" href="([^"]+)"/.exec(html) || /<meta property="og:url" content="([^"]+)"/.exec(html);

	return canonical ? new URL(canonical[1]).host : new URL(BASE).host;
}

export { BASE };
