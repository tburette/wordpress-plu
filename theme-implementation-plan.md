\*\*# Plan d’implémentation — thème bloc `lepaysanurbain`

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

Le but est d’obtenir vite une base saine, de réaliser la page d’accueil réseau (Home), puis d’étendre les pages et les contenus. Ne pas ajouter de bloc personnalisé ou de plugin de production avant d’avoir prouvé qu’un bloc cœur ne suffit pas.

La page réseau (Home) à faire en premier est Home de `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/`
ses sections sont décrites dans `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/sections/sections-Home.txt` et dans `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/sections/description-sections.txt`.

Tous les chemins et toutes les commandes ci-dessous sont relatifs à `site/wordpress-lpu/`, sauf mention contraire. Ne jamais essayer d'écrire dans une installation wordpress directement (`wordpress/`) : l'installation de wordpress est un répertoire généré par `wp-env` et il peut être effacé ou recréé n'importe quand.

## État d'avancement

**Dernière mise à jour :** 2026-08-15 23:36 (Europe/Paris)

**Position exacte dans le plan :** Étape 6 — assembler la Home réseau dans Gutenberg — a terminé son assemblage technique de démonstration ; sa validation éditoriale finale reste ouverte. Les Étapes 0, 1, 2, 3, 4 et 5 sont validées ; la Page `Accueil` réseau est provisionnée dans Gutenberg et contrôlée sur le front-end.

_(Du travail des étapes suivantes a déjà été entamé dans le dépôt, notamment sur le socle du thème, le cadre partagé et le provisioning de contenu. Nous le conservons comme état de travail, mais nous avançons désormais systématiquement, une seule étape à la fois, avec validation avant de passer à la suivante.)_

### Terminé

- 2026-08-13 — Étape 0 : environnement `wp-env` multisite fonctionnel, avec le site réseau et les trois sites de ferme.
- 2026-08-13 — Étape 1 : thème bloc minimal monté par `wp-env`, reconnu par WordPress avec la version de thème `0.1.0` et actif sur les quatre sites ; `style.css`, `functions.php`, `templates/index.html` et `scripts/setup-theme.sh` contrôlés, sans source dans un éventuel `wordpress/wp-content/` local.
- 2026-08-13 — Test sur base WordPress vierge puis second passage sans doublons ; le thème, les pages et les navigations sont recréés correctement après un reset.
- 2026-08-13 — Port vérifié : `wp-env` injecte bien `:8888` dans `WP_SITEURL`, `WP_HOME`, `siteurl` et `home` malgré leur forme abrégée dans `.wp-env.json`.
- 2026-08-13 — Étape 2 : système de design et assets contrôlés statiquement, dans WordPress `7.0.4` et visuellement dans Gutenberg et sur le front-end ; palette limitée, familles disponibles, fond écru, motifs, boutons, survols, focus clavier, bordures à `2px`, transition à `100ms`, `alignwide` à `1200px` et `alignfull` pleine largeur validés. Le contenu interne reste volontairement contraint à `700px` pour la lisibilité ; les faces DM Sans Regular et Bold restent différées. La contrainte native du bloc `core/button`, enfant de `core/buttons`, est confirmée ; aucun bloc personnalisé n'est introduit.

### Partiellement réalisé

- 2026-08-14 — Étape 3 validée après contrôle statique, vérification de la configuration multisite, inspection Gutenberg du template part `Pied de page` et inspection visuelle finale avec `web-inspector` sur le réseau, Paris, Lyon et Marseille à `1440px` et `390px`. Les quatre sites répondent en HTTP 200, le footer et son logo SVG sont visibles, les assertions de contenu passent et aucune erreur console, erreur de page ou requête échouée n'est relevée. Les contrôles clavier, pointeur, responsive et Firefox réalisés précédemment restent inclus dans cette validation.

