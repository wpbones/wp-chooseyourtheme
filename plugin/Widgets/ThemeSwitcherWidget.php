<?php

namespace WPChooseYourTheme\Widgets;

use WPChooseYourTheme\Support\Renderer;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The classic widget: a title and a mode.
 */
class ThemeSwitcherWidget extends \WP_Widget
{
  public function __construct()
  {
    parent::__construct(
      'wp_chooseyourtheme',
      __('Choose Your Theme', 'wp-chooseyourtheme'),
      [
        'classname' => 'widget_wp_chooseyourtheme',
        'description' => __('Let visitors choose which theme they see the site with.', 'wp-chooseyourtheme'),
        'customize_selective_refresh' => false,
      ]
    );
  }

  public function widget($args, $instance)
  {
    $output = Renderer::render(Renderer::normalizeMode($instance['mode'] ?? ''));

    // A single theme is nothing to choose from: no empty box with a title.
    if ($output === '') {
      return;
    }

    echo $args['before_widget'];

    $title = apply_filters('widget_title', $instance['title'] ?? '', $instance, $this->id_base);
    if ($title !== '') {
      echo $args['before_title'] . esc_html($title) . $args['after_title'];
    }

    // Built, and escaped, by the renderer.
    echo $output;

    echo $args['after_widget'];
  }

  public function update($new_instance, $old_instance)
  {
    return [
      'title' => sanitize_text_field($new_instance['title'] ?? ''),
      'mode' => Renderer::normalizeMode($new_instance['mode'] ?? ''),
    ];
  }

  public function form($instance)
  {
    $title = (string) ($instance['title'] ?? '');
    $mode = Renderer::normalizeMode($instance['mode'] ?? '');
    $modes = [
      'menu' => __('Dropdown menu', 'wp-chooseyourtheme'),
      'list' => __('List of links', 'wp-chooseyourtheme'),
      'roll' => __('One link to the next theme', 'wp-chooseyourtheme'),
    ];
    ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'wp-chooseyourtheme'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('mode')); ?>"><?php esc_html_e('Show as:', 'wp-chooseyourtheme'); ?></label>
      <select class="widefat" id="<?php echo esc_attr($this->get_field_id('mode')); ?>" name="<?php echo esc_attr($this->get_field_name('mode')); ?>">
        <?php foreach ($modes as $value => $label) : ?>
          <option value="<?php echo esc_attr($value); ?>" <?php selected($mode, $value); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
      </select>
    </p>
    <?php
  }
}
