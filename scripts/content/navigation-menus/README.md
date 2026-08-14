# Données de navigation WordPress

Ce répertoire contient les fragments Gutenberg qui servent à provisionner les
menus `wp_navigation` du réseau local. Ce ne sont ni des pages HTML autonomes,
ni la spécification graphique complète du header.

Pour comprendre le menu avant de modifier ces données, lire la [fiche de
référence du menu](../../../../../design/design%20site%20Fanny/menu-design-brief.md).
Elle renvoie vers les [règles de design](../../../../../design/design%20site%20Fanny/règles-design-site-Le_Paysan_Urbain.txt),
l'[arborescence éditoriale](../../../../../contextes/site%20context/navigation%20site.txt)
et le [plan d'implémentation du thème](../../../theme-implementation-plan.md).

## Organisation du répertoire

- `network.html` contient les blocs du menu du site réseau ;
- `local.html` contient les blocs communs aux menus des fermes Paris, Lyon et
  Marseille ;
- `navigation-sites.tsv` associe chaque hostname local au bon fichier de
  contenu avec trois colonnes séparées par des tabulations ;
- `setup.sh` crée ou retrouve le post `wp_navigation` de chaque site et
  enregistre son identifiant dans `lpu_navigation_id`.

Les fichiers `.html` contiennent uniquement les blocs enfants du post
`wp_navigation`. Il ne faut pas ajouter un bloc `wp:navigation` englobant : le
bloc Navigation du template partagé fournit déjà ce conteneur.

## Contrat de contenu

Le menu réseau contient :

- `Le Projet` comme lien direct ;
- `Nos Fermes` comme rubrique déployable avec Lyon, Marseille et Paris ;
- `Contact` comme lien direct.

Les menus de ferme contiennent :

- `Qui sommes-nous` avec cinq sous-rubriques ;
- `Nos Activités` avec quatre sous-rubriques ;
- `Nos Cultures` avec cinq sous-rubriques ;
- `Nos Projets & Initiatives` avec quatre sous-rubriques ;
- `Infos pratiques` comme lien direct.

« Agir avec nous » n'est pas une entrée de menu. Le sélecteur visuel des
fermes sur la Home réseau est une section de contenu indépendante du
sous-menu `Nos Fermes`.

## Destinations présentes dans le prototype

Les ancres utilisées dans `network.html` et `local.html` servent actuellement
à tester la structure du menu. Elles ne constituent pas toutes les URLs finales
du site.

- les destinations de `Le Projet` et `Contact` doivent encore être choisies
  entre pages dédiées et ancres de la Home réseau ;
- les liens des fermes utilisent les hostnames `*.lepaysanurbain.test:8888`
  propres à `wp-env` ;
- les ancres des menus locaux correspondent aux identifiants de sections du
  prototype et devront être remplacées ou confirmées lorsque les pages et
  leurs URLs seront assemblées.

Ne pas modifier ces destinations en inventant une architecture d'URL sans
mettre à jour la fiche de référence et le contenu WordPress correspondant.

## Provisioning multisite

Le header est partagé par les quatre sites, mais chaque site possède son propre
post `wp_navigation`. Le thème ne contient donc pas de `ref` numérique fixe.
Le filtre du thème utilise l'option `lpu_navigation_id` du site courant pour
associer le bon menu au bloc Navigation partagé.

`setup.sh` est idempotent :

- si le menu `menu-principal` n'existe pas, il est créé à partir du fragment
  indiqué dans `navigation-sites.tsv` ;
- s'il existe déjà, son contenu édité dans WordPress n'est pas écrasé ;
- son identifiant est enregistré dans `lpu_navigation_id`.

Les fichiers de ce répertoire sont donc la source reproductible utilisée lors
de la création d'un environnement, pas une synchronisation destructive de la
base WordPress.

## Ce que ce répertoire ne décide pas

Le comportement visuel et accessible du menu — centrage du logo, méga-menu
contextuel, ouverture au clic/focus/survol, clavier, hamburger mobile, fond
opaque ou transparent — est documenté dans la fiche de référence et dans les
règles de design. Le template partagé utilise le modèle natif « lien parent +
chevron de déploiement séparé » : une rubrique parente conserve sa destination
et son chevron ouvre le panneau. Il ne faut pas redéfinir cette présentation
dans les fragments de contenu.

Le bloc Navigation natif doit être testé avant d'ajouter du JavaScript. Le
fichier `assets/js/header.js` ne doit être créé que si un manque précis du bloc
Navigation est démontré.
