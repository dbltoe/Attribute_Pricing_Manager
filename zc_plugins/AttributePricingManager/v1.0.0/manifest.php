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
 * plugin. That is why nothing state-dependent belongs in it.
 */
$apmPluginDir = 'zc_plugins/AttributePricingManager/v1.0.0/';
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

$apmButtonGap = '6px';

$apmLinks =
    '<div style="margin:10px 0 0;padding:0 0 0 ' . $apmButtonGap . '">'
    . '<a href="' . $apmReadmeUrl . '" target="_blank" rel="noopener noreferrer"'
    . ' class="btn btn-primary" role="button"'
    . ' style="margin:0 ' . $apmButtonGap . ' 0 0">Read Me</a>'
    . '<a href="' . $apmGithubUrl . '" target="_blank" rel="noopener noreferrer"'
    . ' class="btn btn-primary" role="button"'
    . ' style="margin:0 ' . $apmButtonGap . ' 0 0">GitHub</a>'
    . ($apmForumUrl !== ''
        ? '<a href="' . $apmForumUrl . '" target="_blank" rel="noopener noreferrer"'
          . ' class="btn btn-primary" role="button"'
          . ' style="margin:0 ' . $apmButtonGap . ' 0 0">Forum Support Thread</a>'
        : '')
    . '</div>';

return [
    'pluginVersion' => 'v1.0.0',
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
    // ID from the Zen Cart Plugins Library -- the number after "vb" in the
    // plugin's page URL. It has to be right the FIRST time a store installs
    // the plugin: on v1.5.8/v2.0/v2.1 plugin_control.zc_contrib_id is written
    // only by the INSERT that creates the row. The Library assigns the id on
    // final acceptance, so it is zero in the submitted package; set it here,
    // rebuild, and re-upload before any store installs from the Library.
    'pluginId' => 2258, // the id the Plugins Library embeds at acceptance, not the release number in the download URL
    'zcVersions' => ['v158', 'v200', 'v210', 'v220', 'v230', 'v300'],
    'changelog' => 'changelog.txt',
    'github_repo' => $apmGithubUrl,
    'pluginGroups' => [],
];
