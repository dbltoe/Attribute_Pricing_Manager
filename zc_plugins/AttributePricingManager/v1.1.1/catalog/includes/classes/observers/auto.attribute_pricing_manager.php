<?php
/**
 * Attribute Pricing Manager -- storefront observer.
 *
 * Auto-loaded and instantiated by `includes/init_includes/init_observers.php`
 * on every supported Zen Cart release. The filename
 * (`auto.attribute_pricing_manager.php`) and the class name below must stay in step:
 * Zen Cart derives the expected class as
 * 'zcObserver' . base::camelize('attribute_pricing_manager', true).
 *
 * One notifier is enough. NOTIFY_HTML_HEAD_END fires at the end of <head> on
 * every release from v1.5.8 to v3.0.0-dev, and it arrives with the current
 * page name, which is all the plugin needs to decide whether this is a
 * product page. The price data itself comes from the database, not from the
 * attributes module, so the plugin does not care whether the template has
 * built its attribute fields yet.
 *
 * @package  AttributePricingManager
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class zcObserverAttributePricingManager extends base
{
    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_HTML_HEAD_END',
        ]);
    }

    /**
     * End of <head>: stylesheet, price data and script, on product pages only.
     *
     * @param object $class
     * @param string $eventID
     * @param mixed $param1 the current page base, e.g. 'product_info'
     */
    public function updateNotifyHtmlHeadEnd($class, $eventID, $param1 = null)
    {
        if (!function_exists('apm_render_head')) {
            // The plugin's extra_functions file did not load -- stay quiet
            // rather than take the storefront down.
            return;
        }

        $currentPageBase = is_string($param1) ? $param1 : (isset($GLOBALS['current_page_base']) ? (string)$GLOBALS['current_page_base'] : '');

        echo apm_render_head($currentPageBase);
    }
}
