/*
 * Password strength.
 *
 * Four bars and one line of advice. It is an opinion, not a gate: the server
 * enforces one rule, a minimum length, and this never blocks a submission.
 *
 * The opinion is deliberately about length first. A twelve-character
 * passphrase of ordinary words beats a mangled eight-character word, and a
 * meter that rewards punctuation over length teaches the opposite.
 */

const score = (value) => {
	if (!value) {
		return 0;
	}

	const length = value.length;
	const variety =
		(/[a-z]/.test(value) ? 1 : 0) +
		(/[A-Z]/.test(value) ? 1 : 0) +
		(/[0-9]/.test(value) ? 1 : 0) +
		(/[^A-Za-z0-9]/.test(value) ? 1 : 0);

	if (length < 10) {
		return 1;
	}

	if (length >= 20) {
		return 4;
	}

	if (length >= 16) {
		return variety > 1 ? 4 : 3;
	}

	return variety > 2 ? 3 : 2;
};

export default function initPasswordStrength() {
	const fields = document.querySelectorAll('[data-password-strength]');

	if (!fields.length) {
		return;
	}

	fields.forEach((field) => {
		const meter = document.querySelector(field.dataset.passwordStrength);
		const echo = field.closest('.field')?.querySelector('[data-password-strength-echo]');

		if (!meter) {
			return;
		}

		const bars = Array.from(meter.querySelectorAll('i'));

		field.addEventListener('input', () => {
			const level = score(field.value);

			bars.forEach((bar, index) => {
				bar.classList.toggle('on', index < level);
			});

			if (echo) {
				const said = echo.dataset[`level${level}`];

				if (said) {
					echo.textContent = said;
				}
			}
		});
	});
}
