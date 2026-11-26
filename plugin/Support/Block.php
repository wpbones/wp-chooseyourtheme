<?php

namespace WPChooseYourTheme\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The "Theme Switcher" block, `wp-chooseyourtheme/switcher`: the shortcode and the widget as a block.
 *
 * It has no markup of its own: the same renderer as the shortcode, the template tag and the widget
 * builds and escapes it, and the block only wraps it and puts the title above. The editor's preview is
 * that same output, asked for by ServerSideRender.
 */
final class Block
{
  public const NAME = 'wp-chooseyourtheme/switcher';

  /** The editor script's handle, as WordPress derives it from block.json. */
  public const EDITOR_SCRIPT = 'wp-chooseyourtheme-switcher-editor-script';

  public static function dir(): string
  {
    return dirname(__DIR__, 2) . '/blocks/switcher';
  }

  /**
   * `init`.
   */
  public static function register(): void
  {
    register_block_type(self::dir(), ['render_callback' => [self::class, 'render']]);

    // A block's script is looked up for translations in WP_LANG_DIR only: the plugin's own files are
    // given here.
    wp_set_script_translations(self::EDITOR_SCRIPT, 'wp-chooseyourtheme', dirname(__DIR__, 2) . '/languages');
  }

  /**
   * @param array<string, mixed> $attributes The block's attributes: `mode` and `title`.
   */
  public static function render(array $attributes = []): string
  {
    $switcher = Renderer::render(Renderer::normalizeMode($attributes['mode'] ?? ''));

    // One theme is nothing to choose from: no empty box, and not the title alone.
    if ($switcher === '') {
      return '';
    }

    $title = isset($attributes['title']) && is_string($attributes['title']) ? trim($attributes['title']) : '';
    $heading = $title === '' ? '' : '<p class="wp-block-wp-chooseyourtheme-switcher__title">' . esc_html($title) . '</p>';

    // The editor draws the wrapper itself (useBlockProps), with the same classes: a second one here
    // would apply the margin and an em font size twice. The switcher is built, and escaped, by the renderer.
    if (Renderer::isEditorPreview()) {
      return $heading . $switcher;
    }

    return '<div ' . get_block_wrapper_attributes() . '>' . $heading . $switcher . '</div>';
  }
}
