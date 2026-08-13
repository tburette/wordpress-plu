# Plan d’implémentation — thème bloc `lepaysanurbain`

## Objectif

Construire un seul thème WordPress bloc, partagé par les quatre sites du réseau :

- `lepaysanurbain.test:8888` — site réseau ;
- `paris.lepaysanurbain.test:8888` ;
- `lyon.lepaysanurbain.test:8888` ;
- `marseille.lepaysanurbain.test:8888`.

Le premier jalon est une démonstration utile, pas un thème « fini » :

1. le thème est activé sur les quatre sites ;
2. on crée les patterns nécessaires pour réaliser la page d’accueil réseau uniquement ;
3. les logos, les menus et le contenu sont propres à chaque site ;
4. la charte (couleurs, typographies, espacements, boutons, motifs) est cohérente dans l’éditeur comme sur le front ;
5. un éditeur local peut construire une page sans toucher aux fichiers du thème ;
6. le mobile est pris en compte dès le début.

Le but est d’obtenir vite une base saine, de réaliser la page d’accueil réseau (Home), puis d’étendre les pages et les contenus. Ne pas ajouter de CPT, de bloc personnalisé ou de plugin de production avant d’avoir prouvé qu’un bloc cœur ne suffit pas.

La page réseau (Home) à faire en premier est Home de `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/`
ses sections sont décrites dans `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/sections/sections-Home.txt` et dans `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/sections/description-sections.txt`.

Tous les chemins et toutes les commandes ci-dessous sont relatifs à `site/wordpress-lpu/`, sauf mention contraire. Ne jamais essayer d'écrire dans une installation wordpress directement (`wordpress/`) : l'installation de wordpress est un répertoire généré par `wp-env` et il peut être effacé ou recréé n'importe quand.

## Repères rapides sur les block themes

Le développeur connaît déjà Gutenberg ; ce rappel sert à remettre les bons séparateurs au bon endroit.

- `theme.json` est le contrat visuel du thème. Il fournit les tokens utilisables par le front-end et l’éditeur : palette, tailles, familles de police, espacements, largeurs et styles globaux.
- Un **template** (`templates/*.html`) est le squelette d’un type de page. Il contient du markup de blocs, pas du PHP.
- Un **template part** (`parts/*.html`) est un morceau de template réutilisable, typiquement le header et le footer.
- Une **Page** contient le contenu éditorial propre à un site. Le bloc `post-content` dans le template affiche ce contenu. C’est ce qui permet aux quatre accueils de diverger avec un seul thème.
- Un **pattern** (`patterns/*.php`) est une composition de blocs insérable dans une Page. Ce sont les bandeaux de Fanny, pas des templates verrouillés.
- `alignwide` utilise la largeur large définie dans `theme.json` ; `alignfull` va d’un bord à l’autre de la fenêtre. Les bandeaux colorés ou photographiques sont donc généralement `alignfull`.

Deux sources peuvent exister pour un template ou un template part : le fichier du thème et une personnalisation enregistrée dans la base par le Site Editor. Le fichier doit rester la source de vérité. Utiliser le Site Editor pour explorer ou tester, mais reporter les changements validés dans les fichiers et supprimer les personnalisations de test avant de conclure que le rendu est correct.

## Règles de mise en œuvre

- Utiliser les blocs cœur en premier : Group, Cover, Columns, Image, Heading, Paragraph, Buttons, Navigation, Site Logo, Social Icons, Query Loop et Search.
- Le code, les noms de fichiers et les classes CSS sont en anglais ; les titres visibles dans Gutenberg et le contenu du site sont en français.
- Les couleurs, tailles et familles doivent venir de `theme.json`, jamais de valeurs arbitraires ajoutées dans les patterns.
- Le CSS sert aux traitements que `theme.json` ne sait pas décrire proprement : motifs, hover d’images, disposition spécifique du header, focus et responsive fin.
- Le JavaScript est une exception. Le bloc Navigation couvre déjà une grande partie du menu responsive, du clavier et des attributs ARIA ; ne lui ajouter du JS que lorsqu’un essai démontre un manque précis.
- Pour le premier jalon, conserver `assets/js/header.js` comme JavaScript vanilla directement chargeable. Ne pas introduire de build npm uniquement pour une cinquantaine de lignes sans imports. Si un build devient nécessaire plus tard, l’ajouter au `package.json` existant de `wordpress-lpu/`, pas dans un second projet npm imbriqué.
- Un environnement wordpress de développement existe via wp-env (à utiliser via `npm run env:status`, `npm run env:start`,..). Cet environnement peut être supprimé et recréé à tout moment. Les fichiers de l'installation wordpress de cet environnement ainsi que la base de données peuvent disparaitre. Il ne faut donc pas ajouter des données directement dans le répertoire de l'environnement wordpress ou la base de donnée.
- Il existe une installaton wordpress multisite séparée de test disponible en ligne. Lien : PAS DIVULGÉ POUR LE MOMENT. Il faudra pouvoir installer le site créé sur cette autre installation wordpress (pour pouvoir partager le nouveau site avec le client avant la mise en production).

