# Gaitcha for WordPress

[English](README.md) · Français

Gaitcha ajoute une case de captcha aux formulaires WordPress, avec une vérification sur ton propre serveur. Le visiteur coche une case plutôt que de résoudre une grille d'images. Tu installes l'extension et tu ajoutes un champ à ton formulaire, sans compte à créer ni clé API à récupérer.

[Site](https://gaitcha.com/fr/) · [Démo](https://gaitcha.com/fr/#try-it) · [Guide WordPress](https://gaitcha.com/fr/wordpress/) · [Dépannage](https://gaitcha.com/fr/guides/troubleshooting/)

L'extension fournit des connecteurs pour huit constructeurs de formulaires, une protection optionnelle des formulaires natifs WordPress et des réglages d'apparence clairs, sombres ou minimalistes. Elle utilise la [bibliothèque PHP Gaitcha](https://github.com/willybahuaud/gaitcha) pour le score comportemental, avec la preuve de travail active par défaut.

## Installation

Prérequis : WordPress 6.0+ et PHP 7.4+.

1. Télécharge **l'archive `gaitcha-for-wp-…zip` fournie dans la release** depuis [GitHub Releases](https://github.com/willybahuaud/gaitcha-for-wp/releases/latest). Elle contient les dépendances Composer. L'archive **Source code** générée automatiquement par GitHub ne les contient pas.
2. Dans WordPress, ouvre **Extensions → Ajouter une extension → Téléverser une extension**, envoie le ZIP et active-le.
3. Ajoute un champ Gaitcha à un formulaire compatible, ou active un formulaire natif dans **Réglages → Gaitcha**.
4. Teste le formulaire publié en étant déconnecté. Les administrateurs contournent la vérification par défaut.

L'activation crée le secret de signature. La preuve de travail et l'anti-rejeu sont actifs par défaut ; l'état temporaire est conservé dans les options WordPress. Le core autonome utilise d'autres valeurs par défaut.

## Intégrations de formulaires

Les connecteurs se chargent quand l'extension de formulaire correspondante est active.

| Extension | Ajouter Gaitcha |
|---|---|
| [Contact Form 7](https://contactform7.com/) | Insérer `[gaitcha]` avant le bouton d'envoi |
| [Gravity Forms](https://www.gravityforms.com/) | Ajouter Gaitcha depuis les champs avancés |
| [WPForms](https://wpforms.com/) | Ajouter Gaitcha depuis les champs standards |
| [Fluent Forms](https://fluentforms.com/) | Ajouter l'élément Gaitcha dans le constructeur |
| [Formidable Forms](https://formidableforms.com/) | Choisir Gaitcha dans la liste des champs |
| [Ninja Forms](https://ninjaforms.com/) | Ajouter le champ Gaitcha |
| [WS Form Pro](https://wsform.com/) | Glisser Gaitcha depuis Spam Protection |
| [Elementor Pro Forms](https://elementor.com/) | Ajouter un champ de type Gaitcha au widget Forms |

Guides détaillés : [Contact Form 7](https://gaitcha.com/fr/guides/contact-form-7/), [Gravity Forms](https://gaitcha.com/fr/guides/gravity-forms/) et [Elementor Pro](https://gaitcha.com/fr/guides/elementor-pro/).

Le texte dans la case utilise le libellé traduit de l'extension. Dans Gravity Forms, tu peux modifier ou masquer le **libellé du champ** avec ses réglages habituels ; c'est un autre texte que celui de la case.

## Formulaires natifs WordPress

Dans **Réglages → Gaitcha**, tu peux activer séparément :

- La connexion (`wp-login.php`)
- L'inscription
- Le mot de passe oublié
- Les commentaires

Les quatre sont désactivés par défaut et concernent les formulaires natifs WordPress. Le paiement WooCommerce et les formulaires d'espace membre personnalisés ne font pas partie de ces intégrations.

## Apparence

La page de réglages propose deux choix indépendants :

- **Thème :** clair, sombre ou automatique pour suivre la préférence système du visiteur
- **Style :** Default pour le widget classique, ou Minimal pour des bordures plus fines et aucune ombre

Le thème et le style s'appliquent à tous les connecteurs. Ils ne changent pas les règles de vérification. Le core injecte son CSS avec `!important` ; tiens-en compte si tu ajoutes tes propres styles.

## Ce qui se passe sur un formulaire

1. Un emplacement non interactif réserve la place du widget.
2. Une interaction déclenche une requête vers `/wp-json/gaitcha/v1/init`.
3. Le client résout une preuve de travail, puis obtient un jeton signé et la case devient interactive.
4. Le journal d'interaction est capturé quand la case est cochée.
5. L'envoi transmet les champs de vérification à WordPress, où le core contrôle le jeton et évalue le journal.

Le widget prend en charge la souris, le clavier et le tactile. La preuve de travail se calcule en arrière-plan ; sa durée dépend de l'appareil du visiteur et de la difficulté configurée.

## Données et mises à jour

Les données d'interaction transitent du navigateur du visiteur vers ton serveur WordPress. Elles ne sont pas envoyées à un fournisseur de vérification captcha. Le client fourni ne pose pas de cookies de suivi, ne charge pas de pixels publicitaires et ne crée pas d'empreinte persistante du visiteur.

L'anti-rejeu conserve temporairement l'état des jetons et challenges dans `wp_options`. Ton hébergeur et d'autres extensions peuvent garder leurs propres logs. [Parcours des données et confidentialité](https://gaitcha.com/fr/privacy/).

L'extension consulte **GitHub Releases** et intègre les nouvelles versions à l'écran de mises à jour WordPress. Ces requêtes contactent GitHub et sont distinctes de la vérification captcha.

## Hooks pour les développeurs

L'extension expose deux filtres : `gaitcha_config` pour les réglages de vérification et `gaitcha_bypass_admin` pour l'exemption des administrateurs.

### `gaitcha_config`

Filtre le tableau de configuration avant l'initialisation du core. Enregistre-le dans une extension de site ou un mu-plugin : Gaitcha le lit sur `plugins_loaded`, avant le chargement du `functions.php` du thème. Le filtre reçoit un tableau et doit retourner le tableau modifié.

Par exemple, pour augmenter le seuil de score et raccourcir la durée de validité des jetons :

```php
/**
 * Augmente le seuil de score et raccourcit la validité des jetons.
 *
 * @param array $config Configuration Gaitcha.
 * @return array
 */
function mysite_gaitcha_config( array $config ): array {
    $config['score_threshold'] = 0.6;
    $config['ttl']             = 60;
    return $config;
}
add_filter( 'gaitcha_config', 'mysite_gaitcha_config' );
```

Un seuil plus élevé demande un meilleur score comportemental et peut rejeter davantage de soumissions. Teste le changement sur tes formulaires avant de le déployer.

#### Preuve de travail

La preuve de travail est active par défaut. Tu peux ajuster sa difficulté indépendamment du score comportemental :

```php
/**
 * Augmente le calcul demandé pour obtenir un jeton.
 *
 * @param array $config Configuration Gaitcha.
 * @return array
 */
function mysite_gaitcha_pow( array $config ): array {
    $config['pow_difficulty'] = 20;
    return $config;
}
add_filter( 'gaitcha_config', 'mysite_gaitcha_pow' );
```

Chaque bit supplémentaire double le calcul attendu : `20` demande environ quatre fois le travail du réglage par défaut `18`. Teste les appareils lents avant de l'augmenter. Pour désactiver la preuve de travail, utilise `$config['pow'] = false;` dans le filtre ; le score comportemental reste actif.

#### Référence des options

Voici les **valeurs par défaut de l'extension WordPress**, y compris celles héritées du core :

| Option | Valeur par défaut | Rôle |
|---|---|---|
| `secret` | Généré à l'activation | Secret de signature côté serveur, au moins 32 caractères |
| `ttl` | `120` | Durée de validité du jeton en secondes |
| `score_threshold` | `0.5` | Score comportemental minimum accepté, entre 0 et 1 |
| `debug` | Valeur de `WP_DEBUG`, ou `false` | Ajouter le détail du score aux résultats de validation |
| `no_js_fallback` | `'reject'` | Rejeter les envois sans jeton ; `'allow'` les accepte sans vérification |
| `anti_replay` | `true` | Contrôler les jetons et challenges déjà utilisés |
| `token_store` | `GaitchaWP\WPTokenStore` si l'anti-rejeu est actif | État temporaire dans les options WordPress ; accepte une implémentation de `Gaitcha\TokenStoreInterface` |
| `pow` | `true` | Exiger une preuve de travail avant de délivrer un jeton |
| `pow_difficulty` | `18` | Nombre de bits à zéro demandés, de 8 à 26 |
| `pow_challenge_ttl` | `90` | Durée de validité du challenge en secondes, minimum 10 |

`no_js_fallback: 'allow'` accepte aussi les soumissions automatisées sans jeton. Garde `'reject'` si chaque envoi doit passer la vérification.

La [référence du core](https://github.com/willybahuaud/gaitcha/blob/main/README.fr.md#preuve-de-travail-et-configuration) documente aussi les noms des champs. Modifie le tableau existant plutôt que de le remplacer, pour conserver le secret généré et les réglages de l'extension.

### `gaitcha_bypass_admin`

Filtre l'exemption de vérification. Sa valeur par défaut est `current_user_can( 'manage_options' )` : les administrateurs sont dispensés du captcha pour travailler sur les formulaires sans le remplir à chaque essai. Le filtre reçoit et retourne un booléen.

Pour soumettre aussi les administrateurs à la vérification :

```php
add_filter( 'gaitcha_bypass_admin', '__return_false' );
```

C'est utile pour tester le formulaire en restant connecté. Place ce filtre dans la même extension de site ou le même mu-plugin que tes autres réglages Gaitcha.

## Limites

Les données d'interaction côté client peuvent être fabriquées : une automatisation conçue pour Gaitcha peut donc passer la vérification. La preuve de travail ajoute un coût de calcul ; elle ne prouve pas que le visiteur est humain. Conserve la limitation de débit, la validation des champs et les protections de connexion de ton site.

Avant la mise en ligne, teste tes formulaires au clavier, sur mobile et avec les technologies d'assistance, y compris une nouvelle tentative après un rejet. Pour les formulaires AJAX, les popups ou les formulaires à plusieurs pages, teste le parcours d'envoi complet.

## Dépannage et développement

Si le widget ne se charge pas, vérifie ses fichiers JavaScript, l'endpoint REST, les exclusions de cache et les erreurs du navigateur. Si un formulaire coché est rejeté, examine l'expiration du jeton et les champs AJAX avant de changer le score. Le [guide de dépannage](https://gaitcha.com/fr/guides/troubleshooting/) couvre les cas courants.

Depuis une copie des sources, installe les dépendances PHP avec :

```bash
composer install
```

La bibliothèque core vient de Composer. `assets/js/gaitcha.min.js` est un bundle précompilé de cette bibliothèque. L'historique des versions est dans [CHANGELOG.md](CHANGELOG.md).

Pour un problème reproductible, indique les versions de WordPress, PHP et de l'extension de formulaires, la configuration du formulaire et les étapes de reproduction. Retire les données des visiteurs et les secrets avant de publier dans les [issues](https://github.com/willybahuaud/gaitcha-for-wp/issues).

## Auteur et licence

Développé par [Willy Bahuaud](https://wabeo.fr). Distribué sous [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).
