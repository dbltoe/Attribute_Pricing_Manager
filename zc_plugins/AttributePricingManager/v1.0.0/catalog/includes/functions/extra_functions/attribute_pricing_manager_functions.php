<?php
/**
 * Attribute Pricing Manager -- storefront functions.
 *
 * Everything the observer needs: the decision whether this request is a
 * product page the plugin should act on, the price data that page is given,
 * and the HTML that carries it into the <head>.
 *
 * THE PRICE MATH MIRRORS THE SHOPPING CART, NOT THE PRODUCT PAGE. The figure
 * a customer sees while choosing options must be the figure the cart charges
 * a moment later, so the base price and every attribute delta are worked out
 * with the same core functions, in the same order, as
 * shoppingCart::calculate() -- for a quantity of one. Where the product page
 * and the cart disagree (they do, in corners), the cart is the one that takes
 * the money, so the cart wins.
 *
 * Nothing here is computed in the browser that depends on the database. The
 * browser receives one JSON object -- the base price, a delta per attribute
 * value, the tax rate and the currency format -- and only adds up what the
 * customer has selected. That keeps the plugin free of any AJAX endpoint,
 * which matters because Zen Cart v1.5.8's ajax.php cannot load a class from
 * a zc_plugins directory at all.
 *
 * @package  AttributePricingManager
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/* ------------------------------------------------------------------ *
 * Settings
 * ------------------------------------------------------------------ */

/**
 * A configuration value, with a default for a store where the key is absent
 * (mid-upgrade, or a key added in a later release than the one installed).
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function apm_setting($key, $default = '')
{
    return defined($key) ? (string)constant($key) : $default;
}

/**
 * The master switch.
 *
 * @return bool
 */
function apm_is_enabled()
{
    return apm_setting('APM_STATUS', 'true') === 'true';
}

/**
 * Where the running total goes: 'cart', 'original' or 'both'.
 *
 * The admin setting is Top, Bottom or Both. Top is where the template shows
 * "Starting at:", so the total is written into that heading; Bottom is in
 * front of the Add to Cart button; Both is both, kept in step. This maps the
 * setting to the token the JavaScript understands. Anything unrecognised
 * falls back to the plugin's whole point, the spot in front of the button.
 *
 * @return string
 */
function apm_placement()
{
    $value = strtolower(trim(apm_setting('APM_PLACEMENT', 'Bottom')));
    if ($value === 'both') {
        return 'both';
    }
    if ($value === 'top' || strpos($value, 'starting at') !== false || strpos($value, 'original') !== false) {
        return 'original';
    }
    return 'cart';
}

/**
 * Whether the stock "Starting at:" block is hidden once the running total is
 * in place before the button.
 *
 * Only meaningful for the Bottom placement: Top and Both write into that
 * block rather than hiding it.
 *
 * @return bool
 */
function apm_hide_original()
{
    return apm_placement() === 'cart' && apm_setting('APM_HIDE_ORIGINAL', 'false') === 'true';
}

/**
 * A comma-separated selector list from the admin, made safe for a <style>
 * block and a JSON string.
 *
 * The admin is trusted, but a stylesheet is not a place to find out that
 * somebody pasted a stray brace. Nothing that can open or close a rule, a
 * tag, a comment or a string survives; a CSS selector needs none of them.
 *
 * @param string $selectors
 * @return string
 */
function apm_clean_selectors($selectors)
{
    $clean = preg_replace('~[<>{}\\\\"\'`;/]~', '', (string)$selectors);
    $clean = preg_replace('~\s+~', ' ', trim($clean));
    // Drop empty entries left by a trailing comma.
    $parts = array_filter(array_map('trim', explode(',', $clean)), 'strlen');

    return implode(', ', $parts);
}

/* ------------------------------------------------------------------ *
 * Which pages
 * ------------------------------------------------------------------ */

/**
 * The product this page is about, if it is a product page at all.
 *
 * Zen Cart has several product-information pages (product_info,
 * product_music_info, document_product_info, and any a store adds), so the
 * check is not "is this product_info" but "is this the info page Zen Cart
 * itself would send this product to". That also keeps the plugin off the
 * reviews, ask-a-question and tell-a-friend pages, which carry a products_id
 * too.
 *
 * @param string $currentPageBase
 * @return int products_id, or 0 when this is not a product page
 */