---

## Étape 0 — Vérifier l’environnement et les assets avant de coder

Cette étape évite de découvrir une police absente ou une configuration multisite cassée lorsque le header est déjà construit.

**Fichiers à consulter, pas encore à modifier**

- `wordpress-lpu/.wp-env.json`
- `wordpress-lpu/scripts/setup-multisite-network.sh`
- `wordpress-lpu/scripts/content/`
- `wordpress-lpu/README.md`
- le kit graphique et la livraison de Fanny dans `../../design/`

**Actions**

Depuis `wordpress-lpu/` :

```sh
npm run env:start
npm run env:multisite:verify
npm run env:cli -- core version
npm run env:cli -- site list --fields=blog_id,domain,path,url
```

Vérifier les polices avant de les référencer dans `theme.json` :

- Ruden Regular, DM Sans Regular et Bold, Oswald Medium, Compagnon ;
- fichiers valides et licence de diffusion web confirmée ;
- WOFF2 disponible ou conversion possible depuis un OTF/TTF licencié ;
- aucune police ne doit venir d’un CDN en production.

Ruden Regular est maintenant disponible dans `design/design site Fanny/fonts/myfonts_order_7268462657706/Ruden-Regular.otf` et sa licence est confirmée. La livraison actuelle contient encore seulement une variante italique de DM Sans ; les faces Regular et Bold seront intégrées plus tard, avant la validation finale.

**Résultat attendu**

Les quatre URLs locales répondent, les sites enfants existent et les sources de chaque police à utiliser sont identifiées.

---

## Étape 1 — Créer et monter le thème minimal

Pour ce projet, le block theme repose sur `style.css`, `theme.json` et `templates/index.html`. Techniquement, c’est le template `index.html` qui distingue un thème bloc ; `theme.json` porte ici le système de design. À ce stade, le rendu peut être très simple : l’objectif est seulement de vérifier le montage et l’activation.

**Fichiers à créer**

```text
wordpress-lpu/themes/lepaysanurbain/
├── style.css
├── functions.php
├── theme.json
├── assets/
│   ├── css/theme.css
│   ├── fonts/
│   ├── images/
│   └── js/header.js
├── parts/
│   ├── header.html
│   └── footer.html
├── patterns/
└── templates/
    └── index.html
```

**Fichiers à modifier**

- `wordpress-lpu/.wp-env.json`
- `wordpress-lpu/themes/lepaysanurbain/style.css`
- `wordpress-lpu/themes/lepaysanurbain/functions.php`
- `wordpress-lpu/themes/lepaysanurbain/theme.json`
- `wordpress-lpu/themes/lepaysanurbain/templates/index.html`

**Actions**

1. Ajouter `"./themes/lepaysanurbain"` au tableau `themes` de `.wp-env.json`.
2. Écrire dans `style.css` le seul en-tête nécessaire à WordPress (`Theme Name`, `Text Domain`, `Requires at least`, `Requires PHP`, etc.). Ne pas y mettre le CSS applicatif : il vivra dans `assets/css/theme.css`.
3. Créer un `functions.php` minimal qui protège l’accès direct et charge le CSS du thème sur le front-end et dans l’éditeur. Les traitements spécifiques viendront plus tard.
4. Créer un `index.html` temporaire avec header, un `main` et footer ; il sera complété à l’étape 3.
5. Redémarrer l’environnement pour que le montage de thème soit pris en compte, rendre le thème disponible au réseau, puis l’activer site par site :

