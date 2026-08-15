# Page de test des sections du design de Fanny

Ce script crée ou régénère une page sur le site réseau avec les sections issues
du design de site de Fanny, assemblées à partir des patterns de l’étape 5 dans
`theme-implementation-plan.md`. Il sert à contrôler le rendu front-end (avec
le skill `web-inspector` par exemple). Seul le sous-ensemble de sections
déclaré dans `sections-patterns-names.txt` est injecté.

La page est publiée lorsqu’elle est créée afin d’être accessible sans session
administrateur. Elle est identifiée par le slug distinctif
`lpu-sections-patterns-test`. Une page existante portant exactement ce slug
est mise à jour avec les définitions actuelles des patterns ; une page à la
corbeille est restaurée.

L’ordre des insertions est déclaré dans `sections-patterns-names.txt`. Le contenu des
patterns est lu depuis le registre WordPress du thème actif, et non recopié
dans un second fichier. La grille `cards` apparaît deux fois, comme dans la
Home de référence.

Depuis `site/wordpress-lpu/` :

```sh
npm run env:patterns-test-page:setup
```

La page est ensuite disponible à l’adresse :

`http://lepaysanurbain.test:8888/lpu-sections-patterns-test/`

## Limite avec la Home réseau

Ce script ressemble volontairement à l’assemblage prévu pour l’Étape 6 : il
lit le registre des patterns actifs et les concatène dans un ordre déclaré.
Il reste toutefois une fixture de contrôle distincte :

- il ne cible que la Page `lpu-sections-patterns-test` ;
- il régénère son contenu à chaque exécution pour tester les patterns ;
- il n’importe ni média éditorial, ni destination de lien, ni option de header
  transparent ;
- il est appelé par le bootstrap de développement, contrairement à la Home.

La Home réseau (`Accueil`) ne doit donc pas être branchée sur ce script ni
être rafraîchie par `scripts/content/setup.sh`. L’Étape 6 peut réutiliser la
même logique de lecture du registre et de déclaration de l’ordre, mais doit
porter son propre contenu éditorial persistant et son propre contrat de
provisionnement manuel.

Le script est inclus dans `env:content:setup` : la page est donc créée ou
rafraîchie automatiquement lorsque l’environnement de test est recréé. Elle
reste un outil de contrôle développeur et ne constitue pas la Home réseau.
Pour la supprimer après inspection, vérifier son slug et son titre puis la
déplacer à la corbeille depuis WordPress ; le prochain provisioning la
restaurera.
