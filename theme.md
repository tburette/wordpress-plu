# Pre-prepared editor building blocks in a WordPress theme

This guide uses the current theme’s text domain, **atheme**, and assumes WordPress 7.1. Paths are relative to themes/atheme.

| Need                                                      | Best fit                   |
| --------------------------------------------------------- | -------------------------- |
| Different appearance for one existing block               | Block style variation      |
| Different visual treatment for a whole section            | Section style              |
| A preset existing block, possibly with inner blocks       | Block variation            |
| A ready-made layout that editors can change independently | Non-synced pattern         |
| One layout whose original updates every use               | Synced pattern             |
| Central layout plus per-instance content                  | Synced pattern + overrides |
| Preserve structure after insertion                        | Locks/template locking     |
| Restrict direct children of a custom container            | allowedBlocks              |
| Remove a block type’s design controls                     | theme.json settings.blocks |

## 1. Block style variation (custom block style)

**What it is.** This is an alternate appearance for one existing block type. It does not create a new block type, does not add initial inner blocks, and does not alter behaviour. Selecting it adds an `is-style-{slug}` class to the block wrapper, which WordPress styles with CSS or theme.json. The standard UI lets an editor select one style variation at a time.

Use this for “Outline button”, “Callout heading”, or “Framed image”. If the choice needs a pre-filled layout or settings, use a block variation or a pattern instead. [Official documentation](https://developer.wordpress.org/themes/features/block-style-variations/)

**Where it appears.** Select the relevant block in the Post Editor or Site Editor, then open the selected block’s **Styles** panel in the right inspector.

**Recommended registration: a JSON partial.** WordPress discovers it automatically because it is below the theme’s styles directory and has a `blockTypes` property.

File: styles/block/callout-heading.json

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "title": "Callout heading",
  "slug": "callout-heading",
  "blockTypes": ["core/heading"],
  "styles": {
    "border": {
      "left": {
        "color": "#EE0044",
        "style": "solid",
        "width": "0.3rem"
      }
    },
    "spacing": {
      "padding": {
        "left": "0.75rem"
      }
    }
  }
}
```

The selected Heading receives `is-style-callout-heading`.

**PHP alternative.** Choose this instead of the JSON file when registration must be conditional or generated at runtime.

File: functions.php

```php
add_action( 'init', function () {
    register_block_style(
        'core/heading',
        array(
            'name'       => 'callout-heading',
            'label'      => __( 'Callout heading', 'atheme' ),
            'style_data' => array(
                'border' => array(
                    'left' => array(
                        'color' => '#EE0044',
                        'style' => 'solid',
                        'width' => '0.3rem',
                    ),
                ),
            ),
        )
    );
} );
```

## 2. Section style

**What it is.** “Section style” is not a separate API. It is a block style variation intended for section/container blocks such as Group, Cover, Columns, and Column. It is normally registered for several container block types with the same slug, and can style the container **plus nested headings, links, buttons, and child blocks**.

It is ideal for “Dark section”, “Soft card”, or “Brand-colour band”. It changes appearance; it does not insert a layout. Pair it with a pattern when editors also need a ready-made group of blocks. [Official documentation](https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/)

**Where it appears.** Select a compatible Group, Cover, or Columns block and use its **Styles** panel. The variation is available on every type named in `blockTypes`.

File: styles/block/section-dark.json

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "title": "Dark section",
  "slug": "section-dark",
  "blockTypes": ["core/group", "core/columns", "core/cover"],
  "styles": {
    "color": {
      "background": "#1B1B1D",
      "text": "#FFFFFF"
    },
    "spacing": {
      "padding": {
        "top": "3rem",
        "right": "2rem",
        "bottom": "3rem",
        "left": "2rem"
      }
    },
    "elements": {
      "heading": {
        "color": { "text": "#FFFFFF" }
      },
      "link": {
        "color": { "text": "#00ee11" }
      }
    },
    "blocks": {
      "core/button": {
        "color": {
          "background": "#FFFFFF",
          "text": "#1B1B1D"
        }
      }
    }
  }
}
```