```sh
npm run env:start
npm run env:cli -- theme enable lepaysanurbain --network

for site_url in lepaysanurbain.test:8888 paris.lepaysanurbain.test:8888 lyon.lepaysanurbain.test:8888 marseille.lepaysanurbain.test:8888; do
  npm run env:cli -- theme activate lepaysanurbain --url="$site_url"
done
```

`theme enable --network` rend le thème disponible ; il ne l’active pas sur les sites. C’est pourquoi l’activation reste explicite dans la boucle.

**Contrôle**

```sh
npm run env:cli -- theme list --url=lepaysanurbain.test:8888
npm run env:cli -- theme list --url=marseille.lepaysanurbain.test:8888
```

Le thème doit être actif sur les deux sites vérifiés et aucune source ne doit se trouver sous `wordpress/wp-content/`.

---

## Étape 2 — Poser le système de design et les assets

Faire cette étape avant de construire des patterns : le but est que les blocs ajoutés dans Gutenberg disposent d’emblée des bonnes options.

### 2.1 `theme.json` : les tokens autorisés

**Fichier à modifier**

- `wordpress-lpu/themes/lepaysanurbain/theme.json`

**À déclarer**

| Famille        | Décision initiale                                                                                                                                                                                                                                                                      |
| -------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Palette        | Les huit couleurs du kit, avec des slugs sans accent. Désactiver la palette par défaut et les couleurs personnalisées ; les quatre couleurs secondaires sont destinées aux motifs et bandeaux graphiques.                                                                              |
| Typographie    | Familles de police locales, tailles nommées et une hiérarchie utilisable par les blocs Heading et Paragraph. Prévoir le texte courant DM Sans Regular entre 16px, avec un interlignage généreux ; désactiver les tailles libres pour la première démo si cela protège mieux la charte. |
| Espacement     | Une petite échelle de 6 à 8 valeurs ; utiliser ces presets dans les margins et paddings des patterns.                                                                                                                                                                                  |
| Layout         | `contentSize` autour de `700px`, `wideSize` autour de `1200px`. Ajuster après comparaison avec les maquettes, pas avant.                                                                                                                                                               |
| Template parts | Déclarer `header` et `footer`, chacun avec `name`, `title` et `area`.                                                                                                                                                                                                                  |
| Styles globaux | Fond écru, texte principal, liens, headings, boutons et block gap.                                                                                                                                                                                                                     |

Palette à reprendre du kit :

| Slug         | Valeur    |
| ------------ | --------- |
| `vert-fonce` | `#153d30` |
| `vert-grise` | `#e2e6e4` |
| `ecru`       | `#f8f8f0` |
| `jaune`      | `#fdf69a` |
| `vert-moyen` | `#3a9b64` |
| `framboise`  | `#a861a3` |
| `bleu`       | `#2e65ad` |
| `brun`       | `#995d3b` |

Choisir la version de `theme.json` compatible avec la version de WordPress réellement exécutée à l’étape 0. Le schéma aide l’éditeur, mais c’est le core en cours qui décide ce qui est accepté.

### 2.2 Polices locales

**Fichiers à créer ou modifier**

- `wordpress-lpu/themes/lepaysanurbain/assets/fonts/*`
- `wordpress-lpu/themes/lepaysanurbain/theme.json`

**Actions**

1. Copier uniquement les faces validées dans `assets/fonts/`, avec des noms simples et stables.
2. Convertir les OTF/TTF retenues en WOFF2 si nécessaire, sans modifier les originaux livrés.
3. Déclarer chaque face via `fontFace`, avec son poids, son style et `fontDisplay: "swap"`.
4. Assigner clairement les rôles : Ruden pour les grands titres, DM Sans pour le texte et les boutons, Oswald pour les labels/surtitres. Ne charger Compagnon qu’après avoir confirmé sa fonction dans le site.

### 2.3 CSS global et éléments graphiques

**Fichiers à créer ou modifier**

- `wordpress-lpu/themes/lepaysanurbain/assets/css/theme.css`
- `wordpress-lpu/themes/lepaysanurbain/functions.php`
- `wordpress-lpu/themes/lepaysanurbain/assets/images/*`

**Actions**

