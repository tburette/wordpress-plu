# lpu-provisioning

Plugin de provisioning pour Le Paysan Urbain. Il applique la configuration et
le contenu de développement sur un multisite WordPress (site réseau + sous-sites
`paris`, `lyon`, `marseille`), idempotent : relançable sans risque.

Remplaçant des scripts shell `wp-env` (`.sh`), il exécute la même logique mais :

- en local → via WP-CLI en un seul aller-retour (`wp lpu provision`),
- sur l'hébergement OVH Perso (sans SSH, sans WP-CLI) → via un bouton dans
  l'administration du réseau.

## Étapes

1. Réseau multisite et sous-sites (`wpmu_create_blog`).
2. Thème `lepaysanurbain` : activation réseau + activation sur chaque site.
3. Locale française (`fr_FR`) : téléchargement du paquet + langue de chaque site + langue du profil admin réseau.
4. Logos de site (`custom_logo` + logo transparent).
5. Pages d'accueil des fermes.
6. Navigations d'en-tête + template parts.
7. Navigations de pied de page + template parts.
8. Page de test typographique.
9. Page de test des patterns.
10. Page d'accueil du réseau (assemblée depuis des patterns).

## Contenu

Les données sources qui ne viennent pas du thème sont dans `content/` (fragments
HTML, TSV,..).
Les URL codées en dur y sont remplacées par des jetons `{{NETWORK_URL}}`,
`{{FARM_PARIS_URL}}`, `{{FARM_LYON_URL}}`, `{{FARM_MARSEILLE_URL}}` résolus à
l'exécution selon le domaine réel du site.

## Usage local (WP-CLI)

```bash
wp-env run cli wp lpu provision          # application idempotente
wp-env run cli wp lpu provision --force # remplace une Home réseau déjà assemblée
```

## Usage pas de SSH/WP-CLI (OVH)

1. Téléverser et activer thème et plugins (sur site du **réseau**) :
   - Le thème `lepaysanurbain` : **Apparence → Thèmes → Ajouter → Téléverser un
     thème**.
   - Les plugins `lpu-split-section`, `nav-group` et `lpu-provisioning` :
     **Extensions → Ajouter → Téléverser une extension**, puis activer sur le réseau.
     > Au lancement, le provisioning active d'abord `lpu-split-section` et
     > `nav-group` sur le réseau , puis vérifie que le thème `lepaysanurbain` est
     > présent et que ces deux plugins sont bien activés **au niveau du réseau**.
     > C'est ce qui rend le bloc `lpu/nav-group` et les patterns
     > `lpu-split-section/*` disponibles sur les sites fermes.
2. Vérifications manuelles (une seule fois) :
   - le réseau multisite doit déjà être activé (OVH gère ça),
   - activer le thème `lepaysanurbain` sur le site principal avant de lancer.
3. Réglages du réseau → **Provisionnement LPU** → bouton _Provisionner le site_.
   Le paquet de langue `fr_FR` est téléchargé automatiquement par le bouton ; si
   l'hébergement bloque ce téléchargement (pas d'accès sortant à wordpress.org) le
   provisioning s'arrête avec un message d'erreur : installer le paquet
   manuellement dans **Réglages → Général → Langue du site**, puis relancer le
   bouton.

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

### Maintenabilité

La logique vit dans le trait `Lpu_Util` ; les étapes (`provision_*`) restent
courtes et lisibles. WP-CLI n'est utilisé que pour un affichage live ; tous les
retours de lignes sont collectés via `record_log()`/`get_log()`, ce qui permet
le déclenchement HTTP sans interface supplémentaire.
