/*
 * Attribute Pricing Manager -- storefront script.
 *
 * Reads the price data the plugin placed in window.attributePricingManager, adds up
 * whatever the customer has selected on the add-to-cart form, and shows the
 * figure in front of the Add to Cart button (and/or in place of the stock
 * "Starting at:" heading, per the admin setting).
 *
 * No AJAX, no jQuery. The arithmetic mirrors Zen Cart's shopping cart: base
 * price, plus each selected attribute's delta, floored at zero, tax added if
 * the store displays prices with tax, then multiplied by the quantity if the
 * store asked for that. One-time charges are shown on their own line because
 * the cart adds them once per order, never per item.
 *
 * The pure functions are exposed as window.AttributePricingManager so they can be
 * tested outside a store.
 *
 * Written as ES5 on purpose: a shop cannot choose its customers' browsers.
 */
(function (win, doc) {
    'use strict';

    var APM = {};

    /* ------------------------------------------------------------------ *
     * Arithmetic
     * ------------------------------------------------------------------ */

    /**
     * zen_round(): round half up to a number of places, the way PHP's round()
     * does. Shifting the decimal point in the string rather than multiplying
     * matters: 71.445 * 100 is 7144.499999999999 in binary floating point
     * and Math.round would give 71.44 where PHP gives 71.45.
     */
    APM.round = function (value, places) {
        var shifted, factor;
        if (String(value).indexOf('e') === -1) {
            shifted = Math.round(parseFloat(value + 'e' + places));
            return parseFloat(shifted + 'e-' + places);
        }
        factor = Math.pow(10, places);
        return Math.round(value * factor) / factor;
    };

    /**
     * The tier that applies for a quantity, with the semantics of
     * zen_get_attributes_qty_prices_onetime(): walk the tiers in order and
     * take the first whose quantity is >= the requested one; past the last
     * tier, the last tier's price stands.
     */
    APM.tierPrice = function (tiers, qty) {
        var price = 0;
        var i;
        if (!tiers || !tiers.length) {
            return 0;
        }
        for (i = 0; i < tiers.length; i++) {
            price = tiers[i].price;
            if (qty <= tiers[i].qty) {
                break;
            }
        }
        return price;
    };

    /**
     * The product's own quantity-discount tier: the highest tier whose
     * quantity is <= the requested one, else the base price.
     */
    APM.discountPrice = function (basePrice, tiers, qty) {
        var price = basePrice;
        var i;
        if (!tiers || !tiers.length) {
            return basePrice;
        }
        for (i = 0; i < tiers.length; i++) {
            if (qty >= tiers[i].qty) {
                price = tiers[i].price;
            }
        }
        return price;
    };

    /** zen_get_word_count(): words beyond the free allowance. */
    APM.wordCount = function (text, free) {
        var s = String(text || '').replace(/[\r\n\t]+/g, ' ').replace(/ +/g, ' ').replace(/^ | $/g, '');
        if (s === '') {
            return 0;
        }
        return (s.split(' ').length) - (free || 0);
    };

    /** zen_get_letters_count(): letters beyond the free allowance, spaces optional. */
    APM.letterCount = function (text, free, spacesFree) {
        var s = String(text || '').replace(/[\r\n\t]+/g, ' ').replace(/ +/g, ' ').replace(/^ | $/g, '');
        var count = spacesFree ? s.replace(/ /g, '').length : s.length;
        count -= (free || 0);
        return count >= 1 ? count : 0;
    };

    /**
     * The figures for a set of selections.
     *
     * @param {Object} data       window.attributePricingManager
     * @param {Array}  selections [{key: '2:3'}, {key: 'txt:5', text: 'hello'}]
     * @param {number} qty        the quantity in the box
     * @returns {{unit: number, total: number, onetime: number, qty: number}}
     *          unit and onetime are before tax; total is what to display.
     */
    APM.compute = function (data, selections, qty) {
        var price, attrs = 0, onetime = 0, i, sel, a, words, letters, unit, total, onetimeShown;

        qty = parseFloat(qty);
        if (isNaN(qty) || qty <= 0) {
            qty = 1;
        }

        price = APM.discountPrice(data.basePrice, data.discountTiers, qty);

        for (i = 0; i < selections.length; i++) {
            sel = selections[i];
            a = data.attributes[sel.key];
            if (!a) {
                continue;
            }
            attrs += a.price;
            if (a.qtyTiers) {
                // The delta already holds the tier for one; swap it.
                attrs += APM.tierPrice(a.qtyTiers, qty) - APM.tierPrice(a.qtyTiers, 1);
            }
            if (a.text && typeof sel.text === 'string') {
                words = APM.wordCount(sel.text, a.text.wordsFree);
                if (words >= 1) {
                    attrs += words * a.text.priceWords;
                }
                letters = APM.letterCount(sel.text, a.text.lettersFree, data.spacesFree);
                if (letters * a.text.priceLetters > 0) {
                    attrs += letters * a.text.priceLetters;
                }
            }
            onetime += a.onetime;
            if (a.onetimeTiers) {
                onetime += APM.tierPrice(a.onetimeTiers, qty) - APM.tierPrice(a.onetimeTiers, 1);
            }
        }

        // shoppingCart::calculate() floors the item at zero before tax.
        unit = Math.max(0, price + attrs);

        total = unit;
        onetimeShown = onetime;
        if (data.withTax && data.taxRate > 0) {
            total = total + total * data.taxRate / 100;
            onetimeShown = onetimeShown + onetimeShown * data.taxRate / 100;
        }
        if (data.multiplyQty) {
            total = total * qty;
        }

        return {unit: unit, total: total, onetime: onetimeShown, qty: qty};
    };

    /* ------------------------------------------------------------------ *
     * Formatting: currencies->format()
     * ------------------------------------------------------------------ */

    /** PHP's number_format() for a non-negative amount. */
    APM.numberFormat = function (value, decimals, decimalPoint, thousandsPoint) {
        var fixed, parts, whole, frac, out = '', i, n;
        fixed = APM.round(Math.abs(value), decimals).toFixed(decimals);
        parts = fixed.split('.');
        whole = parts[0];
        frac = parts.length > 1 ? parts[1] : '';
        n = whole.length;
        for (i = 0; i < n; i++) {
            if (i > 0 && (n - i) % 3 === 0) {
                out += thousandsPoint;
            }
            out += whole.charAt(i);
        }
        if (decimals > 0) {
            out += decimalPoint + frac;
        }
        return (value < 0 ? '-' : '') + out;
    };

    /** currencies->format(): exchange rate, rounding, symbols. */
    APM.format = function (amount, cur) {
        var converted = APM.round(amount * cur.value, cur.decimalPlaces);
        return cur.symbolLeft
            + APM.numberFormat(converted, cur.decimalPlaces, cur.decimalPoint, cur.thousandsPoint)
            + cur.symbolRight;
    };

    /* ------------------------------------------------------------------ *
     * Reading the form
     * ------------------------------------------------------------------ */

    /**
     * What is selected right now, as keys matching the price data.
     *
     * Field names are Zen Cart's own: id[<option>] for a dropdown or radio,
     * id[<option>][<value>] for a checkbox, id[txt_<option>] for text and
     * file inputs. A radio group with nothing checked contributes nothing,
     * exactly as it would contribute nothing to the cart.
     */
    APM.readSelections = function (form) {
        var selections = [], fields = form.elements, i, el, name, m, tag, type;

        for (i = 0; i < fields.length; i++) {
            el = fields[i];
            name = el.name || '';
            if (name.indexOf('id[') !== 0) {
                continue;
            }
            tag = (el.tagName || '').toLowerCase();
            type = (el.type || '').toLowerCase();

            m = /^id\[txt_(\d+)\]$/.exec(name);
            if (m) {
                selections.push({key: 'txt:' + m[1], text: (type === 'file') ? '' : String(el.value || '')});
                continue;
            }

            m = /^id\[(\d+)\]\[(\d+)\]$/.exec(name);
            if (m) {
                if (type === 'checkbox' && el.checked) {
                    selections.push({key: m[1] + ':' + m[2]});
                }
                continue;
            }

            m = /^id\[(\d+)\]$/.exec(name);
            if (!m) {
                continue;
            }
            if (tag === 'select') {
                if (el.selectedIndex >= 0 && el.options[el.selectedIndex].value !== '') {
                    selections.push({key: m[1] + ':' + el.options[el.selectedIndex].value});
                }
            } else if (type === 'radio') {
                if (el.checked) {
                    selections.push({key: m[1] + ':' + el.value});
                }
            } else if (type === 'hidden') {
                if (el.value !== '') {
                    selections.push({key: m[1] + ':' + el.value});
                }
            }
        }
        return selections;
    };

    /** The quantity box, or 1 when the template hides it. */
    APM.readQty = function (form) {
        var el = form.elements.cart_quantity;
        var qty;
        if (!el) {
            return 1;
        }
        // A radio-style qty picker is a NodeList; take the checked one.
        if (typeof el.length === 'number' && !el.tagName) {
            el = el[0];
        }
        qty = parseFloat(el.value);
        return (isNaN(qty) || qty <= 0) ? 1 : qty;
    };

    /* ------------------------------------------------------------------ *
     * The box
     * ------------------------------------------------------------------ */

    function makeBox(data, inPlace) {
        var box = doc.createElement('div');
        var label = doc.createElement('span');
        var amount = doc.createElement('span');
        var suffix, onetime, onetimeLabel, onetimeAmount;

        box.className = 'apm-box' + (inPlace ? ' apm-inplace' : '');
        box.setAttribute('aria-live', 'polite');

        label.className = 'apm-label';
        label.appendChild(doc.createTextNode(data.multiplyQty ? data.labels.total : data.labels.price));
        amount.className = 'apm-amount';

        box.appendChild(label);
        box.appendChild(amount);

        if (data.labels.suffix) {
            suffix = doc.createElement('span');
            suffix.className = 'apm-suffix';
            suffix.appendChild(doc.createTextNode(data.labels.suffix));
            box.appendChild(suffix);
        }

        if (data.showOnetime) {
            onetime = doc.createElement('span');
            onetime.className = 'apm-onetime';
            onetime.style.display = 'none';
            onetimeLabel = doc.createElement('span');
            onetimeLabel.className = 'apm-onetime-label';
            onetimeLabel.appendChild(doc.createTextNode(data.labels.onetime + ' '));
            onetimeAmount = doc.createElement('span');
            onetimeAmount.className = 'apm-onetime-amount';
            onetime.appendChild(onetimeLabel);
            onetime.appendChild(onetimeAmount);
            box.appendChild(onetime);
        }

        return box;
    }

    function setText(el, text) {
        while (el.firstChild) {
            el.removeChild(el.firstChild);
        }
        el.appendChild(doc.createTextNode(text));
    }

    function queryAll(selector) {
        try {
            return selector ? doc.querySelectorAll(selector) : [];
        } catch (e) {
            return [];
        }
    }

    function firstSubmit(root) {
        return root.querySelector('input[type="image"], button[type="submit"], input[type="submit"]');
    }

    /** Put a box in front of the Add to Cart button. */
    function placeBeforeButton(form, data) {
        var box = makeBox(data, false);
        var targets = queryAll(data.targetSelector);
        var target = targets.length ? targets[0] : null;
        var button;

        if (target) {
            button = firstSubmit(target);
            if (button) {
                button.parentNode.insertBefore(box, button);
            } else {
                target.insertBefore(box, target.firstChild);
            }
            return box;
        }
        button = firstSubmit(form);
        if (button) {
            button.parentNode.insertBefore(box, button);
            return box;
        }
        target = doc.getElementById('productAttributes');
        if (target && target.parentNode) {
            target.parentNode.insertBefore(box, target.nextSibling);
            return box;
        }
        return null;
    }

    /** Replace the stock "Starting at:" content with a box, in each block. */
    function placeInOriginals(data) {
        var boxes = [], blocks = queryAll(data.originalSelectors), i, block, heading, box;
        for (i = 0; i < blocks.length; i++) {
            block = blocks[i];
            // A card wraps its heading; write into the heading so the card
            // keeps its own frame.
            heading = /^h[1-6]$/i.test(block.tagName) ? block : block.querySelector('h1, h2, h3, h4');
            if (!heading) {
                heading = block;
            }
            box = makeBox(data, true);
            while (heading.firstChild) {
                heading.removeChild(heading.firstChild);
            }
            heading.appendChild(box);
            boxes.push(box);
        }
        return boxes;
    }

    function showOriginals() {
        var root = doc.documentElement;
        root.className = root.className.replace(/(^|\s)apm-active(?=\s|$)/g, ' ').replace(/\s+/g, ' ');
    }

    /* ------------------------------------------------------------------ *
     * Wiring
     * ------------------------------------------------------------------ */

    function init() {
        var data = win.attributePricingManager;
        var form, boxes = [], box, timer = null, last = null;

        if (!data || !data.attributes) {
            showOriginals();
            return;
        }

        form = doc.querySelector('form[name="cart_quantity"]');
        if (!form) {
            form = doc.getElementById('addToCartForm');
        }
        if (!form) {
            showOriginals();
            return;
        }

        if (data.placement === 'cart' || data.placement === 'both') {
            box = placeBeforeButton(form, data);
            if (box) {
                boxes.push(box);
            }
        }
        if (data.placement === 'original' || data.placement === 'both') {
            boxes = boxes.concat(placeInOriginals(data));
        }

        if (!boxes.length) {
            // Nowhere to put it: leave the stock price alone.
            showOriginals();
            return;
        }

        function render() {
            var result = APM.compute(data, APM.readSelections(form), APM.readQty(form));
            var text = APM.format(result.total, data.currency);
            var onetimeText = result.onetime > 0 ? APM.format(result.onetime, data.currency) : '';
            var changed = (last !== null && (last.text !== text || last.onetime !== onetimeText));
            var i, b, amount, onetime, onetimeAmount;

            for (i = 0; i < boxes.length; i++) {
                b = boxes[i];
                amount = b.querySelector('.apm-amount');
                setText(amount, text);
                onetime = b.querySelector('.apm-onetime');
                if (onetime) {
                    onetimeAmount = onetime.querySelector('.apm-onetime-amount');
                    setText(onetimeAmount, onetimeText);
                    onetime.style.display = onetimeText ? '' : 'none';
                }
                if (changed && data.highlight) {
                    b.className = b.className.replace(/(^|\s)apm-changed(?=\s|$)/g, '');
                    // Restart the animation even when the last one has not finished.
                    void b.offsetWidth;
                    b.className += ' apm-changed';
                }
            }
            last = {text: text, onetime: onetimeText};
        }

        function schedule() {
            if (timer) {
                win.clearTimeout(timer);
            }
            timer = win.setTimeout(render, 30);
        }

        form.addEventListener('change', schedule, false);
        form.addEventListener('input', schedule, false);
        form.addEventListener('keyup', schedule, false);
        // A template's own +/- quantity buttons usually click, not change.
        form.addEventListener('click', schedule, false);

        render();
    }

    APM.init = init;
    win.AttributePricingManager = APM;

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', init, false);
    } else {
        init();
    }
})(window, document);
