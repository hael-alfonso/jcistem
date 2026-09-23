# JCI Carmona Online Project Management System — Admin System Master Rebuild v42

A clean, responsive Admin workspace rebuilt from the supplied JCI Carmona master functionality, navigation and workflow document.

## Open
Open `index.html` in a browser. Navigation uses hash routes so the prototype works as a static site.

## Main Admin modules
Dashboard • Projects • Tasks & Milestones • LOI • Calendar • Project Reports • Financial Monitoring • Members & Accounts • Member Registration • My Member Dues • Notifications • My Account • Audit Log.

## Important authority rule
The Admin workspace **does not expose Treasurer-only recording controls**. Financial Monitoring is read-only. Treasurer Ledger, financial transaction recording, disbursements, liquidation maintenance, Overall Financial Report preparation, and official monthly member-dues recording remain Treasurer responsibilities per the master blueprint.

## Technology
- HTML5 / CSS / Vanilla JavaScript
- Responsive admin shell
- Static demo data in `modules/data.js`
- Route/page logic in `modules/pages.js`
- UI helpers in `modules/ui.js`
- No image assets required for the core UI

## Included page launchers
The `pages/` folder contains direct module launchers that open the corresponding hash route in `index.html`.

## Production warning
This is a front-end prototype using demo data. Backend authentication, authorization, validation, database persistence, secure document storage, notifications, audit storage and server-side financial rules are required for production.

Responsive UI: v42 adds device-adaptive layouts, embedded SVG icons, touch-friendly controls, mobile navigation overlay, mobile dialog behavior, and overflow-safe tables/tabs. See docs/RESPONSIVE_UI_NOTES.md.


## v43 JCI visual refresh
The v43 build keeps the documented Admin permissions and responsive behavior while applying the current JCI 2026 brand palette:
- Primary: JCI Blue `#0097D7`, JCI Black `#130F2D`, White `#FFFFFF`
- Secondary: JCI Navy `#1F4789`, JCI Teal `#57BCBC`, JCI Yellow `#EFC40F`
Yellow is used sparingly as an attention accent.

New visual analytics are limited to decision-useful Admin views:
- Dashboard portfolio status donut
- Dashboard budget vs. used funds comparison
- Dashboard project progress bars
- Financial Monitoring budget vs. utilization comparison
- Fund Utilization visual comparison before the detail table

The dashboard charts are generated locally with HTML/CSS and do not require a charting library.


## v44 navigation simplification
- Sidebar groups are reduced to Workspace, Projects, Reports & Finance, Members, and Account.
- Project status views (Approved, Ongoing, Completed, Archived) remain inside the Projects module rather than occupying sidebar space.
- The sidebar has an independent vertical scroll, sticky section labels, and active-item auto-scroll.
- Mobile navigation continues to use the slide-out drawer.
- Dashboard visual hierarchy is intentionally calmer while retaining only the most useful monitoring charts.


## v45 clean/organized UI
- Removed decorative colored top lines from KPI/stat cards.
- Removed colored active-navigation line treatment; active navigation uses a soft background and subtle icon tint.
- Reduced dashboard repetition and removed repeated source-module summaries.
- Dashboard charts now have distinct purposes:
  1. Project Status — lifecycle distribution.
  2. Project Progress — implementation progress.
  3. Budget Use — approved allocation usage.
- Financial subpages no longer repeat the same Admin/Treasurer permission warning.
- Dashboard and module descriptions use shorter, clearer language.


## v48 chart and folder update
The project folder and package are now named **jci system**.

Chart style was simplified:
- Status, roles, and report distribution use readable horizontal category bars.
- Project progress uses ranked horizontal progress bars.
- Budget charts compare Used Funds versus Remaining Funds directly.
- Charts show counts and percentages beside the bars so the meaning is clear without interpreting a donut.


## v49 visual update
- Distribution charts now use familiar pie charts.
- Progress, comparison, and budget views use bar charts.
- Card and section spacing was tightened to reduce unused white space while retaining readable touch targets.
- Tables and forms use tighter but still legible padding.
- Navigation spacing was reduced slightly while keeping the sidebar scrollable.


## v49 final packaging
The ZIP root folder is exactly `jci system`.


## v50 Admin cleanup
Standardized navigation, active states, workspace context, cards, tables, and finance monitoring. Reduced repeated summary blocks while preserving the documented Admin scope and Treasurer financial-recording boundary.
