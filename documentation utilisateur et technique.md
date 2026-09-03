# Documentations

Elements à mettre dans la documentation utilisateur et technique qui seront
remisent au client.

## Documentation technique

### Thème

- thème custom lepaysanurbain.
  Block theme.
  From scratch

### multisite et sous-site

#### Pour ajouter un sous-site

- dns (gandi)
- .htaccess (OVH)
- Ajouter le site via la page WordPre admin du réseau
- configurer le contenu

### Header

#### Header transparent

Une variante du header transparente peut être utilisée.

Cette variante peut s'activer manuellement page par page.
L'utilisateur peut activer cette valeur via le sidebar dans l'éditeur Gutenberg
d'un post. Il y a dans la sidebar la section : "Header de la page".
Cette option ajoute le meta `lpu_header_transparent` au post.
Dans le frontend, cela ajoute une classe à body :
`<body class="lpu-header-transparent">`.
C'est iplémenté par `themes/lepaysanurbain/assets/js/editor-settings.js`.

Pour fonctionner il faut deux versions du logo. Une pour la version normale
(vert) et une pour la version transparent (écru).
Les deux logos peuvent être modifiés par un admin dans la section
`Apparence > Personnaliser`.
La configuration du logo normal est réalisée en utilisant le theme_support
'custom_logo'.
La version transparente est configurée par un réglage ajouté au 'Customize'
voir `themes/lepaysanurbain/inc/site-logos.php`.

#### Structuration Header

Structuration du header: template part : wp_template_part id 'header', nom
En-tête. Pour chaque sous-site il faut rattacher à son wp:navigation un menu compatible.

OBSOLETE:
menu compatible avec le wp_template_part 'header' :

- doit contenir un unique `<!-- wp:site-logo {"width":190,"shouldSyncIcon":false,"className":"lpu-header__logo"} /-->`
  Le logo est conçu pour être au milieu. Les éléments avants seront distribués
  à gauche du logo (centré vers le logo plus que le bord), idem pour la droite
  - class `lpu-header__logo` essentielle
  - Il doit y en avoir un et il doit être uniuque

`navigation.js` est du javascript qui détecte quand il n'y a plus assez de place
dans le menu pour afficher tous les menu items et le logo sans overflow. Quand
un manque d'espace est détecté le menu passe en mode 'compact' (mobile). Le
JS est nécessaire car le logo est une simple entrée dans le menu, au même niveau
que les menus items. Cela permet d'utiliser les fonctionnalités standards de WP
et rend les choses simple pour l'utilisateur. Cependant cela rend le code un peu
plus complexe au niveau du code.

## Documentation utilisateur

Règle pour lemenu utilisé pour le template part `header`. Voir
[section Header](#header).
