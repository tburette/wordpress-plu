# Rapport de recherche — boutons et espacement des patterns

Date : 15 août 2026  
Périmètre : thème bloc `themes/lepaysanurbain`, WordPress local 7.0.4  
Nature du travail : analyse et recommandations uniquement ; aucune modification du thème

Terminologie : les concepts WordPress sont conservés dans leurs termes techniques anglais : `block`, `pattern`, `template`, `template part`, `template part area`, `synced pattern`, `Block Variation` et `Block Style Variation`.

## Synthèse décisionnelle

1. **Le parent `Boutons` n'est pas un bug du thème.** Depuis WordPress 5.4, `core/button` est officiellement un enfant de `core/buttons`. Surtout, l'insertion du bloc `Boutons` crée déjà un premier bouton : l'utilisateur ne devrait pas avoir à effectuer deux insertions. Il faut donc d'abord préciser si la gêne porte sur le libellé pluriel, sur la vue en liste à deux niveaux, ou sur un comportement local réellement anormal.
2. **Amélioration légère recommandée pour le bouton unique :** si le problème est le vocabulaire ou la découvrabilité, enregistrer une Block Variation de `core/buttons` intitulée « Bouton », préremplie avec un `core/button`. Elle conserve un contenu 100 % natif et peut disparaître avec le thème sans migration. Elle ne supprime toutefois pas le parent de la vue en liste.
3. **Ne pas supprimer la relation parent par filtre.** Les anciens boutons autonomes peuvent encore être rendus, mais cette structure est hors du chemin pris en charge depuis WordPress 5.4 et perd notamment le layout porté par `core/buttons`.
4. **Pour les rendus LPU, utiliser des Block Style Variations.** La charte décrit quatre rendus finis : texte ou flèche, chacun sur fond clair ou coloré. Le compromis le plus robuste est de proposer quatre styles français explicites. Une option plus élégante, si tous les CTA restent dans des sections LPU maîtrisées, consiste à n'exposer que « Texte » et « Flèche » et à faire dépendre automatiquement les couleurs du contexte de section.
5. **Le padding des sections peut devenir éditable, mais avec des garde-fous.** Il faut proposer des presets de charte, fluides et nommés, jamais une saisie arbitraire. Le contrôle natif existe déjà sur `Group` et `Cover`, mais WordPress 7.0 place les patterns non synchronisés en mode contenu seulement : il faut entrer dans « Modifier le pattern » pour atteindre le bloc racine et son panneau Dimensions.
6. **Une seule source de vérité pour le padding, pattern par pattern.** Les valeurs par défaut devraient être inscrites avec des tokens dans le bloc structurel réellement concerné. Hero et Text-image-motif ne doivent pas recevoir le même défaut que les autres sections. Les règles concurrentes de `.lpu-band` ne seront retirées qu'après migration et validation, tout en conservant temporairement un fallback pour les anciennes copies.
7. **Le découpage `pattern` / `template part` est déjà correct pour le header et le footer.** `parts/header.html` et `parts/footer.html` sont déclarés dans `theme.json` et appelés par les templates avec `core/template-part`. Les sept fichiers de `patterns/` sont des compositions éditoriales à copier et personnaliser ; ils ne doivent pas être déplacés vers `parts/`. Aucune `template part area` custom n'est nécessaire actuellement.

## État local constaté

- La version locale a été vérifiée par WP-CLI : **WordPress 7.0.4**.
- Les sept patterns actuels ont pour racine `core/group` ou `core/cover`, avec la classe `lpu-band`.
- Tous les CTA présents dans les patterns suivent la structure native `core/buttons` > `core/button`.
- Le thème active `appearanceTools`, fournit six presets d'espacement et interdit les valeurs de taille personnalisées avec `customSpacingSize: false`.
- Le socle de padding des sections est actuellement défini en CSS :
  - vertical : `var(--wp--preset--spacing--section)` ;
  - horizontal : `var(--wp--preset--spacing--lg)` ;
  - horizontal sous 782 px : `var(--wp--preset--spacing--md)`.
- Plusieurs patterns remplacent ce socle : Hero possède un haut fluide et un bas `xl`, Cards et Columns utilisent `xl`, Farm selector et Graphic band utilisent `section`, tandis que Text-image-motif annule le padding racine et le reporte sur ses colonnes internes. Les sept patterns ne peuvent donc pas recevoir mécaniquement le même défaut.
- Le bouton courant est un contour vert sur fond clair. La classe manuelle `lpu-button-on-color` inverse le traitement sur fond coloré. Il n'existe pas encore de choix éditorial nommé pour ces contextes ni de variante avec la flèche officielle.
- `parts/header.html` et `parts/footer.html` sont des `template parts` déclarés dans `theme.json` avec les areas natives `header` et `footer`. Les cinq templates (`front-page`, `page`, `single`, `index` et `404`) les insèrent avec `core/template-part` et un `tagName` sémantique.
- Le `template part` est partagé par le thème, mais une édition dans le Site Editor peut créer une version `wp_template_part` en base pour le site courant et masquer le fichier du thème. Le filtre `render_block_data` lie actuellement les blocs `Navigation` du header et du footer à des `wp_navigation` locaux, uniquement quand aucun `ref` n'est déjà enregistré.
- Le worktree contenait déjà des modifications avant cette étude. Elles n'ont pas été modifiées.

