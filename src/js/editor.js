/*
 * lupin3rd.org — editor bundle.
 *
 * Loaded only in the block editor, never on a public page, so the front end's
 * one-bundle rule is untouched.
 *
 * Everything here talks to WordPress through the `wp.*` globals rather than
 * bundling @wordpress packages: the packages are already on the page, and
 * bundling a second copy of React into a theme is how editors get slow.
 */

import registerCodeBlock from './editor/block-code.js';
import registerNoticeBlock from './editor/block-notice.js';
import registerLegalGate from './editor/legal-gate.js';
import registerTutorialPanel from './editor/tutorial-panel.js';

registerCodeBlock();
registerNoticeBlock();
registerLegalGate();
registerTutorialPanel();
