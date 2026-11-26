<?php

declare(strict_types=1);

// A stand-in for WP_Widget, so the unit tests can load the plugin's widget. WordPress's own class
// is used wherever WordPress is loaded.
if (!class_exists('WP_Widget')) {
  class WP_Widget
  {
    public $id_base = '';

    public function __construct($id_base = '', $name = '', $widget_options = [], $control_options = [])
    {
      $this->id_base = $id_base;
    }

    public function get_field_id($field)
    {
      return 'widget-' . $field;
    }

    public function get_field_name($field)
    {
      return 'widget[' . $field . ']';
    }
  }
}
