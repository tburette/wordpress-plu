# Corrections côte à côte

## 1. Rendre les cadres de `lpu/split-zone` configurables

- Déplacé le catalogue des cadres vers `plugins/lpu-split-section/frames.json`.
- Utilisé ce catalogue comme source unique pour les options Gutenberg et les déclarations CSS du front-end et de l’éditeur.
- Ajouté le filtre `lpu_split_section_frames` pour qu’un thème ou une extension puisse ajouter, modifier ou masquer un cadre sans toucher au JavaScript.
- Conservé les cadres masqués (`available: false`) dans le rendu afin que les contenus déjà enregistrés ne perdent pas leur apparence.

À retenir : pour retirer une option sans casser les pages existantes, gardez son entrée dans `frames.json` et passez `available` à `false`. Le cadre `none` reste le repli sûr pour les valeurs inconnues.

Validation : syntaxe PHP/JavaScript et JSON vérifiés ; dans WordPress, les 11 cadres, le CSS généré et le filtre d’ajout/retrait ont été exercés avec WP-CLI.

## 2. Utiliser la version LPU partout

- Rendu les quatre patterns du plugin `lpu-split-section/*` canoniques pour les sections côte à côte.
- Migré l’ordre et les remplacements de contenu de la Home vers `lpu-split-section/split-content-image` et `lpu-split-section/split-motif-image`.
- Supprimé les six patterns côte à côte Core du thème, leurs styles de cadres et leur enregistrement de styles `core/group`.
- Restreint `lpu/split-zone` à son parent `lpu/split-section` et nettoyé la fixture des patterns pour ne plus parler de prototype.

À retenir : les blocs Core `Columns` restent utilisés pour les grilles et les colonnes de texte qui ne sont pas des sections côte à côte ; la structure deux-zones LPU est désormais la seule source pour ce cas.
