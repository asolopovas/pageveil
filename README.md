# Pageveil

Veil your WordPress site with any chosen Gutenberg page — a chrome-free under-construction screen.

## Features

- Pick any published page; it becomes the public face of the site.
- No theme menus, sidebars or extra chrome — just `the_content` output.
- Returns `503` with `noindex,nofollow` so the holding page doesn't get indexed.
- Bypassed for admins, REST, AJAX, cron, WP-CLI and `wp-login.php`.

## Install (from release zip)

1. Download `pageveil-x.y.z.zip` from the latest [release](../../releases).
2. WordPress → Plugins → Add New → Upload Plugin.
3. Settings → Pageveil → choose page → Enable.

## Develop

```sh
make install     # composer install
make test        # phpunit
make lint        # php -l on every file
make build       # builds dist/pageveil-<version>.zip
make release     # tags + creates a GitHub release with the zip
make wp-deploy   # publishes to WordPress.org SVN trunk and tags/<version>
```

Bump the version in `pageveil.php` and `readme.txt`, commit, then `make tag && make release`.

## License

GPL-2.0-or-later.
