/*
 * A one-line stand-in for JSX.
 *
 * The theme has no Babel step and does not want one for four editor controls.
 * `h('div', { className: 'x' }, child)` reads closely enough to the markup it
 * produces.
 */

export const h = (...args) => wp.element.createElement(...args);
export const { __ } = wp.i18n;