- Charger `theme.css` sur le front-end et dans l’éditeur ; sinon un pattern peut sembler juste dans le navigateur mais faux dans Gutenberg.
- Mettre dans `theme.json` les styles ordinaires des boutons, titres, couleurs et typographies ; réserver `theme.css` au hover, aux motifs, aux détails de layout et aux règles responsive.
- Construire le style de bouton contour vert / contour blanc à partir des variables `--wp--preset--…`, sans `!important`.
- Ajouter `:focus-visible` et `prefers-reduced-motion` dès maintenant ; le zoom discret des images ne doit pas devenir une animation obligatoire.
- Copier les motifs SVG sous des noms ASCII stables, par exemple `motif-1-bandeau.svg`. Les référencer comme arrière-plans CSS : ils sont décoratifs, peuvent être mis en cache et ne doivent pas encombrer le contenu éditorial.
- Conserver les logos SVG dans les assets si nécessaire, mais utiliser des PNG compatibles dans la médiathèque pour le bloc Site Logo. WordPress n’autorise pas les SVG par défaut dans la médiathèque.

**Contrôle**

Dans une Page de test, vérifier à la fois dans Gutenberg et sur le front-end : palette limitée, familles disponibles, boutons cohérents, fond écru, `alignwide` et `alignfull` visibles.

---

## Étape 3 — Construire le cadre partagé : templates, header et footer

Le template est le cadre ; le contenu reste dans les Pages. Garder les templates minces rend le multisite maintenable.

**Fichiers à créer ou modifier**

- `wordpress-lpu/themes/lepaysanurbain/templates/front-page.html`
- `wordpress-lpu/themes/lepaysanurbain/templates/page.html`
- `wordpress-lpu/themes/lepaysanurbain/templates/single.html`
- `wordpress-lpu/themes/lepaysanurbain/templates/index.html`
- `wordpress-lpu/themes/lepaysanurbain/templates/404.html`
- `wordpress-lpu/themes/lepaysanurbain/parts/header.html`
- `wordpress-lpu/themes/lepaysanurbain/parts/footer.html`
- `wordpress-lpu/themes/lepaysanurbain/assets/css/theme.css`
- éventuellement `wordpress-lpu/themes/lepaysanurbain/assets/js/header.js`

### 3.1 Templates de base

Pour `front-page.html`, `page.html` et `single.html`, utiliser la même forme :

1. template part `header` avec `tagName: "header"` ;
2. un Group avec `tagName: "main"` ;
3. `post-content` à l’intérieur ;
4. template part `footer` avec `tagName: "footer"`.

Le `main` est important : il rend le document plus clair pour les lecteurs d’écran et permet à WordPress d’exposer un lien d’évitement vers le contenu.

Ne pas mettre les bandeaux de l’accueil dans `front-page.html`. Ce fichier doit contenir seulement le cadre et `post-content`.

`index.html` est le fallback pour les listes de contenus : il doit utiliser un Query Loop minimal, pas un `post-content` isolé. `404.html` doit contenir un message, un lien de retour et/ou un bloc Search. Ce ne sont pas encore les écrans définitifs, mais ils doivent rester fonctionnels.

### 3.2 Header : valider le point risqué du multisite tôt

Construire une seule barre de header partagée, avec un seul bloc Navigation et un bloc Site Logo dans un Group sémantique. La structure de cette barre est commune aux quatre sites ; le contenu du menu et le logo sont propres à chaque site. Sur mobile, le panneau hamburger est l’overlay du même bloc Navigation, pas une deuxième barre permanente.

**Procédure**

1. Faire fonctionner un menu complet sur le site réseau et sur un site local avant de chercher le rendu exact de la maquette.
2. Configurer le bloc Navigation avec son overlay mobile et ses sous-menus au clic si nécessaire. Tester d’abord le comportement cœur au clavier, sur mobile et sur tablette.
3. Réaliser un petit essai de disposition centrée : logo au centre et navigation de part et d’autre.
4. Conserver un seul bloc Navigation desktop. Ne créer deux blocs Navigation que si le prototype démontre une impossibilité réelle ; ce n’est pas l’architecture de départ.
5. Ajouter `assets/js/header.js` seulement si le prototype montre que le Navigation block ne couvre pas l’ouverture souhaitée. Le script devra rester progressif : Échap, clic extérieur, focus, état ARIA et navigation sans souris.

**Attention aux menus multisite**

