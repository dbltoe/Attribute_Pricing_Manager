<?php
/**
 * Attribute Pricing Manager -- plugin manifest.
 *
 * @package  AttributePricingManager
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

/**
 * Links shown in the Plugin Manager, alongside Install / Uninstall / Disable.
 *
 * Zen Cart stores `pluginDescription` in `plugin_control.description` (a TEXT
 * column) and echoes it into the Plugin Manager's info box as raw HTML,
 * without escaping, on every release from v1.5.8 to v3.0.0. That box is also
 * where the action buttons live, so markup placed here appears exactly once,
 * right where the store owner is already looking, with no core file changed.
 *
 * The Read Me link is built from DIR_WS_CATALOG rather than hard-coded, so it
 * works whatever the store lives at and whatever the admin directory has been
 * renamed to. Zen Cart's shipped `zc_plugins/.htaccess` denies everything
 * then explicitly re-allows `.html`, so readme.html is reachable by design.
 *
 * On v2.2 and later the description is refreshed from this file on every
 * Plugin Manager scan. On v1.5.8, v2.0 and v2.1 it is not: those releases
 * update only the `infs` column for a row that already exists, so the
 * description is whatever was captured the first time Plugin Manager saw the
 * plugin. The one state-dependent part, the Settings button, is handled
 * below.
 */
$apmPluginDir = 'zc_plugins/AttributePricingManager/v1.1.1/';
$apmReadmeUrl = (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/') . $apmPluginDir . 'readme.html';
$apmGithubUrl = 'https://github.com/dbltoe/Attribute_Pricing_Manager';

/**
 * The Zen Cart forum's support thread for this plugin.
 *
 * PUT THE URL HERE BEFORE THE FIRST RELEASE, not after. On v1.5.8, v2.0 and
 * v2.1 plugin_control.description is written only by the INSERT that first
 * creates the row, so a link added later never reaches those stores short of
 * an uninstall and re-install. An empty string renders nothing at all, which
 * is the right outcome before the thread exists.
 */
$apmForumUrl = 'https://www.zen-cart.com/threads/207329?page=1#post-1347048';

/**
 * The Settings button: this plugin's Configuration page, shown only once the
 * plugin is installed. Its gID is assigned at install, so it differs from
 * store to store and can't be written here; it's read from the admin page the
 * installer registers (page key configAttributePricingManager). The link is
 * relative ("index.php?cmd=configuration&gID=N") because Plugin Manager is an
 * admin page, so it survives a domain or admin-folder rename.
 *
 * Zen Cart reads manifests only on the Plugin Manager page, but the lookup is
 * still guarded so a manifest read anywhere else (no database, catalog side,
 * CLI) just leaves the button out. On v2.2 and later this file is re-read on
 * every Plugin Manager visit, so the button follows the install state by
 * itself; on v1.5.8, v2.0 and v2.1 the installer saves the re-read
 * description at Install, Upgrade and Uninstall (apmRefreshPluginDescription).
 */
$apmSettingsUrl = '';
if (defined('IS_ADMIN_FLAG') && IS_ADMIN_FLAG === true && defined('TABLE_ADMIN_PAGES')
    && isset($GLOBALS['db']) && is_object($GLOBALS['db'])) {
    $apmPage = $GLOBALS['db']->Execute(
        "SELECT main_page, page_params FROM " . TABLE_ADMIN_PAGES
        . " WHERE page_key = 'configAttributePricingManager' LIMIT 1"
    );
    if (!$apmPage->EOF && defined($apmPage->fields['main_page'])) {
        $apmSettingsUrl = 'index.php?cmd=' . constant($apmPage->fields['main_page'])
            . '&' . $apmPage->fields['page_params'];
    }
}

// One row of buttons: ReadMe | Settings | Forum Thread | GitHub. The tighter
// padding and gaps keep all four on one line in Plugin Manager's side panel
// from a 1366px-wide window up (measured on the v2.2 admin).
$apmButtonGap = '4px';
$apmButtonStyle = ' style="margin:0 ' . $apmButtonGap . ' 0 0;padding-left:8px;padding-right:8px"';
$apmButton = static function ($url, $label, $newTab) use ($apmButtonStyle) {
    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
        . ($newTab ? ' target="_blank" rel="noopener noreferrer"' : '')
        . ' class="btn btn-primary" role="button"' . $apmButtonStyle . '>' . $label . '</a>';
};

$apmLinks =
    '<div style="margin:10px 0 0;padding:0 0 0 ' . $apmButtonGap . '">'
    . $apmButton($apmReadmeUrl, 'ReadMe', true)
    . ($apmSettingsUrl !== '' ? $apmButton($apmSettingsUrl, 'Settings', false) : '')
    . ($apmForumUrl !== '' ? $apmButton($apmForumUrl, 'Forum Thread', true) : '')
    . $apmButton($apmGithubUrl, 'GitHub', true)
    . '</div>';

return [
    'pluginVersion' => 'v1.1.1',
    'pluginName' => 'Attribute Pricing Manager',
    'pluginDescription' =>
        'Replaces the static "Starting at:" price on product pages with a running '
        . 'total that changes as the customer picks options, and puts it right in '
        . 'front of the Add to Cart button. The figure is worked out the way the '
        . 'shopping cart works it out, so what they see is what they will pay. '
        . 'No AJAX, no template edits; works with the stock templates and ZCA Bootstrap.'
        . $apmLinks,
    // Shown as the Author in Plugin Manager, and stored in
    // plugin_control.author / plugin_control_versions.author (varchar(64)).
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    // ID from the Zen Cart Plugins Library -- the "Plugin ID" printed on the
    // plugin's Library page, not the release number in a download URL.
    // Plugin Manager sends it to the version server to check for updates.
    // It has to be right the FIRST time a store sees the plugin: on
    // v1.5.8/v2.0/v2.1 plugin_control.zc_contrib_id is written only by the
    // INSERT that creates the row (v2.2 and later refresh it on every Plugin
    // Manager scan). A bare integer, never quoted.
    'pluginId' => 2456, // assigned by the Library 2026-10-06; replaces 2258, which was also another plugin's id
    'zcVersions' => ['v158', 'v200', 'v210', 'v220', 'v230', 'v300'],
    'changelog' => 'changelog.txt',
    'github_repo' => $apmGithubUrl,
    'pluginGroups' => [],
];
