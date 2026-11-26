# Playground checks

Blueprints that set up a WordPress Playground for a check by hand, installing the plugin from WordPress.org (so they test the released version: the block and the way-back bar need 1.1.0 or later):

- [`classic-theme-widget.json`](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wpbones/wp-chooseyourtheme/main/tests/playground/classic-theme-widget.json): Twenty Twenty-One (a classic theme) on the block-based Widgets screen. Add the plugin's widget from the block inserter, save, and look at the footer.
- [`block-theme-template.json`](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wpbones/wp-chooseyourtheme/main/tests/playground/block-theme-template.json): Twenty Twenty-Five (a block theme) with the plugin's Theme Switcher block at the top of the footer template part, saved the way the Site Editor saves it. Pick Twenty Twenty-Four in the footer, then look at its footer: it has no switcher (a template part belongs to one theme), and the "way back" bar at the bottom of the page is how you return.

The demo WordPress.org shows as its Live Preview is [`assets/blueprints/blueprint.json`](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wpbones/wp-chooseyourtheme/main/assets/blueprints/blueprint.json).
