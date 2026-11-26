<?php

/**
 * This file is included by the "bones" command when you run `php bones deploy`:
 *
 *   php bones deploy <path> --wp --no-build
 *
 * There is nothing to build (no React, no bundler), hence --no-build. $this is the console, $path
 * the destination.
 */

/**
 * Filter the list of the folders and files to skip for the deploy version.
 *
 * bones already leaves out the tests, the PHPUnit configuration, the Composer dev packages (it runs
 * `composer install --no-dev` in the copy, never in this working tree), `assets/` (the WordPress.org
 * banner, icon and Live Preview blueprint live in SVN's own assets folder), the README.md and the
 * agent instruction files.
 *
 * @param array $folders List of folders to skip
 * @return array List of folders to skip
 */
add_filter('wpbones_console_deploy_skip_folders', function ($folders) {
  // No JavaScript is built, so the package manifest is not needed in the package.
  return array_merge($folders, ['/package.json']);
});