function apm_product_page_id($currentPageBase)
{
    if (empty($_GET['products_id']) || !is_numeric($_GET['products_id'])) {
        return 0;
    }
    $productsId = (int)$_GET['products_id'];
    if ($productsId <= 0) {
        return 0;
    }

    $product = zen_get_product_details($productsId);
    if (!is_object($product) || $product->EOF) {
        return 0;
    }

    if (zen_get_info_page($productsId) !== (string)$currentPageBase) {
        return 0;
    }

    return $productsId;
}

/**
 * Whether a running total should be shown for this product at all.
 *
 * Every reason to stay silent is a reason the stock template would not show
 * a usable price either: prices hidden by store status or customer approval,
 * call-for-price, a document product, maintenance mode with prices off.
 * The one rule of our own is "only on products with attributes", which is a
 * setting.
 *
 * @param int $productsId
 * @return bool
 */
function apm_should_render($productsId)
{
    if (!apm_is_enabled() || $productsId <= 0) {
        return false;
    }

    if (!zen_check_show_prices()) {
        return false;
    }

    // currencies->format() returns '' in this state, so there would be
    // nothing to show.
    if (defined('DOWN_FOR_MAINTENANCE') && DOWN_FOR_MAINTENANCE === 'true'
        && defined('DOWN_FOR_MAINTENANCE_PRICES_OFF') && DOWN_FOR_MAINTENANCE_PRICES_OFF === 'true'
    ) {
        return false;
    }

    $product = zen_get_product_details($productsId);
    if (!is_object($product) || $product->EOF) {
        return false;
    }
    $fields = $product->fields;

    // A "Call for price" product shows no figure; neither should we.
    if (isset($fields['product_is_call']) && $fields['product_is_call'] === '1') {
        return false;
    }
    // Document General: Zen Cart never prices it.
    if (isset($fields['products_type']) && (string)$fields['products_type'] === '3') {
        return false;
    }

    if (apm_setting('APM_ATTRIBUTES_ONLY', 'false') === 'true' && !zen_has_product_attributes_values($productsId)) {
        return false;
    }

    return true;
}

/* ------------------------------------------------------------------ *
 * Price data
 * ------------------------------------------------------------------ */

/**
 * The retail or wholesale figure for a price pair.
 *
 * Wholesale pricing arrived in Zen Cart v2.2.0, along with
 * zen_get_retail_or_wholesale_price(). On earlier releases neither the
 * function nor the *_w columns exist, and the retail price is the only
 * price there is.
 *
 * @param mixed $retail
 * @param mixed $wholesale
 * @return float
 */
function apm_retail_or_wholesale($retail, $wholesale = null)
{
    if ($wholesale !== null && function_exists('zen_get_retail_or_wholesale_price')) {
        return (float)zen_get_retail_or_wholesale_price($retail, $wholesale);
    }

    return (float)$retail;
}

/**
 * Zen Cart's "qty:price,qty:price" tier string as a list the browser can
 * walk. Same tokenising as zen_get_attributes_qty_prices_onetime(): spaces
 * removed, split on ':' and ','. An odd trailing token is dropped rather than
 * paired with nothing.
 *
 * @param string $string
 * @return array [['qty' => float, 'price' => float], ...]
 */
function apm_parse_qty_tiers($string)
{
    $string = str_replace(' ', '', (string)$string);
    if ($string === '') {
        return [];
    }
    $parts = preg_split('/[:,]/', $string);
    $tiers = [];
    for ($i = 0, $n = count($parts) - 1; $i < $n; $i += 2) {
        $tiers[] = ['qty' => (float)$parts[$i], 'price' => (float)$parts[$i + 1]];
    }

    return $tiers;
}

/**
 * A float the browser can trust: never NAN/INF (json_encode refuses them),
 * and rounded past any currency's precision so the JSON stays short.
 *
 * @param mixed $value
 * @return float
 */
function apm_num($value)
{
    $value = (float)$value;
    if (!is_finite($value)) {
        return 0.0;
    }

    return round($value, 6);
}

