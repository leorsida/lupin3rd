/*
 * Copy a code block.
 *
 * The button is rendered by the code block itself and is absent when there is
 * nothing to copy, so a page without code pays nothing for this.
 */

export default function initCodeCopy() {
	const buttons = document.querySelectorAll('[data-copy]');

	if (!buttons.length) {
		return;
	}

	buttons.forEach((button) => {
		button.addEventListener('click', async () => {
			// `code`, not `pre`: with line numbers on, the gutter is a sibling
			// of the pre and must not end up on the clipboard.
			const block = button.closest('.codeblock');
			const code = block ? block.querySelector('pre code') || block.querySelector('pre') : null;

			if (!code) {
				return;
			}

			const label = button.textContent;

			try {
				await navigator.clipboard.writeText(code.innerText);
				button.textContent = button.dataset.copiedLabel || 'copiato';
			} catch (error) {
				/*
				 * Clipboard access can be refused — a private window, a denied
				 * permission, an insecure origin. Say so rather than claim a
				 * copy that did not happen, and do the next best thing: select
				 * the code, so the reader's own copy shortcut works.
				 */
				button.textContent = button.dataset.failedLabel || 'seleziona e copia';

				const selection = window.getSelection();
				if (selection) {
					const range = document.createRange();
					range.selectNodeContents(code);
					selection.removeAllRanges();
					selection.addRange(range);
				}
			}

			setTimeout(() => {
				button.textContent = label;
			}, 1600);
		});
	});
}
