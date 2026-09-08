# Configuring Attribute Pricing Manager

Everything is under **Admin → Configuration → Attribute Pricing Manager**.

---

## The settings

| Setting | Default | What it does |
|---|---|---|
| **Enable Attribute Pricing Manager?** | true | The master switch. Set to false to turn the plugin off without uninstalling it. |
| **Where to show the running total** | Bottom | *Top* writes it into the stock "Starting at:" heading, wherever the template puts that. *Bottom* puts it directly in front of the Add to Cart button. *Both* does both, kept in step. |
| **Hide the stock "Starting at:" price?** | false | For the *Bottom* placement only: hide the template's own price heading so the page does not show two prices. If the total cannot be placed, the heading is shown again automatically. |
| **Stock price block selectors** | `#productPrices, #productsPriceTop-card, #productsPriceBottom-card` | CSS selectors, comma separated, that find the template's price heading(s). The defaults cover Zen Cart's stock templates and ZCA Bootstrap. |
| **Add to Cart container selector** | `#cartAdd` | The element that holds the Add to Cart button. The total goes immediately before the submit button inside it. |
| **Multiply by the quantity box?** | true | *false*: the price of one item with the chosen options, labelled "Your Price:". *true*: that times the quantity in the box, labelled "Total:", with the product's quantity discounts applied. |
| **Show one-time charges?** | true | When a chosen option carries a once-per-order charge, show it on its own line beneath the total. |
| **Only on products with options?** | false | *true*: only where there is a "Starting at:" to improve on. *false*: every product page, which pairs well with multiply-by-quantity. |
| **Flash the figure when it changes?** | true | A brief highlight on the amount. Respects the browser's reduced-motion preference. |

## Placement, in detail

**Bottom** is the plugin's reason to exist. The total is inserted
immediately before the submit button inside the *Add to Cart container*. On the
stock templates that is between the quantity box and the button; on ZCA
Bootstrap it is directly above the button in the Add to Cart card.

If the container selector matches nothing, the script looks for the form's
submit button anywhere on the page, then for the attributes block
(`#productAttributes`) and places the total after it. If none of those exist,
it gives up and shows the stock heading again rather than leave the page with
no price.

**Top** writes the total into the stock "Starting at:" heading. Where the
selector matches a wrapper (a Bootstrap card), the total goes into the heading
inside it so the card keeps its frame. Nothing is hidden in this mode.

**Both** is for a long page where the price at the top and the price at the
button are both useful. The two figures are updated together.

## What counts toward the total

The arithmetic mirrors `shoppingCart::calculate()` for a quantity of one, so
for a single item the figure and the cart agree to the cent.

| Element | Notes |
|---|---|
| Base price | Specials and sales applied as the cart applies them. A product *priced by attributes* starts from its raw price, because its attributes carry the real prices. A *free* product starts at zero. |
| Dropdown, radio, checkbox | Plus and minus prefixes. An attribute marked *discounted* goes through the same sale/special calculation as the cart. |
| Text and file options | The option's flat price counts whenever the field is on the form, as it does in the cart. Per-word and per-letter pricing is counted as the customer types, with Zen Cart's rules for free words, free letters and *Text Pricing - Spaces are Free*. |
| Price factor | Percentage-of-price attributes, including the one-time variant. |
| Quantity-tiered attribute prices | The tier for the quantity in the box, chosen with the cart's own rule (first tier whose quantity is at least the requested one; past the last tier, the last price). |
| One-time charges | Shown on their own line, never multiplied by quantity, tax applied as for the item. |
| Product quantity discounts | Applied when *Multiply by the quantity box* is on. Each tier's price is worked out on the server with `zen_get_products_discount_price_qty()`, so wholesale, specials and *discount from* behave as in the cart, and the mixed-quantity floor from what is already in the cart is honoured. |
| Tax | Added when *Display Prices with Tax* is true, at the product's tax class rate for the customer's location. |
| Currency | Converted and formatted in the currency the customer selected, with its own symbols, separators and decimal places. |
| Wholesale (v2.2+) | A wholesale customer sees wholesale figures. |
| Read-only options | Never charged; they are never submitted. |

## When the plugin stays quiet

The running total is not shown, and the stock heading is left alone, when:

- the plugin is disabled;
- the page is not the product's own information page (reviews, ask-a-question
  and tell-a-friend pages carry a `products_id` too, and are left alone);
- the product has no attributes and *Only on products with options?* is true;
- prices are hidden: showcase mode, "must log in to see prices", a customer
  awaiting approval, or maintenance mode with prices off;
- the product is *Call for price*, or a Document General product.

Every one of those is a case where the stock template shows no usable price
either.

## Troubleshooting

**Nothing appears and the stock price is still there.** Check the master
switch, then whether the product has options. Then view the page source and
search for `attributePricingManager`: if it is absent from the `<head>`, the plugin
decided not to act (see the list above); if it is present, the script could
not find a place to put the total — check the two selectors.

**Two prices are showing.** Your template names its price heading something
other than the defaults. Add its id to *Stock price block selectors*.

**The total is in the wrong place.** Change *Add to Cart container selector*
to the element that holds your template's Add to Cart button.

**The figure differs from the cart.** For a quantity of one it should not.
Note the product, the options chosen, and whether the product has a special, a
sale, quantity discounts or a discounted attribute, and report it with those
details. For quantities above one, see the limitation in
[CUSTOMIZING.md](CUSTOMIZING.md#limitations).

To find your template's ids, open a product page, right-click the price,
choose *Inspect*, and read the `id` of the heading and of the element around
the Add to Cart button.
