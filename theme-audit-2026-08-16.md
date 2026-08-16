# Audit expert du thème Le Paysan Urbain

**Date :** 16 août 2026

**Périmètre :** thème bloc `lepaysanurbain`, plugin prototype `lpu-split-section`, configuration multisite, provisioning et rendu local.
**État revu :** `main` à `5c0d240`, avec des modifications locales préexistantes du plugin. Ces modifications n'ont pas été changées par cet audit.

## Verdict

La base technique est prometteuse : le thème bloc est mince, le système de navigation évite les IDs multisite figés, les tokens de marque sont centralisés et le rendu local est sain. En revanche, la Home actuelle est une **démo technique**, pas encore un rendu présentable au client ou prêt à déployer. Avant d'ajouter de nouvelles sections, il faut traiter les liens inactifs, la portabilité du contenu, la reproductibilité du bootstrap et les assets éditoriaux/typos.

Le premier objectif devrait être : rendre un seul parcours complet, réel et portable sur la Home réseau, puis appliquer cette méthode aux fermes.

## Ce qui a été vérifié

- `npm run env:status` : environnement multisite actif sur les quatre URLs locales.
- `npm run env:multisite:verify` : topologie, constantes et sites Paris/Lyon/Marseille cohérents.
- Le thème est actif sur le réseau et sur Marseille ; le plugin `lpu-split-section` est actif au niveau réseau.
- Syntaxe PHP, syntaxe JavaScript, JSON de `theme.json`, syntaxe shell et `git diff --check` : contrôles réussis.
- Rendu Chromium de la Home réseau à 1440 px, 1024 px et Pixel 5 / 390 px : statut 200, pas d'erreur console/page/requête ni de débordement horizontal observé.
- Navigation : sous-menu desktop opaque après stabilisation, overlay mobile ouvrable, focus visible et fermeture Échap observés.

Limites : aucun reset ni reprovisionnement complet n'a été lancé, car les règles du projet imposent une confirmation préalable. Le lecteur d'écran, Firefox, les couleurs forcées, Gutenberg et les vrais médias n'ont pas fait l'objet d'une recette complète.

## Priorité 1 — à corriger avant une démo éditoriale ou une mise en ligne

### 1. Les appels à l'action publiés ne mènent nulle part

La Home rend sept CTA sous la forme de `<a>` sans `href` : ils ne sont ni de vrais liens ni accessibles au clavier. L'assembleur remplace les libellés, mais n'injecte pas les destinations.

Exemples : `themes/lepaysanurbain/patterns/hero.php:26-31`, `cards.php:35-40`, `graphic-band.php:21-26`, `scripts/content/home-network/setup.sh:206-307`.

À faire :

1. Dans les patterns génériques, supprimer le CTA tant qu'il n'est pas nécessaire, ou le rendre explicitement non interactif dans le modèle.
2. Dans la fixture Home, ajouter les URLs réelles pour chaque CTA conservé.
3. Ajouter un contrôle de prépublication qui échoue sur un lien vide, un `#` provisoire ou un fragment introuvable.
4. Donner des noms de lien spécifiques : « Découvrir les activités » est préférable à sept occurrences de « En savoir plus ».

Le lien de menu `#nos-fermes` est lui aussi inopérant : aucun élément de la Home rendue ne porte cet `id`. L'ID doit être ajouté uniquement à l'instance Home de la section, pas au pattern générique qui pourrait être inséré plusieurs fois.

### 2. La Home utilise encore des médias de substitution

Neuf utilisations de `pattern-placeholder.svg` ont été observées dans le rendu. Elles remplacent les photos, modifient fortement la hiérarchie du hero et empêchent une comparaison fidèle avec `Home_1440.jpg`.

Le hero approuvé est photo-dirigé, avec texte calé à gauche et une image recadrée avec intention. Le Cover actuel est correctement structuré, mais son visuel générique ne permet pas de valider la maquette. Ne pas ajouter un carrousel uniquement parce que les maquettes montrent des points : le plan a raison de préférer un hero fixe tant qu'un besoin éditorial de carousel n'est pas établi.

