# Logos du header

Ce répertoire décrit les logos officiels utilisés par le bloc Site Logo du
thème partagé. Les SVG sont des copies versionnées des sources validées dans
`design/identité graphique Fanny/Livraison sources Le Paysan Urbain/Livraison
logos et graphisme/Logos Paysan Urbain/`.

Le header opaque écru utilise les logotypes horizontaux verts avec baseline :

- le logotype général pour le site réseau ;
- le logotype correspondant à Lyon, Marseille ou Paris pour chaque site local.

Le fichier source `Logotype horizontal écru + baseline + Lyon.svg` a été
corrigé pour contenir l'écru officiel. Les variantes écrues du réseau, de
Paris, de Lyon et de Marseille sont copiées dans les assets du thème et servent
à la variante de header transparent activable explicitement sur une page.

`setup.sh` importe les SVG officiels dans la médiathèque de chaque site et
définit le thème mod `custom_logo`. Il est idempotent pour les logos déjà
importés par leur titre géré par le script.