Une valeur `ref` d’un Navigation block désigne un contenu `wp_navigation` par son ID, et cet ID est propre à un site. Ne jamais figer un `ref` numérique issu du site réseau dans `parts/header.html` en pensant qu’il fonctionnera sur Paris, Lyon et Marseille.

Faire le test suivant avant de styliser le menu : créer/associer une navigation sur chacun des quatre sites, puis modifier une règle structurelle du header dans le fichier du thème. Vérifier que les quatre sites conservent leur menu local et reçoivent bien la modification partagée. Si une personnalisation de template part en base empêche cette propagation, résoudre ce choix d’architecture maintenant, pas après l’assemblage des pages.

### 3.3 Footer

Le footer partage sa structure, mais les liens et l’appel à l’action peuvent différer entre le réseau et une ferme. Utiliser des blocs Navigation, Site Logo et Social Icons lorsque cela évite de figer des URLs locales dans le thème.

Prévoir les groupes visuels validés : colonnes de liens, informations légales, réseaux sociaux et copyright. Le wording local est « Soutenir la ferme » ; le site réseau doit employer un wording réseau adapté, plutôt que reprendre mécaniquement ce texte.

**Contrôle**

Chaque site affiche un logo propre, un menu propre, un header navigable au clavier et un footer utilisable. À ce stade, le rendu peut encore être imparfait, mais la stratégie multisite doit être prouvée.

---

## Étape 4 — Créer les pages d’accueil statiques de façon idempotente

Une Page statique par site est nécessaire pour que `post-content` puisse porter les bandeaux. Le script doit être rejouable sans créer de doublons et sans réécrire le travail éditorial.

**Fichiers à créer ou modifier**

- `wordpress-lpu/scripts/content/`
- `wordpress-lpu/scripts/setup-multisite-network.sh`
- `wordpress-lpu/README.md`

**Comportement du nouveau script**

Pour chacun des quatre sites :

1. chercher une Page de slug `accueil` ;
2. la créer avec le titre français `Accueil` seulement si elle n’existe pas ;
3. définir `show_on_front=page` ;
4. définir `page_on_front` avec l’ID trouvé ou créé ;
5. ne jamais modifier le contenu, le titre ou le statut d’une Page existante.

Appeler `scripts/content/setup.sh` depuis `setup-multisite-network.sh` après la création des sites, car le script de bootstrap est déjà exécuté au démarrage de `wp-env`. Chaque script de contenu conserve ses données Gutenberg dans son propre répertoire, hors du code shell. Un redémarrage ne doit jamais réinitialiser une page ou un menu déjà édité dans Gutenberg.

**Contrôle**

```sh
for site_url in lepaysanurbain.test:8888 paris.lepaysanurbain.test:8888 lyon.lepaysanurbain.test:8888 marseille.lepaysanurbain.test:8888; do
  npm run env:cli -- option get show_on_front --url="$site_url"
  npm run env:cli -- option get page_on_front --url="$site_url"
done
```

Chaque site doit retourner `page`, puis un ID de Page existant.

---

## Étape 5 — Construire la bibliothèque de patterns par type de bandeau

Les patterns constituent le vrai livrable éditorial. Ils doivent être utiles dans une Page vide sans obliger les équipes à reconstruire des colonnes, backgrounds et espacements à la main.

Un fichier PHP placé dans `patterns/` et muni d’un en-tête (`Title`, `Slug`, `Categories`, `Description`, `Post Types`) est découvert automatiquement par WordPress. Les fichiers sont écrits en code anglais ; les titres affichés dans Gutenberg sont français.

**Fichiers à créer ou modifier**

- `wordpress-lpu/themes/lepaysanurbain/patterns/*.php`
- `wordpress-lpu/themes/lepaysanurbain/functions.php` — seulement pour créer la catégorie de patterns « Bandeaux Le Paysan Urbain » si nécessaire
- `wordpress-lpu/themes/lepaysanurbain/assets/css/theme.css`

**Patterns à réaliser pour la Home réseau du premier jalon**

Les noms de sections ci-dessous suivent `description-sections.txt`. Les deux occurrences de la grille de cartes dans `sections-Home.txt` utilisent le même pattern avec des contenus différents ; la description et le bouton sont facultatifs dans ce pattern.

