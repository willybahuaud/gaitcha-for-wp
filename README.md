# Gaitcha for WordPress

A behavioral captcha that stays on your server.

Most captcha solutions send your visitors' data to a third-party service — every interaction, every page load, every form submission. Gaitcha does the opposite: it runs entirely on your WordPress install, scores human behavior through fine-grained analysis (mouse trajectory, speed patterns, keyboard timing, touch dynamics), and never phones home.

It works with a single checkbox. No puzzles, no image grids, no "select all the traffic lights". The trick is in *how* the user reaches and checks that box — mouse trajectory, speed variation, keyboard timing, touch patterns. Humans hesitate, overshoot, decelerate. Bots don't.

The behavioral log is scored server-side using HMAC-signed tokens. No session, no database query, no external API. Stateless and lightweight.

## What it blocks

Gaitcha catches the vast majority of automated submissions: scripted bots, headless browsers, form stuffers, and credential sprayers. The scoring engine analyzes 10+ behavioral signals simultaneously — faking all of them at once in a human-like way is a hard problem.

It won't stop a determined attacker running a full browser with manual-like automation (but at that point, rate limiting is your friend, not a captcha).

## Supported Form Plugins

- [WS Form Pro](https://wsform.com/)
- [Contact Form 7](https://contactform7.com/)
- [Formidable Forms](https://formidableforms.com/)
- [Gravity Forms](https://www.gravityforms.com/)
- [WPForms](https://wpforms.com/)
- [Fluent Forms](https://fluentforms.com/)
- [Ninja Forms](https://ninjaforms.com/)

Connectors are loaded conditionally — only when the corresponding form plugin is active.

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

1. Download the latest release ZIP from [GitHub Releases](https://github.com/willybahuaud/gaitcha-for-wp/releases)
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Upload the ZIP and activate

That's it. The plugin generates a cryptographic secret on activation. No API key, no account, no settings page to configure.

### Auto-updates

The plugin checks GitHub Releases for new versions and integrates with the WordPress update system. Updates show up in **Dashboard > Updates** like any other plugin.

## Usage

Each form plugin gets a **Gaitcha** field type in its builder. Add it to your form, publish, done.

On the frontend:
1. The form loads normally — no captcha visible
2. As soon as the user moves the mouse, touches the screen, or presses a key, a checkbox fades in
3. The user checks the box — behavioral data is collected silently in the background
4. On submit, the server scores the behavior and accepts or rejects

### Contact Form 7

Use the `[gaitcha]` form tag, or click the **gaitcha** button in the editor toolbar.

Optional custom label: `[gaitcha "I'm human"]`

### Other form plugins

Drag the **Gaitcha** field from the builder palette into your form. The label is configurable in the field settings.

## Privacy

This is the whole point:
- No data leaves your server — ever
- No cookies, no fingerprinting, no tracking pixels
- No external JavaScript loaded
- Nothing to declare in your privacy policy
- GDPR-friendly by design, not by configuration

## Hooks

### `gaitcha_bypass_admin`

Bypass captcha validation for logged-in admins. Enabled by default.

```php
// Disable admin bypass (admins must solve captcha too).
add_filter( 'gaitcha_bypass_admin', '__return_false' );
```

### `gaitcha_config`

Filter the Gaitcha configuration array before initialization.

```php
add_filter( 'gaitcha_config', function ( $config ) {
    $config['score_threshold'] = 0.6; // Stricter scoring (default: 0.5).
    $config['ttl']             = 60;  // Shorter token validity (default: 120s).
    return $config;
} );
```

Available options: `secret`, `ttl`, `score_threshold`, `debug`, `no_js_fallback`, `anti_replay`, `token_store`.

## How It Works

Gaitcha combines two layers:

**Behavioral analysis** — the JS client collects interaction data in a circular buffer: mouse trajectory curvature, angular jitter, direction reversals, endpoint deceleration, speed autocorrelation, keyboard dwell times, tab timing entropy, touch offset patterns. Three profiles (mouse, keyboard, touch) are scored independently; the highest wins.

**Stateless HMAC tokens** — each form load generates a random field name and a signed token. On submit, the server verifies the signature, checks the TTL, and scores the behavioral log. No session to manage, no database table to maintain.

Several "kill signals" cause immediate rejection: interaction under 100ms, zero movement before click, pixel-perfect center click, no keyboard activity before a keyboard-triggered check.

## Development

```bash
composer install
```

The core Gaitcha library is pulled via Composer (`wabeo/gaitcha`). The JS client (`assets/js/gaitcha.min.js`) is a pre-built bundle from the core library.

## License

GPL-2.0-or-later

## Author

[Willy Bahuaud](https://wabeo.fr)