À faire : constituer un manifeste versionné de médias approuvés (fichier, crédit/droits, texte alternatif, légende, focal point, section cible), les importer de façon reproductible et composer le hero avec l'image finale avant toute validation visuelle.

### 3. Les URLs de thème et de ferme sont fragiles au déploiement

Les patterns et le footer sérialisent des chemins tels que `/wp-content/themes/lepaysanurbain/...` (`parts/footer.html:7-9`, `patterns/hero.php:11-13`, `patterns/cards.php:21-22`). Les menus réseau sérialisent les domaines locaux `*.lepaysanurbain.test:8888` (`scripts/content/navigation-menus/network.html:3-5`, `scripts/content/footer-menus/network.html:7-9`).

Ces choix cassent avec un `WP_CONTENT_URL` personnalisé, une installation dans un sous-répertoire, un renommage de thème ou le multisite de préproduction en ligne.

À faire :

- Dans les patterns PHP, construire les URLs avec `esc_url( get_theme_file_uri( ... ) )`.
- Pour le template part HTML, préférer un mécanisme WordPress/site-aware plutôt qu'un chemin absolu ou `href="/"`.
- Résoudre les URLs de fermes au provisioning depuis un manifeste ou l'API multisite, comme le fait déjà la fixture Home.
- Ne pas stocker les photos éditoriales uniquement dans la médiathèque : une réinitialisation les détruirait.

### 4. Le bootstrap annoncé comme idempotent s'arrête après la première Home

`scripts/setup-multisite-network.sh:48` appelle le dispatcher complet. Celui-ci exécute systématiquement `home-network` (`scripts/content/setup.sh:43-50,85-98`), mais `home-network/setup.sh:130-136` refuse à juste titre d'écraser une Home déjà assemblée sans `--force`. Cela contredit la promesse de bootstrap rejouable dans le README et rend l'option `afterStart` risquée.

À faire : sortir l'assemblage de Home du bootstrap normal, ou lui attribuer une empreinte/version gérée. Une Home identique générée par le script doit être un no-op ; une Home modifiée par l'éditrice doit rester protégée. Ne jamais ajouter `--force` automatiquement.

### 5. La typographie de marque n'est pas complète

`theme.json` définit DM Sans comme police globale (`theme.json:117-121,169-172`), mais le thème ne contient ni WOFF2 ni `fontFace` DM Sans. Le navigateur utilise donc Arial ou une police locale non maîtrisée pour le texte et les boutons.

À faire : obtenir les fichiers sous licence DM Sans Regular 400 et Bold 700, les déclarer en `fontFace` avec `font-display: swap`, puis valider à nouveau les tailles, interlignages et césures. Cette étape est indispensable avant un feu vert visuel.

### 6. Les pages de ferme ne sont pas encore un livrable visuel

La Home Paris rend actuellement un `main` vide puis le footer. C'est cohérent avec la partie différée du plan, mais le site ne peut pas encore être comparé à `Ferme_1440.jpg` ni présenté comme un ensemble multisite fini.

À faire : après avoir stabilisé la Home réseau, assembler une ferme de référence avec la même discipline de contenu, médias et liens ; ne pas dupliquer les données réseau sans les adapter à la ferme.

## Priorité 2 — solidifier l'architecture avant de multiplier les contenus

### 7. Choisir explicitement entre patterns Core et plugin de blocs custom

Le plan privilégie les blocs Core, mais le plugin réseau `lpu-split-section` introduit aussi deux blocs `lpu/*` qui recouvrent les patterns côte-à-côte. Une fois du contenu éditorial créé avec ces blocs, le plugin devient une dépendance de production : le désactiver laisserait des blocs non pris en charge.

Décision recommandée :

