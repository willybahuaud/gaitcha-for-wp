=== Gaitcha for WordPress ===
Contributors: willybahuaud
Tags: captcha, spam, security, forms, antispam
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.3.1
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A self-hosted captcha for WordPress forms. Checks interaction data on your server, with proof of work enabled by default.

== Description ==

Gaitcha adds a checkbox to your forms. It collects mouse, keyboard or touch interaction data, then checks a signed token and scores the log on your WordPress server. You do not need a captcha service account or API key.

Website and demo: https://gaitcha.com/
WordPress documentation: https://gaitcha.com/wordpress/
Documentation en français : https://gaitcha.com/fr/wordpress/

= What the check means =

The interaction log comes from the client and can be fabricated. A script can request a token, solve the required computation and submit a plausible log without running a browser. The signature protects the token; it does not prove that the reported gestures happened.

Proof of work adds a computational cost. It does not establish that a visitor is human. There is no published detection rate for Gaitcha. Keep rate limiting, normal field validation and login protections. Test with your users' input methods and allow a retry after rejection.

= Supported forms =

* Contact Form 7
* Gravity Forms
* WPForms
* Fluent Forms
* Formidable Forms
* Ninja Forms
* WS Form Pro
* Elementor Pro Forms

Connectors load when the corresponding form plugin is active. Add the Gaitcha field in the builder. In Contact Form 7, insert [gaitcha] before the submit button.

The plugin also supports native WordPress login, registration, lost-password and comment forms. Enable each separately under Settings > Gaitcha. All four are off by default. Custom login pages, membership plugins and WooCommerce checkout require separate compatibility checks.

= Appearance =

Choose a Light, Dark or Auto theme and a Default or Minimal style under Settings > Gaitcha. Minimal uses thinner borders and no shadows. These settings apply across connectors and do not change verification rules.

The text inside the checkbox uses the plugin's translated default. Gravity Forms lets you edit or hide the separate field label through its normal settings.

= Data and external requests =

Interaction data travels from the visitor's browser to your WordPress server. The plugin does not send that log to a third-party captcha verification service. The bundled client does not set tracking cookies, load advertising pixels or create a persistent visitor fingerprint.

Proof of work and anti-replay are enabled by default. Temporary token and challenge state is kept in WordPress options. Hosting and application logs are separate; describe the processing used on your site rather than assuming the plugin settles all privacy obligations.

The plugin contacts GitHub Releases to check for updates. These requests are separate from visitor verification.

More about the data flow: https://gaitcha.com/privacy/

== Installation ==

1. Download the packaged gaitcha-for-wp ZIP asset from https://github.com/willybahuaud/gaitcha-for-wp/releases/latest. It includes Composer dependencies; the automatically generated Source code ZIP does not.
2. Open Plugins > Add New > Upload Plugin in WordPress, upload the ZIP and activate it.
3. Add a Gaitcha field to a supported form, or enable a native form under Settings > Gaitcha.
4. Test the published form while logged out. Administrators bypass verification by default.

Activation creates the signing secret. The plugin requires WordPress 6.0+ and PHP 7.4+.

== Frequently Asked Questions ==

= Does it require an API key? =

No. Captcha verification runs on your WordPress server and requires no third-party captcha account.

= Does it work without JavaScript? =

JavaScript is required by default. Setting no_js_fallback to 'allow' through the gaitcha_config filter bypasses verification when a token is absent. This also permits direct automated submissions without a token; the option cannot tell why the token is missing.

= Can I adjust the settings? =

Use Settings > Gaitcha for appearance and native-form protections. Developers can use gaitcha_config to change the score threshold, token lifetime and proof-of-work settings. Examples: https://gaitcha.com/wordpress/#developer-hooks

The score threshold defaults to 0.5, token lifetime to 120 seconds and proof-of-work difficulty to 18. Raising the threshold can reject more legitimate submissions. Raising the difficulty adds work for visitors too.

= Can administrators be checked too? =

Yes. Add this filter in a site plugin or mu-plugin:

`add_filter( 'gaitcha_bypass_admin', '__return_false' );`

= Is it accessible? =

The widget supports mouse, keyboard and touch input. Heuristic checks can reject legitimate interactions, so test your forms with assistive technology and your audience's input methods. These features do not establish an accessibility certification.

= What if the widget does not load or a checked form is rejected? =

Check the JavaScript assets, /wp-json/gaitcha/v1/init, cache exclusions, token expiry and AJAX submission fields before changing scoring. Test a failed submission followed by a retry. See https://gaitcha.com/guides/troubleshooting/

== Screenshots ==

1. Gaitcha checkbox on a Contact Form 7 form
2. Gaitcha field in the Gravity Forms editor
3. Gaitcha field in the WPForms builder

== Changelog ==

= 1.3.1 =
* Gravity Forms: editable field label and native Field Label Visibility setting

= 1.3.0 =
* Default and Minimal widget styles, including builder previews
* Core dependency updated to 0.8
* Fixed missing widgets in Ninja Forms and WS Form

= 1.2.0 =
* Proof of work enabled by default
* Placeholder widget at page load
* REST endpoint forwards proof-of-work solutions to the core
* Fixed Ninja Forms resubmission after a validation error

= 1.1.0 =
* Elementor Pro and native WordPress form integrations
* Settings page for appearance and native-form protections
* Translated default checkbox text across connectors
* Updated mobile/touch support and form connectors

= 1.0.3 =
* Anti-replay enabled by default
* Scoring and connector fixes
* French, Spanish, Italian and German translations

= 1.0.0 =
* Initial release

The full version history is in CHANGELOG.md in the source repository.

== Upgrade Notice ==

= 1.3.1 =
Gravity Forms field-label settings. Use the packaged release ZIP, which includes the core dependency.