/**
 * The product's base price, as the cart would start from for a quantity of
 * one, plus the quantity-discount tiers if the product has them.
 *
 * This follows shoppingCart::calculate() step for step -- including its
 * quirks, because they are what the customer will be charged:
 *
 *  - a special or sale replaces the price unless the product is priced by
 *    attributes;
 *  - a "free" product is zero;
 *  - a product priced by attributes (with attributes) goes back to the raw or
 *    special price, because its attributes carry the real prices;
 *  - otherwise a quantity-discount product takes its tier price, which for
 *    quantity 1 is also where the "actual" (special/sale) price comes back in.
 *
 * @param int $productsId
 * @param array $fields the products row
 * @return array ['base' => float, 'tiers' => array, 'raw' => float, 'special' => float, 'pricedByAttr' => bool]
 */
function apm_base_price($productsId, array $fields)
{
    global $db;

    $raw = apm_retail_or_wholesale($fields['products_price'], $fields['products_price_w'] ?? null);
    $price = $raw;
    $pricedByAttr = (isset($fields['products_priced_by_attribute']) && (string)$fields['products_priced_by_attribute'] === '1');

    $special = zen_get_products_special_price($productsId);
    if ($special && !$pricedByAttr) {
        $price = (float)$special;
    } else {
        $special = 0;
    }

    if (zen_get_products_price_is_free($productsId)) {
        $price = 0;
    }

    $tiers = [];
    $discountType = isset($fields['products_discount_type']) ? (string)$fields['products_discount_type'] : '0';
    if ($pricedByAttr && zen_has_product_attributes($productsId, false)) {
        $price = $special ? (float)$special : $raw;
    } elseif ($discountType !== '0') {
        $price = (float)zen_get_products_discount_price_qty($productsId, 1);

        // The tiers, each priced by the same core function the cart uses,
        // so whatever it does with specials, wholesale and "discount from"
        // is done once, here, rather than re-derived in JavaScript.
        $rows = $db->Execute(
            "SELECT discount_qty
               FROM " . TABLE_PRODUCTS_DISCOUNT_QUANTITY . "
              WHERE products_id = " . (int)$productsId . "
                AND discount_qty != 0
              ORDER BY discount_qty"
        );
        while (!$rows->EOF) {
            $qty = (float)$rows->fields['discount_qty'];
            $tiers[] = [
                'qty' => $qty,
                'price' => apm_num(zen_get_products_discount_price_qty($productsId, $qty)),
            ];
            $rows->MoveNext();
        }
    }

    return [
        'base' => apm_num($price),
        'tiers' => $tiers,
        'raw' => (float)$raw,
        'special' => (float)$special,
        'pricedByAttr' => $pricedByAttr,
    ];
}

/**
 * One entry per attribute value: what selecting it adds to the unit price,
 * what it adds once per order, and anything quantity- or text-dependent
 * that the browser has to finish.
 *
 * Keys match what the browser can read off the form:
 *
 *   "<options_id>:<options_values_id>"   dropdown, radio, checkbox
 *   "txt:<options_id>"                   text and file inputs (one row each)
 *
 * The arithmetic is shoppingCart::calculate()'s, for a quantity of one.
 *
 * @param int $productsId
 * @param array $fields the products row
 * @param array $base from apm_base_price()
 * @return array
 */
