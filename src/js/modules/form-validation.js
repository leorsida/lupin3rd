/*
 * Client-side form checks.
 *
 * Nothing here is a security measure: the server validates everything again.
 * This only saves a round trip and puts the error next to the field that
 * caused it, which the browser's own bubble does not.
 */

export default function initFormValidation() {
	const forms = document.querySelectorAll('[data-validate]');

	if (!forms.length) {
		return;
	}

	forms.forEach((form) => {
		form.setAttribute('novalidate', 'novalidate');

		form.addEventListener('submit', (event) => {
			let firstInvalid = null;

			form.querySelectorAll('[required]').forEach((field) => {
				const valid = field.checkValidity() && String(field.value).trim() !== '';
				const error = form.querySelector(`[data-error-for="${field.name}"]`);

				field.setAttribute('aria-invalid', String(!valid));

				if (error) {
					error.classList.toggle('hidden', valid);
				}

				if (!valid && !firstInvalid) {
					firstInvalid = field;
				}
			});

			if (firstInvalid) {
				event.preventDefault();
				firstInvalid.focus();
			}
		});
	});
}
