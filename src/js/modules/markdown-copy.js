/*
 * Copy a page as Markdown.
 *
 * The link is rendered by the server and points at a real file: with this
 * module absent, or JavaScript off, clicking it opens the Markdown. Here it is
 * fetched and put on the clipboard instead, because that is what somebody
 * pasting a page into a model actually wants, and it saves them a round trip
 * through a tab they then have to close.
 *
 * On any failure — a refused clipboard, a network that is not there — the link
 * goes back to being a link and says so. Nothing here may leave the reader
 * believing a copy happened that did not.
 */

export default function initMarkdownCopy() {
	const links = document.querySelectorAll('[data-copy-markdown]');

	if (!links.length) {
		return;
	}

	links.forEach((link) => {
		link.addEventListener('click', async (event) => {
			if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
				return; // Opening it in a tab on purpose is not our business.
			}

			if (!navigator.clipboard) {
				return; // No clipboard: let the browser follow the link.
			}

			event.preventDefault();

			const label = link.textContent;

			try {
				const response = await fetch(link.href, { headers: { Accept: 'text/markdown' } });

				if (!response.ok) {
					throw new Error(String(response.status));
				}

				await navigator.clipboard.writeText(await response.text());
				link.textContent = link.dataset.copiedLabel || 'copiato';
			} catch (error) {
				/*
				 * Say what went wrong by doing the honest thing: open the file,
				 * which is what the link always meant. The label changes first
				 * so that a reader who returns to the tab knows why.
				 */
				link.textContent = link.dataset.failedLabel || 'apri il markdown';
				window.location.assign(link.href);
				return;
			}

			window.setTimeout(() => {
				link.textContent = label;
			}, 2000);
		});
	});
}