function apm_attribute_prices($productsId, array $fields, array $base)
{
    global $db;

    $languageId = isset($_SESSION['languages_id']) ? (int)$_SESSION['languages_id'] : 1;

    // The option type comes along so a text option is known without a query
    // per attribute. It is the same in every language, so the language filter
    // only stops the join from multiplying rows.
    $rows = $db->Execute(
        "SELECT pa.*, popt.products_options_type
           FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " popt
                    ON popt.products_options_id = pa.options_id
                   AND popt.language_id = " . $languageId . "
          WHERE pa.products_id = " . (int)$productsId . "
          ORDER BY pa.options_id, pa.products_attributes_id"
    );

    $productIsFree = zen_get_products_price_is_free($productsId);
    $textPricing = (defined('ATTRIBUTES_ENABLED_TEXT_PRICES') && ATTRIBUTES_ENABLED_TEXT_PRICES === 'true');
    $textType = defined('PRODUCTS_OPTIONS_TYPE_TEXT') ? (string)PRODUCTS_OPTIONS_TYPE_TEXT : '1';
    $fileType = defined('PRODUCTS_OPTIONS_TYPE_FILE') ? (string)PRODUCTS_OPTIONS_TYPE_FILE : '4';

    $attributes = [];
    while (!$rows->EOF) {
        $a = $rows->fields;
        $rows->MoveNext();

        $attributesId = (int)$a['products_attributes_id'];
        $optionType = isset($a['products_options_type']) ? (string)$a['products_options_type'] : '';
        $isTextLike = ($optionType === $textType || $optionType === $fileType);

        $key = $isTextLike
            ? 'txt:' . (int)$a['options_id']
            : (int)$a['options_id'] . ':' . (int)$a['options_values_id'];

        $entry = ['price' => 0.0, 'onetime' => 0.0];

        if (isset($a['product_attribute_is_free']) && (string)$a['product_attribute_is_free'] === '1' && $productIsFree) {
            // No charge for the attribute: the cart skips it entirely.
            $attributes[$key] = $entry;
            continue;
        }

        $ovp = apm_retail_or_wholesale($a['options_values_price'], $a['options_values_price_w'] ?? null);
        $discounted = (isset($a['attributes_discounted']) && (string)$a['attributes_discounted'] === '1');

        $delta = 0.0;
        if (isset($a['price_prefix']) && $a['price_prefix'] === '-') {
            $delta = $discounted
                ? -(float)zen_get_discount_calc($productsId, $attributesId, $ovp, 1)
                : -$ovp;
        } elseif ($discounted) {
            // For a product priced by attributes the discount is worked out
            // on price + attribute, then the price taken back off.
            $attrBase = $base['pricedByAttr'] ? $base['raw'] : 0.0;
            $delta = (float)zen_get_discount_calc($productsId, $attributesId, $ovp + $attrBase, 1) - $attrBase;
        } else {
            $delta = $ovp;
        }

        // Price factor: a percentage of the product's price (or special).
        if (!empty($a['attributes_price_factor']) && (float)$a['attributes_price_factor'] > 0) {
            $delta += (float)zen_get_attributes_price_factor(
                zen_get_products_base_price($productsId),
                zen_get_products_special_price($productsId, false),
                $a['attributes_price_factor'],
                $a['attributes_price_factor_offset'] ?? 0
            );
        }

        // Quantity-tiered attribute price. The tier for one is in the delta;
        // the browser swaps it for the right tier when the quantity changes.
        if (!empty($a['attributes_qty_prices'])) {
            $tiers = apm_parse_qty_tiers($a['attributes_qty_prices']);
            if ($tiers !== []) {
                $delta += (float)zen_get_attributes_qty_prices_onetime($a['attributes_qty_prices'], 1);
                $entry['qtyTiers'] = $tiers;
            }
        }

        // One-time charges: added once per order, never multiplied.
        $onetime = 0.0;
        if (!empty($a['attributes_price_onetime']) && (float)$a['attributes_price_onetime'] > 0) {
            $onetime += (float)$a['attributes_price_onetime'];
        }
        if (!empty($a['attributes_price_factor_onetime']) && (float)$a['attributes_price_factor_onetime'] > 0) {
            $onetime += (float)zen_get_attributes_price_factor(
                zen_get_products_base_price($productsId),
                zen_get_products_special_price($productsId, false),
                $a['attributes_price_factor_onetime'],
                $a['attributes_price_factor_onetime_offset'] ?? 0
            );
        }
        if (!empty($a['attributes_qty_prices_onetime'])) {
            $tiers = apm_parse_qty_tiers($a['attributes_qty_prices_onetime']);
            if ($tiers !== []) {
                $onetime += (float)zen_get_attributes_qty_prices_onetime($a['attributes_qty_prices_onetime'], 1);
                $entry['onetimeTiers'] = $tiers;
            }
        }

        // Per-word and per-letter pricing on a text option. The counting is
        // done in the browser as the customer types, with the same rules as
        // zen_get_word_count() and zen_get_letters_count().
        if ($textPricing && $optionType === $textType) {
            $entry['text'] = [
                'wordsFree' => (int)($a['attributes_price_words_free'] ?? 0),
                'priceWords' => apm_num($a['attributes_price_words'] ?? 0),
                'lettersFree' => (int)($a['attributes_price_letters_free'] ?? 0),
                'priceLetters' => apm_num($a['attributes_price_letters'] ?? 0),
            ];
        }

        $entry['price'] = apm_num($delta);
        $entry['onetime'] = apm_num($onetime);
        $attributes[$key] = $entry;
    }

    return $attributes;
}

