# Technical front-page provisioning

This script provisions the WordPress reading target for the four network
sites. It is deliberately limited to the technical foundation of Step 4:

- select or create one Page with the `accueil` slug per site;
- use the French title `Accueil` only when creating the Page;
- set `show_on_front=page` and `page_on_front` to that Page;
- leave an existing Page's title, content, and status untouched.

The newly created Page receives `accueil.html`, which contains only an
invisible explanatory HTML comment. No design section, pattern, image, or
maquette content is provisioned here. Patterns belong to Step 5 and the
network Home is assembled in Gutenberg during Step 6.

The script is safe to run repeatedly. It searches for the slug among all
non-trash WordPress post statuses, so an existing Page being edited is not
mistaken for a missing Page. The four sites and their local data are listed in
`sites.tsv`.

Run it from the repository root with:

```sh
npm run env:front-pages:setup
```

Then check the reading settings and selected Page IDs:

```sh
for site_url in lepaysanurbain.test:8888 paris.lepaysanurbain.test:8888 lyon.lepaysanurbain.test:8888 marseille.lepaysanurbain.test:8888; do
  npm run env:cli -- option get show_on_front --url="$site_url"
  npm run env:cli -- option get page_on_front --url="$site_url"
done

# For each returned ID, confirm that it is the Page with the accueil slug.
npm run env:cli -- post get PAGE_ID --fields=ID,post_type,post_name,post_status --url=SITE_URL
```