- 2026-08-14 — Étape 3 : `front-page.html` et `page.html` ont le cadre `header`/`main`/`post-content`/`footer`, `index.html` utilise un Query Loop dans un `main` sémantique, le header contient un seul bloc Navigation sans `ref` codé en dur, et la liaison vers le menu local fonctionne sur les quatre sites. Les quatre `custom_logo` sont provisionnés par `scripts/content/site-logos/`. Le header transparent est implémenté comme réglage explicite de page pour les variantes écrues disponibles ; le footer partagé est implémenté, avec ses destinations finales volontairement différées.
- 2026-08-14 — Prototype du menu corrigé puis validé : `submenuVisibility:"hover"` et `showSubmenuIcon:true` rendent le lien parent accessible et confient le déploiement au chevron séparé ; le rendu WordPress contient les directives natives de hover, focus et clic, sans `openSubmenusOnClick` legacy. Le header utilise une grille à logo centré, les panneaux desktop sont absolus, pleine largeur, écrus et positionnés sous la hauteur réelle du header ; le même overlay Navigation est exposé jusqu'à `1099px` pour les menus locaux longs. Le seuil a été revalidé avec les logos et libellés finaux.
- 2026-08-14 — Inspection visuelle et fonctionnelle finale réalisée avec Playwright via `web-inspector` sur les quatre sites à `1440px`, à la frontière `1100/1099px`, puis sur l'overlay Paris à `390px`. Le panneau réseau et les panneaux locaux restent écrus, pleine largeur et répartis en colonnes ; le hamburger à trois barres n'apparaît qu'à partir de `1099px` ; les sous-rubriques mobiles ne dépendent pas du survol. L'ouverture au clavier par Entrée, la fermeture Échap, le clic extérieur mobile et le retour du focus sont validés. Les captures finales n'ont aucune erreur console, erreur de page ou requête échouée. Le survol desktop ajoute un maintien de `500ms` pendant la traversée entre parent et panneau, ainsi qu'un délai d'intention de `150ms` avant de remplacer un panneau actif : il supprime le clignotement dans le vide du header et évite qu'un simple passage sur une autre rubrique change le contenu. La variante transparente réseau et Paris a également été testée temporairement : le logo écru est chargé à l'état initial et le header repasse vert sur écru à l'ouverture du menu.
- 2026-08-14 — Une inspection complémentaire a corrigé l'ancrage vertical du méga-menu : le sous-menu absolu était positionné sous la seule rangée de navigation et recouvrait la baseline du logo. Il commence maintenant sous la hauteur complète du header ; la traversée plus longue reste couverte par le maintien temporaire du survol. Le rendu corrigé a été vérifié à `2048px` dans Chromium et Firefox, sans erreur de page, console ou requête.
- 2026-08-14 — Le bouton hamburger mobile héritait d'une marge supérieure Core de `24px` et se trouvait donc plus bas que le logo. La marge est supprimée dans la variante responsive ; les centres verticaux du logo et du bouton sont maintenant alignés à `547px` et `390px`, avec ouverture de l'overlay vérifiée dans Chromium et Firefox.
- 2026-08-14 — Les quatre logotypes horizontaux verts avec baseline sont provisionnés par `scripts/content/site-logos/`, sélectionnés comme `custom_logo` sur les quatre sites et vérifiés visuellement à `1440px` ; le logo Paris est également vérifié sur Pixel 5. Les variantes écrues du réseau, de Paris, de Lyon et de Marseille sont présentes dans les assets du thème pour l'état transparent. Les captures des quatre sites chargent leurs déclinaisons territoriales sans erreur.
- 2026-08-14 — Le fichier source écru Lyon corrigé a été ajouté comme `lyon-horizontal-ecru-baseline.svg` dans les assets du thème et relié à la configuration du site Lyon. Le chargement initial transparent et le retour au logo vert à l'ouverture du menu sont vérifiés sur Lyon en Chromium et Firefox à `1440px`, ainsi qu'en mobile Chromium à `390px`, sans erreur console ni requête échouée.
- 2026-08-14 — Contrôle structurel du menu passé sur les quatre sites : réseau `1` rubrique à sous-menu et `1` chevron ; Paris, Lyon et Marseille `4` rubriques et `4` chevrons ; tous portent `Navigation principale` et aucun ne rend l'ancien mode `open-on-click`. L'interaction du réseau et de Paris est validée ; les captures fermées de Lyon et Marseille confirment le même rendu partagé aux tailles desktop et mobile.
- 2026-08-14 — Footer partagé implémenté puis rapproché de la maquette `sections/pied-de-page.jpg` : fond vert foncé `#003d2e`, logotype vertical écru avec baseline, paragraphe de présentation, trois colonnes principales de rubriques et étoile décorative écrue en bas à droite. Les informations légales, réseaux sociaux et copyright restent présents en zone secondaire compacte ; aucun bouton jaune n'est affiché. `parts/footer.html` fournit cette structure et `render_block_data` associe la classe `lpu-footer-navigation` à l'option locale `lpu_footer_navigation_id`. `scripts/content/footer-menus/` provisionne séparément les contenus réseau et ferme sans remplacer une navigation existante. Le rendu et le chargement des logos ont été vérifiés sur le réseau, Paris, Lyon et Marseille à `1440px`, sur Paris à `390px` dans Chromium et Firefox, et sans erreur console, page ou requête ; Lyon et Marseille ont également passé une assertion de contenu corrigée après une première assertion trop sensible aux majuscules.
- 2026-08-14 — Correction du template part dans Gutenberg : les classes `has-text-color has-background` manquantes ont été ajoutées au groupe, puis le logo de footer a été rendu par un bloc Image natif pointant vers l'asset SVG vertical écru. Cela évite l'écart entre le rendu serveur du front-end et l'aperçu de l'éditeur. Le template part est maintenant validé sans erreur Gutenberg ; son rendu public a été recontrôlé sur le réseau et Paris à `1440px` et `390px`, sans erreur console, page ou requête.
- 2026-08-14 — Le provisioning de navigation a été durci sans réécriture de contenu : `navigation-sites.tsv` est maintenant réellement séparé par tabulations et `setup.sh` retrouve aussi un `wp_navigation` non publié via `--post_status=any`. La vérification d'idempotence après environnement propre reste soumise à la procédure de reset documentée dans `AGENTS.md`.
- 2026-08-14 — Réévaluation du menu à partir des échanges validés, des règles de design, de `sections-Home.txt` et des extractions `menu Home_1440 (transparent).jpg` / `menu Ferme_1440 (fond écru).jpg`. Les maquettes fixent l'intention visuelle du header (logo centré et liens de part et d'autre, fond transparent ou écru) mais montrent encore un hamburger desktop et « Agir avec nous » ; ces deux éléments sont écartés par la décision ultérieure acceptée. « Sézane » reste une inspiration de fonctionnement du méga-menu, pas une source de contenu, de typographie ou de structure commerciale.
- 2026-08-14 — Étape 4 validée comme provisioning technique uniquement : les quatre sites sélectionnent leur Page `Accueil` de slug `accueil`, avec `show_on_front=page` et `page_on_front` réglé sur l’ID trouvé ou créé (les IDs sont attribués par la base et ne constituent pas le contrat ; après le reset actuel, le réseau utilise l’ID `5`). Le bootstrap appelle `scripts/content/setup.sh` après la création des sites ; le script de pages est idempotent, reconnaît aussi une page existante non publiée avec `--post_status=any`, et ne réécrit ni titre, ni contenu, ni statut. Les deux passages réels ont sélectionné les mêmes pages sur les quatre sites. Leur contenu reste le commentaire invisible prévu ; aucun pattern, aucune image et aucune section de maquette n’a été ajouté. Le contrat est maintenant porté par les commentaires de `scripts/content/front-pages/setup.sh` et par la commande `npm run env:content`.
- 2026-08-14 — Étape 5 validée après recréation de l’environnement : les sept patterns `hero`, `text-image`, `cards`, `network-farm-selector`, `columns`, `text-image-motif` et `graphic-band` sont disponibles automatiquement dans la catégorie `Sections Le Paysan Urbain`. Les structures utilisent les blocs cœur, les tokens de `theme.json`, les classes `lpu-band`/`lpu-card-grid`/`lpu-motif-*` et des règles responsives partagées ; `cards` est réutilisable pour les deux grilles de la Home. Le visuel SVG local `pattern-placeholder.svg` sert uniquement de média de démonstration remplaçable dans Gutenberg, avec `alt=""` tant qu’il est décoratif : aucune photo de maquette ni contenu de Home n’a été injecté dans `Accueil` ou dans le bootstrap. Le markup `core/cover` du Hero a été normalisé (`has-background`, sans `wp-image-0`) et la page permanente `lpu-sections-patterns-test` a été régénérée sans erreur de validation Gutenberg. Le registre WordPress, l’éditeur authentifié et le rendu serveur des sept patterns ne signalent aucun bloc invalide. La page de contrôle concatène maintenant automatiquement tous les patterns fournis par le thème actif, triés par nom, et a été inspectée avec `web-inspector` à `1440px` et en émulation Pixel 5 à `390px` : HTTP 200, aucune erreur console/page/requête et aucun débordement horizontal. Le provisioning de cette page a été rejoué deux fois après reset sans créer de doublon.
- 2026-08-15 — Étape 6 — assemblage technique de démonstration terminé : `scripts/content/home-network/setup.sh` assemble explicitement la Page réseau `Accueil` à partir de l’ordre déclaré dans `scripts/content/home-network/home-sections-names.txt`. Il est inclus dans `npm run env:content` et peut aussi être sélectionné avec `npm run env:content -- home-network`. Il vérifie `show_on_front`/`page_on_front`, protège le contenu éditorial par une comparaison exacte du placeholder technique et n’autorise le remplacement qu’avec `--force`. Il active le header transparent, supprime la marge de la pile de blocs devant un hero transparent, résout les URLs des trois fermes depuis le multisite et adapte la seconde grille `cards` en variante gris-vert à titres seuls. Les médias restent les placeholders SVG autorisés de démonstration, en attente des médias éditoriaux autorisés. La revue code confirme 8 blocs racine et 81 descendants (89 blocs au total) valides, dont un `core/cover` valide ; la revue visuelle indépendante et les captures locales à `1440px`, `1280px`, `768px` et Pixel 5 à `390px` ne relèvent aucune erreur, aucun débordement et confirment le retour du header à l’écru à l’ouverture du méga-menu.

