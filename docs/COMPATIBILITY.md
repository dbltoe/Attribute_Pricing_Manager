# Compatibility notes: Zen Cart v1.5.8 → v3.0.0, PHP 7.4 → 8.5

This plugin runs unmodified across six years of Zen Cart releases. That came
from choosing, at each decision point, the mechanism that exists in *every*
supported version rather than the newest one. This document records those
choices so a future maintainer knows which ones are load-bearing.

## How it is verified

Not by assertion. The maintainer harness (not shipped) checks three things
against the real thing:

| Check | Method |
|---|---|
| **Zen Cart API** | Every core function, constant and notifier the plugin uses is extracted from its source and looked up in all six release branches (`v158`, `2.0`, `2.1`, `2.2`, `2.3`, `master`) checked out locally. Missing anywhere fails the build. |
| **PHP** | Lint plus the harness suite on 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5, with zero diagnostics at `E_ALL`. |
| **The browser side** | A fixture page built from the ZCA Bootstrap and stock product-page markup, driven in a real browser, asserting the placement and the arithmetic. |

---

## The design decision that everything else follows from

**No AJAX.** The obvious way to show a live price is to ask the store for it
on every change. On v2.0.0 and later `ajax.php` will load a class from an
installed plugin's `catalog/includes/classes/ajax/` directory. **On v1.5.8 it
will not**: it only looks in the store's own `includes/classes/ajax/`, so a
plugin endpoint would have needed a file dropped outside `zc_plugins`, which
is the thing an encapsulated plugin exists to avoid.

Instead the server works out, once per page view, what each attribute value
adds to the price, and hands the browser the result. The browser only sums.
Everything that is version-specific stays in PHP, where `function_exists()`
can deal with it.

## What we rely on

### One notifier: `NOTIFY_HTML_HEAD_END`

Fires at the end of `<head>` in `html_header.php`, with the current page name,
on every release from v1.5.8 to v3.0.0-dev. The price data goes into the head
so it is in place before the body parses, and so the stock price block can be
hidden by a class on `<html>` before it ever paints.

The attributes module's own notifiers (`NOTIFY_ATTRIBUTES_MODULE_*`) also
exist throughout, but they fire *after* the head on some layouts and *before*
it on others depending on the template, so the plugin does not use them. It
reads the attributes from the database itself.

### The cart's arithmetic, with the cart's functions

`shoppingCart::calculate()` is the reference. Every function it calls that the
plugin also calls exists on all six branches:

`zen_get_product_details`, `zen_get_products_special_price`,
`zen_get_products_price_is_free`, `zen_has_product_attributes`,
`zen_has_product_attributes_values`, `zen_get_products_discount_price_qty`,
`zen_get_discount_calc`, `zen_get_products_base_price`,
`zen_get_attributes_price_factor`, `zen_get_attributes_qty_prices_onetime`,
`zen_get_tax_rate`, `zen_get_info_page`, `zen_check_show_prices`,
`zen_output_string_protected`.

### Wholesale pricing is v2.2+ only

`zen_get_retail_or_wholesale_price()` and the `products_price_w` /
`options_values_price_w` columns arrived in v2.2.0. The plugin calls the
function only if it exists and the column is present, and otherwise uses the
retail price, which on those releases is the only price there is.

### `zen_get_attribute_details()` is v2.2+ only

The cart uses it on v2.2+; v1.5.8 runs its own query. The plugin runs its own
query on every release (one query for all of a product's attributes, joined to
the option type), so it needs neither.

### `$currencies->currencies` is public everywhere

The currency's symbols, separators, decimal places and exchange rate are read
straight from that array, which is declared `public` on v1.5.8 and typed
`public array` on v2.2+ and v3.0. `getCurrencyInfo()` would be tidier but is
protected, and only exists from v2.2.

### Observer registration: `auto.*.php` + `zcObserver*`

`init_observers.php` includes each installed plugin's
`catalog/includes/classes/observers/auto.*.php` and instantiates
`'zcObserver' . base::camelize($name, true)`. Identical from v1.5.8 to
v3.0.0-dev:

```
auto.attribute_pricing_manager.php  →  class zcObserverAttributePricingManager
```

### Language files: `lang.*.php` returning an array

Understood by every supported release. Plugin `extra_definitions` directories
are scanned on the catalog side throughout.

### Stylesheet and script, linked not inlined

Zen Cart's shipped `zc_plugins/.htaccess` denies everything then re-allows a
list that includes `css` and `js`, on every release. The plugin links real,
cacheable assets from its own directory, checking the active template first so
a store can override either by dropping a copy into its template.

### The installer uses only `executeInstallerSql()`

`addConfigurationKey()`, `getOrCreateConfigGroupId()` and friends arrived in
v2.0.1/v2.1.0. The plugin's installer writes its configuration group and keys
with plain SQL, `INSERT IGNORE` for the value and an `UPDATE` for the label
and help text, so an upgrade refreshes wording without resetting a choice.
`executeUpgrade()` takes an optional argument because v1.5.8 passes none and
v2.x/v3.x pass `$oldVersion`.

### `pluginDescription` renders as raw HTML in Plugin Manager

Which is where the Read Me and GitHub buttons come from. On v1.5.8, v2.0 and
v2.1 the description is written only by the INSERT that first creates the
`plugin_control` row, so nothing state-dependent belongs in it, and the forum
link must be in the manifest before the first release.

### `pluginId` must be right the first time

`plugin_control.zc_contrib_id` is written by that same first INSERT on
v1.5.8/v2.0/v2.1. A wrong or zero id there is frozen on every store that
installs it. The release harness refuses to package while the manifest's id is
zero.

## PHP

Written to run on 7.4: no `str_starts_with`, no `match`, no named arguments,
no nullsafe operator, no readonly properties, no enums. `?->` in particular is
easy to reach for on 8.x and is a parse error on 7.4, which is why the suite
lints on every version rather than just running on one.

The JavaScript is ES5 for the same reason in the other direction: a store
cannot choose its customers' browsers.
