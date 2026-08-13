# WordPress content provisioning

This directory contains the small, repeatable WP-CLI scripts that create the
content structure needed by the local multisite environment. It is separate
from `scripts/`, which contains environment and network tooling.

Run everything from `site/wordpress-lpu/` with:

```sh
npm run env:content:setup
```

The content provisioning scripts are safe to run repeatedly. They do not
replace existing WordPress content. When a script needs Gutenberg markup or
other input data, those files live beside that script in its own directory;
there is no shared data directory.

The database remains the runtime copy. The files in this directory are the
reproducible source used when the local WordPress environment is recreated.
