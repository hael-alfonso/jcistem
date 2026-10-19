# Front-end performance and deployment

- Sidebar disclosure arrows point right when collapsed and down when expanded. Native details/summary keeps keyboard navigation and no-JavaScript operation.
- The sticky system topbar keeps its pale-blue color with an opaque background, removing the continuous backdrop blur work during scrolling.
- Landing, login/recovery, and chapter pages discover their shared font stylesheet directly in the head instead of through a CSS import. Preconnect starts the two font connections early; display=swap keeps text readable while fonts load.
- Chapter CSS/JavaScript URLs include modification-time versions, matching public/auth page behavior, so changed assets invalidate browser caches.
- public/assets/.htaccess enables text asset compression and short-lived browser caching when Apache mod_filter/mod_deflate/mod_expires are enabled. This applies only to public static assets, not private pages or files. CSS/JS cache for one hour, images for one day, fonts for one week.
- The PHP development server (artisan serve) does not process .htaccess. The local Apache module check on 20 October 2026 found none of these optional modules loaded. Enable them in the production host or configure equivalent compression/cache headers in its web server/CDN. Verify actual response headers after deployment.
- Production setup should use APP_DEBUG=false, optimized Composer autoloading, and Laravel's deployment caches for the deployed environment. Do not copy a local config cache to production. Hosting response time, database load, and network conditions still require measurement on the live deployment; no online speed score is claimed from local checks.

References: https://httpd.apache.org/docs/2.4/mod/mod_deflate.html and https://httpd.apache.org/docs/2.4/mod/mod_expires.html .
