# Changelog

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