### À faire maintenant

- 2026-08-15 — Correction post-contrôle du header transparent de la Home : l’état `.lpu-menu-open` conserve maintenant le header en superposition (`position:absolute`) et ne réintroduit plus sa hauteur dans le flux quand le méga-menu s’ouvre. Le panneau écru recouvre le haut du hero sans déplacer les sections ; le rendu Home fermé/ouvert a été recontrôlé à `1440px`, et l’overlay mobile à `390px`, avec HTTP 200 et aucune erreur console, page ou requête.
- 2026-08-15 — Correction du pont de survol du méga-menu : la zone transparente de `1.8rem` entre l’option et le panneau est maintenant une extension interactive du panneau. Le parent reste donc survolé, `.lpu-menu-open` et le chevron restent ouverts, et le menu ne se ferme qu’après sortie de cette zone et du panneau. La traversée a été reproduite avec le pointeur maintenu dans la zone à `1440px`, puis contrôlée avec le panneau atteint après le délai, sans erreur navigateur.
- 2026-08-15 — Ajustements post-contrôle des patterns : le Hero conserve un bouton cœur avec libellé éditable et accessible, dont la variante front-end `lpu-button-arrow` utilise maintenant la flèche organique SVG fournie par Fanny, dans la direction de la maquette. Le thème désactive le mode WordPress 7 « contenu seulement » pour les patterns non synchronisés : le bloc cœur `Columns` est donc directement sélectionnable dans chaque instance de Page, sans « Modifier la composition » ni effet global ; son réglage natif `1–6` a été contrôlé de `3` à `4` puis ramené à `3` sans sauvegarde. Sa bande desktop mesure environ `722px` à `1440px`, contre `719px` dans la référence `titre-colonnes`, tandis que sa règle mobile revient au rythme compact partagé. Enfin, la marge est explicitement nulle aux deux bords des bandes racines `.lpu-band` ; la jonction Colonnes / Image avec motif a été mesurée à `0px` dans Chromium et Firefox, sans retirer les espacements des contenus internes. Après reset autorisé, le provisioning complet, le rendu Home et la fixture permanente des patterns ont été recontrôlés à `1440px` et sur Pixel 5, sans erreur navigateur ni débordement horizontal.
- Finaliser la validation éditoriale de l’Étape 6 lorsque les médias autorisés, les textes validés et les Pages de destination des CTA seront disponibles ; aucun contenu de maquette aplati n’est injecté par le bootstrap.
- Après intégration du contenu réel, rejouer le comportement déjà validé au clavier et au pointeur si les libellés ou la structure des navigations changent.
- Conserver les décisions validées : méga-menu texte en colonnes uniquement, header opaque écru par défaut, variante transparente activable explicitement sur une page avec hero suffisamment contrasté, et seuil responsive `1099px` pour les libellés actuels.

### Points ouverts

