# Gaitcha for WordPress

WordPress plugin that integrates [Gaitcha](https://github.com/willybahuaud/gaitcha), a self-hosted behavioral captcha, into popular form builders.

No external service, no API key, no user tracking. The captcha analyzes how the user interacts with a checkbox (mouse trajectory, keyboard timing, touch patterns) and scores the behavior server-side via HMAC-signed tokens.

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

The plugin generates an HMAC secret automatically on activation. No configuration needed.

### Auto-updates

The plugin checks GitHub Releases for new versions and integrates with the WordPress update system. Updates appear in the standard **Dashboard > Updates** screen.

## Usage

Each supported form plugin gets a **Gaitcha** field type in its form builder. Add the field to your form — that's it.

On the frontend:
1. The form loads without any captcha visible
2. On the first user interaction (mouse, keyboard, touch), a checkbox appears
3. The user checks the box — behavioral data is collected silently
4. On submit, the data is validated server-side

### Contact Form 7

Use the `[gaitcha]` form tag, or click the **gaitcha** button in the editor toolbar to generate it.

Optional custom label: `[gaitcha "I'm human"]`

### Other form plugins

Drag the **Gaitcha** field from the builder palette into your form. The label is configurable in the field settings.

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

Gaitcha combines two mechanisms:

- **Behavioral analysis**: mouse trajectory curvature, speed variation, angular jitter, endpoint deceleration, keyboard dwell time variance, tab timing entropy, touch offset patterns
- **Stateless HMAC tokens**: each captcha init returns a random field name + signed token. No session, no database, no external API

The scoring engine detects three interaction profiles (mouse, keyboard, touch) and uses whichever scores highest. Several "kill signals" cause immediate rejection: interaction under 100ms, no movement before click, pixel-perfect center click, no keyboard events before a keyboard-triggered check.

## Development

```bash
composer install
```

The core Gaitcha library is pulled via Composer (`wabeo/gaitcha`). The JS client (`assets/js/gaitcha.min.js`) is a pre-built bundle from the core library.

## License

GPL-2.0-or-later

## Author

[Willy Bahuaud](https://wabeo.fr)