| Fichier                     | Usage                                                                                                                      | Blocs cœur principaux                                    |
| --------------------------- | -------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| `hero.php`                  | `hero` : image pleine largeur, titre et texte superposé.                                                                   | Cover, Heading, Paragraph, Buttons                       |
| `text-image.php`            | `cote-a-cote titre-texte et image` : texte à gauche, image à droite.                                                       | Group, Columns, Image, Heading, Paragraph, Buttons       |
| `cards.php`                 | `grille-cartes` : cartes avec image, titre, description et bouton facultatifs.                                             | Group, Columns, Image, Heading, Paragraph, Buttons/links |
| `network-farm-selector.php` | `selecteur-ferme` : présentation visuelle des trois fermes du réseau.                                                      | Group, Columns, Heading, Buttons/links                   |
| `columns.php`               | `colonnes-texte` : titre commun et plusieurs colonnes de texte ou d’images.                                                | Group, Columns, Heading, Paragraph, Image                |
| `text-image-motif.php`      | `cote-a-cote titre-texte-motif et image2` : variante avec motif bleu/vert et image, adaptée à l’ordre de la maquette Home. | Group, Columns, Image, Heading, Paragraph, Buttons       |
| `graphic-band.php`          | `section-mise-en-valeur-couleur` : bandeau final coloré, contenu centré et bouton.                                         | Group, Heading, Paragraph, Buttons                       |

**Règles de conception des patterns**

- Chaque pattern porte des classes CSS explicites (`lpu-band`, `lpu-card-grid`, `lpu-motif-…`) ; ne cibler ni le rang d’un bloc ni du contenu saisi par l’éditeur.
- Les couleurs et espacements viennent des slugs `theme.json`.
- Les motifs sont attribués par classe CSS, pas collés en data URI dans le markup.
- Le hero est fixe : pas de carrousel. Le pattern applique par défaut un overlay noir de 30 % sur l’image ; le contraste est contrôlé sur l’image réellement choisie et l’opacité peut être renforcée si nécessaire.
- Les cartes affichent le texte sous l’image, conformément à la remarque projet ; ne pas transformer leur contenu en texte superposé par défaut.
- Le sélecteur de fermes est une vraie section de la homepage réseau. Il n’est pas remplacé par le sous-menu « Nos Fermes ». En revanche, ses URLs ne doivent pas être codées en dur dans le thème : après insertion, les liens sont configurés dans la Page réseau pour l’environnement concerné.
- Ne pas essayer de créer un « pattern universel » avec mille options ou avec des options cachées. Il faut réfléchir attentivement à l’utilisation des blocs natifs, de l’imbrication de blocs, des patterns et, seulement si nécessaire, de blocs personnalisés afin qu’un éditeur puisse composer facilement les sections avec Gutenberg.

**Méthode de travail**

Créer un pattern, l’insérer dans une Page brouillon, tester desktop et mobile, corriger le fichier source, puis recommencer. Si le plugin Create Block Theme est utilisé pour expérimenter visuellement, il reste local et temporaire : exporter, nettoyer le markup, relire le diff et ne jamais en faire une dépendance du site.

**Contrôle**

Dans l’inséreur de blocs, chaque pattern doit être visible dans sa catégorie, ne proposer que des tokens de charte et s’afficher correctement après insertion sur une Page vide.

---

## Étape 6 — Assembler la Home réseau dans Gutenberg

À partir d’ici, le contenu est volontairement stocké en base, dans les Pages de chaque site. Ce n’est pas du code de thème et ne doit pas être injecté par le script de bootstrap.

### 6.1 Accueil réseau

**Site :** `lepaysanurbain.test:8888`  
**Page :** `Accueil`

Assembler dans l’éditeur, dans l’ordre de `sections-Home.txt` :

1. header avec option transparente sur le hero ;
2. hero « Cultiver le vivant en ville » avec overlay noir par défaut à 30 % ;
3. bandeau texte + image sans motif ;
4. grille de cartes des publics / manières d’agir, avec motif jaune et écru ;
5. sélecteur des fermes ;
6. seconde grille de cartes, avec titres seuls ;
7. titre et colonnes de texte ;
8. variante texte-image avec image entourée du motif bleu/vert ;
9. section finale de mise en valeur sur fond coloré.

Utiliser `Home_1280.jpg`, `Home_1440.jpg` et les PDF comme références de composition, pas comme contenu final sans autorisation. Importer les images de démonstration dans la médiathèque du site réseau ; les médias ne sont pas partagés automatiquement entre les sous-sites.

