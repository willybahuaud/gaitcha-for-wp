=== Gaitcha for WordPress ===
Contributors: willybahuaud
Tags: captcha, spam, security, forms, antispam
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A behavioral captcha that stays on your server. Fine-grained interaction analysis, no external service, no tracking.

== Description ==

Most captcha solutions send your visitors' data to a third-party server with every form submission. Gaitcha takes a different approach: everything runs on your WordPress install, and nothing leaves your server.

Instead of puzzles or image grids, Gaitcha uses a simple checkbox. The trick is in *how* the user interacts with it. Humans hesitate, overshoot, decelerate before clicking. Bots click instantly, perfectly, without inertia. The behavioral log — mouse trajectory, keyboard timing, touch patterns — is scored server-side to tell the two apart.

No API key to manage, no account to create, no quota to worry about. Install, activate, add the field to your form. That's it.

= What it catches =

Gaitcha blocks the vast majority of automated submissions: scripted bots, headless browsers, form stuffers, and credential sprayers. The scoring engine analyzes 10+ behavioral signals simultaneously — faking all of them at once in a human-like way is a hard problem for automated tools.

= What it won't catch =

A determined attacker running a full browser with manual-like behavioral simulation. But at that point, you need rate limiting, not a captcha. Gaitcha is designed to stop mass spam — and it does that well.

= Privacy — the whole point =

* No data leaves your server — ever
* No cookies, no fingerprinting, no tracking pixels
* No external JavaScript loaded
* Nothing to declare in your privacy policy
* GDPR-friendly by design, not by configuration

= Supported Form Plugins =

* WS Form Pro
* Contact Form 7
* Formidable Forms
* Gravity Forms
* WPForms
* Fluent Forms
* Ninja Forms

Each form plugin gets a Gaitcha field type in its builder. Just drag it into your form.

Connectors are loaded conditionally — only when the corresponding form plugin is active. Zero overhead for plugins you don't use.

= How It Works =

1. The form loads normally — no captcha visible
2. On the first interaction (mouse, touch, keyboard), a checkbox appears
3. The user checks the box — behavioral data is collected silently in the background
4. On submit, the server verifies a signed token, scores the behavior, and accepts or rejects

No session, no database query, no external API call. Validation is stateless and lightweight.

== Installation ==

1. Upload the `gaitcha-for-wp` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Add the **Gaitcha** field to your forms

No configuration needed. The plugin generates a cryptographic secret automatically on activation.

== Frequently Asked Questions ==

= Does it require an API key? =

No. Gaitcha is fully self-hosted. No account, no API key, no external service, no quota.

= Does it work without JavaScript? =

By default, submissions are rejected when JavaScript is disabled. This can be changed via the `gaitcha_config` filter by setting `no_js_fallback` to `'allow'`.

= Can I adjust the sensitivity? =

Yes, via the `gaitcha_config` filter:

`add_filter( 'gaitcha_config', function ( $config ) {
    $config['score_threshold'] = 0.6; // Default: 0.5, range: 0.0-1.0
    return $config;
} );`

Higher values are stricter (fewer false negatives, more false positives). The default of 0.5 is a good balance for most sites.

= Is it accessible? =

Yes. The captcha uses a standard checkbox with proper ARIA attributes (`role="group"`, `aria-label`, `aria-required`). It follows natural tab order and works with keyboard-only navigation.

= Can admins bypass the captcha? =

Yes, by default logged-in administrators bypass validation. Disable this with:

`add_filter( 'gaitcha_bypass_admin', '__return_false' );`

= Where does my data go? =

Nowhere. That's the point. All behavioral analysis happens on your server. No data is sent to any external service. There is no "cloud" component.

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
