# Customizing Attribute Pricing Manager

---

## Restyling

The plugin's stylesheet ships inside the plugin at:

```
zc_plugins/AttributePricingManager/v1.0.1/catalog/includes/templates/template_default/css/attribute_pricing_manager.css
```

To change it, copy that file to `includes/templates/YOUR_TEMPLATE/css/` in
your store and edit the copy. The plugin links the template's copy in
preference to its own, so an upgrade never touches your changes. The same
applies to the script, at `.../template_default/jscript/attribute_pricing_manager.js`,
copied to `includes/templates/YOUR_TEMPLATE/jscript/`.

The markup the script builds:

```html
<div class="apm-box" aria-live="polite">
  <span class="apm-label">Your Price:</span>
  <span class="apm-amount">$249.95</span>
  <span class="apm-suffix"> plus tax</span>          <!-- only when APM_TEXT_SUFFIX is set -->
  <span class="apm-onetime">                          <!-- only when "Show one-time charges?" is on -->
    <span class="apm-onetime-label">One-time charge: </span>
    <span class="apm-onetime-amount">$25.00</span>
  </span>
</div>
```

A box written into the stock heading also carries the class `apm-inplace`, and
the stylesheet makes it inherit the heading's size and alignment. When the
figure changes, the box briefly carries `apm-changed`, which drives the
highlight animation; turn the setting off or override the rule if you would
rather not.

## Another template

Two settings tell the script where things are; see
[CONFIGURATION.md](CONFIGURATION.md#placement-in-detail). The ids used by the
templates the plugin was written against:

| Template | Price heading | Add to Cart container |
|---|---|---|
| `responsive_classic`, `template_default` (every release) | `#productPrices` | `#cartAdd` |
| ZCA Bootstrap 3.x | `#productsPriceTop-card` and `#productsPriceBottom-card` (cards around `h2.productPriceTopPrice` / `h2.productPriceBottomPrice`) | `#cartAdd` |

For any other template, inspect a product page and put its ids in the two
settings. The selectors are ordinary CSS, so a class or an attribute selector
works as well as an id.

## Translating

The visible strings are in:

```
catalog/includes/languages/english/extra_definitions/lang.attribute_pricing_manager.php
```

Copy it to `catalog/includes/languages/<your language>/extra_definitions/`
inside the plugin and translate the values. There are four:

| Constant | Default | Used |
|---|---|---|
| `APM_TEXT_PRICE` | `Your Price:` | in front of the figure when it is the price of one item |
| `APM_TEXT_TOTAL` | `Total:` | in front of the figure when *Multiply by the quantity box* is on |
| `APM_TEXT_ONETIME` | `One-time charge:` | on the once-per-order line |
| `APM_TEXT_SUFFIX` | *(empty)* | after the figure, e.g. ` plus VAT` for a store that shows prices without tax |

The settings' own labels and help text are English and live in the database;
edit them under Configuration if you wish.

## Limitations

- **JavaScript is required.** Without it the page is exactly as Zen Cart made
  it; the stock heading is hidden only once the script has placed the total.
- **Quantities above one are close, not exact, in one corner.** An attribute
  marked *discounted* on a product that also has quantity discounts is priced
  by the cart through a quantity-aware calculation; the plugin prices it for
  one and multiplies. Products without quantity discounts, and attributes not
  marked discounted, are exact at any quantity.
- **What is already in the cart is not added in.** The figure is for this
  page. The mixed-quantity discount floor is honoured, though.
- **Gift certificates** take their price from the amount typed, which the
  plugin does not read.
- The plugin does not touch listings, search results or the cart page.