**Contrôle**

La page réseau ne contient ni logo, ni menu, ni URL de ferme codés en dur dans le thème. Elle utilise la même palette, les mêmes patterns et le même cadre partagé que les futures pages locales.

---

## Étape 7 — Vérifier, documenter et préparer la suite

Cette étape est courte mais obligatoire avant de montrer la démo.

### 7.1 Vérifications techniques

```sh
npm run env:multisite:verify
npm run env:cli -- theme list --url=lepaysanurbain.test:8888
npm run env:cli -- theme list --url=marseille.lepaysanurbain.test:8888
npm run env:cli -- user list --url=marseille.lepaysanurbain.test:8888 --fields=user_login,roles
```

Vérifier aussi qu’aucune personnalisation de test de template ou template part dans le Site Editor ne masque les fichiers du thème sans que ce soit intentionnel.

### 7.2 Vérifications visuelles et d’accessibilité

- Contrôler la Home réseau à environ 360 px, 768 px et 1280 px.
- Tester le menu au clavier : tabulation, ouverture de sous-menu, fermeture avec Échap, focus visible et retour cohérent.
- Vérifier la lisibilité réelle du texte du hero avec l’image de fond choisie ;
- Vérifier l’ordre des titres, les textes alternatifs, la présence d’un `main`, les liens sans libellé ambigu et les boutons.
- Vérifier que les cartes s’empilent proprement et que les hover ne sont pas indispensables à la compréhension.
- Vérifier que les médias ont des dimensions et un poids raisonnables. Utiliser WebP lorsque cela est pertinent et les tailles responsive générées par WordPress.

### 7.3 Documentation à mettre à jour

**Fichiers à modifier**

- `wordpress-lpu/README.md`
- éventuellement `wordpress-lpu/scripts/` si une commande de vérification ou de provisioning est ajoutée

Documenter : démarrage de l’environnement, activation du thème, provisioning des accueils, emplacement des assets, construction des patterns, et ce qui est volontairement différé. Ajouter une note sur les modifications de template part dans le Site Editor : elles sont locales à un site et peuvent masquer les fichiers du thème.

---

## Éléments explicitement différés

- Templates détaillés des pages intérieures et bibliothèque complète de tous les bandeaux.
- Assemblage de l’accueil Marseille : il sera réalisé après la démonstration de la Home réseau, avec les mêmes patterns adaptés aux contenus de la ferme.
- Sélecteur de fermes dynamique / administrable automatiquement. Le pattern visuel réseau est fait, pas son automatisation.
- SEO, redirections de l’ancien site, Analytics et consentement cookies.
- Shopify Paris et tout e-commerce intégré. Un simple lien externe vers la boutique peut néanmoins être placé dans le contenu du site Paris : il ne constitue pas une intégration e-commerce au thème.
- Pipeline complet d’optimisation d’images ; l’optimisation manuelle raisonnable des médias de démonstration reste requise.
- CPT, blocs personnalisés, plugins de fonctionnalités et build JavaScript élaboré.
- Décision finale sur les rôles locaux. L’hypothèse de travail reste : équipes locales en rôle Éditeur, sans accès quotidien au Site Editor ; les besoins réels de template-level access seront validés après le premier essai.
- Typographies à compléter plus tard : obtenir et intégrer les faces DM Sans Regular et Bold, absentes de la livraison actuelle (seule une variante italique est disponible). Ruden Regular est disponible et licenciée dans `design/design site Fanny/fonts/myfonts_order_7268462657706/Ruden-Regular.otf`. Ne pas bloquer le prototype sur ce remplacement, mais le faire avant la validation finale.

## Points à traiter pendant l’exécution

1. Les destinations de « Le Projet » et « Contact » seront décidées pendant l’assemblage : pages dédiées ou ancres de la Home réseau.
2. Les tailles `contentSize` et `wideSize` seront ajustées après comparaison avec la maquette.
3. Le détail de l’implémentation du header transparent (variante de logo, classe ou réglage éditorial, comportement à l’ouverture du menu) sera arrêté pendant le prototype du header.

L’architecture de départ du menu est fixée pour le prototype : une seule barre de header et un seul bloc Navigation par site, avec des contenus de menu propres à chaque site.
