# lpu-provisioning

Plugin de provisioning pour Le Paysan Urbain. Il applique la configuration et
le contenu de développement sur un multisite WordPress (site réseau + sous-sites
`paris`, `lyon`, `marseille`). La plupart des étapes sont idempotentes et
relançables ; la Home réseau est reconstruite à chaque exécution.

Le plugin centralise la configuration et le contenu de développement, et peut
être exécuté :

- en local → via WP-CLI en un seul aller-retour (`wp lpu provision`),
- sur l'hébergement OVH Perso (sans SSH, sans WP-CLI) → via un bouton dans
  l'administration du réseau.

## Étapes

1. Installation de Query Monitor depuis WordPress.org + activation réseau des plugins locaux.
2. Réseau multisite et sous-sites (`wpmu_create_blog`).
3. Thème `lepaysanurbain` : activation réseau + activation sur chaque site.
4. Locale française (`fr_FR`) : téléchargement du paquet + langue de chaque site + langue du profil admin réseau.
5. Logos de site (`custom_logo` + logo transparent).
6. Pages d'accueil des fermes.
7. Navigations d'en-tête + template parts.
8. Navigations de pied de page + template parts.
9. Page de test typographique.
10. Page de test des patterns.
11. Page d'accueil du réseau (assemblée depuis des patterns).

## Contenu

Les données sources qui ne viennent pas du thème sont dans `content/` (fragments
HTML, TSV,..).
Les URL codées en dur y sont remplacées par des jetons `{{NETWORK_URL}}`,
`{{FARM_PARIS_URL}}`, `{{FARM_LYON_URL}}`, `{{FARM_MARSEILLE_URL}}` résolus à
l'exécution selon le domaine réel du site.

## Prérequis avant usage

1. Mettre en place le multisite :
   https://developer.wordpress.org/advanced-administration/multisite/create-network/
   - configurer le DNS
   - Activer le multisite. Dans wp-config.php :
     `define( 'WP_ALLOW_MULTISITE', true );`
   - Aller à la page `/wp-admin/network.php` et activer le multisite.
     Suivre les étapes (configurer wp-config.php et .htaccess).
   - Se déconnecter et se reconnecter. Aller à la page Admin du réseau → Sites.
     Créer chaqe sous-site.

2. Téléverser le thème et plugins (sur site du **réseau**) :
   - Le thème `lepaysanurbain` : **Apparence → Thèmes → Ajouter → Téléverser un
     thème**.
   - Les plugins locaux du projet et le plugin de provisioning :
     **Extensions → Ajouter → Téléverser une extension**.
3. Activer sur le réseau le plugin de provisioning.

## Usage local (WP-CLI)

```bash
wp-env run cli wp lpu provision # applique la configuration et reconstruit la Home réseau
```

## Usage pas de SSH/WP-CLI (OVH)

Aller sur le site Admin du réseau → Réglages → Provisionnement LPU
(/wp-admin/network/settings.php?page=lpu-provisioning).
Appuyer sur le bouton "Provisionner le site".

Vérifier l'installation de Query Monitor et de la langue française.
Si ce n'est pas réalisé (pas d'accès sortant à wordpress.org par exemple),
installer manuellement.
Pour le paquet de langue fr installer le paquet manuellement dans
**Réglages → Général → Langue du site**, puis relancer le bouton de provisioning.

## Implémentation

### Fichiers

- `lpu-provisioning.php` : amorce (header, enregistrement immédiat de la
  commande WP-CLI quand WP_CLI est défini, `network_admin_menu` pour l'écran).
- `inc/class-lpu-util.php` : trait `Lpu_Util` — toute la machinerie (lecture des
  fragments/TSV, changement de contexte blog, import média, assemblage des pages,
  journalisation via `record_log()`/`get_log()`). Pas de dépendance CLI :
  fonctionne aussi en HTTP.
- `inc/class-lpu-provisioner.php` : `Lpu_Provisioner`, les étapes de provisioning
  lisibles qui utilisent le trait `Lpu_Util`.
- `inc/class-lpu-provision-cli.php` : mince enveloppe `wp lpu provision`.
- `inc/class-lpu-provision-admin.php` : écran réseau + handler `admin-post`.
- `content/` : TSV et fragments HTML.
