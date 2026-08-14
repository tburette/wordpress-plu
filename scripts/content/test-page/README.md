# Page de test du thème

Restaure la page de test du thème sur le site réseau, à l’adresse
`http://lepaysanurbain.test:8888/titre-de-test-ruden/`.

Le contenu Gutenberg est dans `titre-de-test-ruden.html` et ses métadonnées
dans `page.tsv`, séparément de la logique du script.
Une page déjà présente avec ce slug est laissée inchangée.

Depuis `site/wordpress-lpu/` :

```sh
npm run env:test-page:setup
```
