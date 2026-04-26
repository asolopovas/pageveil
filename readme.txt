=== Pageveil ===
Contributors: asolopovas
Tags: under construction, maintenance, coming soon, gutenberg
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 0.0.1
License: MIT
License URI: https://opensource.org/licenses/MIT

Veil your WordPress site with any chosen Gutenberg page — a chrome-free under-construction screen.

== Description ==

Pageveil lets you pick any published page in your site and serve it as the public front of the site, with no theme menus, sidebars or extra chrome — just your page's Gutenberg content. Administrators continue to see the live site so they can keep working.

* Use any existing page as the holding screen
* Block-theme and classic-theme friendly (renders core blocks via `the_content`)
* Returns HTTP 503 with `noindex,nofollow` so search engines don't index the holding page
* Bypassed for admins, REST, AJAX, cron, WP-CLI and the login screen

== Installation ==

1. Upload the plugin to `/wp-content/plugins/pageveil` or install via the Plugins screen.
2. Activate the plugin.
3. Go to **Settings → Pageveil**, pick a page, tick **Enable**, save.

== Frequently Asked Questions ==

= Will I still be able to access wp-admin? =
Yes. Anyone with `manage_options` capability sees the live site and admin as normal.

= Does it work with block themes? =
Yes. Page content is rendered through `the_content`, so Gutenberg blocks render normally.

== Screenshots ==

1. Settings → Pageveil — pick a published page and toggle the veil.
2. Public visitors see the chosen page, with no theme menus or sidebars.

== Changelog ==

= 0.0.1 =
* Initial release.

== Upgrade Notice ==

= 0.0.1 =
Initial release.

== Privacy ==

Pageveil does not collect, store, transmit or share any personal data. It does not contact any external services and sets no cookies.