The PHP equivalent can register one variation for multiple blocks:

```php
add_action( 'init', function () {
    register_block_style(
        array( 'core/group', 'core/columns', 'core/cover' ),
        array(
            'name'       => 'section-dark',
            'label'      => __( 'Dark section', 'atheme' ),
            'style_data' => array(
                'color' => array(
                    'background' => '#1B1B1D',
                    'text'       => '#FFFFFF',
                ),
            ),
        ),
    );
} );
```

Use a shared slug such as `section-dark` or `section-1`. A section style is not a global style variation: global variations skin the whole site; section styles are chosen on individual blocks.

## 3. Block variation

**What it is.** A block variation is a preset version of an existing block. It can set initial attributes and, where the original block accepts inner blocks, an initial inner-block structure. It still serializes as the original block type: a Media & Text variation is a `core/media-text` block, not a new block named `atheme/feature-split`.

Use this for a preconfigured Core block that should still expose its normal controls afterward. Create a true custom block if you need new stored data, custom rendering, or a bespoke editor UI. [Official documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-variations/)

**Where it appears.** With the `inserter` scope, it is its own searchable item in the Block Inserter. Query and Columns also use a visual variation picker; the `block` scope makes a variation available to that kind of picker. The `transform` scope exposes it as a transformation.

File: functions.php

```php
add_action( 'enqueue_block_editor_assets', function () {
    wp_enqueue_script(
        'atheme-feature-split-variation',
        get_theme_file_uri( 'assets/js/feature-split-variation.js' ),
        array( 'wp-blocks', 'wp-dom-ready', 'wp-i18n' ),
        wp_get_theme()->get( 'Version' ),
        true
    );
} );
```

File: assets/js/feature-split-variation.js

```js
(function (blocks, domReady, i18n) {
  const { registerBlockVariation } = blocks;
  const { __ } = i18n;

  domReady(function () {
    registerBlockVariation("core/media-text", {
      name: "atheme/feature-split",
      title: __("Feature split", "atheme"),
      description: __("A wide media-and-copy feature section.", "atheme"),
      icon: "align-pull-right",
      attributes: {
        align: "wide",
        mediaPosition: "right",
        backgroundColor: "redish",
      },
      innerBlocks: [
        [
          "core/heading",
          {
            level: 2,
            placeholder: __("Feature title", "atheme"),
          },
        ],
        [
          "core/paragraph",
          {
            placeholder: __("Explain the feature…", "atheme"),
          },
        ],
      ],
      scope: ["inserter"],
      isActive: ["align", "mediaPosition", "backgroundColor"],
    });
  });
})(window.wp.blocks, window.wp.domReady, window.wp.i18n);
```

`isActive` lets WordPress recognize an existing block as this variation. A custom block you own can alternatively declare its own `variations` in its block.json.

**PHP alternative.** Use the server-side filter when the choices depend on registered post types, taxonomies, or other PHP data. In PHP, use an attribute array for `isActive` rather than a JavaScript callback.

```php
add_filter(
    'get_block_type_variations',
    function ( $variations, $block_type ) {
        if ( 'core/image' !== $block_type->name ) {
            return $variations;
        }

        $variations[] = array(
            'name'       => 'atheme/wide-image',
            'title'      => __( 'Wide image', 'atheme' ),
            'scope'      => array( 'inserter' ),
            'attributes' => array( 'align' => 'wide' ),
            'isActive'   => array( 'align' ),
        );

        return $variations;
    },
    10,
    2
);
```

## 4. Non-synced pattern

**What it is.** A non-synced pattern is a prebuilt group of blocks. Insertion copies the markup into the post or template. Each resulting copy is independent, so editing one copy does not affect another, and a later change to the theme pattern file does not update old copies.

