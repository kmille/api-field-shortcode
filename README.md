# API Field Shortcode

A small WordPress plugin that adds an `[api_field]` shortcode: it sends a GET request to a URL you specify, reads one field out of the JSON object response, and prints it as plain text.

```
[api_field url="https://example.com/api/event-42" field="registrations"]
```

If the API returns `{"registrations": 100}`, the shortcode renders `100`.

## Attributes

| Attribute | Required | Default | Description |
|---|---|---|---|
| `url` | yes | — | Endpoint the plugin sends a GET request to. |
| `field` | yes | — | Key to read from the JSON object. Supports dot notation for nested fields, e.g. `data.registrations.total`. |
| `error` | no | `""` | Text shown if the request or field extraction fails (network error, expired TLS certificate, non-2xx status, invalid JSON, missing field, etc.). |
| `cache` | no | `300` | Cache duration in seconds (via WordPress transients). `0` disables caching. |
| `timeout` | no | `10` | HTTP request timeout in seconds. |

## Why GET-only, and dictionaries only?

This plugin was built for a specific, common case: internal/partner APIs that expect a GET request and return a single JSON object (not a list). That keeps the plugin small and predictable. If you need JSON arrays or HTML templating of multiple fields, see [JSON Content Importer](https://wordpress.org/plugins/json-content-importer/), which is a much more general (and heavier) tool for the same broad problem — at the time of writing there was no small, focused plugin that did just "GET + single field + custom error text", which is why this one exists.

## Error handling & logging

Every failure (network error, bad status code, invalid JSON, missing field, ...) is written to the standard PHP error log via `error_log()`, prefixed with `[API Field Shortcode]`, including the URL and field involved.

## Does this work in Divi?

Yes. Divi's Text and Code modules render normal WordPress shortcodes, so `[api_field ...]` behaves identically there, in the block editor, and in the classic editor.

## Installation

**Option A — Upload as a zip (any WordPress site, no WordPress.org listing needed):**

1. Download this repository as a ZIP (Code → Download ZIP), or download a release ZIP from the Releases page.
2. In wp-admin, go to *Plugins → Add New → Upload Plugin*.
3. Choose the ZIP and click *Install Now*, then *Activate*.

Note: WordPress needs the plugin's `.php` file directly inside the ZIP's top-level folder (i.e. `api-field-shortcode/api-field-shortcode.php`), which is how this repository is structured, so downloading the repo ZIP and uploading it as-is works.

**Option B — Manual FTP/SFTP:**

Copy the `api-field-shortcode` folder into `wp-content/plugins/`, then activate it under *Plugins*.

**Option C — WordPress.org plugin directory:**

GitHub itself isn't a WordPress "repository" in the sense of auto-updating installs — that only works for plugins hosted on wordpress.org (via its SVN-based directory) or through a third-party updater service (e.g. a GitHub-Updater-style plugin, or a premium tool like a self-hosted update server). If you want one-click installs and update notifications from wp-admin without any extra tooling, you'd submit this plugin to the [WordPress.org Plugin Directory](https://developer.wordpress.org/plugins/wordpress-org/) instead of (or in addition to) hosting it on GitHub. That involves:

1. Making sure the plugin follows the [Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) (GPL-compatible license, no obfuscated code, sane use of external services, etc.).
2. Submitting the plugin `readme.txt` + a ZIP for review.
3. Once approved, you get an SVN repository (not Git) where `trunk/` holds the current code and `tags/x.y.z/` holds releases — the `readme.txt` in this repo is already formatted for that.
4. From then on, the plugin is installable straight from *Plugins → Add New* inside any WordPress site, with automatic update notifications.

You can keep developing on GitHub and just mirror releases to the SVN repo (tools like `10up/action-wordpress-plugin-deploy` automate that with GitHub Actions) — plenty of plugin authors work that way.

## License

GPL-2.0-or-later
