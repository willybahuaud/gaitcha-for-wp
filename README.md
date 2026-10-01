# Gaitcha for WordPress

English · [Français](README.fr.md)

Gaitcha adds a captcha checkbox to WordPress forms. The plugin checks the interaction log on your WordPress server and requires proof of work by default. You do not need an account or API key from a captcha provider.

[Website](https://gaitcha.com/) · [Live demo](https://gaitcha.com/#try-it) · [WordPress guide](https://gaitcha.com/wordpress/) · [Troubleshooting](https://gaitcha.com/guides/troubleshooting/)

## Before you install

Gaitcha evaluates mouse, keyboard and touch data supplied by the browser. A script can obtain a token, solve the required computation and submit a fabricated log without running a browser. A successful check does not prove that the visitor is human.

Proof of work adds a computational cost. Gaitcha does not have a published detection rate, and it does not replace rate limiting, field validation or login security. Test the forms with your visitors' input methods and provide a way to retry a rejected submission.

The [standalone library](https://github.com/willybahuaud/gaitcha) documents the verification protocol. This repository supplies the WordPress integration.

## Installation

Requirements: WordPress 6.0+ and PHP 7.4+.

1. Download the **`gaitcha-for-wp-…zip` release asset** from [GitHub Releases](https://github.com/willybahuaud/gaitcha-for-wp/releases/latest). It contains the Composer dependencies. GitHub's automatically generated **Source code** ZIP does not.
2. In WordPress, open **Plugins → Add New → Upload Plugin**, upload the ZIP and activate it.
3. Add a Gaitcha field to a supported form, or enable one of the native forms under **Settings → Gaitcha**.
4. Test the published form while logged out. Administrators bypass verification by default.

Activation creates the signing secret. Proof of work and anti-replay are enabled by default; temporary replay state is stored in WordPress options. The standalone core has different defaults.

## Form integrations

Connectors load when their form plugin is active.

| Form plugin | Add Gaitcha |
|---|---|
| [Contact Form 7](https://contactform7.com/) | Insert `[gaitcha]` before the submit button |
| [Gravity Forms](https://www.gravityforms.com/) | Add Gaitcha from Advanced Fields |
| [WPForms](https://wpforms.com/) | Add Gaitcha from Standard Fields |
| [Fluent Forms](https://fluentforms.com/) | Add the Gaitcha element in the builder |
| [Formidable Forms](https://formidableforms.com/) | Choose Gaitcha in the field palette |
| [Ninja Forms](https://ninjaforms.com/) | Add the Gaitcha field |
| [WS Form Pro](https://wsform.com/) | Drag Gaitcha from Spam Protection |
| [Elementor Pro Forms](https://elementor.com/) | Add a field of type Gaitcha to the Forms widget |

Step-by-step guides: [Contact Form 7](https://gaitcha.com/guides/contact-form-7/), [Gravity Forms](https://gaitcha.com/guides/gravity-forms/) and [Elementor Pro](https://gaitcha.com/guides/elementor-pro/).

The text inside the checkbox uses the plugin's translated default. The old CF7 custom-label example is no longer applicable. In Gravity Forms, you can edit or hide the **field label** using the normal field settings; that is separate from the text inside the checkbox.

Test AJAX forms, popups, conditional fields and multipage forms in your actual setup. An integration does not establish compatibility with every add-on or theme.

## Native WordPress forms

Under **Settings → Gaitcha**, you can enable each of these separately:

- Login (`wp-login.php`)
- Registration
- Lost password
- Comments

All four are off by default. These integrations target native WordPress forms. Custom login pages, membership plugins and WooCommerce checkout need separate compatibility checks.

## Appearance

The settings page provides two independent choices:

- **Theme:** Light, Dark, or Auto to follow the visitor's OS preference
- **Style:** Default for the classic widget, or Minimal for thinner borders and no shadows

Theme and style apply across connectors. They do not change the verification rules. The core injects its widget CSS with `!important`; account for that if you add custom overrides.

## What happens on a form

1. A non-interactive placeholder reserves space for the widget.
2. Interaction starts a request to `/wp-json/gaitcha/v1/init`.
3. The client solves a proof-of-work challenge, then obtains a signed token and the checkbox becomes interactive.
4. Checking the box captures the interaction log.
5. Submission sends the verification fields to WordPress, where the core checks the token and scores the log.

The signature protects the token, not the truth of the interaction log. Computation time varies with the visitor's device and the configured difficulty. Mouse, keyboard and touch input are supported, but that does not establish accessibility for every user or form.

## Data and updates

Interaction data is sent from the visitor's browser to your WordPress server. It is not sent to a third-party captcha verification service. The bundled client does not set tracking cookies, load advertising pixels or create a persistent visitor fingerprint.

Anti-replay keeps temporary token and challenge state in `wp_options`. Your host and other plugins may keep their own logs. Describe the processing used on your site; installing Gaitcha does not settle all your privacy obligations. [Data flow and privacy](https://gaitcha.com/privacy/).

The plugin checks **GitHub Releases** for updates and integrates them into WordPress' update screen. Those requests contact GitHub and are separate from captcha verification.

## Developer hooks

### Configure verification

Use `gaitcha_config` to change the core options. Put your filter in a site plugin or an mu-plugin:

```php
/**
 * Adjust the token lifetime for this site.
 *
 * @param array $config Gaitcha configuration.
 * @return array
 */
function mysite_gaitcha_config( array $config ): array {
    $config['ttl'] = 120;
    return $config;
}
add_filter( 'gaitcha_config', 'mysite_gaitcha_config' );
```

Other options include `score_threshold` (default `0.5`), `pow` (`true` in this plugin), `pow_difficulty` (`18`), `pow_challenge_ttl` (`90` seconds), `anti_replay` (`true`), `token_store`, `debug` and `no_js_fallback` (`'reject'`). See the [core configuration](https://github.com/willybahuaud/gaitcha#proof-of-work-and-configuration).

Increasing the threshold can reject more legitimate submissions. Increasing PoW difficulty adds work for visitors too. Test both before changing them in production.

`no_js_fallback: 'allow'` bypasses verification when the token is missing. It cannot distinguish a visitor with JavaScript disabled from a script posting directly.

### Include administrators in checks

```php
add_filter( 'gaitcha_bypass_admin', '__return_false' );
```

## Troubleshooting and development

If the widget does not load, check its JavaScript assets and REST endpoint, cache exclusions and browser errors. For a checked form that gets rejected, check token expiry and AJAX fields before changing scoring. The [troubleshooting guide](https://gaitcha.com/guides/troubleshooting/) covers common cases.

From a source checkout, install PHP dependencies with:

```bash
composer install
```

The core library comes from Composer. `assets/js/gaitcha.min.js` is a prebuilt bundle from that library. See [CHANGELOG.md](CHANGELOG.md) for version history.

For a reproducible issue, include WordPress, PHP and form-plugin versions, the form configuration and the steps to reproduce it. Remove visitor data and secrets before posting to [Issues](https://github.com/willybahuaud/gaitcha-for-wp/issues).

## Author and license

Built by [Willy Bahuaud](https://wabeo.fr). Licensed under [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).
