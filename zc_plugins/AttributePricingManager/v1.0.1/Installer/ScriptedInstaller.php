<?php
/**
 * Attribute Pricing Manager -- Plugin Manager installer.
 *
 * Deliberately limited to the API that exists in every supported Zen Cart
 * release (v1.5.8 -> v3.0.0):
 *
 *   - only `executeInstallerSql()` is used for database work. The convenience
 *     helpers (`addConfigurationKey()`, `getOrCreateConfigGroupId()`, ...)
 *     were added in ZC v2.0.1/v2.1.0 and do not exist on v1.5.8.
 *   - `executeUpgrade()` is declared with an optional argument, because ZC
 *     v1.5.8 calls it with none and ZC v2.x/v3.x calls it with $oldVersion.
 *
 * Every step is idempotent, so an upgrade is simply a re-run of the install.
 *
 * @package  AttributePricingManager
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    /**
     * Title of the configuration group created for this plugin.
     */
    public const CONFIG_GROUP_TITLE = 'Attribute Pricing Manager';

    /**
     * admin_pages.page_key values this plugin owns.
     *
     * A configuration group is not reachable from the admin menu on its own:
     * Zen Cart draws the Configuration menu from the admin_pages table, so
     * the group needs a row there or the settings can only be found by
     * typing gID= into the address bar. System Inspection reports a plugin
     * that creates a group without one as "missing Admin Configuration
     * Pages", which is exactly what the first build of this plugin did.
     */
    public const ADMIN_PAGE_KEYS = ['configAttributePricingManager'];

    protected function executeInstall()
    {
        $configGroupId = $this->apmGetOrCreateConfigGroup();
        if ($configGroupId === 0) {
            return false;
        }

        $this->apmRemoveRetiredKeys();
        if ($this->apmAddConfigurationKeys($configGroupId) === false) {
            return false;
        }
        $this->apmRegisterAdminPages($configGroupId);

        $this->apmLog('Attribute Pricing Manager: installed/upgraded.', 'info');

        return true;
    }

    /**
     * ZC v1.5.8 calls this with no argument; ZC v2.x/v3.x passes $oldVersion.
     */
    protected function executeUpgrade($oldVersion = null)
    {
        // The install routine is idempotent, so re-running it brings any
        // older installation up to the current settings without touching
        // values the store owner has already customized.
        return $this->executeInstall();
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages(self::ADMIN_PAGE_KEYS);

        $groupId = $this->apmGetConfigGroupId();
        if ($groupId > 0) {
            $this->executeInstallerSql(
                "DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_group_id = " . $groupId
            );
            $this->executeInstallerSql(
                "DELETE FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_id = " . $groupId
            );
        }
        // Belt and braces: a key that somehow ended up in another group.
        $this->executeInstallerSql(
            "DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key LIKE 'APM\\_%'"
        );

        $this->apmLog('Attribute Pricing Manager: uninstalled.', 'info');

        return true;
    }

    /**
     * Write a line to the admin activity log, if we can.
     *
     * Guarded because the installer runs in an unusual context -- Plugin
     * Manager, mid-transaction -- and a missing logger must never be the
     * thing that fails an install.
     *
     * @param string $message
     * @param string $severity
     * @return void
     */
    protected function apmLog($message, $severity = 'info')
    {
        if (function_exists('zen_record_admin_activity')) {
            zen_record_admin_activity($message, $severity);
        }
    }

    /* ----------------------------------------------------------------- *
     * The Configuration menu entry
     * ----------------------------------------------------------------- */

    /**
     * Put the group on Admin -> Configuration.
     *
     * The menu label comes from BOX_CONFIGURATION_ATTRIBUTE_PRICING_MANAGER, defined
     * in this plugin's admin extra_definitions language file. Both
     * zen_register_admin_page() and zen_deregister_admin_pages() exist in
     * admin_access.php on every release from v1.5.8 to v3.0.0.
     *
     * @param int $configGroupId
     * @return void
     */
    protected function apmRegisterAdminPages($configGroupId)
    {
        // zen_register_admin_page() performs a plain INSERT, so clear first
        // to keep a re-run (upgrade) from adding a second menu entry.
        zen_deregister_admin_pages(self::ADMIN_PAGE_KEYS);

        zen_register_admin_page(
            'configAttributePricingManager',
            'BOX_CONFIGURATION_ATTRIBUTE_PRICING_MANAGER',
            'FILENAME_CONFIGURATION',
            'gID=' . (int)$configGroupId,
            'configuration',
            'Y',
            (int)$configGroupId
        );
    }

    /* ----------------------------------------------------------------- *
     * Configuration group
     * ----------------------------------------------------------------- */

    protected function apmGetConfigGroupId()
    {
        $sql =
            "SELECT configuration_group_id
               FROM " . TABLE_CONFIGURATION_GROUP . "
              WHERE configuration_group_title = '" . $this->dbConn->prepare_input(self::CONFIG_GROUP_TITLE) . "'
              LIMIT 1";
        $check = $this->dbConn->Execute($sql);

        return ($check->EOF) ? 0 : (int)$check->fields['configuration_group_id'];
    }

    protected function apmGetOrCreateConfigGroup()
    {
        $groupId = $this->apmGetConfigGroupId();
        if ($groupId > 0) {
            return $groupId;
        }

        $created = $this->executeInstallerSql(
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . "
                (configuration_group_title, configuration_group_description, sort_order, visible)
             VALUES
                ('" . $this->dbConn->prepare_input(self::CONFIG_GROUP_TITLE) . "',
                 'A running price on product pages that updates as the customer chooses options, shown in front of the Add to Cart button.',
                 0, 1)"
        );
        if ($created === false) {
            return 0;
        }

        $groupId = $this->apmGetConfigGroupId();
        if ($groupId > 0) {
            // Zen Cart convention: a configuration group's sort_order matches its id.
            $this->executeInstallerSql(
                "UPDATE " . TABLE_CONFIGURATION_GROUP . "
                    SET sort_order = $groupId
                  WHERE configuration_group_id = $groupId
                  LIMIT 1"
            );
        }

        return $groupId;
    }

    /* ----------------------------------------------------------------- *
     * Configuration keys
     * ----------------------------------------------------------------- */

    /**
     * Keys from earlier builds that this version no longer reads.
     *
     * Constants are defined once and the first definition wins, so a
     * leftover key can silently shadow a later default. Empty for now.
     */
    public const RETIRED_KEYS = [];

    protected function apmRemoveRetiredKeys()
    {
        if (self::RETIRED_KEYS === []) {
            return true;
        }
        $db = $this->dbConn;
        $list = implode("','", array_map(static function ($key) use ($db) {
            return $db->prepare_input($key);
        }, self::RETIRED_KEYS));

        return $this->executeInstallerSql(
            "DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . $list . "')"
        );
    }

    /**
     * The settings, in the order Admin -> Configuration shows them.
     *
     * @param int $configGroupId
     * @return array
     */
    protected function apmConfigurationKeys($configGroupId)
    {
        $yesNo = "zen_cfg_select_option(array('true', 'false'), ";

        return [
            [
                'key' => 'APM_STATUS',
                'title' => 'Enable Attribute Pricing Manager?',
                'value' => 'true',
                'description' => 'Show a running price on product pages that changes as the customer picks options.<br><br>Set to false to switch the plugin off without uninstalling it.',
                'sort_order' => 10,
                'set_function' => $yesNo,
            ],
            [
                'key' => 'APM_PLACEMENT',
                'title' => 'Where to show the running total',
                'value' => 'Bottom',
                'description' => '<b>Top</b>: where the template shows "Starting at:", written into that heading.<br><br><b>Bottom</b>: directly in front of the Add to Cart button, where the customer is about to commit.<br><br><b>Both</b>: in both places, kept in step.',
                'sort_order' => 20,
                'set_function' => "zen_cfg_select_option(array('Top', 'Bottom', 'Both'), ",
            ],
            [
                'key' => 'APM_HIDE_ORIGINAL',
                'title' => 'Hide the stock "Starting at:" price?',
                'value' => 'false',
                'description' => 'Only applies when the placement is <b>Bottom</b>. When true, the template\'s own price heading is hidden so the page does not show two prices.<br><br>If the running total cannot be placed (an unusual template), the stock heading is shown again automatically, so a customer is never left without a price.',
                'sort_order' => 30,
                'set_function' => $yesNo,
            ],
            [
                'key' => 'APM_ORIGINAL_SELECTORS',
                'title' => 'Stock price block selectors',
                'value' => '#productPrices, #productsPriceTop-card, #productsPriceBottom-card',
                'description' => 'CSS selectors, comma separated, that find the template\'s own price heading(s).<br><br>The defaults cover Zen Cart\'s stock templates (<code>#productPrices</code>) and the ZCA Bootstrap template (<code>#productsPriceTop-card</code>, <code>#productsPriceBottom-card</code>). Only change this for a template that names the price block differently.',
                'sort_order' => 40,
                'set_function' => '',
            ],
            [
                'key' => 'APM_TARGET_SELECTOR',
                'title' => 'Add to Cart container selector',
                'value' => '#cartAdd',
                'description' => 'A CSS selector for the element that holds the Add to Cart button. The running total is placed immediately before the button inside it.<br><br><code>#cartAdd</code> is right for Zen Cart\'s stock templates and the ZCA Bootstrap template. If it is not found, the plugin falls back to the form\'s own submit button, then to just after the attributes block.',
                'sort_order' => 50,
                'set_function' => '',
            ],
            [
                'key' => 'APM_MULTIPLY_QTY',
                'title' => 'Multiply by the quantity box?',
                'value' => 'true',
                'description' => '<b>false</b>: the figure is the price of one item with the chosen options, labelled "Your Price:".<br><br><b>true</b>: the figure is that price times the quantity in the box, labelled "Total:", with the product\'s quantity discounts applied.',
                'sort_order' => 60,
                'set_function' => $yesNo,
            ],
            [
                'key' => 'APM_SHOW_ONETIME',
                'title' => 'Show one-time charges?',
                'value' => 'true',
                'description' => 'When a chosen option carries a charge that is added once per order rather than per item, show it on its own line beneath the running total.',
                'sort_order' => 70,
                'set_function' => $yesNo,
            ],
            [
                'key' => 'APM_ATTRIBUTES_ONLY',
                'title' => 'Only on products with options?',
                'value' => 'false',
                'description' => '<b>true</b>: the running total appears only on products that have attributes, which are the ones whose "Starting at:" price can mislead.<br><br><b>false</b>: it appears on every product page. Useful together with "Multiply by the quantity box".',
                'sort_order' => 80,
                'set_function' => $yesNo,
            ],
            [
                'key' => 'APM_HIGHLIGHT',
                'title' => 'Flash the figure when it changes?',
                'value' => 'true',
                'description' => 'A brief highlight on the amount each time it changes, so the customer\'s eye is drawn to it. Respects the browser\'s reduced-motion preference.',
                'sort_order' => 90,
                'set_function' => $yesNo,
            ],
        ];
    }

    protected function apmAddConfigurationKeys($configGroupId)
    {
        foreach ($this->apmConfigurationKeys($configGroupId) as $key) {
            $sql =
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description,
                     configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('" . $this->dbConn->prepare_input($key['title']) . "',
                     '" . $this->dbConn->prepare_input($key['key']) . "',
                     '" . $this->dbConn->prepare_input($key['value']) . "',
                     '" . $this->dbConn->prepare_input($key['description']) . "',
                     " . (int)$configGroupId . ",
                     " . (int)$key['sort_order'] . ",
                     now(),
                     NULL,
                     " . (empty($key['set_function']) ? 'NULL' : "'" . $this->dbConn->prepare_input($key['set_function']) . "'") . ")";

            if ($this->executeInstallerSql($sql) === false) {
                return false;
            }

            // INSERT IGNORE deliberately leaves an existing row alone, which
            // is right for the VALUE -- that belongs to the store owner and
            // must never be reset by an upgrade. It is wrong for everything
            // else: the label, the help text, the ordering and the input
            // type all belong to the plugin, and a store upgrading from an
            // earlier version should get the current wording.
            $sql =
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_title = '" . $this->dbConn->prepare_input($key['title']) . "',
                        configuration_description = '" . $this->dbConn->prepare_input($key['description']) . "',
                        configuration_group_id = " . (int)$configGroupId . ",
                        sort_order = " . (int)$key['sort_order'] . ",
                        set_function = " . (empty($key['set_function']) ? 'NULL' : "'" . $this->dbConn->prepare_input($key['set_function']) . "'") . "
                  WHERE configuration_key = '" . $this->dbConn->prepare_input($key['key']) . "'
                  LIMIT 1";

            if ($this->executeInstallerSql($sql) === false) {
                return false;
            }
        }

        return true;
    }
}
