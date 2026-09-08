# Plugins Library listing text

Paste into the Zen Cart Plugins Library submission form, category **Pricing
Tools**. The form takes Markdown. Not part of the release package.

---

## Title

Attribute Pricing Manager

## Short description

Replaces the static "Starting at:" price on product pages with a running
total that changes as the customer picks options, placed in front of the Add
to Cart button. Priced the way the shopping cart prices, with no AJAX and no
template edits. Encapsulated; one codebase for Zen Cart 1.5.8 through 3.0.0.

## Description

**The problem.** On a product with options, Zen Cart shows *Starting at:
$59.95* at the top of the page and leaves it there. The customer picks the
$149.95 option, the page still says $59.95, and the first time they see the
real figure is in the shopping cart. Some of them leave at that point.

**What this does.** Attribute Pricing Manager shows a price that changes the
moment an option or the quantity changes, right where the customer is about
to commit:

```
  License:  [ Developer - Unlimited Stores ( +$190.00 ) v ]

                    Total: $249.95
                     [ Add to Cart ]
```

The figure is worked out the way the shopping cart works it out, so what the
customer sees is what they will pay: specials and sales, plus/minus and
discounted attributes, price factors, quantity-tiered attribute prices,
per-word and per-letter text pricing, one-time charges on their own line,
tax per *Display Prices with Tax*, the product's quantity discounts, and the
customer's own currency.

**Where it goes is your choice.** Top (written into the stock "Starting at:"
heading), Bottom (in front of the Add to Cart button), or Both, kept in
step. The stock heading can be hidden or left alone. Two CSS-selector
settings cover templates that name things differently; the defaults handle
Zen Cart's stock templates and ZCA Bootstrap without any edits.

**No AJAX, no template edits.** The page is given its price data once, in
the `<head>`, and the browser does the adding up. Nothing is requested from
the server until Add to Cart is pressed. Remove the plugin and the page is
exactly as it was. (Zen Cart 1.5.8's `ajax.php` cannot load a class from a
plugin directory, which is why the well-known Dynamic Price Updater cannot be
encapsulated for that release; this design can.)

**Encapsulated.** One directory under `zc_plugins/`, installed and removed
from Plugin Manager. No files in `admin/`, `includes/` or your template. No
tables. Nine settings under Configuration → Attribute Pricing Manager.

**Runs on Zen Cart 1.5.8, 2.0, 2.1, 2.2, 2.3 and 3.0.0-dev, PHP 7.4 through
8.5, from a single codebase**, verified against all six release branches
rather than assumed.

### Settings

- Enable Attribute Pricing Manager? (master switch)
- Where to show the running total: Top, Bottom, Both
- Hide the stock "Starting at:" price?
- Stock price block selectors
- Add to Cart container selector
- Multiply by the quantity box?
- Show one-time charges?
- Only on products with options?
- Flash the figure when it changes?

### What counts toward the total

Base price with specials and sales as the cart applies them; dropdown, radio
and checkbox attributes with plus/minus prefixes; discounted attributes;
price factors; quantity-tiered attribute prices; text and file options
including per-word and per-letter pricing; one-time charges (shown on their
own line, never multiplied); product quantity discounts; tax; currency;
wholesale pricing on 2.2+. Free and call-for-price products behave as the
page does. Read-only options are never charged.

### Limitations

- Needs JavaScript. Without it the page is exactly as Zen Cart made it.
- With multiply-by-quantity on, an attribute marked *discounted* on a product
  that also has quantity discounts is priced for one and multiplied, so it is
  close rather than exact above quantity one. Everything else is exact.
- Does not touch listings, search results or the cart page.

### Installation

Upload `zc_plugins/AttributePricingManager/` so it lands at
`<store root>/zc_plugins/AttributePricingManager/v1.0.0/`, then install it
from Modules → Plugin Manager. It is on as soon as it is installed. Full
documentation (readme.html) is inside the plugin and linked from the Plugin
Manager panel.

### Support

Support thread: https://www.zen-cart.com/threads/207329
Source and issues: https://github.com/dbltoe/Attribute_Pricing_Manager

Author: My Zen Cart Host (dbltoe). GNU General Public License v2.0.
