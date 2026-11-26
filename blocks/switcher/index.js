/*
 * Theme Switcher block. Written by hand, no build step: it uses the wp.* globals the block editor
 * provides, and its dependencies are listed in index.asset.php.
 *
 * The editor shows the real switcher: ServerSideRender asks the server for the same markup the front
 * end prints. Its settings are the shortcode's mode and the widget's title.
 */
(function (wp) {
  'use strict';

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var __ = wp.i18n.__;
  var registerBlockType = wp.blocks.registerBlockType;
  var createBlock = wp.blocks.createBlock;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var SelectControl = wp.components.SelectControl;
  var TextControl = wp.components.TextControl;
  var Disabled = wp.components.Disabled;
  var Placeholder = wp.components.Placeholder;
  var ServerSideRender = wp.serverSideRender;

  var NAME = 'wp-chooseyourtheme/switcher';
  var MODES = ['menu', 'list', 'roll'];

  function modeOptions() {
    return [
      { value: 'menu', label: __('Dropdown menu', 'wp-chooseyourtheme') },
      { value: 'list', label: __('List of links', 'wp-chooseyourtheme') },
      { value: 'roll', label: __('One link to the next theme', 'wp-chooseyourtheme') },
    ];
  }

  function normalizeMode(mode) {
    mode = typeof mode === 'string' ? mode.trim().toLowerCase() : '';
    return MODES.indexOf(mode) === -1 ? 'menu' : mode;
  }

  function Empty() {
    // The server has nothing to show: a lone theme is nothing to choose from.
    return el(
      Placeholder,
      { icon: 'art', label: __('Theme Switcher', 'wp-chooseyourtheme') },
      __('There is only one theme to choose from. Tick more themes in Appearance > WP Choose Your Theme.', 'wp-chooseyourtheme')
    );
  }

  function Edit(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;

    return el(
      Fragment,
      null,
      el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: __('Theme Switcher', 'wp-chooseyourtheme') },
          el(SelectControl, {
            label: __('Show as', 'wp-chooseyourtheme'),
            value: normalizeMode(attributes.mode),
            options: modeOptions(),
            onChange: function (mode) {
              setAttributes({ mode: mode });
            },
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
          }),
          el(TextControl, {
            label: __('Title', 'wp-chooseyourtheme'),
            help: __('Optional. Shown above the switcher.', 'wp-chooseyourtheme'),
            value: attributes.title || '',
            onChange: function (title) {
              setAttributes({ title: title });
            },
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
          })
        )
      ),
      el(
        'div',
        useBlockProps(),
        // Disabled: a click in the editor must not follow a link or submit the form.
        el(
          Disabled,
          null,
          el(ServerSideRender, {
            block: NAME,
            attributes: attributes,
            EmptyResponsePlaceholder: Empty,
          })
        )
      )
    );
  }

  registerBlockType(NAME, {
    edit: Edit,
    // Rendered on the server: nothing is saved in the post but the block's comment.
    save: function () {
      return null;
    },
    transforms: {
      from: [
        {
          // [wp_chooseyourtheme mode="list"], pasted or already in a post.
          type: 'shortcode',
          tag: 'wp_chooseyourtheme',
          attributes: {
            mode: {
              type: 'string',
              shortcode: function (attrs) {
                return normalizeMode(attrs && attrs.named && attrs.named.mode);
              },
            },
          },
        },
        {
          // The classic widget, in the Widgets screen's Legacy Widget block.
          type: 'block',
          blocks: ['core/legacy-widget'],
          isMatch: function (attributes) {
            return attributes.idBase === 'wp_chooseyourtheme';
          },
          transform: function (attributes) {
            var raw = (attributes.instance && attributes.instance.raw) || {};
            return createBlock(NAME, {
              mode: normalizeMode(raw.mode),
              title: typeof raw.title === 'string' ? raw.title : '',
            });
          },
        },
      ],
    },
  });
})(window.wp);
