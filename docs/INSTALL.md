# Installing Attribute Pricing Manager

Applies to Zen Cart v1.5.8 and later, including v2.x and the v3.0.0 development
branch. PHP 7.4 through 8.5.

---

## 1. Copy the files

This repository contains one directory that belongs in your store:

```
zc_plugins/AttributePricingManager/
```

Upload it so it lands at:

```
<your store root>/zc_plugins/AttributePricingManager/v1.0.0/
```

That directory should contain `manifest.php`, `readme.html`, `changelog.txt`,
and the `Installer/`, `admin/` and `catalog/` sub-directories.

Nothing goes anywhere else. The plugin does not drop files into your `admin/`,
`includes/` or template directories, which is what makes it safe to remove
later.

> **If your store is itself a git checkout of Zen Cart:** `zc_plugins/.gitignore`
> uses a deny-all rule with an explicit allowlist, so add `!AttributePricingManager/`
> and `!AttributePricingManager/**` to it or git will not see the plugin. This has no
> effect on the plugin working.

## 2. Install it

1. Log in to your Zen Cart admin.
2. Go to **Modules → Plugin Manager**.
3. Find **Attribute Pricing Manager** and click **Install**.

> Selecting the plugin shows an info panel on the right. Alongside the
> Install / Uninstall buttons are **Read Me** and **GitHub** buttons; Read Me
> opens the full documentation from inside the plugin directory.

Installing creates a configuration group, **Attribute Pricing Manager**, with nine
settings, and its entry on the Configuration menu. Nothing else: no tables,
no admin pages of its own.

## 3. Look at a product

Open any product. The running total is in front of the Add to Cart button,
labelled "Total:" and multiplied by the quantity box; the stock "Starting at:"
heading stays where it was. Change an option or the quantity; the figure
changes. Hiding the stock heading and multiplying by quantity are both
settings.

If it does not, see the troubleshooting section of
[CONFIGURATION.md](CONFIGURATION.md) — almost always the template names its
price heading or Add to Cart box something other than the stock ids, and two
settings put that right.

---

## Upgrading

Upload the new version's directory beside the old one, so that both
`zc_plugins/AttributePricingManager/v1.0.0/` and the newer directory are present. In
Plugin Manager, select the plugin and click **Upgrade**.

Your settings are kept. An upgrade refreshes each setting's label and help
text but never its value. Delete the old version directory afterwards.

## Uninstalling

In Plugin Manager, select the plugin and click **Uninstall**. That removes the
configuration group and every setting. Then delete `zc_plugins/AttributePricingManager/`.

No other file in your store was changed, and no table was created, so there is
nothing else to undo.
