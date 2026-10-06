# Attribute Pricing Manager

An encapsulated Zen Cart plugin that replaces the static **Starting at:** price
on product pages with a running total that changes as the customer chooses
options, and puts it right in front of the Add to Cart button.

```
  License:  [ Developer - Unlimited Stores ( +$190.00 ) v ]

                    Total: $249.95
                     [ Add to Cart ]
```

The figure is worked out the way the shopping cart works it out, so what the
customer sees is what they will pay: specials and sales, plus/minus and
discounted attributes, price factors, quantity-tiered attribute prices,
per-word and per-letter text pricing, one-time charges on their own line, tax
per *Display Prices with Tax*, and the customer's own currency.

**No AJAX, no template edits.** The page is given its price data once, in the
`<head>`, and the browser does the adding up. Remove the plugin and the page is
exactly as it was.

**Runs on Zen Cart v1.5.8 through v3.0.0 and PHP 7.4 through 8.5 from a single
codebase**, verified against all six release branches rather than assumed. See
[docs/COMPATIBILITY.md](docs/COMPATIBILITY.md).

---

## Why not AJAX?

The well-known Dynamic Price Updater asks the store for a new price on every
change. That is a fine design, but Zen Cart v1.5.8's `ajax.php` cannot load a
class from a `zc_plugins` directory at all, so it would have meant a version
branch. Computing every attribute's delta on the server once and summing in the
browser needs nothing that v1.5.8 lacks, and it is faster for the customer.

## Installing

Upload `zc_plugins/AttributePricingManager/` so it lands at
`<store root>/zc_plugins/AttributePricingManager/v1.0.1/`, then install it from
**Modules → Plugin Manager**. It is on as soon as it is installed. Details in
[docs/INSTALL.md](docs/INSTALL.md).

## Settings

Under **Configuration → Attribute Pricing Manager**: the master switch, where the total
goes (Top, where *Starting at:* sits; Bottom, in front of the button; or
Both), whether the
stock heading is hidden, the two CSS selectors that find your template's price
block and Add to Cart container, multiply-by-quantity, one-time charges, and
the highlight. Every setting is explained in
[docs/CONFIGURATION.md](docs/CONFIGURATION.md).

## Documentation

- [docs/INSTALL.md](docs/INSTALL.md) — installing, upgrading, uninstalling
- [docs/CONFIGURATION.md](docs/CONFIGURATION.md) — every setting, and what counts toward the total
- [docs/CUSTOMIZING.md](docs/CUSTOMIZING.md) — restyling, other templates, translating
- [docs/COMPATIBILITY.md](docs/COMPATIBILITY.md) — how the v1.5.8 → v3.0.0 claim is kept true
- `zc_plugins/AttributePricingManager/v1.0.1/readme.html` — the same, as one page, also linked from Plugin Manager

## License

GNU General Public License v2.0. Copyright © 2026 My Zen Cart Host (dbltoe).
