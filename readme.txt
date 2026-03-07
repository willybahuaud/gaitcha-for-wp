=== Gaitcha for WordPress ===
Contributors: willybahuaud
Tags: captcha, spam, security, forms, antispam
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted behavioral captcha for WordPress forms. No external service, no API key, no user tracking.

== Description ==

Gaitcha adds a behavioral captcha to your WordPress forms. Instead of puzzles or image grids, a simple checkbox analyzes how the user interacts with it — mouse trajectory, keyboard timing, touch patterns — to tell humans from bots.

Everything runs on your server. No data is sent to any external service.

= Supported Form Plugins =

* WS Form Pro
* Contact Form 7
* Formidable Forms
* Gravity Forms
* WPForms
* Fluent Forms
* Ninja Forms

Connectors are loaded conditionally — only when the corresponding form plugin is active.

= How It Works =

1. The form loads without any captcha visible
2. On the first user interaction (mouse, keyboard, touch), a checkbox appears
3. The user checks the box — behavioral data is collected silently
4. On submit, the data is validated server-side using HMAC-signed tokens

The scoring engine detects three interaction profiles (mouse, keyboard, touch) and uses whichever scores highest. No session, no database query, no external API call during validation.

= Privacy =

* No data leaves your server
* No cookies set by the captcha
* No user fingerprinting
* No external scripts loaded
* GDPR-friendly by design

== Installation ==

1. Upload the `gaitcha-for-wp` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Add the **Gaitcha** field to your forms — no configuration needed

The plugin generates a cryptographic secret automatically on activation.

== Frequently Asked Questions ==

= Does it require an API key? =

No. Gaitcha is fully self-hosted. No account, no API key, no external service.

= Does it work without JavaScript? =

By default, submissions are rejected when JavaScript is disabled. This can be changed via the `gaitcha_config` filter by setting `no_js_fallback` to `'allow'`.

= Can I adjust the sensitivity? =

Yes, via the `gaitcha_config` filter:

`add_filter( 'gaitcha_config', function ( $config ) {
    $config['score_threshold'] = 0.6; // Default: 0.5, range: 0.0-1.0
    return $config;
} );`

= Is it accessible? =

The captcha uses a standard checkbox with proper ARIA attributes (`role="group"`, `aria-label`, `aria-required`). It follows natural tab order and works with keyboard-only navigation.

= Can admins bypass the captcha? =

Yes, by default logged-in administrators bypass validation. Disable this with:

`add_filter( 'gaitcha_bypass_admin', '__return_false' );`

= How is it different from reCAPTCHA or hCaptcha? =

Gaitcha runs entirely on your server. No user data is sent to Google, Cloudflare, or any third party. No privacy consent banner needed for the captcha itself.

== Screenshots ==

1. Gaitcha checkbox on a Contact Form 7 form
2. Gaitcha field in the Gravity Forms editor
3. Gaitcha field in the WPForms builder

== Changelog ==

= 1.0.0 =
* Initial release
* Connectors: WS Form Pro, Contact Form 7, Formidable Forms, Gravity Forms, WPForms, Fluent Forms, Ninja Forms
* ARIA accessibility attributes on captcha widget
* Auto-update via GitHub Releases

== Upgrade Notice ==

= 1.0.0 =
Initial release.
