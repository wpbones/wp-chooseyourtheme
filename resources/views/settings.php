<?php
/**
 * Appearance › WP Choose Your Theme.
 *
 * @var array  $rows       One row per installed theme: slug, name, version, kind, parent, active, selectable, checked.
 * @var string $group      The Settings API group.
 * @var string $option     The option the form saves.
 * @var int    $cookieDays Days the choice is remembered.
 * @var int    $minDays
 * @var int    $maxDays
 * @var bool   $backLink   Whether the way-back bar is on.
 * @var string $activeKind "block" or "classic".
 */

if (!defined('ABSPATH')) {
  exit();
}

$kinds = [
  'block' => __('Block theme', 'wp-chooseyourtheme'),
  'classic' => __('Classic theme', 'wp-chooseyourtheme'),
];

// Whole sentences, not a translated noun inside one: other languages inflect it.
$notOffered = [
  'block' => __('Not offered: the active theme is a block theme', 'wp-chooseyourtheme'),
  'classic' => __('Not offered: the active theme is a classic theme', 'wp-chooseyourtheme'),
];
?>
<div class="wrap">
  <h1><?php esc_html_e('WP Choose Your Theme', 'wp-chooseyourtheme'); ?></h1>

  <p><?php esc_html_e('Choose which of your installed themes a visitor may pick. The visitor sees the site with that theme; you and the other visitors keep the active one.', 'wp-chooseyourtheme'); ?></p>

  <form method="post" action="options.php">
    <?php settings_fields($group); ?>

    <h2 class="title"><?php esc_html_e('Themes visitors may choose', 'wp-chooseyourtheme'); ?></h2>

    <table class="widefat striped" style="max-width: 50rem">
      <thead>
        <tr>
          <td class="check-column"><span class="screen-reader-text"><?php esc_html_e('Allowed', 'wp-chooseyourtheme'); ?></span></td>
          <th scope="col"><?php esc_html_e('Theme', 'wp-chooseyourtheme'); ?></th>
          <th scope="col"><?php esc_html_e('Kind', 'wp-chooseyourtheme'); ?></th>
          <th scope="col"><?php esc_html_e('Status', 'wp-chooseyourtheme'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row) : ?>
          <?php $id = 'wpcyt-theme-' . sanitize_html_class(str_replace('/', '-', $row['slug'])); ?>
          <tr>
            <th scope="row" class="check-column">
              <?php if ($row['active']) : ?>
                <input type="checkbox" checked disabled id="<?php echo esc_attr($id); ?>" />
              <?php elseif (!$row['selectable']) : ?>
                <input type="checkbox" disabled id="<?php echo esc_attr($id); ?>" />
              <?php else : ?>
                <input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($option); ?>[allowed_themes][]" value="<?php echo esc_attr($row['slug']); ?>" <?php checked($row['checked']); ?> />
              <?php endif; ?>
            </th>
            <td>
              <label for="<?php echo esc_attr($id); ?>"><strong><?php echo esc_html($row['name']); ?></strong></label>
              <?php if ($row['version'] !== '') : ?>
                <span class="description"><?php echo esc_html($row['version']); ?></span>
              <?php endif; ?>
              <?php if ($row['parent'] !== '') : ?>
                <br /><span class="description">
                  <?php
                  /* translators: %s: the name of a parent theme. */
                  echo esc_html(sprintf(__('Child of %s', 'wp-chooseyourtheme'), $row['parent']));
                  ?>
                </span>
              <?php endif; ?>
            </td>
            <td><?php echo esc_html($kinds[$row['kind']]); ?></td>
            <td>
              <?php
              if ($row['active']) {
                esc_html_e('Active: always available', 'wp-chooseyourtheme');
              } elseif (!$row['selectable']) {
                echo esc_html($notOffered[$activeKind]);
              } elseif ($row['checked']) {
                esc_html_e('Visitors may choose it', 'wp-chooseyourtheme');
              } else {
                esc_html_e('Hidden from visitors', 'wp-chooseyourtheme');
              }
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <p class="description" style="max-width: 50rem">
      <?php esc_html_e('Block themes and classic themes keep their templates, menus and widget areas in different places, so a visitor is only ever switched to a theme of the same kind as the active one.', 'wp-chooseyourtheme'); ?>
    </p>

    <h2 class="title"><?php esc_html_e('Remember the choice', 'wp-chooseyourtheme'); ?></h2>

    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="wpcyt-cookie-days"><?php esc_html_e('Days', 'wp-chooseyourtheme'); ?></label></th>
        <td>
          <input type="number" class="small-text" id="wpcyt-cookie-days" name="<?php echo esc_attr($option); ?>[cookie_days]" value="<?php echo esc_attr((string) $cookieDays); ?>" min="<?php echo esc_attr((string) $minDays); ?>" max="<?php echo esc_attr((string) $maxDays); ?>" step="1" />
          <p class="description">
            <?php
            /* translators: 1: the smallest number of days, 2: the largest. */
            echo esc_html(sprintf(__('How long a visitor\'s browser remembers the theme they chose, from %1$d to %2$d days.', 'wp-chooseyourtheme'), $minDays, $maxDays));
            ?>
          </p>
        </td>
      </tr>
    </table>

    <h2 class="title"><?php esc_html_e('A way back', 'wp-chooseyourtheme'); ?></h2>

    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><?php esc_html_e('Link back', 'wp-chooseyourtheme'); ?></th>
        <td>
          <input type="hidden" name="<?php echo esc_attr($option); ?>[back_link]" value="0" />
          <label for="wpcyt-back-link">
            <input type="checkbox" id="wpcyt-back-link" name="<?php echo esc_attr($option); ?>[back_link]" value="1" <?php checked($backLink); ?> />
            <?php esc_html_e('Show visitors who chose another theme a link back to the site\'s own theme, when the page has no switcher', 'wp-chooseyourtheme'); ?>
          </label>
          <p class="description">
            <?php esc_html_e('A template part or a widget area belongs to one theme, so the theme a visitor chose may have no switcher in it. This small bar at the bottom of the page lets them go back.', 'wp-chooseyourtheme'); ?>
          </p>
        </td>
      </tr>
    </table>

    <?php submit_button(); ?>
  </form>

  <h2 class="title"><?php esc_html_e('Show the switcher', 'wp-chooseyourtheme'); ?></h2>

  <p><?php esc_html_e('Four ways in, each with the same three modes: menu (a dropdown), list (a list of links) and roll (one link to the next theme).', 'wp-chooseyourtheme'); ?></p>
  <ul style="list-style: disc; margin-left: 1.5em">
    <li><?php esc_html_e('Widget: Appearance › Widgets, "Choose Your Theme".', 'wp-chooseyourtheme'); ?></li>
    <li><?php esc_html_e('Shortcode:', 'wp-chooseyourtheme'); ?> <code>[wp_chooseyourtheme mode="list"]</code></li>
    <li><?php esc_html_e('Template tag:', 'wp-chooseyourtheme'); ?> <code>&lt;?php wp_chooseyourtheme( 'mode=roll' ); ?&gt;</code></li>
    <li><?php esc_html_e('Block: "Theme Switcher", in the block editor and the Site Editor.', 'wp-chooseyourtheme'); ?></li>
  </ul>

  <p class="description">
    <?php esc_html_e('A page cache serves one copy of a page to everyone. If a caching plugin or your host caches pages, set it to skip the cache for visitors who send the wpcyt_theme cookie, or visitors may be shown each other\'s theme.', 'wp-chooseyourtheme'); ?>
  </p>
</div>
