# Gaitcha for WordPress

Plugin WordPress connecteur pour [Gaitcha](https://github.com/willybahuaud/gaitcha), un captcha comportemental auto-heberge.

## Architecture

```
gaitcha-for-wp/
├── gaitcha-for-wp.php              # Entry point, constantes, activation/deactivation
├── composer.json                   # Dependance wabeo/gaitcha ^0.1, PSR-4
├── assets/js/
│   ├── gaitcha.min.js              # Core JS (build depuis gaitcha)
│   └── gaitcha-wsform.js           # Adapter JS pour WS Form
└── includes/                       # PSR-4: GaitchaWP\ (fichiers nommes par classe)
    ├── Plugin.php                  # Orchestrateur (Config, Endpoint, Connectors)
    ├── Endpoint.php                # REST POST /wp-json/gaitcha/v1/init
    ├── Updater.php                 # Auto-update via GitHub Releases
    └── connectors/                 # PSR-4: GaitchaWP\Connectors\
        ├── ConnectorInterface.php  # Interface: register_hooks()
        └── WSFormConnector.php     # WS Form: field type + enqueue + validation
```

## Namespace & Autoload

- Namespace : `GaitchaWP\` (PSR-4 → `includes/`)
- Connectors : `GaitchaWP\Connectors\`
- Dependance core : `Gaitcha\` (via Composer)

## Constantes

- `GAITCHA_WP_VERSION` — Version plugin
- `GAITCHA_WP_FILE` — `__FILE__` du plugin
- `GAITCHA_WP_PATH` — `plugin_dir_path()`
- `GAITCHA_WP_URL` — `plugin_dir_url()`
- `GAITCHA_WP_BASENAME` — `plugin_basename()`

## Hooks exposes

### Filtres
- `gaitcha_config` — Modifier les options Config (secret, debug, ttl...)
- `gaitcha_bypass_admin` — Bypass pour admins (defaut: manage_options)
- `wsf_config_field_types` — Enregistre le field type "gaitcha" dans WS Form

## Flux d'integration Gaitcha

1. **Client** : `gaitcha.min.js` detecte interaction → fetch token via `/gaitcha/v1/init`
2. **Endpoint** : genere token HMAC + fieldName aleatoire
3. **Submit** : le JS serialise le log comportemental dans le formulaire
4. **Validation** : `ValidationOrchestrator->validate($_POST)` verifie token + score comportemental
5. **Resultat** : accepted/rejected selon seuil (defaut 0.5)

## Ajouter un connecteur

1. Creer `includes/connectors/class-{slug}-connector.php`
2. Implementer `ConnectorInterface` (methode `register_hooks()`)
3. Injecter `Config` + `Endpoint` via constructeur
4. Charger conditionnellement dans `Plugin::init()` (`class_exists()`)
5. Creer l'adapter JS dans `assets/js/gaitcha-{slug}.js`

## Conventions

- WordPress Coding Standards
- PHPDoc sur toutes les classes/methodes
- JSDoc sur toutes les fonctions JS
- Pas de fonctions anonymes
- Commits : `type(scope): message` (EN + FR)
- Filtres pour l'extensibilite, pas de hardcode

## Dependance core Gaitcha

Le repo source est `/Users/willy/GitHub/gaitcha`.
Classes cles utilisees :
- `Gaitcha\Config` — Configuration (secret, ttl, debug, seuil...)
- `Gaitcha\AbstractEndpoint` — Base pour l'endpoint init (methode `handleInit()`)
- `Gaitcha\ValidationOrchestrator` — Pipeline de validation complet
- `Gaitcha\ValidationResult` — Value object (isAccepted, getScore, getReason)

## WS Form Pro

Le code source de reference est dans `/Users/willy/Downloads/alpha/plugins/ws-form-pro/includes`.

### Approche field type
Gaitcha s'integre comme un **field type custom** dans WS Form (groupe "Spam Protection").
L'utilisateur drag & drop le champ "Gaitcha" dans son formulaire — seuls les forms
qui contiennent ce champ sont proteges. Le label est celui du champ WS Form (editable
dans le builder).

### Hooks WS Form utilises
- `wsf_config_field_types` — Enregistre le field type "gaitcha"
- `wsf_submit_validate` — Valide les submissions (verifie si le form a un champ gaitcha)
- `wsf-rendered` — Event jQuery pour les forms dynamiques (AJAX/popups)
- `wsf-public` — Script handle pour conditionner l'enqueue

### Rendu HTML du champ
`<div id="wsf-X-Y" data-gaitcha-container="wsf-X-Y"></div>`
Le JS adapter detecte `[data-gaitcha-container]` et y injecte la checkbox gaitcha.
