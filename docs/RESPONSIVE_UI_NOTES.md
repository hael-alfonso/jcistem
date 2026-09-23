# Responsive UI Notes — v42

The Admin System keeps the same master-document functionality and Admin permission scope while improving device usability.

## Responsive behavior
- Desktop: fixed navigation rail with full-width content area and multi-column dashboard/project layouts.
- Tablet: narrower navigation, reduced content gutters, two-column statistics, and flexible toolbars.
- Mobile: slide-over navigation with backdrop, touch-sized controls, one-column forms/cards, full-width primary actions, and bottom-sheet style dialogs.
- Narrow phones: stacked statistics, actions, project financial blocks, and horizontally scrollable tabs/subnavigation where needed.

## Icons
The interface uses embedded SVG line icons instead of font-dependent symbol glyphs. This keeps icon shape, alignment, and visibility consistent across operating systems and browsers.

## Touch and accessibility polish
- Primary interactive controls use approximately 42px minimum touch targets.
- Icon-only controls include visible titles/ARIA labels where applicable.
- Dialogs support Escape-to-close and safe mobile sizing.
- Reduced-motion preferences are respected.
- Horizontal data tables remain readable through controlled scrolling rather than squeezing text into unusable columns.

## Validation performed
- JavaScript syntax checks passed for `assets/js/app.js`, `modules/ui.js`, `modules/pages.js`, and `modules/data.js`.
- Static responsive CSS and navigation overlay rules were inspected after rebuild.
- Chromium headless smoke testing was attempted, but the container browser did not complete within the execution limit; therefore no claim of full visual browser certification is made from that run.
