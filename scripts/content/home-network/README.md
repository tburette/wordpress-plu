# Home réseau

Ce script assemble explicitement la page `Accueil` du site réseau à partir des
patterns enregistrés dans le thème. Il réalise le premier contenu de l’Étape 6
dans la page WordPress ; il ne modifie aucun template et ne fait pas partie du
bootstrap `env:content:setup`.

L’ordre des sections est la déclaration commune
[`../home-sections-names.txt`](../home-sections-names.txt). Par rapport à la
fixture `patterns-test-page/`, ce provisioning configure uniquement les
éléments propres à la Home :

- le header transparent de la page réseau ;
- les URLs courantes des sites Paris, Lyon et Marseille ;
- la variante gris-vert sans motif de la seconde occurrence du pattern `cards`,
  avec ses trois titres seuls.

Les patterns utilisent encore `pattern-placeholder.svg` comme média de
démonstration. Les images aplaties de la maquette ne sont pas importées comme
contenu final sans validation éditoriale et autorisation d’usage ; elles seront
remplacées dans Gutenberg par les médias autorisés.

La commande refuse de remplacer une `Accueil` qui contient déjà un contenu
éditorial. Après une première assemblée, utiliser `--force` seulement pour
reproduire volontairement cette fixture locale :

```sh
npm run env:home-network:setup
npm run env:home-network:setup -- --force
```
