<?php
/**
 * Attribute Pricing Manager -- storefront language strings (English).
 *
 * The `lang.` prefix plus an array return is the language format understood
 * by Zen Cart v1.5.8 through v3.0.0. To translate, copy this file to
 * `catalog/includes/languages/<your language>/extra_definitions/` inside this
 * plugin and translate the values.
 *
 * @package  AttributePricingManager
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

$define = [
    /* The label in front of the running total when it shows the price of
     * one item with the chosen options. */
    'APM_TEXT_PRICE' => 'Your Price:',

    /* The label when "Multiply by quantity" is on and the figure is the
     * price for the quantity in the box. */
    'APM_TEXT_TOTAL' => 'Total:',

    /* Shown beneath the total when a chosen option carries a charge that is
     * added once per order rather than per item. */
    'APM_TEXT_ONETIME' => 'One-time charge:',

    /* Appended after the figure, e.g. ' plus tax' or ' incl. VAT'. Left empty
     * by default because the figure follows the store's own "Display Prices
     * with Tax" setting, exactly as the cart does. */
    'APM_TEXT_SUFFIX' => '',
];

return $define;
