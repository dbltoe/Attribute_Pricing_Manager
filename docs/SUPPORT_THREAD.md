# Attribute Pricing Manager - support thread

Opening post for the Zen Cart forum support thread (Addon Order Total Modules). Markdown; the forum also accepts it as plain text. Not part of the release package.

---

**Attribute Pricing Manager v1.0.0** - a running price on the product page that changes as the customer picks options

**Plugins Library:** (link)
**GitHub:** https://github.com/dbltoe/Attribute_Pricing_Manager
**Zen Cart:** 1.5.8, 2.0, 2.1, 2.2, 2.3 and 3.0.0-dev, from one codebase
**PHP:** 7.4 through 8.5
**License:** GPL-2.0

**The problem it solves**

On a product with options, Zen Cart shows *Starting at: $59.95* at the top of the page and leaves it there. The customer picks the $149.95 option, the page still says $59.95, and the first time they see the real figure is in the shopping cart. Some of them leave at that point.

**What it does**

Shows a price that changes the moment an option or the quantity changes, right where the customer is about to commit:

```
  License:  [ Developer - Unlimited Stores ( +$190.00 ) v ]

                    Total: $249.95
                     [ Add to Cart ]
```

The figure is worked out the way the shopping cart works it out, so what the customer sees is what they will pay: specials and sales, plus/minus and discounted attributes, price factors, quantity-tiered attribute prices, per-word and per-letter text pricing, one-time charges on their own line, the product's quantity discounts, tax per *Display Prices with Tax*, and the customer's own currency.

**Where it goes is a setting:** Top (written into the stock "Starting at:" heading), Bottom (in front of the Add to Cart button), or Both, kept in step. The stock heading can be hidden or left alone.

**No AJAX, no template edits.** The page is given its price data once, in the `<head>`, and the browser does the adding up. Nothing goes back to the server until Add to Cart is pressed. Remove the plugin and the page is exactly as it was.

**Encapsulated.** One directory under `zc_plugins/`, installed and removed from Plugin Manager. No files in `admin/`, `includes/` or your template, and no database tables. Nine settings under Configuration -> Attribute Pricing Manager.

**Installing**

1. Upload `zc_plugins/AttributePricingManager/` so it lands at `<store root>/zc_plugins/AttributePricingManager/v1.0.0/`.
2. Admin -> Modules -> Plugin Manager -> Attribute Pricing Manager -> Install.
3. Open a product. It is on as soon as it is installed.

The full documentation (readme.html) is inside the plugin and linked from the Plugin Manager panel.

**Templates**

The defaults cover Zen Cart's stock templates (`responsive_classic`, `template_default`) and ZCA Bootstrap. For any other template, two settings hold CSS selectors: the stock price heading(s) to hide or write into, and the element that holds the Add to Cart button. Inspect your product page for the ids and put them there.

**Limitations**

- Needs JavaScript. Without it the page is exactly as Zen Cart made it.
- With "Multiply by the quantity box" on, an attribute marked *discounted* on a product that also has quantity discounts is priced for one and multiplied, so it is close rather than exact above quantity one. Everything else is exact.
- Does not touch listings, search results or the cart page.

**Why not Dynamic Price Updater?**

DPU asks the store for a new price on every change over AJAX. Zen Cart 1.5.8's `ajax.php` cannot load a class from a plugin directory, so DPU cannot be encapsulated for that release. Computing every attribute's delta on the server once and summing in the browser needs nothing that 1.5.8 lacks, and it is faster for the customer. DPU also updates stock quantity and weight; this plugin does prices only.

**Reporting a problem**

Please include: your Zen Cart and PHP versions, your template, the product's link, what you selected, what the plugin showed and what the cart charged, and whether the product has a special, a sale, quantity discounts or a discounted attribute. If nothing appears at all, view the page source and say whether `attributePricingManager` is in the `<head>`: if it is not, the plugin decided not to act (call-for-price, showcase mode, prices hidden); if it is, the script could not find a place to put the total and the two selector settings are the place to look.