## 1. Éviter d'avoir à ajouter le parent « Boutons »

### Le problème réel

WordPress distingue :

- `core/buttons`, le conteneur chargé du layout, de la justification, de l'espacement et de l'ajout de plusieurs actions ;
- `core/button`, l'action individuelle chargée du libellé, de l'URL et de son apparence.

Cette architecture a été introduite dans WordPress 5.4 parce que plusieurs boutons adjacents sont fréquents. La note de version dit explicitement qu'un bouton ne peut plus être inséré hors du conteneur, tout en conservant la compatibilité de rendu des anciens contenus autonomes.

La nuance essentielle issue de la documentation utilisateur est que l'insertion de `Boutons` crée **déjà** un bouton enfant. Une variation personnalisée ne gagnerait donc pas normalement un clic. Elle servirait surtout à afficher un libellé singulier, à améliorer la découvrabilité et à préconfigurer le rendu LPU.

Il faut distinguer trois irritants possibles :

1. « Je dois insérer deux blocs » : ce n'est pas le comportement Core attendu ; il faut le reproduire localement avant de développer quoi que ce soit.
2. « Je cherche “Bouton” mais WordPress me propose “Boutons” » : une variation renommée répond bien au problème.
3. « Je ne veux pas voir deux niveaux dans la vue en liste » : ni une variation ni un pattern ne supprimera le parent ; seul un bloc autonome le ferait réellement.

### Ce que disent les sources en ligne

