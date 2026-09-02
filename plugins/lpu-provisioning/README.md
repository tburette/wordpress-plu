# lpu-provisioning

Plugin de provisioning pour Le Paysan Urbain. Il applique la configuration et
le contenu de développement sur un multisite WordPress (site réseau + sous-sites
`paris`, `lyon`, `marseille`), idempotent : relançable sans risque.

Remplaçant des scripts shell `wp-env` (`.sh`), il exécute la même logique mais :

- en local → via WP-CLI en un seul aller-retour (`wp lpu provision`),
- sur l'hébergement OVH Perso (sans SSH, sans WP-CLI) → via un bouton dans
  l'administration du réseau.

## Étapes

1. Réseau multisite et sous-sites manquants (`wpmu_create_blog`).
2. Thème `lepaysanurbain` : activation réseau + activation sur chaque site.
3. Locale française (`fr_FR`).
4. Logos de site (`custom_logo` + logo transparent).
5. Pages d'accueil des fermes.
6. Navigations d'en-tête + template parts.
7. Navigations de pied de page + template parts.
8. Page de test typographique.
9. Page de test des patterns.
10. Page d'accueil du réseau (assemblée depuis des patterns).

## Contenu

Toutes les données sources sont dans `content/` (fragments HTML, TSV). Les URL
codées en dur y sont remplacées par des jetons `{{NETWORK_URL}}`,
`{{FARM_PARIS_URL}}`, `{{FARM_LYON_URL}}`, `{{FARM_MARSEILLE_URL}}` résolus à
l'exécution selon le domaine réel du site.

## Usage local (WP-CLI)

```bash
wp-env run cli wp lpu provision          # application idempotente
wp-env run cli wp lpu provision --force # remplace une Home réseau déjà assemblée
```

## Usage OVH (pas de SSH/WP-CLI)

1. Téléverser le thème `lepaysanurbain` et ce plugin dans wp-admin
   (Extensions/Distribution → Téléverser), l'activer sur le réseau.
2. Vérifications manuelles (une seule fois, à documenter) :
   - le réseau multisite doit déjà être activé (OVH gère ça),
   - activer le thème `lepaysanurbain` sur le site principal avant de lancer,
   - installer le paquet de langue `fr_FR` dans Réglages → Langue.
3. Réglages du réseau → **Provisionnement LPU** → bouton *Provisionner le site*.

## Différences local / OVH

| Étape | Local (WP-CLI) | OVH (bouton) |
|-------|--------|------|
| Réseau + sous-sites | auto | auto (les fermes existent déjà → vérifie) |
| Thème | auto | thème téléversé + activation à faire |
| Langue `fr_FR` | auto (`wp language core install`, pas fait ici) | pas d'API → manuel wp-admin |
| Logos / pages / onglets / Home | auto | auto |
| Étape palette typographique | shell | shell |
| Vérif réseau multi-sites | shell | shell |

## Fichiers

- `lpu-provisioning.php` : amorce (header, `init` tardif pour la commande CLI,
  `network_admin_menu` pour l'écran).
- `inc/class-lpu-provisioner.php` : moteur de provisioning (pas de dépendance
  CLI : fonctionne aussi en HTTP).
- `inc/class-lpu-provision-cli.php` : mince enveloppe `wp lpu provision`.
- `inc/class-lpu-provision-admin.php` : écran réseau + handler `admin-post`.
- `content/` : TSV et fragments HTML.

## Maintenabilité

Le moteur (`Lpu_Provisioner`) n'utilise WP-CLI que pour un affichage live ;
tous les retour de lignes sont collectés via `record_log()`/`get_log()`, ce qui
permet le déclenchement HTTP sans interface supplémentaire.
