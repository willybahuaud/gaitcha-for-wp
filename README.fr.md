# Gaitcha for WordPress

[English](README.md) · Français

Gaitcha ajoute une case de captcha aux formulaires WordPress. L'extension évalue le journal d'interaction sur ton serveur WordPress et exige une preuve de travail par défaut. Tu n'as pas besoin de compte ni de clé API auprès d'un fournisseur de captcha.

[Site](https://gaitcha.com/fr/) · [Démo](https://gaitcha.com/fr/#try-it) · [Guide WordPress](https://gaitcha.com/fr/wordpress/) · [Dépannage](https://gaitcha.com/fr/guides/troubleshooting/)

## Avant l'installation

Gaitcha évalue les données de souris, de clavier et de tactile fournies par le navigateur. Un script peut obtenir un jeton, résoudre le calcul demandé et envoyer un journal fabriqué sans exécuter de navigateur. Une vérification réussie ne prouve pas que le visiteur est humain.

La preuve de travail ajoute un coût de calcul. Aucun taux de détection n'est publié pour Gaitcha, qui ne remplace ni la limitation de débit, ni la validation des champs, ni les protections de connexion. Teste les formulaires avec les modes de saisie de tes visiteurs et prévois une nouvelle tentative après un rejet.

La [bibliothèque autonome](https://github.com/willybahuaud/gaitcha) documente le protocole de vérification. Ce dépôt fournit son intégration WordPress.

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

Le texte dans la case utilise le libellé traduit de l'extension. L'ancien exemple de libellé personnalisé pour CF7 ne s'applique plus. Dans Gravity Forms, tu peux modifier ou masquer le **libellé du champ** avec ses réglages habituels ; c'est un autre texte que celui de la case.

Teste les formulaires AJAX, popups, champs conditionnels et formulaires à plusieurs pages dans ta configuration réelle. La présence d'un connecteur ne garantit pas la compatibilité avec tous les modules complémentaires ou thèmes.

## Formulaires natifs WordPress

Dans **Réglages → Gaitcha**, tu peux activer séparément :

- La connexion (`wp-login.php`)
- L'inscription
- Le mot de passe oublié
- Les commentaires

Les quatre sont désactivés par défaut. Ces intégrations ciblent les formulaires natifs WordPress. Les pages de connexion personnalisées, extensions d'espace membre et paiements WooCommerce demandent des vérifications de compatibilité séparées.

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

La signature protège le jeton, pas la véracité du journal. Le temps de calcul varie selon l'appareil et la difficulté configurée. La souris, le clavier et le tactile sont pris en charge, ce qui ne suffit pas à établir l'accessibilité pour tous les utilisateurs et formulaires.

## Données et mises à jour

Les données d'interaction transitent du navigateur du visiteur vers ton serveur WordPress. Elles ne sont pas envoyées à un fournisseur de vérification captcha. Le client fourni ne pose pas de cookies de suivi, ne charge pas de pixels publicitaires et ne crée pas d'empreinte persistante du visiteur.

L'anti-rejeu conserve temporairement l'état des jetons et challenges dans `wp_options`. Ton hébergeur et d'autres extensions peuvent garder leurs propres logs. Décris le traitement utilisé sur ton site ; installer Gaitcha ne règle pas toutes tes obligations concernant les données personnelles. [Parcours des données et confidentialité](https://gaitcha.com/fr/privacy/).

L'extension consulte **GitHub Releases** et intègre les nouvelles versions à l'écran de mises à jour WordPress. Ces requêtes contactent GitHub et sont distinctes de la vérification captcha.

## Hooks pour les développeurs

### Configurer la vérification

Utilise `gaitcha_config` pour modifier les options du core. Place ton filtre dans une extension de site ou un mu-plugin :

```php
/**
 * Ajuste la durée de validité du jeton pour ce site.
 *
 * @param array $config Configuration Gaitcha.
 * @return array
 */
function mysite_gaitcha_config( array $config ): array {
    $config['ttl'] = 120;
    return $config;
}
add_filter( 'gaitcha_config', 'mysite_gaitcha_config' );
```

Les autres options incluent `score_threshold` (`0.5` par défaut), `pow` (`true` dans l'extension), `pow_difficulty` (`18`), `pow_challenge_ttl` (`90` secondes), `anti_replay` (`true`), `token_store`, `debug` et `no_js_fallback` (`'reject'`). Voir la [configuration du core](https://github.com/willybahuaud/gaitcha/blob/main/README.fr.md#preuve-de-travail-et-configuration).

Augmenter le seuil peut rejeter davantage de soumissions légitimes. Augmenter la difficulté PoW impose aussi plus de calcul aux visiteurs. Teste les deux avant de changer les réglages en production.

`no_js_fallback: 'allow'` ignore la vérification quand le jeton manque. Il ne distingue pas un visiteur qui a désactivé JavaScript d'un script qui soumet directement des données.

### Inclure les administrateurs dans les contrôles

```php
add_filter( 'gaitcha_bypass_admin', '__return_false' );
```

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