- La [note officielle WordPress 5.4](https://make.wordpress.org/core/2020/02/27/new-or-updated-blocks-in-wordpress-5-4/) explique l'introduction du conteneur et prescrit `Buttons` avec un `Button` imbriqué pour les templates et insertions automatiques.
- La [référence actuelle des blocs Core](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/) confirme `Parent: core/buttons` pour `core/button` et attribue le layout à `core/buttons`.
- La [documentation utilisateur du bloc Buttons](https://wordpress.org/documentation/article/buttons-block/) précise qu'un premier bouton est créé par défaut et que les suivants héritent du style adjacent.
- Une [discussion Reddit sur Button/Buttons](https://www.reddit.com/r/Wordpress/comments/1rmotek/difference_between_button_and_buttons/) et une [discussion plus ancienne sur les blocs réutilisables](https://www.reddit.com/r/Wordpress/comments/iqhyer/no_create_reusable_block_option_for_a_button_block/) illustrent la confusion entre parent et enfant. Ce sont des témoignages, pas un consensus UX.
- Sur Stack Overflow, la [question sur l'alignement de `core/buttons`](https://stackoverflow.com/questions/79094930/how-to-set-default-alignment-for-core-buttons-block-to-center-in-gutenberg-block) montre que justification et disposition doivent être configurées sur le `layout` du parent. C'est une confirmation technique isolée, pas une étude d'usage.
- Un retour WordPress 7.0 récent signale qu'un `core/button` autonome dans un pattern peut mal interagir avec le nouveau mode contenu seulement ; le wrapper standard corrige le cas : [discussion de support](https://wordpress.org/support/topic/cant-view-some-saved-patterns-or-insert-unsynced-pattern/).

Les recherches dédiées Reddit et Stack Overflow n'ont pas révélé de solution Core permettant de masquer structurellement le parent tout en conservant `core/button`. Elles confirment surtout que le parent est la structure normale et que son existence reste source de confusion.

### Solutions comparées

| Solution | Plus | Moins | Avis |
|---|---|---|---|
| Conserver Core tel quel | Zéro code, compatibilité maximale, premier bouton déjà automatique | Libellé pluriel et hiérarchie toujours visibles | À choisir si le test local montre que l'insertion en un geste suffit |
| Block Variation `core/buttons` intitulée « Bouton » avec un enfant | Contenu natif, entrée claire dans l'inséreur, préconfiguration possible, aucune migration si retirée | Parent toujours visible ; possible doublon avec l'entrée Core ; ne réduit pas forcément les clics | **Recommandée si la gêne est terminologique ou liée à la découvrabilité** |
| Pattern « Bouton LPU » | Très simple, peut précharger style, alignement et texte indicatif | Moins direct dans l'inséreur ; parent toujours présent | Bon complément, surtout pour des CTA précomposés |
| Mode `contentOnly` dans les patterns | Réduit les manipulations accidentelles de la structure | Ne simplifie pas un bouton inséré librement ; il faut tester le workflow WP 7.0 | À traiter séparément, pas comme solution principale |
| Bloc LPU autonome | Hiérarchie réellement plate, UX entièrement contrôlée | Réimplémentation du lien, des attributs, styles, accessibilité, migrations et évolutions Core | Seulement si un test éditorial prouve que la hiérarchie bloque réellement le travail |
| Retirer `parent` avec un filtre | Peu de code en apparence | Structure non prise en charge, layout perdu, risques avec l'éditeur, les patterns et les versions futures | **Déconseillée** |

### Avis demandé au sous-agent et jugement

Le sous-agent a validé le diagnostic d'architecture mais a corrigé une faiblesse importante de ma première réponse : j'avais attribué à la variation un gain de clic qu'offre déjà Core. Cette correction est pleinement méritée et modifie la recommandation : **tester d'abord l'insertion native locale**, puis utiliser la variation seulement si le vocabulaire ou la préconfiguration justifie le code.

Il a également souligné avec raison qu'une variation ne résout pas une plainte portant sur la vue en liste, et que les sources Reddit/Stack Overflow doivent rester qualifiées de témoignages techniques. J'ai conservé ces deux réserves. Sa suggestion de prévoir des transformations et une stratégie de dépréciation si un bloc custom devait être créé est pertinente, mais cette piste reste trop lourde au stade actuel.

### Recommandation finale

1. Reproduire le geste exact avec un rôle éditorial réel sur WordPress 7.0.4.
2. Si l'insertion native crée déjà le bouton enfant et que seul le terme gêne, ajouter une variation `core/buttons` de scope `inserter`, intitulée « Bouton », avec un enfant et le style LPU par défaut.
3. Éviter d'en faire une variation « active » fondée uniquement sur le nombre d'enfants : après que l'utilisateur ajoute un second bouton, cette reconnaissance devient ambiguë.
4. Ne créer un bloc autonome que si des tests utilisateurs montrent que la hiérarchie parent/enfant elle-même constitue un obstacle fréquent et coûteux.

## 2. Ajouter facilement toutes les variantes du bouton

### La matrice de la charte

La charte de Fanny définit deux axes et non une palette combinatoire ouverte :

| Contexte | Type texte | Type flèche |
|---|---|---|
| Fond clair | contour et texte verts ; survol vert plein et texte écru | capsule compacte, contour et flèche verts ; survol vert plein et flèche écrue |
| Fond coloré | contour et texte blancs ; survol blanc plein et texte vert | capsule compacte, contour et flèche blancs ; survol blanc plein et flèche verte |

Les états `hover`, `focus`, `focus-visible` et `active` font partie du style, pas des variantes à choisir manuellement.

### Le bon mécanisme WordPress

Une **Block Style Variation** est faite pour changer l'apparence d'un bloc existant. Elle ajoute une classe `is-style-*`, apparaît dans le panneau Styles et peut être changée après insertion. Ce mécanisme est différent d'une **Block Variation**, qui crée une entrée préconfigurée dans l'inséreur.

Depuis WordPress 6.6, un thème peut enregistrer une Block Style Variation avec un fichier JSON sous `/styles`, par exemple `/styles/block/button-text-light.json`, contenant `version: 3`, `slug`, `title`, `blockTypes: ["core/button"]` et `styles`. WordPress parcourt les sous-dossiers. Pour WordPress 7.0, `core/button` et ses styles acceptent en plus `:hover`, `:focus`, `:focus-visible` et `:active` directement dans ces données de style.

Le SVG officiel de la flèche et les détails non exprimables proprement dans les propriétés de style structurées resteront dans une feuille CSS ciblant la classe du style. Il ne faut pas confondre les « pseudo-selectors » de la note WP 7.0 avec les pseudo-éléments CSS `::before`/`::after`.

Sources principales :

- [Block Style Variations — Theme Handbook](https://developer.wordpress.org/themes/features/block-style-variations/)
- [Styles de sections et enregistrement JSON depuis WordPress 6.6](https://developer.wordpress.org/news/2024/06/styling-sections-nested-elements-and-more-with-block-style-variations-in-wordpress-6-6/)
- [Pseudo-classes de bouton dans `theme.json` avec WordPress 7.0](https://make.wordpress.org/core/2026/03/09/pseudo-element-support-for-blocks-and-their-variations-in-theme-json/)
- [API `register_block_style()`](https://developer.wordpress.org/reference/functions/register_block_style/)
- [Question Stack Overflow sur les styles de `core/button`](https://stackoverflow.com/questions/75735178/is-there-a-way-to-register-core-button-styles-via-theme-json) : la réponse acceptée de 2023 est désormais datée ; une réponse de 2025 montre les variations Core dans `theme.json`, mais un nouveau style doit toujours être enregistré.
- [Discussion Reddit sur les styles custom et `theme.json`](https://www.reddit.com/r/Wordpress/comments/1ah4xul/question_on_block_style_variations/) : utile pour les retours de pratique, mais antérieure à l'API JSON stabilisée de WordPress 6.6.

### Deux architectures valables

#### Option A — quatre styles explicites

- « Texte — fond clair »
- « Texte — fond coloré »
- « Flèche — fond clair »
- « Flèche — fond coloré »

**Plus :** fonctionnement prévisible partout, y compris hors d'un pattern ; libellés explicites ; changement possible après insertion ; migration simple depuis `lpu-button-on-color`.

**Moins :** quatre choix ; l'éditrice doit comprendre le contraste de son fond ; il faut décider quoi faire des styles Core « Plein » et « Contour » pour ne pas afficher six choix incohérents.

#### Option B — deux styles sémantiques et contexte automatique

- « Texte »
- « Flèche »

La section LPU porte des variables de couleurs de bouton, par exemple contexte clair ou contexte coloré, et le bouton en hérite.

**Plus :** seulement deux décisions éditoriales ; le bon contraste suit la section ; aucune matrice artificielle de quatre choix.

**Moins :** fonctionne seulement si les sections LPU sont correctement marquées ; il faut un fallback hors section ; il est impossible de déduire de manière fiable la luminance de toute couleur CSS arbitraire ; davantage de tests éditeur/front.

**Choix conseillé :** commencer par les quatre styles explicites, car rien ne garantit que tous les futurs boutons seront toujours dans une section contrôlée. Garder l'option à deux styles comme évolution après observation des usages. Les patterns doivent présélectionner le bon style afin que l'éditrice n'ait généralement rien à changer.

### Autres solutions

| Solution | Plus | Moins | Avis |
|---|---|---|---|
| Quatre Block Variations de `core/buttons` dans l'inséreur | Chaque rendu est insérable directement | Encombre l'inséreur ; mauvais outil pour changer le rendu après insertion | Raccourci complémentaire seulement |
| Classes saisies manuellement | Aucun code d'interface | Erreurs de frappe, non découvrable, contraire à l'objectif | Remplacer l'actuelle classe manuelle par des styles nommés |
| Bloc custom | Peut gérer parfaitement SVG, libellé accessible et preview | Coût de développement et de maintenance | Réserver au bouton flèche seule si le bloc Core échoue aux tests |

### Cas critique : le bouton flèche seule

Dans ces patterns, les CTA de navigation sont généralement des liens (`<a>`) visuellement traités comme boutons. Une flèche seule doit malgré tout avoir un nom accessible décrivant sa destination ou son action, jamais « Flèche ».

Deux cas doivent être distingués :

- **texte visible + flèche décorative** : conserver le texte, ajouter le SVG officiel sans nom accessible ; c'est le cas le plus simple et le plus robuste ;
- **flèche réellement seule** : conserver un libellé descriptif dans le DOM et le masquer visuellement avec une technique accessible, ou fournir un vrai attribut de nom accessible. Le libellé doit rester éditable et vérifiable dans Gutenberg.

Le [W3C rappelle qu'un lien doit avoir un nom accessible non vide](https://www.w3.org/WAI/standards-guidelines/act/rules/c487ae/) et que son but doit être compréhensible. Le `title` seul n'est pas un fallback suffisamment robuste. Il faudra tester clavier, lecteur d'écran, contraste, couleurs forcées, focus visible et cible tactile.

### Avis demandé au sous-agent et jugement

Le sous-agent a confirmé que les Block Style Variations sont le bon niveau d'abstraction et que les pseudo-classes sont réellement prises en charge dans WordPress 7.0. Il a corrigé ma proposition initiale d'enregistrement PHP : les fichiers JSON partiels sous `/styles/block/` peuvent enregistrer et styler les variantes à eux seuls. Cette correction est solide, appuyée par la documentation WordPress 6.6, et a été adoptée.

Son objection UX — deux intentions plus un contexte de section plutôt que quatre choix — est excellente sur le plan du design system. Je ne la place toutefois pas en premier pour le premier lot, car les boutons pourront exister hors des sept patterns actuels et parce qu'un fallback explicite restera nécessaire. Elle mérite un prototype comparatif.

Ses réserves sur la flèche seule sont déterminantes : si le texte est caché aussi dans l'éditeur, l'éditrice ne saura plus quel nom elle modifie. Cette variante doit donc être prototypée et auditée avant de conclure que `core/button` suffit.

## 3. Contrôler le padding des sections de patterns

### Est-ce une bonne idée ?

**Oui, si le besoin est formulé comme une densité éditoriale encadrée.** Un contenu court, une section finale ou une section de cartes n'ont pas toujours besoin de la même respiration. Refuser tout réglage pousse souvent vers des `Spacer`, des classes manuelles ou des demandes de CSS ponctuelles, qui sont moins cohérents.

**Non, si cela revient à exposer quatre champs libres et des réglages par breakpoint.** Cela transforme la bibliothèque de patterns en page-builder, augmente les incohérences entre sites et rend la revue responsive imprévisible.

Le compromis recommandé en ligne et dans WordPress est l'usage de presets : WordPress 6.1 les a précisément introduits pour permettre aux thèmes de limiter les valeurs et de les faire évoluer globalement. Les retours professionnels sont partagés mais cohérents sur le principe : certains conservent padding et gap avec 1 à 6 tokens, d'autres les masquent tant qu'un besoin client explicite n'est pas démontré.

### Sources principales

- [Introduction des presets pour padding, margin et block gap](https://make.wordpress.org/core/2022/10/07/introduction-of-presets-across-padding-margin-and-block-gap/) : motivation officielle, cohérence et mises à jour globales.
- [Guide complet de l'espacement dans les thèmes blocs](https://developer.wordpress.org/news/2023/03/everything-you-need-to-know-about-spacing-in-block-themes/).
- [Design intrinsèque et presets fluides avec `clamp()`](https://developer.wordpress.org/news/2023/02/intrinsic-design-theming-and-rethinking-how-to-design-with-wordpress/).
- [Référence actuelle de `core/group`](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-design/core-block-group/) et [référence actuelle de `core/cover`](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-media/core-block-cover/) : les deux supportent le padding.
- [Édition des patterns dans WordPress 7.0](https://make.wordpress.org/core/2026/03/15/pattern-editing-in-wordpress-7-0/) : les patterns non synchronisés sont en mode contenu seulement par défaut ; « Modifier le pattern » donne accès à l'édition complète.
- [Retour Reddit professionnel favorable à quelques tokens](https://www.reddit.com/r/ProWordPress/comments/1c9q19s/) et [retour Reddit plus restrictif](https://www.reddit.com/r/ProWordPress/comments/1lpf87e/) : deux positions utiles pour cadrer la gouvernance, sans valeur de norme.
- [Question Stack Overflow sur le spacing responsive](https://stackoverflow.com/questions/79032409/wordpress-custom-block-spacing-padding-and-margin-responsive) : elle illustre la limite des valeurs par instance et l'intérêt de presets fluides calculés avec `clamp()`.
- [Migration vers `theme.json` v3](https://developer.wordpress.org/block-editor/reference-guides/theme-json-reference/theme-json-migrations/) : `defaultSpacingSizes` contrôle la présence des presets Core.

### Solution recommandée — contrôle natif avec tokens fluides

1. Ajouter `defaultSpacingSizes: false`. Avec `theme.json` v3, les presets Core sont sinon activés par défaut en plus de ceux du thème ; `customSpacingSize: false` ne suffit pas à garantir une palette fermée.
2. Conserver `customSpacingSize: false` et rendre fluides avec `clamp()` les presets pertinents. Le token `section` existe déjà : mieux vaut le faire évoluer que créer un doublon. Un token de gouttière horizontale peut être ajouté si son usage global reste compréhensible.
3. Inscrire un padding par défaut **propre à chaque composition** dans `style.spacing.padding` du bloc concerné. Cards/Columns, Farm selector, Graphic band, Hero et Text-image-motif n'ont pas aujourd'hui les mêmes règles structurelles.
4. Garder provisoirement `.lpu-band` comme fallback pour les anciennes instances. Retirer ses déclarations de padding seulement après audit et migration ; conserver ensuite la classe pour le box sizing, la largeur, le layout et les comportements réellement spécifiques.
5. Documenter le geste WordPress 7.0 : sélectionner la section, entrer dans « Modifier le pattern », sélectionner le bloc racine, puis ouvrir Dimensions > Padding.
6. Prévoir dans les patterns le bon défaut afin que le contrôle serve d'exception, pas de tâche obligatoire. Pour Hero et Text-image-motif, conserver éventuellement le padding figé si l'exception structurelle ne peut pas être exprimée sans fragiliser la maquette.

**Plus :** blocs Core, aperçu direct, valeurs de charte, contenu sauvegardé explicite, pas de JavaScript custom, cohérence editor/front.

**Moins :** le panneau natif existe aussi pour les autres `Group` et `Cover` ; il ne sait pas limiter ses choix à la classe `.lpu-band` ; les presets dédiés à une section restent visibles sur d'autres axes et blocs ; la mise en mode édition complète du pattern doit être comprise ; les valeurs par instance ne sont pas un vrai système de breakpoints.

### Alternatives

| Solution | Plus | Moins | Avis |
|---|---|---|---|
| Trois Block Style Variations « Compacte / Standard / Généreuse » sur Group et Cover | Choix sémantique, responsive centralisé, trois options seulement | Un seul style peut être actif ; conflit possible avec de futurs styles visuels de section | Bonne option si les styles de section n'utilisent pas déjà ce mécanisme |
| Contrôle Inspector « Densité » uniquement sur `.lpu-band` | UX idéale, choix limité aux vraies sections, mapping à trois classes/tokens | JavaScript et API éditeur à maintenir ; comportement à tester avec `contentOnly` | Seulement si le contrôle natif produit réellement des erreurs |
| Padding figé en CSS | Design très stable, aucune formation | Aucun ajustement au contenu ; demandes CSS ponctuelles probables | Acceptable si les tests éditoriaux ne révèlent aucun besoin |
| Valeurs libres ou réglages desktop/tablette/mobile par instance | Flexibilité maximale | Incohérence, charge cognitive, dette responsive, gouvernance multisite difficile | **Déconseillé** |

### Migration et validation nécessaires

- Modifier les sept sources de patterns ne met pas automatiquement à jour les copies déjà insérées. Les fixtures provisionnées et les pages éditoriales existantes doivent être recensées séparément.
- Tant que les anciens contenus conservent seulement `lpu-band`, retirer son padding casserait leur espacement. Il faut donc une transition : migrer les blocs existants ou conserver temporairement un fallback CSS ciblant uniquement les sections sans attribut de padding.
- Une migration sûre doit être idempotente et ciblée : parcourir les blocs récursivement, reconnaître les racines avec `.lpu-band` ou `metadata.patternName`, ajouter uniquement les valeurs manquantes, préserver tout choix déjà sérialisé, proposer un dry-run et une sauvegarde. Rejouer le provisioning sur une Home déjà éditée risquerait d'écraser le contenu et n'est pas une migration acceptable.
- Vérifier l'ordre de cascade entre styles inline générés par les block supports, `theme.json` et CSS du thème.
- Un attribut de padding sérialisé gagnera normalement sur les règles CSS actuelles, y compris sur les exceptions et media queries du Hero. Ces exceptions doivent donc être traduites dans des valeurs fluides équivalentes ou rester non éditables.
- Valider dans Gutenberg et sur le front à 1440 px, 782 px, 781 px et 390 px, avec contenu court et long, sections adjacentes, images remplacées, Hero `Cover`, groupes colorés et focus clavier.
- Vérifier que le mode `contentOnly` expose bien le geste « Modifier le pattern » aux rôles éditoriaux attendus.

### Avis demandé au sous-agent et jugement

Le sous-agent a validé le compromis « contrôle de densité, pas page-builder » ainsi que la faisabilité native sur `Group` et `Cover`. Sa correction sur `defaultSpacingSizes: false` est importante et pleinement adoptée : sans elle, la palette annoncée comme fermée peut encore contenir les valeurs Core.

Son examen pattern par pattern a révélé l'insuffisance principale de ma première réponse : le padding n'est pas uniforme. Hero et Text-image-motif portent des règles de composition qui ne doivent pas être écrasées par un défaut générique. J'ai donc remplacé la généralisation aux sept racines par des valeurs initiales propres à chaque pattern, avec la possibilité de laisser ces deux compositions non éditables.

Sa stratégie de migration est également supérieure à un retrait immédiat de `.lpu-band` : le fallback doit rester tant que les copies non synchronisées en base n'ont pas reçu leurs attributs. L'idée d'une migration idempotente avec dry-run est retenue ; le provisioning ne doit servir qu'aux contenus de développement encore jetables et uniquement dans le cadre des confirmations destructives prévues par le projet.

Enfin, son classement nuance utilement la recommandation : padding natif si l'opération est occasionnelle et réservée à une personne formée ; styles « Compacte / Standard / Généreuse » si elle devient fréquente ; contrôle Inspector seulement après preuve d'un problème UX. Je retiens ce classement.

## 4. `pattern`, `template part` et `template part area`

### Réponse courte

Oui, un `header` ou un `footer` de site doit généralement être un `template part`, pas un `pattern`. Mais ce n'est pas une conversion à faire ici : le thème possède déjà `parts/header.html` et `parts/footer.html`, les déclare dans `theme.json` et les inclut dans tous ses templates avec `core/template-part`. Le découpage actuel est donc conforme à l'architecture d'un block theme.

Les sept fichiers de `patterns/` ont un autre rôle. Ils décrivent des compositions de page — Hero, Text-image, Cards, Columns, Farm selector, Text-image-motif et Graphic band — que l'éditrice peut insérer puis adapter. Ils doivent rester des regular patterns tant que leur texte, leurs images, leurs liens ou leur ordre peuvent varier d'une page à l'autre.

### Les concepts à ne pas confondre

| Concept | Rôle WordPress | Synchronisation et emplacement | Usage LPU |
|---|---|---|---|
| `pattern` (regular pattern) | Point de départ composé de plusieurs blocks, inséré puis personnalisable | Copie non synchronisée dans le contenu de la page ; source dans `patterns/` | Sections éditoriales réutilisables, comme Hero ou Cards |
| `synced pattern` | Composition de contenu réutilisable dont les instances restent liées | Mise à jour propagée ; contenu enregistré comme `wp_block` ; aucun rôle dans la template hierarchy | CTA ou horaires qui doivent rester identiques, éventuellement avec des overrides ; pas le chrome du site |
| `template part` | Section structurelle réutilisée par un ou plusieurs templates | Incluse par `core/template-part`, généralement depuis `parts/` ; une personnalisation du Site Editor peut être enregistrée dans `wp_template_part` | Header, Footer, Sidebar ou Comments partagés |
| `template` | Structure top-level choisie par la template hierarchy pour un type de vue | Fichier dans `templates/`, pouvant composer des `template parts`, du contenu dynamique et des patterns | `front-page`, `page`, `single`, `index` et `404` |
| `template part area` | Classification sémantique et aide de navigation pour les `template parts` | Areas natives `header`, `footer` et `uncategorized` (General) ; une custom area se déclare via `default_wp_template_part_areas` et reçoit notamment un `area_tag` | Aucun besoin custom détecté actuellement |

La documentation WordPress distingue explicitement la structure de site portée par les `template parts`, le contenu synchronisé porté par les `synced patterns` et le contenu non synchronisé porté par les regular patterns : [Comparing Patterns, Template Parts, and Synced Patterns](https://wordpress.org/documentation/article/comparing-patterns-template-parts-and-reusable-blocks/). Les `template parts` sont des sections plus petites incluses dans un ou plusieurs templates, via le block `core/template-part` : [Template Parts — Theme Handbook](https://developer.wordpress.org/themes/templates/template-parts/).

### `template part area` : ce que cela fait, et ce que cela ne fait pas

Une `template part area` n'est pas un slot CSS, une Grid area ou une région qui insérerait automatiquement un bloc. C'est une catégorie sémantique qui regroupe des `template parts` et facilite la navigation dans le Site Editor. L'article de référence la décrit comme une sorte de taxonomie : un thème peut avoir plusieurs `template parts` dans une même area, et l'éditeur peut les présenter comme des options pour cette région structurelle. Les areas natives sont `header`, `footer` et `uncategorized`; une custom area passe par le filtre `default_wp_template_part_areas`, puis les `template parts` sont affectés à cette area dans `theme.json` : [Upgrading the site-editing experience with custom template part areas](https://developer.wordpress.org/news/2023/06/upgrading-the-site-editing-experience-with-custom-template-part-areas/).

Le bon critère n'est donc pas « faut-il plusieurs variantes ? ». Une seule `Sidebar` peut déjà mériter une area dédiée si cette région structurelle doit être repérable et distinguée de General. À l'inverse, créer une area pour chaque section visuelle ou chaque pattern ajouterait une taxonomie inutile dans l'interface. Dans LPU, `header` et `footer` suffisent aujourd'hui. Une area `sidebar`, `utility` ou `footer-secondary` ne se justifierait que lorsqu'une telle région devient réellement structurelle, est appelée par des templates et doit être administrée comme un groupe distinct.

Un `pattern` peut être utilisé pour construire un `template` ou un `template part` grâce au block `Pattern` (markup `<!-- wp:pattern {"slug":"namespace/slug"} /-->`). C'est une technique de composition DRY, pas une nouvelle catégorie d'architecture : [Usage in Templates](https://developer.wordpress.org/themes/patterns/usage-in-templates/). Il est préférable de construire un `template part` avec un `pattern` si cela évite une duplication réelle ; il ne faut pas mettre un `template part` dans un regular pattern destiné à du contenu de page, car cela mélange la structure globale et le contenu éditorial.

### Audit du dépôt

#### Header et Footer : ne rien déplacer

- `parts/header.html` et `parts/footer.html` contiennent déjà la composition structurelle partagée.
- `theme.json` les enregistre dans les areas natives `header` et `footer`.
- Les cinq templates les appellent avec `core/template-part` et `tagName: "header"` ou `tagName: "footer"`, ce qui conserve la sémantique HTML.
- Le header et le footer ne portent pas de `ref` fixe vers un `Navigation` post. `functions.php` utilise `render_block_data` pour injecter le `ref` de la navigation du site courant, à partir des classes `lpu-header__navigation` et `lpu-footer-navigation`. C'est une bonne raison de conserver un seul fichier de `template part` dans le thème plutôt que de dupliquer un header par site.

Il faut toutefois documenter une limite multisite. Le fichier du thème est partagé, mais chaque site peut enregistrer sa propre version `wp_template_part` en base après une édition dans le Site Editor. Cette version publiée masque alors le fichier du thème pour ce site et peut survivre à une mise à jour du code ; l'outil de retour à la version du thème est alors « Réinitialiser/Effacer les personnalisations ». Ce fonctionnement est décrit dans la documentation de l'architecture [Full Site Editing templates](https://developer.wordpress.org/block-editor/explanations/architecture/full-site-editing-templates/). Avant de rendre le header ou le footer administrables, il faut décider quels rôles peuvent les éditer et comment les différences fichier/base seront auditées.

Le filtre de navigation ne remplace pas un `ref` déjà sérialisé. Si un administrateur sauvegarde dans la version en base un `Navigation` avec un `ref`, ce `ref` devient prioritaire et peut contourner le choix piloté par les options LPU. Ce scénario doit être testé sur chaque site : soit le `ref` édité devient explicitement la source de vérité, soit l'édition structurelle du header/footer doit être restreinte et documentée. Ce point est une décision de gouvernance, pas une raison de transformer le `template part` en pattern.

#### Les sept patterns : les conserver comme regular patterns

Hero, Text-image, Cards, Columns, `network-farm-selector`, Text-image-motif et Graphic Band sont des layouts dont l'éditeur doit pouvoir changer le contenu après insertion. Les transformer en `template parts` ferait de chaque instance une dépendance structurelle globale ; modifier le texte ou l'image d'un `template part` toucherait potentiellement toutes les pages qui l'appellent. Leur nature correspond donc à un regular pattern, avec les contrôles de padding et de style étudiés dans les sections précédentes.

`network-farm-selector` mérite néanmoins une décision fonctionnelle explicite. Le script de provisioning remplace ses placeholders par les noms et URLs des sites du réseau. Si cette section reste une présentation libre, personnalisable et éventuellement différente d'une page à l'autre, le regular pattern est correct. Si elle doit toujours afficher la liste canonique du réseau, une copie de pattern peut vieillir ou conserver de mauvais liens : un block dynamique alimenté par la configuration multisite serait plus fiable. Un `template part` ne serait justifié que si le sélecteur devenait une région structurelle imposée par plusieurs templates, pas simplement parce qu'il est réutilisé.

Même règle pour un futur CTA de campagne : regular pattern si chaque instance varie ; `synced pattern` si une source éditoriale unique doit se propager ; `template part` seulement s'il appartient au chrome structurel du site.

### Solutions comparées

| Solution | Plus | Moins | Décision LPU |
|---|---|---|---|
| Garder les sept fichiers comme regular patterns | Copie indépendante, liberté éditoriale, aucune dépendance au contenu global | Une correction dans la source ne met pas à jour les copies existantes ; le contenu canonique peut vieillir | **Recommandée** pour les sections de page actuelles |
| Convertir une section en `template part` | Une source structurelle partagée, édition globale, bonne intégration aux templates | Toute modification est globale ; version en base pouvant masquer le fichier ; inadapté au contenu par page | Uniquement pour une vraie région structurelle (`header`, `footer`, `sidebar`, etc.) |
| Inclure un `pattern` dans un `template part` via `core/pattern` | Évite la duplication du markup interne, source de composition claire | Ajoute une référence à maintenir ; le contenu inclus reste une dépendance du `template part` | À utiliser seulement lorsqu'une duplication réelle apparaît |
| Créer un `synced pattern` | Mise à jour propagée, adapté à un CTA ou une information commune | Pas dans la template hierarchy ; pas d'area ; risque de modifier du contenu de page par surprise | Pour contenu récurrent, jamais pour header/footer |
| Ajouter une custom `template part area` | UI mieux organisée pour une région structurelle distincte et ses parts | Code PHP + déclaration `theme.json`, nouvelle catégorie, intérêt limité si aucun autre part ne l'utilise | **Ne pas ajouter maintenant** ; réévaluer avec une vraie Sidebar/utility |
| Utiliser un `template` | Structure top-level appropriée à un type de vue | Trop large pour une section interne ; ne remplace ni pattern ni template part | Déjà utilisé par les cinq fichiers de `templates/` |

### Avis demandé au sous-agent et jugement

Le sous-agent confirme la conclusion principale : les sept sections restent des regular patterns et `parts/header.html` / `parts/footer.html` sont déjà au bon endroit, correctement déclarés et appelés par les cinq templates. Cette partie est retenue sans modification.

Il a apporté quatre corrections importantes :

1. La distinction `synced pattern` / `template part` doit être formulée en termes de contenu synchronisé contre structure de site. Un `synced pattern` est enregistré comme `wp_block`, n'a ni area ni rôle dans la template hierarchy ; il convient à un CTA identique, pas au chrome.
2. Le critère d'une custom `template part area` n'est pas le nombre de variantes. Une seule Sidebar peut justifier une area si elle forme une région structurelle distincte. J'ai corrigé la formulation initiale pour retenir le critère sémantique et d'interface, tout en concluant qu'aucune area custom n'est nécessaire dans LPU aujourd'hui.
3. `network-farm-selector` est le seul pattern actuel qui mérite une décision métier : le provisioning injecte des URLs multisite. Si les données doivent rester canoniques, un block dynamique est plus solide qu'une copie vieillissante ; cela ne suffit pas à en faire un `template part`. Cette réserve est adoptée.
4. La navigation site-local doit être examinée avec les versions `wp_template_part` en base. Le filtre `render_block_data` ne remplace pas un `ref` déjà enregistré ; le scénario Site Editor peut donc contourner la liaison par options. Cette observation est vérifiable dans le code et devient une validation obligatoire avant d'autoriser l'édition globale.

Le sous-agent a enfin confirmé que `core/pattern` est officiellement supporté dans les templates et `template parts`, mais qu'il doit rester un outil DRY ponctuel, non une raison de fragmenter artificiellement le header ou le footer. Je retiens cette nuance.

### Recommandation finale

1. Ne déplacer aucun des sept patterns vers `parts/`.
2. Conserver `header` et `footer` comme `template parts` dans les areas natives existantes.
3. Ne créer aucune custom `template part area` tant qu'une Sidebar, une utility region ou une autre région structurelle distincte n'existe pas réellement.
4. Décider si `network-farm-selector` est libre (regular pattern) ou canonique (à terme, block dynamique) avant d'investir dans son évolution.
5. Tester l'édition Site Editor du header/footer sur le réseau multisite : version `wp_template_part` en base, bouton de réinitialisation et priorité d'un `Navigation` `ref` enregistré.

## Ordre de mise en œuvre proposé

Cette étude ne lance pas un nouveau lot d'implémentation. Si ces recommandations sont acceptées, l'ordre prudent serait :

1. confirmer l'architecture `template part` de `header`/`footer` et décider la gouvernance des versions Site Editor en base ;
2. trancher le statut libre ou canonique de `network-farm-selector` ;
3. test utilisateur court sur l'insertion native `Boutons` et définition exacte de l'irritant ;
4. prototype de la variation d'inséreur « Bouton » seulement si nécessaire ;
5. prototype des quatre styles LPU, avec audit particulier de la flèche seule ;
6. décision entre quatre styles explicites et deux styles contextuels ;
7. prototype du padding natif sur un seul pattern non critique ;
8. validation editor/front et mobile ;
9. migration planifiée des patterns et contenus existants ;
10. seulement ensuite, généralisation aux sept patterns.

Ce travail devra s'inscrire dans l'étape courante du plan d'implémentation et ne pas être considéré comme un nouveau chantier parallèle sans validation.