- **Option A — Core-first :** conserver les patterns Core, réserver le plugin au laboratoire et le retirer de l'environnement de production avant que des pages ne l'emploient.
- **Option B — produit :** assumer le plugin, passer à `block.json`, définir support/versioning/migrations/tests, documenter son déploiement et le livrer avec le thème.

Dans les deux cas, `lpu/split-zone` doit déclarer `lpu/split-section` comme parent (`lpu-split-section.php:227-266`, `assets/editor.js:255-284`) afin qu'une demi-section ne puisse pas être insérée seule.

Le catalogue de cadres actuellement modifié doit aussi distinguer les valeurs historiques valides des valeurs proposées à l'insertion : retirer une option via `available: false` ne doit pas réécrire un bloc existant vers `ecru` ni provoquer sa validation Gutenberg.

### 8. La navigation multisite est ingénieuse, mais fragile

Le filtre `render_block_data` associe les navigations locales sans ID figé : c'est une bonne réponse au multisite. Il dépend toutefois de classes marqueuses, d'options locales, de markup interne Core et d'une grille qui ne gère explicitement que trois ou cinq entrées (`functions.php:445-490`, `theme.css:754-814`).

À faire :

- Ajouter au vérificateur une erreur claire si une option de navigation est absente, pointe vers un brouillon/corbeille ou si le nombre d'entrées déclenche le fallback hamburger.
- Traiter une mise à jour WordPress ou une modification de menu comme un événement de régression desktop/tablette/mobile sur les quatre sites.
- Préserver et basculer `srcset`/`sizes` avec `src` pour les logos transparents : le PHP les réécrit (`functions.php:340-359`), mais `navigation.js:39-46` ne restaure que `src`.

### 9. Les templates doivent garantir une politique de titres claire

`templates/page.html` n'affiche pas de `post-title`, contrairement à `single.html`. Une page créée normalement peut donc être dépourvue de H1. `index.html` gagnerait aussi à avoir un titre de requête.

À faire : définir une règle simple : chaque page possède exactement un H1. La Home le porte dans son hero ; les pages standards utilisent `post-title`; les listes utilisent `query-title`. Corriger aussi le padding de `404.html:2`, où il est placé dans l'attribut top-level `spacing` au lieu de `style.spacing`.

### 10. Les contrôles de design sont trop permissifs pour une charte stricte

`appearanceTools: true`, les unités étendues et les contrôles de bordure facilitent une dérive depuis la maquette (`theme.json:5-9,62-97`). Le filtre de l'éditeur désactive également la protection content-only pour tous les patterns non synchronisés (`functions.php:99-104`), pas seulement les patterns LPU.

À faire : proposer les décisions récurrentes sous forme de pattern/style variation nommé, garder la structure verrouillée lorsque nécessaire et choisir explicitement si les presets Core d'espacement doivent être cachés (`defaultSpacingSizes: false` est déjà un point ouvert du plan). L'éditrice doit pouvoir adapter un contenu sans devoir reconstruire — ou casser — la composition.

### 11. Les fixtures et logos ont besoin d'un contrat plus net

La page de test des patterns est publiée et réécrite par le bootstrap ; elle devrait être explicitement dev-only, privée ou `noindex`. Le provisioning de logo recherche de façon large et ne rattache pas l'attachement à une empreinte de source, ce qui peut réutiliser un SVG obsolète.

À faire : marquer les fixtures, ajouter une commande volontaire de refresh, et tracer les médias gérés avec métadonnée + hash de source.

## Priorité 3 — maintenance, documentation et assurance qualité

### 12. Compléter la vérification et la CI

Le vérificateur actuel confirme la topologie mais pas l'activation thème/plugin, les locales, pages d'accueil, logos, IDs de navigation ni contenu attendu. `package.json` ne contient pas de commandes de test/lint/CI.

À faire :

1. Une commande read-only de smoke test : syntaxe PHP/JS/shell, JSON, activation, options, pages, navigation et URLs.
2. Une CI sans Docker pour les contrôles statiques.
3. Une CI wp-env sur environnement propre pour le bootstrap, une fois la politique de reset explicitement approuvée.
4. Des captures de régression aux largeurs 390, 768, 1024, 1280 et 1440, sur Home opaque/transparente et sur une ferme.

