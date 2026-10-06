# Changelog

## v1.0.1

Released 2026-10-06.

- Plugin ID is now 2456, the ID the Zen Cart Plugins Library assigned on
  6 October 2026. It replaces 2258, which belonged to another plugin, so
  update notices in Plugin Manager now report this plugin.
- No code or settings changes. Upgrading is optional and only matters for
  update notices.

Upgrading: put the v1.0.1 folder beside the old one and click **Upgrade** in
Admin > Modules > Plugin Manager. On Zen Cart v2.2 and later the new ID is
picked up the next time Plugin Manager loads. On v1.5.8, v2.0 and v2.1 Zen
Cart records the ID only when it first sees the plugin; to set it there, run
`UPDATE plugin_control SET zc_contrib_id = 2456 WHERE unique_key = 'AttributePricingManager';`
in Admin > Tools > Install SQL Patches, which adds your table prefix itself
(in phpMyAdmin, add the prefix to `plugin_control`). It is not required.

## v1.0.0

First release.

- A running price on product pages that changes as the customer picks
  options, placed in front of the Add to Cart button. The stock
  "Starting at:" heading is hidden, written into, or left alone, per setting.
- Priced the way the shopping cart prices: base price with specials and
  sales, each attribute's delta (plus/minus prefixes, discounted attributes,
  price factors, quantity tiers), per-word and per-letter text pricing,
  one-time charges on their own line, tax per *Display Prices with Tax*, and
  the session currency's own format.
- Optional multiply-by-quantity with the product's quantity discounts.
- No AJAX endpoint and no template edits: the page is given its price data
  once and the browser does the adding up.
- Runs on Zen Cart v1.5.8 through v3.0.0 and PHP 7.4 through 8.5 from a
  single codebase.