/**
 * How the session's currency is written, so the browser can format a number
 * exactly as currencies->format() would.
 *
 * @return array
 */
function apm_currency_format()
{
    global $currencies;

    $code = $_SESSION['currency'] ?? (defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : '');
    $info = [];
    if (is_object($currencies) && isset($currencies->currencies[$code]) && is_array($currencies->currencies[$code])) {
        $info = $currencies->currencies[$code];
    } elseif (is_object($currencies) && defined('DEFAULT_CURRENCY') && isset($currencies->currencies[DEFAULT_CURRENCY])) {
        $info = $currencies->currencies[DEFAULT_CURRENCY];
    }

    return [
        'symbolLeft' => isset($info['symbol_left']) ? (string)$info['symbol_left'] : '$',
        'symbolRight' => isset($info['symbol_right']) ? (string)$info['symbol_right'] : '',
        'decimalPoint' => isset($info['decimal_point']) ? (string)$info['decimal_point'] : '.',
        'thousandsPoint' => isset($info['thousands_point']) ? (string)$info['thousands_point'] : ',',
        'decimalPlaces' => isset($info['decimal_places']) ? (int)$info['decimal_places'] : 2,
        'value' => isset($info['value']) && (float)$info['value'] > 0 ? (float)$info['value'] : 1.0,
    ];
}

/**
 * Everything the page's script needs, as one array ready for json_encode().
 *
 * @param int $productsId
 * @return array empty when the product cannot be priced
 */
function apm_build_data($productsId)
{
    $product = zen_get_product_details($productsId);
    if (!is_object($product) || $product->EOF) {
        return [];
    }
    $fields = $product->fields;

    $base = apm_base_price($productsId, $fields);
    $taxRate = zen_get_tax_rate($fields['products_tax_class_id'] ?? 0);

    $labels = [
        'price' => defined('APM_TEXT_PRICE') ? APM_TEXT_PRICE : 'Your Price:',
        'total' => defined('APM_TEXT_TOTAL') ? APM_TEXT_TOTAL : 'Total:',
        'onetime' => defined('APM_TEXT_ONETIME') ? APM_TEXT_ONETIME : 'One-time charge:',
        'suffix' => defined('APM_TEXT_SUFFIX') ? APM_TEXT_SUFFIX : '',
    ];

    return [
        'version' => apm_plugin_version(),
        'productsId' => (int)$productsId,
        'basePrice' => $base['base'],
        'discountTiers' => $base['tiers'],
        'attributes' => apm_attribute_prices($productsId, $fields, $base),
        'taxRate' => apm_num($taxRate),
        'withTax' => (defined('DISPLAY_PRICE_WITH_TAX') && DISPLAY_PRICE_WITH_TAX === 'true'),
        'spacesFree' => (defined('TEXT_SPACES_FREE') && (string)TEXT_SPACES_FREE === '1'),
        'currency' => apm_currency_format(),
        'placement' => apm_placement(),
        'hideOriginal' => apm_hide_original(),
        'originalSelectors' => apm_clean_selectors(apm_setting('APM_ORIGINAL_SELECTORS', '#productPrices, #productsPriceTop-card, #productsPriceBottom-card')),
        'targetSelector' => apm_clean_selectors(apm_setting('APM_TARGET_SELECTOR', '#cartAdd')),
        'multiplyQty' => apm_setting('APM_MULTIPLY_QTY', 'true') === 'true',
        'showOnetime' => apm_setting('APM_SHOW_ONETIME', 'true') === 'true',
        'highlight' => apm_setting('APM_HIGHLIGHT', 'true') === 'true',
        'labels' => $labels,
    ];
}

