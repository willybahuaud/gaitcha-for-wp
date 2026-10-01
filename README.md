# Gaitcha for WordPress

English · [Français](README.fr.md)

**Gaitcha is a free, open-source behavioral captcha that runs on your WordPress server.** Your visitors check a single box, without image grids or puzzles. Their interaction data stays between their browser and your site.

The check looks at how someone reaches and checks the box: mouse trajectories, speed changes, keyboard timing and touch gestures. PHP scores those interactions when the form is submitted. Proof of work runs in the background before a token is issued, adding a computational cost to repeated automated requests. Both are enabled out of the box.

- **No captcha service to sign up for.** Install the plugin and add a Gaitcha field. There is no API key to obtain or external captcha API to call.
- **No visitor tracking in the widget.** It sets no tracking cookies and creates no persistent visitor fingerprint. The interaction log is checked on your own server.
- **Works in your form builder.** Eight connectors cover Contact Form 7, Gravity Forms, Elementor Pro Forms and more. You can also enable Gaitcha on native login, registration, lost-password and comment forms.
- **You control the setup.** Combine light, dark or automatic themes with a classic or minimal style. Use WordPress filters to adjust scoring, token lifetime and proof of work. The code is available under GPL-2.0-or-later.

[Try the live demo](https://gaitcha.com/#try-it) · [WordPress guide](https://gaitcha.com/wordpress/) · [Website](https://gaitcha.com/) · [Troubleshooting](https://gaitcha.com/guides/troubleshooting/)

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

The text inside the checkbox uses the plugin's translated default. In Gravity Forms, you can edit or hide the **field label** using the normal field settings; that is separate from the text inside the checkbox.

## Native WordPress forms

Under **Settings → Gaitcha**, you can enable each of these separately:

- Login (`wp-login.php`)
- Registration
- Lost password
- Comments

All four are off by default and apply to native WordPress forms. WooCommerce checkout and custom membership forms are outside these integrations.

## Appearance

The settings page provides two independent choices:

- **Theme:** Light, Dark, or Auto to follow the visitor's OS preference
- **Style:** Default for the classic widget, or Minimal for thinner borders and no shadows

Theme and style apply across connectors. They do not change the verification rules. The core injects its widget CSS with `!important`; account for that if you add custom overrides.

## How it works

1. A non-interactive placeholder reserves space for the widget.
2. Interaction starts a request to `/wp-json/gaitcha/v1/init`.
3. The client solves a proof-of-work challenge, then obtains a signed token and the checkbox becomes interactive.
4. Checking the box captures the interaction log.
5. Submission sends the verification fields to WordPress, where the core checks the token and scores the log.

The scorer uses a profile suited to the input method:

- **Mouse:** trajectory shape, speed changes, direction reversals and click offset
- **Keyboard:** navigation, key press durations and timing variation
- **Touch:** movement, tap offset, pressure and contact radius when available

The PHP check verifies the token signature and expiry, then compares the behavioral score with the configured threshold (`0.5` by default). Proof of work adds a computational cost before the token is issued; its duration depends on the device and difficulty. You can adjust both through the [developer hooks](#developer-hooks).

## Data and updates

Interaction data is sent from the visitor's browser to your WordPress server. It is not sent to a third-party captcha verification service. The bundled client does not set tracking cookies, load advertising pixels or create a persistent visitor fingerprint.

Anti-replay keeps temporary token and challenge state in `wp_options`. Your host and other plugins may keep their own logs. [Data flow and privacy](https://gaitcha.com/privacy/).

The plugin checks **GitHub Releases** for updates and integrates them into WordPress' update screen. Those requests contact GitHub and are separate from captcha verification.

## Developer hooks

The plugin exposes two filters: `gaitcha_config` for verification settings and `gaitcha_bypass_admin` for the administrator exemption.

### `gaitcha_config`

Filters the configuration array before the core is initialized. Register it in a site plugin or an mu-plugin: Gaitcha reads it on `plugins_loaded`, before the theme's `functions.php` loads. The filter receives an array and must return the updated array.

For example, to raise the score threshold and shorten token validity:

```php
/**
 * Set a stricter score threshold and a shorter token lifetime.
 *
 * @param array $config Gaitcha configuration.
 * @return array
 */
function mysite_gaitcha_config( array $config ): array {
    $config['score_threshold'] = 0.6;
    $config['ttl']             = 60;
    return $config;
}
add_filter( 'gaitcha_config', 'mysite_gaitcha_config' );
```

A higher threshold requires a higher behavioral score and can reject more submissions. Test the change on your forms before deploying it.

#### Proof of work

Proof of work is enabled by default. You can change its difficulty independently of behavioral scoring:

```php
/**
 * Increase the computation required to obtain a token.
 *
 * @param array $config Gaitcha configuration.
 * @return array
 */
function mysite_gaitcha_pow( array $config ): array {
    $config['pow_difficulty'] = 20;
    return $config;
}
add_filter( 'gaitcha_config', 'mysite_gaitcha_pow' );
```

Each extra bit doubles the expected computation: `20` requires about four times the work of the default `18`. Test slower devices before raising it. To disable proof of work, set `$config['pow'] = false;` in the filter; behavioral scoring remains active.

#### Configuration reference

These are the **WordPress plugin defaults**, including values inherited from the core:

| Option | Default | Purpose |
|---|---|---|
| `secret` | Generated on activation | Server-side signing secret, at least 32 characters |
| `ttl` | `120` | Token lifetime in seconds |
| `score_threshold` | `0.5` | Minimum accepted behavioral score, between 0 and 1 |
| `debug` | Value of `WP_DEBUG`, or `false` | Include scoring details in validation results |
| `no_js_fallback` | `'reject'` | Reject submissions without a token; `'allow'` accepts them without verification |
| `anti_replay` | `true` | Check previously used tokens and challenges |
| `token_store` | `GaitchaWP\WPTokenStore` when anti-replay is enabled | Temporary state in WordPress options; accepts a custom `Gaitcha\TokenStoreInterface` implementation |
| `pow` | `true` | Require proof of work before issuing a token |
| `pow_difficulty` | `18` | Required leading zero bits, from 8 to 26 |
| `pow_challenge_ttl` | `90` | Challenge lifetime in seconds, minimum 10 |

`no_js_fallback: 'allow'` also accepts automated submissions without a token. Keep `'reject'` if every submission must pass verification.

The [core configuration reference](https://github.com/willybahuaud/gaitcha#proof-of-work-and-configuration) also documents field naming. Change the existing array rather than replacing it, so the generated secret and plugin defaults are preserved.

### `gaitcha_bypass_admin`

Filters whether to skip verification. Its default is `current_user_can( 'manage_options' )`: administrators are exempt so you can work on forms without completing the captcha on each test. The filter receives and returns a boolean.

To check administrators too:

```php
add_filter( 'gaitcha_bypass_admin', '__return_false' );
```

This is useful when testing the form while logged in. Put the filter in the same site plugin or mu-plugin as your other Gaitcha settings.

## Limits

Client-side interaction data can be fabricated, so targeted automation can still pass verification. Proof of work adds computational cost; it does not prove that a visitor is human. Keep rate limiting, field validation and your site's login protections alongside Gaitcha.

Before launch, test your forms with keyboard navigation, mobile devices and assistive technology, including a retry after rejection. For AJAX forms, popups or multipage forms, test the full submission flow.

## Troubleshooting and development

If the widget does not load, check its JavaScript assets and REST endpoint, cache exclusions and browser errors. For a checked form that gets rejected, check token expiry and AJAX fields before changing scoring. The [troubleshooting guide](https://gaitcha.com/guides/troubleshooting/) covers common cases.

From a source checkout, install PHP dependencies with:

```bash
composer install
```

The [Gaitcha core library](https://github.com/willybahuaud/gaitcha) comes from Composer. `assets/js/gaitcha.min.js` is a prebuilt bundle from that library. See [CHANGELOG.md](CHANGELOG.md) for version history.

For a reproducible issue, include WordPress, PHP and form-plugin versions, the form configuration and the steps to reproduce it. Remove visitor data and secrets before posting to [Issues](https://github.com/willybahuaud/gaitcha-for-wp/issues).

## Author and license

Built by [Willy Bahuaud](https://wabeo.fr). Licensed under [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).