- Aucun élément bloquant du cadre partagé ne reste pour l'Étape 3 validée.
- Vérifier la généricité de toute l’implémentation multisite avant la livraison : aucun `blog_id`, ID de site, ID de page, ID de navigation, ID de logo ou autre identifiant propre à un site ne doit être codé en dur. Les scripts, templates et liaisons de contenu doivent résoudre le site courant à partir de son contexte ou de données déclaratives prévues à cet effet. Tester explicitement l’ajout d’un nouveau sous-site et la suppression d’un sous-site existant — avec la seule mise à jour nécessaire des données déclaratives — puis vérifier que les autres sites et le bootstrap continuent à fonctionner sans adaptation du code. (il faudra demander pour l'ajout dans le fichiers hosts au moment de testeer l'ajout d'un nouveau sous-site)
- Gutenberg émet encore un avertissement générique sur le contraste automatique de la couleur écru dans ses composants ; il ne produit ni bloc invalide ni défaut de rendu et reste non bloquant.
- Décider le cache-busting des assets en développement (`Version` du thème ou `filemtime`).
- Réévaluer plus tard la robustesse de la liaison des navigations multisite via `render_block_data` et `lpu_navigation_id`.
- Remplacer les URLs provisoires `/` de « Le Projet », « Contact » et des liens de rubriques, légaux et réseaux sociaux du footer lorsque les pages et comptes dédiés existeront ; l'activation éditoriale de la variante transparente est maintenant portée par le réglage de page `Header de la page`.
- Les CTA des patterns restent volontairement sans destination dans la fixture de contrôle et dans cette première assemblée de démonstration ; ils seront reliés aux Pages réelles dans Gutenberg, sans URL fictive ni URL de ferme codée en dur dans le thème. Les trois liens de fermes de la Home réseau sont déjà résolus par le provisioning explicite.
- La fixture `lpu-sections-patterns-test` reste publiée en permanence pour les contrôles ; son SVG abstrait, ses textes d’exemple et son header opaque ne sont pas la Home finale. La Home réseau assemblée utilise encore le SVG abstrait comme média de démonstration jusqu’à validation et import des médias éditoriaux autorisés.
- Le seuil responsive `1099px` est validé pour les logos, libellés et largeurs actuels ; le reprendre uniquement si ces éléments changent.
- Les réglages spécifiques au site dans le thème sont indexés par une clé dérivée du sous-domaine (`network`, `paris`, `lyon`, `marseille`), et non par le `blog_id` attribué par l'installation.
- Le remplacement du logo écru transparent reste différé lorsque l’image opaque fournit un `srcset` : les logos SVG actuellement provisionnés n’en rendent pas, mais cette variante devra être reprise si des logos raster ou responsive sont introduits.
- La composition visuelle des méga-menus est arrêtée : texte en colonnes uniquement ; aucune carte produit ni copie de la mise en page commerciale de Sézane ne doit être introduite.

 

 

 

 
[espace laissé blanc intentionnellement]

 

 

 

 

## Rappels utiles sur les block themes

- `alignwide` utilise la largeur large définie dans `theme.json` ; `alignfull` va d’un bord à l’autre de la fenêtre. Les sections colorées ou photographiques sont donc généralement `alignfull`.
- Deux sources peuvent exister pour un template ou un template part : le fichier du thème et une personnalisation enregistrée dans la base par le Site Editor. Le fichier doit rester la source de vérité.

## Règles de mise en œuvre

- Utiliser les blocs cœur en premier : Group, Cover, Columns, Image, Heading, Paragraph, Buttons, Navigation, Site Logo, Social Icons, Query Loop et Search.
- Le code, les noms de fichiers et les classes CSS sont en anglais ; les titres visibles dans Gutenberg et le contenu du site sont en français.
- Les couleurs, tailles et familles doivent venir de `theme.json`, jamais de valeurs arbitraires ajoutées dans les patterns.
- Le CSS sert aux traitements que `theme.json` ne sait pas décrire proprement.
- Le JavaScript est une exception. Le bloc Navigation devrait couvrir une grande partie du menu responsive, du clavier et des attributs ARIA ; ne lui ajouter du JS que lorsqu’un essai démontre un manque précis.
- Si un JavaScript est démontré nécessaire pour le premier jalon, conserver `assets/js/navigation.js` comme JavaScript vanilla directement chargeable. Ne pas introduire de build npm uniquement pour une cinquantaine de lignes sans imports. Si un build devient nécessaire plus tard, l’ajouter au `package.json` existant de `wordpress-lpu/`, pas dans un second projet npm imbriqué.
- Un environnement wordpress de développement existe via wp-env (à utiliser via `npm run env:status`, `npm run env:start`,..). Cet environnement peut être supprimé et recréé à tout moment. Les fichiers de l'installation wordpress de cet environnement ainsi que la base de données peuvent disparaitre. Il ne faut donc pas ajouter des données directement dans le répertoire de l'environnement wordpress ou la base de donnée.
- Il existe une installaton wordpress multisite séparée de test disponible en ligne. Lien : PAS DIVULGÉ POUR LE MOMENT. Il faudra pouvoir installer le site créé sur cette autre installation wordpress (pour pouvoir partager le nouveau site avec le client avant la mise en production).

---

## Étape 0 — Vérifier l’environnement et les assets avant de coder

Cette étape évite de découvrir une police absente ou une configuration multisite cassée lorsque le header est déjà construit.

**Fichiers à consulter, pas encore à modifier**

- `wordpress-lpu/.wp-env.json`
- `wordpress-lpu/scripts/setup-multisite-network.sh`
- `wordpress-lpu/scripts/setup-theme.sh`
- `wordpress-lpu/scripts/content/`
- `wordpress-lpu/README.md`
- l'identité graphique de l'association et le design du site de Fanny dans `../../design/`

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
- fichiers valides ;
- WOFF2 disponible ou conversion possible depuis un OTF/TTF ;
- aucune police ne doit venir d’un CDN en production.

Ruden Regular est maintenant disponible dans `design/design site Fanny/fonts/myfonts_order_7268462657706/Ruden-Regular.otf`. La livraison actuelle contient encore seulement une variante italique de DM Sans ; les faces Regular et Bold seront intégrées plus tard, avant la validation finale.

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
│   └── js/navigation.js
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
- `wordpress-lpu/scripts/setup-theme.sh`

**Actions**

1. Ajouter `"./themes/lepaysanurbain"` au tableau `themes` de `.wp-env.json`.
2. Écrire dans `style.css` le seul en-tête nécessaire à WordPress (`Theme Name`, `Text Domain`, `Requires at least`, `Requires PHP`, etc.). Ne pas y mettre le CSS applicatif : il vivra dans `assets/css/theme.css`.
3. Créer un `functions.php` minimal qui protège l’accès direct et charge le CSS du thème sur le front-end et dans l’éditeur. Les traitements spécifiques viendront plus tard.
4. Créer un `index.html` temporaire avec header, un `main` et footer ; il sera complété à l’étape 3.
5. Redémarrer l’environnement pour que le montage de thème soit pris en compte, rendre le thème disponible au réseau, puis l’activer site par site avec le script idempotent d’environnement :

```sh
npm run env:start
npm run env:theme:setup
```

`wp-env` monte le thème mais ne choisit pas le thème actif. `scripts/setup-theme.sh` rend le thème disponible au réseau et l’active sur tous les sites détectés par WP-CLI. Il est aussi appelé par `setup-multisite-network.sh` et peut être rejoué seul.

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
- Conserver les logos SVG officiels dans les assets et les importer dans la médiathèque par le provisioning CLI contrôlé du thème ; WordPress n’autorise pas les SVG par défaut dans les uploads ordinaires.

**Contrôle**

Dans une Page de test, vérifier à la fois dans Gutenberg et sur le front-end : palette limitée, familles disponibles, boutons cohérents, fond écru, `alignwide` et `alignfull` visibles.

---

## Étape 3 — Construire le cadre partagé : templates, header et footer

Le template est le cadre ; le contenu reste dans les Pages. Garder les templates minces rend le multisite maintenable.

**Fichiers à créer ou modifier**

- `wordpress-lpu/themes/lepaysanurbain/templates/front-page.html`
- `wordpress-lpu/themes/lepaysanurbain/templates/page.html`
- `wordpress-lpu/themes/lepaysanurbain/templates/index.html`
- `wordpress-lpu/themes/lepaysanurbain/parts/header.html`
- `wordpress-lpu/themes/lepaysanurbain/parts/footer.html`
- `wordpress-lpu/themes/lepaysanurbain/assets/css/theme.css`
- `wordpress-lpu/themes/lepaysanurbain/assets/js/navigation.js`
- `wordpress-lpu/scripts/content/navigation-menus/setup.sh` and its data files
- `wordpress-lpu/scripts/content/footer-menus/setup.sh` and its data files
- `wordpress-lpu/README.md` pour la documentation de l'architecture

### 3.1 Templates de base

Pour `front-page.html` et `page.html`, utiliser la même forme :

1. template part `header` avec `tagName: "header"` ;
2. un Group avec `tagName: "main"` ;
3. `post-content` à l’intérieur ;
4. template part `footer` avec `tagName: "footer"`.

Le `main` est important : il rend le document plus clair pour les lecteurs d’écran et permet à WordPress d’exposer un lien d’évitement vers le contenu.

Ne pas mettre les sections de l’accueil dans `front-page.html`. Ce fichier doit contenir seulement le cadre et `post-content`.

`index.html` est le fallback pour les listes de contenus : il doit utiliser un Query Loop minimal, pas un `post-content` isolé. Ce n'est pas encore l'écran définitif des listes de contenus, mais il doit rester fonctionnel.

La gestion des articles individuels et de la page 404 est différée après la réalisation de la Home réseau. Les templates `single.html` et `404.html` pourront être ajoutés lors d'une étape ultérieure si les besoins éditoriaux le justifient.

### 3.2 Header : valider le point risqué du multisite tôt

Construire une seule barre de header partagée, avec un seul bloc Navigation et un bloc Site Logo dans un Group sémantique. La structure de cette barre est commune aux quatre sites ; le contenu du menu et le logo sont propres à chaque site. Sur mobile, le panneau hamburger est l’overlay du même bloc Navigation, pas une deuxième barre permanente.

#### Référence visuelle et décisions qui prévalent

Les extractions suivantes servent de référence visuelle pour le header non déployé :

- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/menu Home_1440  (transparent).jpg` : header posé sur l'image du hero, avec logo et libellés écrus ;
- `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/menu Ferme_1440 (fond écru).jpg` : header opaque écru, logo et libellés vert foncé.

Ces captures montrent un logo horizontal au centre et les liens principaux de part et d'autre. Elles montrent aussi un hamburger sur desktop et, pour la Home, « Agir avec nous ». Ces deux éléments appartiennent à une version de maquette qui a été discutée ensuite : ils ne font pas partie du comportement retenu pour le prototype. Les règles consolidées du site retiennent : pas de hamburger sur grand écran ; panneau accessible au clic et au focus, éventuellement aussi au survol ; hamburger classique seulement sur tablette et mobile.

Le méga-menu est la version déployée du menu principal lorsqu'une entrée possède des sous-rubriques. Ce n'est pas un deuxième menu indépendant et ce n'est pas un écran de navigation qui remplacerait la barre :

- sur desktop, le panneau s'ouvre sous le header et reste visuellement continu avec lui ;
- il est propre à la rubrique activée, pleine largeur ou suffisamment large, avec des colonnes de sous-rubriques, une hiérarchie lisible et un fond écru ;
- le survol peut déclencher l'ouverture sur ordinateur, mais le clic et le focus doivent produire le même résultat ;
- le panneau reste ouvert pendant le passage du pointeur ou du focus vers ses liens ; un seul panneau est ouvert à la fois ;
- Échap, le clic extérieur et la navigation clavier permettent de le fermer ; après Échap, le focus revient au contrôle d'origine ;
- une rubrique parente ne doit pas rendre sa propre destination inaccessible ; le prototype retient le modèle natif lien parent / contrôle de déploiement séparé, rendu par `submenuVisibility:"hover"` et `showSubmenuIcon:true` ;
- le panneau ne doit pas déplacer brutalement le contenu et aucune information ne doit dépendre uniquement du survol.

L'inspiration Sézane est donc limitée à cet enchaînement fonctionnel et visuel : éventuel header transparent sur le hero, passage à l'écru à l'ouverture, puis grand panneau contextuel. Elle ne justifie pas de reprendre le catalogue, les cartes produit, les icônes utilitaires, la typographie ou le découpage commercial du site actuel de Sézane. Le contenu et les libellés du Paysan Urbain viennent de `contextes/site context/navigation site.txt` et des données co-localisées dans `scripts/content/navigation-menus/`.

Les anciennes notes parlent parfois du « survol du logo ». Cette formulation décrit
l'intention initiale de faire apparaître le header, mais ne définit pas le contrôle
du méga-menu retenu. Le logo reste toujours un lien vers l'accueil ; l'ouverture
du panneau concerne la rubrique qui possède des sous-rubriques. Elle doit être
possible au clic et au focus, avec le survol comme raccourci desktop éventuel.

#### Contrat de contenu

Le menu réseau contient actuellement `Le Projet` (lien direct), `Nos Fermes` (sous-menu Lyon, Marseille, Paris) et `Contact` (lien direct). « Agir avec nous » est volontairement absent, même s'il figure sur la capture de maquette. Les destinations de « Le Projet » et « Contact » utilisent provisoirement `/`, la page d'accueil du site courant ; elles seront remplacées par les URLs des pages dédiées lorsqu'elles existeront.

Les menus de ferme utilisent les rubriques `Qui sommes-nous`, `Nos Activités`, `Nos Cultures` et `Nos Projets & Initiatives` comme entrées à sous-rubriques. `Infos pratiques` reste un lien direct, malgré le détail de ses informations (accès, horaires, contact). Les menus sont propres à chaque site, avec une même structure de comportement et de style.

#### Responsive et fond du header

Le header opaque écru (`#f8f8f0`) avec logo, liens et chevrons vert foncé (`#153d30`) est la valeur par défaut. Une variante transparente est un choix éditorial explicite de la page, uniquement à l'état initial lorsqu'un hero sombre garantit le contraste ; elle utilise le logo horizontal écru. À l'ouverture d'un méga-menu, le header et le panneau redeviennent toujours opaques et vert foncé sur écru.

Lorsque la navigation desktop ne tient plus confortablement, le même bloc Navigation ouvre son overlay mobile via un hamburger. Cet overlay doit proposer un nom accessible, un bouton de fermeture, la fermeture par Échap et clic extérieur, une gestion du focus et des contrôles de sous-rubriques indépendants du survol. Le seuil sera déterminé par l'encombrement réel du logo et des libellés, pas par une largeur recopiée de la maquette.

**Procédure**

1. Faire fonctionner un menu complet sur le site réseau et sur un site local avant de chercher le rendu exact de la maquette.
2. Configurer le bloc Navigation avec son overlay mobile, `submenuVisibility:"hover"` et le chevron de déploiement séparé. Tester d’abord le comportement cœur au clavier, sur mobile et sur tablette, puis vérifier l'ouverture au survol comme amélioration desktop.
3. Réaliser un essai de disposition centrée : logo au centre et navigation de part et d'autre, sans ajouter « Agir avec nous » ni un faux lien pour équilibrer la composition.
4. Vérifier que le sous-menu du bloc Navigation peut être présenté comme un méga-menu contextuel sans créer un second arbre de navigation. Le prototype CSS utilise un panneau absolu pleine largeur et des colonnes textuelles ; vérifier les limites réelles du core pour la largeur, le maintien ouvert et la transition transparent/opaque.
5. Conserver un seul bloc Navigation desktop et un seul overlay mobile. Ne créer deux blocs Navigation que si le prototype démontre une impossibilité réelle ; ce n’est pas l’architecture de départ.
6. Ajouter uniquement le complément démontré `assets/js/navigation.js` lorsque le Navigation block ne couvre pas une exigence précise. Le script reste progressif : il relaie la fermeture Échap malgré l'état CSS `:hover`, ferme la marge extérieure de l'overlay mobile et réutilise les contrôles/focus du bloc cœur ; il ne remplace pas son fonctionnement natif.

**Attention aux menus multisite**

Une valeur `ref` d’un Navigation block désigne un contenu `wp_navigation` par son ID, et cet ID est propre à un site. Ne jamais figer un `ref` numérique issu du site réseau dans `parts/header.html` en pensant qu’il fonctionnera sur Paris, Lyon et Marseille.

Faire le test suivant avant de styliser le menu : créer/associer une navigation sur chacun des quatre sites, puis modifier une règle structurelle du header dans le fichier du thème. Vérifier que les quatre sites conservent leur menu local et reçoivent bien la modification partagée. Si une personnalisation de template part en base empêche cette propagation, résoudre ce choix d’architecture maintenant, pas après l’assemblage des pages.

Ne pas créer ces menus uniquement à la main : pour survivre au reset de la base, ils sont recréés ou sélectionnés par le provisioning de contenu du bootstrap (`scripts/content/navigation-menus/setup.sh`), qui ne réécrit jamais une navigation déjà éditée.

### 3.3 Footer

Le footer partage sa structure, mais les liens peuvent différer entre le réseau et une ferme. Utiliser un bloc Image pour le logotype vertical commun, ainsi que les blocs Navigation et Social Icons ; le bloc Site Logo reste utilisé par le header, dont le logo est propre à chaque site.

Reproduire la composition de `design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/sections/pied-de-page.jpg` : fond vert foncé, logotype vertical écru avec baseline, paragraphe de présentation, trois colonnes simples de rubriques et étoile décorative en bas à droite. Les informations légales, réseaux sociaux et copyright restent accessibles dans une zone secondaire compacte ; aucun bouton jaune n'est prévu dans cette composition.

L'implémentation actuelle utilise un seul template part partagé et deux navigations `wp_navigation` par site (une principale et une footer). La classe `lpu-footer-navigation` déclenche la liaison vers `lpu_footer_navigation_id`, tandis que `scripts/content/footer-menus/` conserve les contenus réseau et ferme dans des fichiers séparés. Les URLs des rubriques, des liens légaux et des réseaux sociaux sont encore des placeholders documentés ; elles ne doivent pas être considérées comme les destinations éditoriales finales.

**Contrôle**

Chaque site affiche un logo propre, un menu propre, un header navigable au clavier et un footer utilisable. Le contrôle du menu doit couvrir au minimum : disposition desktop centrée, distinction lien direct / rubrique à sous-rubriques, ouverture au clic et au focus, réaction au survol sans dépendance exclusive, maintien du panneau vers ses liens, fermeture par Échap et clic extérieur, retour du focus, overlay mobile du même bloc et sous-rubriques utilisables sans survol. À ce stade, le rendu peut encore être imparfait, mais la stratégie multisite doit être prouvée.

---

## Étape 4 — Créer les pages d’accueil statiques de façon idempotente

Une Page statique par site est nécessaire pour que `post-content` puisse porter les sections. Le script doit être rejouable sans créer de doublons et sans réécrire le travail éditorial. Cette étape ne fournit que ce support technique : elle n’ajoute aucun pattern, aucune image et aucune section issue des maquettes.

**Fichiers à créer ou modifier**

- `wordpress-lpu/scripts/content/`
- `wordpress-lpu/scripts/setup-multisite-network.sh`
- `wordpress-lpu/README.md`

**Comportement du provisioning**

Pour chacun des quatre sites :

1. chercher une Page de slug `accueil` ;
2. la créer avec le titre français `Accueil` seulement si elle n’existe pas ;
3. définir `show_on_front=page` ;
4. définir `page_on_front` avec l’ID trouvé ou créé ;
5. ne jamais modifier le contenu, le titre ou le statut d’une Page existante.

Appeler `scripts/content/setup.sh` depuis `setup-multisite-network.sh` après la création des sites, car le script de bootstrap est déjà exécuté au démarrage de `wp-env`. Chaque script de contenu conserve ses données Gutenberg dans son propre répertoire, hors du code shell. Un redémarrage ne doit jamais réinitialiser une page ou un menu déjà édité dans Gutenberg.

`scripts/content/setup.sh` enchaîne aussi `navigation-menus/setup.sh`, qui recrée ou sélectionne le post `wp_navigation` de chaque site (slug `menu-principal`) et enregistre son ID dans l'option `lpu_navigation_id`, ce qui rend les menus reproductibles.

Il enchaîne également `footer-menus/setup.sh`, qui recrée ou sélectionne le post `wp_navigation` de chaque site (slug `footer-principal`) et enregistre son ID dans `lpu_footer_navigation_id`, sans écraser une navigation déjà existante.

**Contrôle**

```sh
for site_url in lepaysanurbain.test:8888 paris.lepaysanurbain.test:8888 lyon.lepaysanurbain.test:8888 marseille.lepaysanurbain.test:8888; do
  npm run env:cli -- option get show_on_front --url="$site_url"
  npm run env:cli -- option get page_on_front --url="$site_url"
done
```

Chaque site doit retourner `page`, puis un ID de Page existant.

---

## Étape 5 — Construire la bibliothèque de patterns par type de section

Les patterns constituent le vrai livrable éditorial. Ils doivent être utiles dans une Page vide sans obliger les équipes à reconstruire des colonnes, backgrounds et espacements à la main. Ils permettent facilement d'ajouter les sections du design de Fanny dans une page.

Un fichier PHP placé dans `patterns/` et muni d’un en-tête (`Title`, `Slug`, `Categories`, `Description`, `Post Types`) est découvert automatiquement par WordPress. Les fichiers sont écrits en code anglais ; les titres affichés dans Gutenberg sont français.

**Fichiers à créer ou modifier**

- `wordpress-lpu/themes/lepaysanurbain/patterns/*.php`
- `wordpress-lpu/themes/lepaysanurbain/functions.php` — seulement pour créer la catégorie de patterns « Sections Le Paysan Urbain » si nécessaire
- `wordpress-lpu/themes/lepaysanurbain/assets/css/theme.css`

**Patterns à réaliser pour la Home réseau du premier jalon**

Les noms de sections ci-dessous suivent `description-sections.txt`. Les deux occurrences de la grille de cartes dans `sections-Home.txt` utilisent le même pattern avec des contenus différents ; la description et le bouton sont facultatifs dans ce pattern. La fixture de contrôle concatène automatiquement tous les patterns fournis par le thème actif ; l’ordre particulier de la Home reste déclaré dans `scripts/content/home-network/home-sections-names.txt`.

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

Pour conserver un support de contrôle après chaque recréation de
l’environnement, `scripts/content/setup.sh` exécute également
`scripts/content/patterns-test-page/setup.sh`. Cette fixture publie la page
`lpu-sections-patterns-test` sur le site réseau avec tous les patterns fournis
par le thème actif, triés par nom. Elle ne constitue pas la Home et ne remplace
pas son assemblage éditorial.

---

## Étape 6 — Assembler la Home réseau dans Gutenberg

Le contenu est stocké en base, dans les Pages de chaque site. Ce n’est pas du
code de thème ; les scripts fournissent toutefois une source reproductible
pour recréer l’environnement de développement.

La fixture `scripts/content/patterns-test-page/setup.sh` concatène tous les
patterns du thème actif dans la page de contrôle
`lpu-sections-patterns-test`. La Home réseau utilise, elle, sa propre
déclaration d’ordre dans `scripts/content/home-network/`, ainsi que son
contrat pour le contenu de `Accueil`, les URLs des fermes et l’option de
header. Elle est incluse dans `npm run env:content` et refuse une réécriture
éditoriale sans `--force`.

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

Utiliser `Home_1280.jpg`, `Home_1440.jpg` et les PDF comme références de composition, pas comme contenu final sans autorisation. L’assemblage technique actuel conserve `pattern-placeholder.svg` comme média de démonstration ; importer les images éditoriales autorisées dans la médiathèque du site réseau lorsqu’elles seront validées. Les médias ne sont pas partagés automatiquement entre les sous-sites.

**Contrôle**

La page réseau ne contient ni logo, ni menu, ni URL de ferme codés en dur dans le thème. Elle utilise la même palette, les mêmes patterns et le même cadre partagé que les futures pages locales.

Le provisioning complet ou ciblé utilisé pour cette assemblée est :

```sh
npm run env:content
npm run env:content -- home-network
```

Après une première assemblée, `--force` est requis pour remplacer un contenu
éditorial déjà présent. Le contrôle réalisé après assemblage vérifie la présence
des huit sections, des deux grilles, des liens Paris/Lyon/Marseille, de la
métadonnée `lpu_header_transparent` et l’absence de paragraphes ou de boutons
dans la seconde grille. Le front-end a été rendu avec `web-inspector` à
`1440px`, `1280px`, `768px` et sur Pixel 5 à `390px`, sans erreur console,
erreur de page, requête échouée ni débordement horizontal. L’ouverture de
« Nos Fermes » a également confirmé le méga-menu écru et le retour du header
opaque sur le hero transparent.

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
- Tester le menu au clavier : tabulation, ouverture du méga-menu au focus, fermeture avec Échap, focus visible et retour cohérent.
- Tester au pointeur l'ouverture au survol, le passage du déclencheur vers le panneau, la fermeture au clic extérieur et la fermeture de l'ancien panneau lorsqu'un autre s'ouvre.
- Vérifier que le lien parent d'une rubrique à sous-rubriques reste accessible, que le chevron est distinct de la flèche organique des boutons et qu'aucune information ne dépend du survol.
- Vérifier sur tablette et mobile que le hamburger est le seul déclencheur nécessaire, que l'overlay est celui du même bloc Navigation, que le focus reste dans le panneau et que les sous-rubriques fonctionnent par contrôle explicite.
- Vérifier séparément la variante opaque par défaut et la variante transparente activée explicitement sur un hero contrasté ; à l'ouverture du méga-menu, le header doit redevenir opaque.
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

- Templates détaillés des pages intérieures et bibliothèque complète de tous les types de sections.
- Assemblage de l’accueil Marseille : il sera réalisé après la démonstration de la Home réseau, avec les mêmes patterns adaptés aux contenus de la ferme.
- Sélecteur de fermes dynamique / administrable automatiquement. Le pattern visuel réseau est fait, pas son automatisation.
- SEO, redirections de l’ancien site, Analytics et consentement cookies.
- Shopify Paris et tout e-commerce intégré. Un simple lien externe vers la boutique peut néanmoins être placé dans le contenu du site Paris : il ne constitue pas une intégration e-commerce au thème.
- Pipeline complet d’optimisation d’images ; l’optimisation manuelle raisonnable des médias de démonstration reste requise.
- Les patterns de types `pastilles-information`, `image-de-couverture` et `colonnes-texte-image` restent hors du premier jalon de la Home réseau : ils appartiennent à la bibliothèque complète de sections, après validation de la composition éditoriale du premier assemblage.
- CPT, blocs personnalisés, plugins de fonctionnalités et build JavaScript élaboré.
- Décision finale sur les rôles locaux. L’hypothèse de travail reste : équipes locales en rôle Éditeur, sans accès quotidien au Site Editor ; les besoins réels de template-level access seront validés après le premier essai.
- Typographies à compléter plus tard : obtenir et intégrer les faces DM Sans Regular et Bold, absentes de la livraison actuelle (seule une variante italique est disponible). Ruden Regular est disponible dans `design/design site Fanny/fonts/myfonts_order_7268462657706/Ruden-Regular.otf`. Ne pas bloquer le prototype sur ce remplacement, mais le faire avant la validation finale.

## Points à traiter pendant l’exécution

1. Les destinations de « Le Projet » et « Contact » utilisent provisoirement `/` ; elles seront remplacées lorsque les pages dédiées existeront.
2. Les tailles `contentSize` et `wideSize` seront ajustées après comparaison avec la maquette.
3. Le header transparent est implémenté par la métadonnée de page `lpu_header_transparent`, la classe `lpu-header-transparent`, les variantes écrues officielles des quatre sites et le retour automatique à l'état opaque lors de l'ouverture du menu.
4. La composition interne des méga-menus est arrêtée : colonnes textuelles seules, sans ajout d'éléments visuels ; les cartes de produits de Sézane sont exclues.

L’architecture de départ du menu est fixée pour le prototype : une seule barre de header et un seul bloc Navigation par site, avec des contenus de menu propres à chaque site.
