# Gaitcha for WordPress

Plugin WordPress connecteur pour [Gaitcha](https://github.com/willybahuaud/gaitcha), un captcha comportemental auto-heberge.

## Architecture

```
gaitcha-for-wp/
├── gaitcha-for-wp.php              # Entry point, constantes, activation/deactivation
├── composer.json                   # Dependance wabeo/gaitcha ^0.5, PSR-4
├── assets/js/
│   ├── gaitcha.min.js              # Core JS (build depuis gaitcha)
│   ├── gaitcha-cf7.js              # Adapter JS pour Contact Form 7
│   ├── gaitcha-wsform.js           # Adapter JS pour WS Form
│   ├── gaitcha-formidable.js       # Adapter JS pour Formidable Forms
│   ├── gaitcha-gravityforms.js     # Adapter JS pour Gravity Forms
│   ├── gaitcha-wpforms.js          # Adapter JS pour WPForms
│   ├── gaitcha-fluentforms.js      # Adapter JS pour Fluent Forms
│   └── gaitcha-ninjaforms.js       # Adapter JS pour Ninja Forms
├── languages/                      # Traductions (FR, ES, IT, DE)
└── includes/                       # PSR-4: GaitchaWP\ (fichiers nommes par classe)
    ├── Plugin.php                  # Orchestrateur (Config, Endpoint, Connectors)
    ├── Endpoint.php                # REST POST /wp-json/gaitcha/v1/init
    ├── Updater.php                 # Auto-update via GitHub Releases
    ├── WPTokenStore.php            # Anti-replay via wp_options
    └── Connectors/                 # PSR-4: GaitchaWP\Connectors\
        ├── ConnectorInterface.php  # Interface: register_hooks()
        ├── CF7Connector.php        # CF7: form tag [gaitcha] + enqueue + spam filter
        ├── WSFormConnector.php     # WS Form: field type + enqueue + validation
        ├── GravityFormsConnector.php # GF: field type + enqueue + validation
        ├── GFFieldGaitcha.php      # GF: GF_Field extension
        ├── WPFormsConnector.php    # WPForms: field type + enqueue + validation
        ├── WPFormsFieldGaitcha.php # WPForms: WPForms_Field extension
        ├── FluentFormsConnector.php # FF: element type + enqueue + validation
        ├── FormidableConnector.php # Formidable: field type + enqueue + validation
        ├── FrmFieldGaitcha.php     # Formidable: FrmFieldType extension
        ├── NinjaFormsConnector.php # NF: field type + template + enqueue
        └── NFFieldGaitcha.php      # NF: NF_Abstracts_Field extension + validation
```

## Namespace & Autoload

- Namespace : `GaitchaWP\` (PSR-4 → `includes/`)
- Connectors : `GaitchaWP\Connectors\`
- Dependance core : `Gaitcha\` (via Composer)
- **Important** : le repertoire est `Connectors/` (majuscule) pour compatibilite Linux (case-sensitive)

## Constantes

- `GAITCHA_WP_VERSION` — Version plugin (doit matcher le header `Version:`)
- `GAITCHA_WP_FILE` — `__FILE__` du plugin
- `GAITCHA_WP_PATH` — `plugin_dir_path()`
- `GAITCHA_WP_URL` — `plugin_dir_url()`
- `GAITCHA_WP_BASENAME` — `plugin_basename()`

## Hooks exposes

### Filtres
- `gaitcha_config` — Modifier les options Config (secret, debug, ttl, anti_replay...)
- `gaitcha_bypass_admin` — Bypass pour admins (defaut: manage_options)

## Flux d'integration Gaitcha

1. **Client** : `gaitcha.min.js` detecte interaction → fetch token via `/gaitcha/v1/init`
2. **Endpoint** : genere token HMAC + fieldName aleatoire
3. **Check** : le JS serialise le log comportemental au moment du check (pas au submit)
4. **Submit** : les hidden fields (token + log) sont envoyes avec le formulaire
5. **Validation** : `ValidationOrchestrator->validate(wp_unslash($_POST))` verifie token + score comportemental
6. **Anti-replay** : le token est consomme uniquement si la soumission est acceptee
7. **Resultat** : accepted/rejected selon seuil (defaut 0.5)

## Ajouter un connecteur

1. Creer `includes/Connectors/{Name}Connector.php`
2. Implementer `ConnectorInterface` (methode `register_hooks()`)
3. Injecter `Config` + `Endpoint` via constructeur
4. Charger conditionnellement dans `Plugin::init()` (`class_exists()`)
5. Creer l'adapter JS dans `assets/js/gaitcha-{slug}.js`
6. Penser a `wp_unslash($_POST)` dans la validation (magic quotes WordPress)
7. Pour les formulaires AJAX : serialiser le log au check, pas au submit

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
- `Gaitcha\Config` — Configuration (secret, ttl, debug, seuil, anti_replay...)
- `Gaitcha\AbstractEndpoint` — Base pour l'endpoint init (methode `handleInit()`)
- `Gaitcha\ValidationOrchestrator` — Pipeline de validation complet
- `Gaitcha\ValidationResult` — Value object (isAccepted, getScore, getReason)
- `Gaitcha\TokenStoreInterface` — Interface anti-replay (has, add, checkAndAdd, purge)

## Contact Form 7

### Approche form tag
Gaitcha s'integre via un **form tag `[gaitcha]`** dans le template CF7.
Usage : `[gaitcha]` ou `[gaitcha "Label custom"]`.

### Hooks CF7 utilises
- `wpcf7_init` — Enregistre le form tag `[gaitcha]`
- `wpcf7_admin_init` — Enregistre le tag generator dans l'editeur
- `wpcf7_enqueue_scripts` — Enqueue les scripts gaitcha
- `wpcf7_spam` (filtre, priorite 9) — Valide via ValidationOrchestrator

### Rendu HTML du tag
`<div class="wpcf7-gaitcha" id="wpcf7-gaitcha-X" data-gaitcha-container="wpcf7-gaitcha-X" data-gaitcha-label="..."></div>`

---

## WS Form Pro

Le code source de reference est dans `/Users/willy/Downloads/alpha/plugins/ws-form-pro/includes`.

### Approche field type
Gaitcha s'integre comme un **field type custom** dans WS Form (groupe "Spam Protection").
L'utilisateur drag & drop le champ "Gaitcha" dans son formulaire.

### Hooks WS Form utilises
- `wsf_config_field_types` — Enregistre le field type "gaitcha"
- `wsf_submit_validate` — Valide les submissions
- `wsf-rendered` — Event jQuery pour les forms dynamiques (AJAX/popups)

### Rendu HTML du champ
`<div id="wsf-X-Y" data-gaitcha-container="wsf-X-Y"></div>`

---

## Gravity Forms

### Approche field type
Gaitcha s'integre via une extension de `GF_Field` (classe `GFFieldGaitcha`).
Champ disponible dans le groupe "Advanced Fields" du builder.

### Hooks GF utilises
- `gform_enqueue_scripts` — Enqueue conditionnel (uniquement si un form GF est present)
- `gform_validation` — Valide via ValidationOrchestrator
- `gform_post_render` — Event JS pour re-init apres AJAX

---

## WPForms

### Approche field type
Extension de `WPForms_Field` (classe `WPFormsFieldGaitcha`).
Champ dans le groupe "Standard Fields" du builder.

### Attention CSS
`div.wpforms-container-full *` force `position: static` sur TOUS les descendants.
Tout element positionne doit utiliser `!important`.

### Hooks WPForms utilises
- `wpforms_frontend_js` — Enqueue conditionnel
- `wpforms_process_validate_field` — Valide via ValidationOrchestrator

---

## Fluent Forms

### Approche element type
Gaitcha s'integre comme un element custom de type `tnc` (Terms & Conditions).

### Hooks FF utilises
- `fluentform/loaded` — Enregistre l'element
- `fluentform/rendering_form` — Enqueue conditionnel
- `fluentform/validate_input_item_input_checkbox` — Valide via ValidationOrchestrator

---

## Formidable Forms

### Approche field type
Extension de `FrmFieldType` (classe `FrmFieldGaitcha`).

### Hooks Formidable utilises
- `frm_available_fields` — Enregistre le type dans la palette
- `frm_get_field_type_class` — Mappe le type vers la classe
- `frm_validate_gaitcha_field_entry` — Valide via ValidationOrchestrator
- `wp_enqueue_scripts` — Enqueue global (pas de hook conditionnel disponible)

---

## Ninja Forms

### Approche field type
Extension de `NF_Abstracts_Field` (classe `NFFieldGaitcha`).
Template Underscore.js pour le rendu Backbone.

### Specificites NF
- NF soumet via jQuery AJAX (`nf_ajax_submit`), pas via `form.submit()`
- L'adapter JS utilise `jQuery.ajaxPrefilter` pour injecter les hidden fields
- La validation est cachee par requete (NF appelle `validate()` plusieurs fois)
- Le cache utilise `REQUEST_TIME_FLOAT` pour eviter les fuites en PHP persistant

### Hooks NF utilises
- `ninja_forms_register_fields` — Enregistre le field type
- `ninja_forms_output_templates` — Output le template Underscore
- `nf_display_enqueue_scripts` — Enqueue conditionnel