This is usually the right answer for an editor-customisable hero, card, testimonial, or call-to-action layout. Theme-registered patterns are always non-synced. [Official documentation](https://developer.wordpress.org/themes/patterns/introduction-to-patterns/)

**Where it appears.** In the Post Editor or Site Editor: **+ → Patterns**, then search or browse a category. In a block theme, **Appearance → Editor → Patterns** is the central patterns library. A theme-file pattern remains source-controlled rather than becoming an editable database pattern.

**Recommended registration.** Every valid PHP file in the theme’s patterns directory is registered automatically.

File: patterns/feature-card.php

```php
<?php
/**
 * Title: Feature card
 * Slug: atheme/feature-card
 * Categories: featured
 * Description: A heading, short description, and call-to-action.
 * Viewport width: 840
 */
?>
<!-- wp:group {"backgroundColor":"redish","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-redish-background-color has-background">
    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading"><?php esc_html_e( 'A useful feature', 'atheme' ); ?></h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p><?php esc_html_e( 'Give editors a starting point they can adapt.', 'atheme' ); ?></p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button">
            <a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Learn more', 'atheme' ); ?></a>
        </div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->
```

**Programmatic alternative.** Prefer the file form unless PHP must generate the pattern dynamically.

```php
add_action( 'init', function () {
    register_block_pattern(
        'atheme/short-notice',
        array(
            'title'      => __( 'Short notice', 'atheme' ),
            'categories' => array( 'text' ),
            'content'    => '<!-- wp:paragraph --><p>Notice text</p><!-- /wp:paragraph -->',
        )
    );
} );
```

## 5. Synced pattern

**What it is.** A synced pattern has one stored original. Each insertion references it, so editing the original updates every instance. It is the modern name for a reusable block.

Use it for an identical notice, CTA, disclaimer, or component that must remain centrally maintained. For a site header/footer, prefer a template part: that is the site-structure tool. Synced patterns are database records of the `wp_block` post type, not a theme-file registration format. [Official documentation](https://developer.wordpress.org/themes/patterns/introduction-to-patterns/)

**Where it appears.**

- Select blocks, use their toolbar **Options → Create pattern**, name it, and turn on **Synced**.
- Or use **Appearance → Editor → Patterns → Create pattern** and select Synced.
- Insert it from **+ → Patterns**. A selected instance has **Edit original**, which opens the central pattern editor.

**Theme limitation.** There is no supported `register_block_pattern()` option that makes a bundled theme pattern synced. Admin creation is the recommended method.

**Programmatic creation.** This is a deliberate migration/setup technique, not normal theme registration. Run it once from a setup plugin or migration, not on every request. Omitting `wp_pattern_sync_status` creates a fully synced pattern; the `unsynced` meta value is only used for non-synced user patterns.

```php
function atheme_create_synced_site_notice() {
    if ( get_option( 'atheme_synced_site_notice_id' ) ) {
        return;
    }

    $pattern_id = wp_insert_post(
        array(
            'post_type'    => 'wp_block',
            'post_status'  => 'publish',
            'post_title'   => __( 'Site notice', 'atheme' ),
            'post_content' => '<!-- wp:paragraph --><p>Important site notice.</p><!-- /wp:paragraph -->',
        ),
        true
    );

    if ( ! is_wp_error( $pattern_id ) ) {
        update_option( 'atheme_synced_site_notice_id', $pattern_id );
    }
}
```

Once its ID is 123, an insertion is a reference:

```html
<!-- wp:block {"ref":123} /-->
```

## 6. Page pattern (starter page pattern)

**What it is.** A page pattern is a non-synced pattern marked as a starting point for new Page content (or another block-editor post type). It is not a page template. Selecting it copies its blocks into that one new page, where the editor can work normally.

It suits About, Contact, Service, and landing-page starting layouts. Do not normally put the global header or footer in it. [Official documentation](https://developer.wordpress.org/themes/patterns/starter-patterns/)

**Where it appears.** On **Pages → Add New**, WordPress can offer a starter-pattern chooser before the empty canvas. It is also an ordinary pattern in **+ → Patterns**.

**Registration.** This is a normal pattern file with two special headers: `Block Types: core/post-content` and `Post Types`.

File: patterns/page-about.php

```php
<?php
/**
 * Title: About page
 * Slug: atheme/page-about
 * Categories: page
 * Keywords: starter, about
 * Block Types: core/post-content
 * Post Types: page
 * Viewport width: 1100
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading {"level":1} -->
    <h1 class="wp-block-heading"><?php esc_html_e( 'About us', 'atheme' ); ?></h1>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"fontSize":"large"} -->
    <p class="has-large-font-size"><?php esc_html_e( 'Introduce the organisation and its people.', 'atheme' ); ?></p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

The filename is not what makes it a page pattern; those two headers are.

## 7. Pattern override

**What it is.** An override is a per-instance value inside a synced pattern. It lets specific supported attributes—such as Heading text, Paragraph text, Image information, or Button text/link—differ for each use while the shared layout and design remain centrally synced.

It is neither a standalone pattern nor a universal override system. It uses the Block Bindings API, stores values on the referencing `core/block` instance, and maps them with a unique `metadata.name` for each overridable block. [Official Block Bindings documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/)

**Where it appears.**

1. Open a synced pattern’s **Edit original** screen.
2. Select a compatible child block.
3. In the right inspector’s **Advanced** panel choose **Enable overrides**, then provide a unique name.
4. In an inserted instance, edit the enabled content or reset it to the original.

**Code form.** This markup must live in the original synced pattern in the database. It cannot become a synced pattern merely by putting it in a theme’s patterns directory. Pairing it with content-only editing makes the intended editing surface clearer.

```html
<!-- wp:group {"templateLock":"contentOnly","layout":{"type":"constrained"}} -->
<div class="wp-block-group">
  <!-- wp:heading {"metadata":{"bindings":{"content":{"source":"core/pattern-overrides"}},"name":"card-title"}} -->
  <h2 class="wp-block-heading">Default card title</h2>
  <!-- /wp:heading -->

  <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/pattern-overrides"}},"name":"card-summary"}} -->
  <p>Default card summary.</p>
  <!-- /wp:paragraph -->

  <!-- wp:buttons -->
  <div class="wp-block-buttons">
    <!-- wp:button {"metadata":{"bindings":{"text":{"source":"core/pattern-overrides"},"url":{"source":"core/pattern-overrides"}},"name":"card-cta"}} -->
    <div class="wp-block-button">
      <a class="wp-block-button__link wp-element-button" href="#">Read more</a>
    </div>
    <!-- /wp:button -->
  </div>
  <!-- /wp:buttons -->
</div>
<!-- /wp:group -->
```

For pattern ID 123, one instance can store different values like this:

```html
<!-- wp:block {"ref":123,"content":{"card-title":{"content":"A different title"},"card-summary":{"content":"Text for this card only."},"card-cta":{"text":"Explore","url":"/explore"}}} /-->
```

The editor UI is safer than authoring this markup manually. The markup explains why override names must be unique inside the pattern.

## 8. Block locking

**What it is.** Block locking protects a specific block from structural changes. Its normal flags are `move` and `remove`. It does not generally stop an editor from changing the block’s content or style controls.

Use it for an important image, required heading, or a pattern element that must not be accidentally dragged or deleted. It is an editorial safeguard, not access control: someone who can unlock blocks can change it. [Official Block Locking API documentation](https://developer.wordpress.org/block-editor/how-to-guides/curating-the-editor-experience/block-locking/)

**Where it appears.** Select a block, open toolbar **Options**, then **Lock**. The dialog has **Disable movement** and **Prevent removal**. A container adds **Apply to all blocks inside**.

**Code example.**

```html
<!-- wp:heading {"lock":{"move":true,"remove":true}} -->
<h2 class="wp-block-heading">This heading must remain in this position</h2>
<!-- /wp:heading -->
```

Use only `"move":true` or `"remove":true` if just one restriction is needed.

To let only visual-site editors change locks through the UI:

```php
add_filter( 'block_editor_settings_all', function ( $settings ) {
    $settings['canLockBlocks'] = current_user_can( 'edit_theme_options' );
    return $settings;
} );
```

## 9. Template locking and content-only editing

**What it is.** Despite the name, `templateLock` often refers to the child-block template inside a container such as Group, Cover, Columns, Column, or Navigation. It controls that container’s inner-block structure, not only files in the templates directory.

| Value           | Structure                                      | Editor experience                                                               |
| --------------- | ---------------------------------------------- | ------------------------------------------------------------------------------- |
| `all`           | No insertion, removal, or movement of children | Child content/settings remain normally editable unless separately restricted    |
| `insert`        | No insertion or removal; children may move     | Child content/settings remain normally editable                                 |
| `contentOnly`   | No structural editing                          | Only recognised text/media content is exposed in a simplified content interface |
| `false`/omitted | No lock                                        | Normal editing                                                                  |

For `all`, add normal block locking to the wrapper too if the wrapper itself should not move or disappear. [Official block-template documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-templates/)

**Where it appears.**

- A container’s **Lock** dialog can create the common `all` form with **Apply to all blocks inside**.
- `insert` and `contentOnly` are normally authored in markup; current Core has no ordinary inspector control for them.
- Content-only mode shows a **Content** view and a simplified List View. It currently includes a **Modify** action that can expose broader design tools, so it is a strong editing guardrail rather than immutable security.

**Pattern example.**

```html
<!-- wp:group {"templateLock":"contentOnly","lock":{"move":true,"remove":true},"backgroundColor":"redish","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-redish-background-color has-background">
  <!-- wp:heading {"level":2} -->
  <h2 class="wp-block-heading">Editable heading</h2>
  <!-- /wp:heading -->
  <!-- wp:paragraph -->
  <p>Editors can change this copy but cannot normally rearrange the layout.</p>
  <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

**Related, separate API.** A custom post type can preload and lock its whole editor with the underscore form `template_lock`:

```php
add_action( 'init', function () {
    register_post_type(
        'event',
        array(
            'show_in_rest'  => true,
            'template'      => array(
                array( 'core/heading', array( 'placeholder' => __( 'Event title', 'atheme' ) ) ),
                array( 'core/paragraph', array( 'placeholder' => __( 'Event details', 'atheme' ) ) ),
            ),
            'template_lock' => 'all',
        )
    );
} );
```

`templateLock` is a nested-block attribute. `template_lock` is a post-type registration property.

## 10. allowedBlocks

**What it is.** `allowedBlocks` is a direct-child allow-list for a container block: it answers “what can be inserted immediately inside this block?” It is not a global list of blocks available on the whole site, and it does not affect grandchildren.

There are two forms:

1. A fixed `allowedBlocks` list in a custom block’s block.json.
2. An editor-configurable `attributes.allowedBlocks` list exposed by `supports.allowedBlocks` (WordPress 6.9+).

This is a block-development feature, not a theme.json switch for changing Core Group’s child-block contract site-wide. [Official metadata documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/) and [support documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/)

**Where it appears.** A fixed list filters the child inserter/appender. With `supports.allowedBlocks: true`, a selected custom container gets an **Allowed blocks** control in its inspector.

**Fixed custom-container example.**

File: blocks/card/block.json

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "atheme/card",
  "title": "Card",
  "category": "design",
  "editorScript": "atheme-card-editor",
  "allowedBlocks": [
    "core/heading",
    "core/paragraph",
    "core/buttons",
    "core/image"
  ]
}
```

File: functions.php

```php
add_action( 'init', function () {
    wp_register_script(
        'atheme-card-editor',
        get_theme_file_uri( 'blocks/card/index.js' ),
        array( 'wp-blocks', 'wp-block-editor', 'wp-element' ),
        wp_get_theme()->get( 'Version' ),
        true
    );

    register_block_type( get_theme_file_path( 'blocks/card' ) );
} );
```

The custom block’s editor component must render InnerBlocks. This browser-ready example deliberately avoids a build step; the same array is passed to `useInnerBlocksProps` because that component owns the child inserter.

File: blocks/card/index.js

```js
(function (blocks, blockEditor, element) {
  const { registerBlockType } = blocks;
  const { useBlockProps, useInnerBlocksProps } = blockEditor;
  const { createElement: el } = element;
  const ALLOWED_BLOCKS = [
    "core/heading",
    "core/paragraph",
    "core/buttons",
    "core/image",
  ];

  registerBlockType("atheme/card", {
    apiVersion: 3,
    title: "Card",
    category: "design",

    edit: function Edit() {
      const blockProps = useBlockProps();
      const innerBlocksProps = useInnerBlocksProps(blockProps, {
        allowedBlocks: ALLOWED_BLOCKS,
      });

      return el("section", innerBlocksProps);
    },

    save: function Save() {
      const blockProps = useBlockProps.save();
      const innerBlocksProps = useInnerBlocksProps.save(blockProps);

      return el("section", innerBlocksProps);
    },
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.element);
```

**Editor-configurable alternative.**

```json
{
  "attributes": {
    "allowedBlocks": {
      "type": "array",
      "default": ["core/heading", "core/paragraph", "core/buttons"]
    }
  },
  "supports": {
    "allowedBlocks": true
  }
}
```

```js
const innerBlocksProps = useInnerBlocksProps(blockProps, {
  allowedBlocks: attributes.allowedBlocks,
});
```

If the requirement is “hide almost every block from the whole editor”, use the separate server-side `allowed_block_types_all` filter. That is a global inserter policy, not `allowedBlocks`.

## 11. Remove controls for a block type with theme.json

**What it is.** This belongs in **theme.json**, not in a block’s block.json. Theme-level `settings.blocks.{block-name}` overrides global editor settings for a particular Core or third-party block type. It removes spacing, colour, typography, and similar controls from every instance of that type.

It curates the UI; it does not delete the block, rewrite existing saved styles, or enforce security against code/raw markup. It only affects features that the target block supports. [Official per-block settings documentation](https://developer.wordpress.org/themes/global-settings-and-styles/settings/blocks/)

**Where it appears.** Select the target block in the Post Editor or Site Editor. The disabled panels/options disappear from its inspector, but the block remains insertable.

File: theme.json

```json
{
  "$schema": "https://schemas.wp.org/wp/6.9/theme.json",
  "version": 3,
  "settings": {
    "blocks": {
      "core/paragraph": {
        "spacing": {
          "margin": false,
          "padding": false
        },
        "typography": {
          "customFontSize": false,
          "defaultFontSizes": false,
          "fontSizes": [],
          "lineHeight": false,
          "letterSpacing": false,
          "textTransform": false
        },
        "color": {
          "text": false,
          "background": false,
          "link": false
        }
      },
      "core/heading": {
        "spacing": {
          "margin": false,
          "padding": false
        },
        "typography": {
          "customFontSize": false,
          "defaultFontSizes": false,
          "fontSizes": [],
          "lineHeight": false,
          "letterSpacing": false
        }
      }
    }
  }
}
```

`customFontSize: false` removes arbitrary size entry. `defaultFontSizes: false` plus an empty `fontSizes` list removes the preset size choices for that target block. Per-block values override global values, including broadly enabled `appearanceTools`.

For a custom block you own, its block.json `supports` decides which controls it implements. A theme cannot edit Core’s shipped block.json; theme.json is the correct way to restrict Core controls.

## Useful adjacent concepts

**Patterns versus template parts.** Use a pattern when an editor chooses where to insert a layout. Use a template part for site structure such as the header, footer, or reusable sidebar.

**Block template versus pattern.** A post-type block template preloads (and can lock) every new item of a post type. A pattern is optional. A page pattern is optional too, but is offered at creation time.

**Global style variation versus block/section style.** A styles JSON file without `blockTypes` can be a whole-site style skin. A block/section style is chosen on one block and gives it an `is-style-*` class.

**Practical default.** For most client themes, start with non-synced patterns for layout choices, section styles for visual alternatives, and theme.json for a restrained design system. Add synced patterns with overrides only when a central source of truth is genuinely useful.
