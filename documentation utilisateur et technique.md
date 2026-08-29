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

Structuration du header: template part : wp_template_part id 'header', nom
En-tête. Pour chaque sous-site il faut rattacher à son wp:navigation un menu compatible.

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