### 13. Rendre l'environnement reproductible

`core: null`, une installation globale non épinglée de `wp-env` et l'absence de PHP fixé font dépendre une installation fraîche de la date. `jq`, `rg` et `getent` sont utilisés sans prérequis documentés ; `getent` est spécifique aux environnements Linux.

À faire : publier une matrice supportée wp-env / WordPress / PHP, documenter les dépendances ou fournir des fallbacks, et centraliser la topologie des sites dans un manifeste au lieu de dupliquer noms/domaines/IDs dans scripts et TSV.

### 14. Mettre à jour la documentation et le cache des assets

Le README affirme encore que les plugins sont vides alors que `.wp-env.json` monte le plugin LPU. Le thème versionne CSS/JS avec la version statique du thème, alors que le plugin utilise `filemtime()` : une mise à jour de CSS peut rester en cache en développement.

À faire : synchroniser README et config, puis utiliser un `filemtime()` limité au développement ou imposer une hausse de version du thème à chaque release.

## Fidélité visuelle — appréciation honnête

La fondation reproduit déjà plusieurs traits utiles de Fanny : fond écru, palette, footer sombre, motifs décoratifs, navigation centrée, layout desktop/mobile et boutons contour. Le footer est actuellement la section la plus proche de sa référence.

Les écarts importants sont cependant structurels :

- hero générique à motif au lieu d'une photographie dirigée ;
- cartes de substitution au lieu de cartes photo compactes avec action fléchée ;
- séparateurs du sélecteur de fermes rendus avec le caractère Unicode `✳` (`theme.css:410-418`) plutôt qu'avec les assets graphiques livrés ;
- corps de texte en fallback faute de DM Sans ;
- photos, textes, destinations et textes alternatifs encore provisoires.

Le breakpoint 1099 px est une décision documentée, pas un défaut en soi. Il faut néanmoins le confirmer après intégration des logos et libellés finaux : il diffère de la capture 1024 px livrée. Les captures montrent que l'overlay mobile et le sous-menu desktop sont bien écrus opaques après la transition ; une recette automatisée doit attendre la fin de cette animation pour éviter un faux positif.

## Ordre de construction conseillé

1. **Sécuriser le contenu publiable :** supprimer liens/ancres vides, décider des URL, rendre les réseaux sociaux et pages légales réels ou invisibles, ajouter la règle H1.
2. **Rendre les assets portables :** médias versionnés, URLs WordPress, DM Sans, direction artistique du hero et alt text.
3. **Fiabiliser la plateforme :** bootstrap idempotent, manifeste multisite, verification enrichie et fixtures dev-only.
4. **Décider de l'éditeur :** Core patterns ou plugin produit, puis verrouillage/variations adaptés.
5. **Assembler une ferme pilote :** contenu réel et responsive, sans copier des URLs ni IDs du réseau.
6. **Valider avant livraison :** clavier, lecteur d'écran, Firefox/Chromium, contraste/forced-colors, performance des images et captures visuelles comparées aux maquettes.

## Points solides à conserver

- Structure de block theme sobre avec `header`, `main` et `footer` sémantiques ; skip link présent.
- Tokens de couleurs, typographies et espacements déjà centralisés dans `theme.json`.
- Gestion de meta du header transparent avec nonce, capacité, sanitation REST et protection d'accès direct.
- SVG restreints au provisioning WP-CLI de confiance.
- Styles de focus, préférence de mouvement réduit et absence d'erreur navigateur observée.
- Liaison de navigation multisite sans `blog_id`/`ref` de menu figé dans les templates.

En synthèse : la bonne prochaine étape n'est pas davantage de CSS générique. C'est un **passage de prototype à contenu réel**, accompagné d'un contrat de déploiement et de tests qui garantit que le même thème reste fiable sur chaque ferme et sur le futur environnement en ligne.
