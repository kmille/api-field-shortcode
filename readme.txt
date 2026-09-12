=== API Field Shortcode ===
Contributors: kmille
Tags: api, shortcode, rest api, json, remote data
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display a single field from a remote API (GET request, JSON object response) anywhere via a shortcode.

== Description ==

**API Field Shortcode** lets you show a live value from an external API inside any post, page, widget, or page builder module (works fine in Divi's Text/Code modules, since it's a plain WordPress shortcode).

Example: your event-registration system exposes an API that returns `{"registrations": 100}`. With this plugin you write:

`[api_field url="https://example.com/api/event-42" field="registrations"]`

...and the page shows `100`, updated automatically whenever the API value changes.

= Features =

* Always sends a GET request to the URL you provide.
* Extracts one field from the JSON object response — supports nested fields via dot notation, e.g. `field="data.registrations.total"`.
* Optional `error` attribute defines what is displayed if anything goes wrong (expired certificate, 5xx response, invalid JSON, missing field, ...).
* Built-in caching (transients) so the API isn't hit on every single page view; configurable or disabled per shortcode.
* Every failure is written to your PHP/WordPress error log with details, and fires an `afs_shortcode_error` action so you can hook in your own alerting.

= Shortcode attributes =

* `url` (required) — the endpoint to send the GET request to.
* `field` (required) — the key (or dot-notation path) to read from the JSON object.
* `error` (optional) — text shown on failure. Default: empty string.
* `body` (optional) — JSON object string turned into GET query parameters, e.g. `body='{"year":2026}'`.
* `cache` (optional) — cache duration in seconds. `0` disables caching. Default: `300`.
* `timeout` (optional) — HTTP timeout in seconds. Default: `10`.

== Installation ==

1. Upload the `api-field-shortcode` folder to `/wp-content/plugins/`, or install the zip via *Plugins > Add New > Upload Plugin*.
2. Activate the plugin through the *Plugins* menu.
3. Use the `[api_field]` shortcode wherever you'd like a value to appear.

== Frequently Asked Questions ==

= Does this work with Divi? =

Yes. Divi's Text module and Code module both render standard WordPress shortcodes, so `[api_field ...]` works exactly the same as in the block editor or classic editor.

= Where do errors get logged? =

To your normal PHP error log (visible via `WP_DEBUG_LOG`, your hosting control panel, or a log-viewer plugin). You can also hook the `afs_shortcode_error` action to send errors elsewhere (email, Slack, monitoring service, etc.).

= What happens if the API returns a list instead of an object? =

The shortcode only supports JSON objects (dictionaries) at the top level, per its design. If a list is returned, it's treated as an error and the `error` text is shown.

== Changelog ==

= 1.0.0 =
* Initial release.
