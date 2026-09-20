/*
 * Closing an account: keep the button inert until the pseudonym matches.
 *
 * Comfort only. The server checks the same thing and refuses without it, so
 * the form works exactly as well with this file missing — which is why the
 * button ships enabled and is disabled here, rather than the other way round.
 * A control that arrives disabled and waits for a script is a control that
 * stays disabled when the script does not arrive.
 */

export default function initAccountClose() {
	const form = document.querySelector('[data-close-account]');

	if (!form) {
		return;
	}

	const field = form.querySelector('[data-close-confirm]');
	const button = form.querySelector('[data-close-submit]');

	if (!field || !button) {
		return;
	}

	const expected = (field.getAttribute('data-close-confirm') || '').trim().toLowerCase();

	const check = () => {
		button.disabled = field.value.trim().toLowerCase() !== expected;
	};

	field.addEventListener('input', check);
	check();
}
