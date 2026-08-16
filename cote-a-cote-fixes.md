# Corrections côte à côte

## 1. Rendre les cadres de `lpu/split-zone` configurables

- Déplacé le catalogue des cadres vers `plugins/lpu-split-section/frames.json`.
- Utilisé ce catalogue comme source unique pour les options Gutenberg et les déclarations CSS du front-end et de l’éditeur.
- Ajouté le filtre `lpu_split_section_frames` pour qu’un thème ou une extension puisse ajouter, modifier ou masquer un cadre sans toucher au JavaScript.
- Conservé les cadres masqués (`available: false`) dans le rendu afin que les contenus déjà enregistrés ne perdent pas leur apparence.

À retenir : pour retirer une option sans casser les pages existantes, gardez son entrée dans `frames.json` et passez `available` à `false`. Le cadre `none` reste le repli sûr pour les valeurs inconnues.

Validation : syntaxe PHP/JavaScript et JSON vérifiés ; dans WordPress, les 11 cadres, le CSS généré et le filtre d’ajout/retrait ont été exercés avec WP-CLI.
