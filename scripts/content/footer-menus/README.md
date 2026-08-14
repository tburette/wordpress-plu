# Menus de footer

Ce répertoire contient les fragments Gutenberg qui provisionnent le contenu
des colonnes de footer. La composition visuelle suit la référence
`design/design site Fanny/livraison-des-fichiers-pu_2026-07-30_1227/sections/pied-de-page.jpg` : trois groupes principaux de rubriques, puis un groupe secondaire
pour les informations légales. La structure visuelle du footer reste dans
`themes/lepaysanurbain/parts/footer.html`; les liens sont propres au site
courant, comme pour le menu principal.

Le thème associe le bloc Navigation portant la classe
`lpu-footer-navigation` à l'identifiant stocké dans l'option
`lpu_footer_navigation_id`. Le menu réseau et les menus de ferme peuvent donc
évoluer séparément sans figer d'identifiant WordPress dans le thème partagé.

Les destinations qui correspondent à des pages pas encore créées utilisent
provisoirement `/` ou une ancre. Elles devront être remplacées quand les pages
dédiées et les URL sociales/légales seront validées.

Depuis `site/wordpress-lpu/` :

```sh
npm run env:footer-navigation:setup
```

Le script est idempotent : il crée le menu `footer-principal` s'il manque et
ne réécrit pas un menu existant.
