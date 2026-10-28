# ShanYing resource bundles

Editable feature sources live in `source/`, outside the public theme. Run from the project root:

```sh
python3 tools/polar-assets/build.py
```

The theme ships two built CSS files (`main.css`, `admin.css`) and two built JavaScript files (`main.js`, `admin.js`), plus `style.css` for theme metadata and the third-party Highlight.js library. The front end uses main.css/main.js, the owner's external Font Awesome stylesheet, LiteZoom and WordPress comment-reply. Optional map providers still load their SDK only when a map is opened.

`bundle-order.json` is the explicit dependency-resolved build order. Builds use local sources and cached font stylesheets, with no homepage snapshot or network request. `inc/modules.json` contains only the front-end and admin module names; PHP selects the modules needed for each request. Disabled modules do not run. All four bundles are minified with esbuild.

Font definitions retain their original remote font URLs. Font Awesome is served exclusively from https://static.bluecdn.com/libs/fontawesome-pro-plus/7.3.1/css/all.min.css; no local Font Awesome copies are included.

After building, run `python3 tools/polar-assets/audit.py` to check JavaScript/CSS syntax and detect unlisted business assets or duplicate build entries. Dependency installation: `npm ci --prefix tools/polar-assets/lucide-motion`.
