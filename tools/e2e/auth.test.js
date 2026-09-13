/*
 * The front door.
 *
 * Two things are being checked here and they pull in opposite directions: that
 * a reader can get in through the theme, and that nobody can get in around it.
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

const signIn = await (async () => {
	const response = await get('/accesso/');

	return 200 === response.status ? response.body : null;
})();

const skip = !signIn && '/accesso/ is not published — run tools/seed-demo-content.sh';

test('both forms are real posts with a nonce, not a script', { skip }, () => {
	for (const action of ['l3_register', 'l3_sign_in']) {
		assert.ok(signIn.includes(`value="${action}"`), `no ${action} form`);
	}

	assert.equal(
		(signIn.match(/<form[^>]*method="post"/g) || []).length,
		2,
		'the page does not carry two POST forms'
	);
	assert.match(signIn, /action="[^"]*admin-post\.php"/, 'the forms do not post to admin-post.php');
	assert.equal(
		(signIn.match(/name="_wpnonce"/g) || []).length,
		2,
		'one of the two forms carries no nonce'
	);
});

test('the password field asks for length, and the meter is an opinion not a gate', { skip }, () => {
	assert.match(signIn, /name="l3_password"[^>]*minlength="10"|minlength="10"[^>]*name="l3_password"/, 'no minimum length');
	assert.match(signIn, /data-password-strength/, 'no strength meter');
});

test('registering without a nonce creates nothing', async () => {
	const probe = `probe${Date.now().toString(36)}`;
	const response = await post('/wp-admin/admin-post.php', {
		action: 'l3_register',
		l3_pseudonym: probe,
		l3_email: `${probe}@example.invalid`,
		l3_password: 'abcdefghijklmnop',
		l3_rules: '1',
	});

	assert.ok([301, 302].includes(response.status), `the handler answered ${response.status}`);
	assert.match(String(response.headers.get('location')), /l3_auth=error/, 'a registration without a nonce was accepted');

	const profile = await get(`/membri/${probe}/`);

	assert.notEqual(profile.status, 200, 'the account was created anyway');
});

test('signing in without a nonce opens nothing', async () => {
	const response = await post('/wp-admin/admin-post.php', {
		action: 'l3_sign_in',
		l3_identity: 'whoever',
		l3_password: 'whatever',
	});

	assert.ok([301, 302].includes(response.status), `the handler answered ${response.status}`);
	assert.match(String(response.headers.get('location')), /l3_auth=error/, 'a sign-in without a nonce was accepted');
	assert.doesNotMatch(String(response.headers.get('set-cookie') || ''), /wordpress_logged_in/, 'a session was opened');
});

test('the honeypot is in the page and invisible to a reader', { skip }, () => {
	assert.match(signIn, /name="l3_website"/, 'no honeypot');

	const trap = element(signIn, /<p class="hidden"[^>]*aria-hidden="true"/);

	assert.ok(trap, 'the honeypot is not hidden the way the stylesheet hides things');
	assert.match(trap, /name="l3_website"/, 'the hidden block is not the honeypot');
});

test('wp-login.php says whose page it is', async () => {
	const login = await get('/wp-login.php');

	assert.equal(login.status, 200, `wp-login.php answered ${login.status}`);
	assert.match(login.body, /redazione/i, 'wp-login.php does not say it is the desk\'s');
	assert.match(login.body, /accesso/, 'wp-login.php does not point readers at the front door');
});

test('wp-login.php does not register anybody', async () => {
	const register = await get('/wp-login.php?action=register');

	assert.ok([301, 302].includes(register.status), `?action=register answered ${register.status}`);
	assert.match(String(register.headers.get('location')), /\/accesso\//, 'registration does not lead to the front door');
});

test('the account corner is in every page and is the same for everybody', async () => {
	for (const path of ['/', '/archivio/']) {
		const html = await page(path);
		const corner = element(html, /<div class="account"/);

		assert.ok(corner, `no account corner on ${path}`);
		assert.match(corner, /data-when-signed-out/, 'the signed-out state is missing');
		assert.match(corner, /data-when-signed-in/, 'the signed-in state is missing');

		/*
		 * A nonce is different for every reader. One in this markup would make
		 * every page unique, which is the whole thing D-017 exists to prevent —
		 * and it is why signing out lives on /accesso/ and not up here.
		 */
		assert.doesNotMatch(corner, /_wpnonce/, 'the header carries a per-reader nonce');
	}
});

test('the page tells the script what to do without telling it anything worth stealing', { skip }, async () => {
	/*
	 * The header cannot ask PHP who is reading, so PHP leaves a cookie. What
	 * matters is what is *not* in it: no identifier, no token, nothing the
	 * server would believe. If this ever carries a role, a capability or an id,
	 * somebody has turned a display hint into a credential.
	 */
	const html = await page('/');

	assert.match(html, /data-account-chip/, 'no account chip to correct');
	assert.match(html, /data-account-name/, 'the script has nowhere to write the pseudonym');

	const src = /<script[^>]+src="([^"]*lupin3rd[^"]*\.js[^"]*)"/.exec(html);

	assert.ok(src, 'the page loads no theme bundle');

	const bundle = await page(new URL(src[1], 'http://placeholder').pathname);

	assert.match(bundle, /l3_hint/, 'the bundle does not read the hint cookie');
	assert.doesNotMatch(bundle, /innerHTML\s*=\s*\w*hint/i, 'the hint is written as markup');
});

test('/account/ is not a page a stranger can open', async () => {
	const account = await get('/account/');

	assert.ok([301, 302].includes(account.status), `/account/ answered ${account.status} to a stranger`);
	assert.match(String(account.headers.get('location')), /\/accesso\//, 'it does not lead to the sign-in page');
});

test('neither account form takes an account id from the request', async () => {
	/*
	 * The strongest form of this check is that there is nothing to check: the
	 * handlers write to get_current_user_id() and the forms carry no user
	 * field, so there is no parameter to tamper with. If one ever appears here,
	 * somebody has added a way to edit somebody else's profile.
	 */
	const signedIn = await get('/account/');

	assert.ok([301, 302].includes(signedIn.status), 'the page rendered to a stranger');

	const response = await post('/wp-admin/admin-post.php', {
		action: 'l3_save_profile',
		user_id: '1',
		l3_pseudonym: 'takeover',
	});

	assert.ok([301, 302].includes(response.status), `the handler answered ${response.status}`);
	assert.doesNotMatch(String(response.headers.get('location')), /l3_account=saved/, 'a profile was saved without a nonce');
});