/* ------------------------------------------------------------------ *
 * Assets and output
 * ------------------------------------------------------------------ */

/**
 * The plugin's version directory name, e.g. "v1.0.0", from where this file
 * sits -- so the directory can be renamed at a release and nothing here
 * needs editing.
 *
 * @return string
 */
function apm_plugin_version()
{
    return basename(dirname(__DIR__, 4));
}

/**
 * A store-relative web path, honouring a store that lives in a subdirectory.
 *
 * @param string $path
 * @return string
 */
function apm_catalog_path($path)
{
    $base = defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/';

    return $base . ltrim($path, '/');
}

/**
 * Where an asset is served from: the active template's own copy if it has
 * one, otherwise the copy shipped inside the plugin.
 *
 * Zen Cart's shipped zc_plugins/.htaccess denies everything and then
 * re-allows a list of extensions that includes css and js, so the plugin's
 * copy is reachable by design on every supported release.
 *
 * @param string $subdir 'css' or 'jscript'
 * @param string $filename
 * @return string '' when neither copy exists
 */
function apm_asset_href($subdir, $filename)
{
    if (defined('DIR_WS_TEMPLATE') && defined('DIR_FS_CATALOG')) {
        $templatePath = DIR_WS_TEMPLATE . $subdir . '/' . $filename;
        if (is_file(DIR_FS_CATALOG . $templatePath)) {
            return apm_catalog_path($templatePath);
        }
    }

    $pluginRoot = dirname(__DIR__, 4);
    $pluginRelative = 'zc_plugins/' . basename(dirname($pluginRoot)) . '/' . basename($pluginRoot)
        . '/catalog/includes/templates/template_default/' . $subdir . '/' . $filename;

    if (!defined('DIR_FS_CATALOG') || is_file(DIR_FS_CATALOG . $pluginRelative)) {
        return apm_catalog_path($pluginRelative);
    }

    return '';
}

/**
 * The <head> contribution for a product page: stylesheet, the price data,
 * and the script that does the adding up.
 *
 * The data goes out as an inline script rather than a data attribute so it is
 * in place before the body parses. The same inline script flags the document
 * root when the stock price block is to be hidden, so the block never paints
 * and then vanishes; the script removes the flag again if it cannot find
 * anywhere to put the total, so a customer is never left with no price.
 *
 * @param string $currentPageBase
 * @return string
 */
function apm_render_head($currentPageBase)
{
    $productsId = apm_product_page_id($currentPageBase);
    if ($productsId <= 0 || !apm_should_render($productsId)) {
        return '';
    }

    $data = apm_build_data($productsId);
    if ($data === []) {
        return '';
    }

    $version = rawurlencode(ltrim($data['version'], 'v'));
    $html = '';

    $css = apm_asset_href('css', 'attribute_pricing_manager.css');
    if ($css !== '') {
        $html .= '<link rel="stylesheet" href="' . zen_output_string_protected($css . '?v=' . $version) . '">' . "\n";
    }

    if ($data['hideOriginal'] && $data['originalSelectors'] !== '') {
        $rules = [];
        foreach (explode(', ', $data['originalSelectors']) as $selector) {
            $rules[] = 'html.apm-active ' . $selector;
        }
        $html .= '<style>' . implode(',', $rules) . '{display:none !important}</style>' . "\n";
    }

    // JSON_HEX_TAG stops a "</script>" inside any string (a currency symbol,
    // a label) from ending the script early.
    $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    if ($json === false) {
        return '';
    }
    $html .= '<script>window.attributePricingManager=' . $json . ';'
        . 'if(window.attributePricingManager.hideOriginal){document.documentElement.className+=" apm-active";}'
        . '</script>' . "\n";

    $js = apm_asset_href('jscript', 'attribute_pricing_manager.js');
    if ($js !== '') {
        $html .= '<script src="' . zen_output_string_protected($js . '?v=' . $version) . '" defer></script>' . "\n";
    }

    return $html;
}
