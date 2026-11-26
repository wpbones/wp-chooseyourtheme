<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use Brain\Monkey\Functions;
use WPChooseYourTheme\Support\Block;
use WPChooseYourTheme\Support\Shortcode;
use WPChooseYourTheme\Tests\Support\FakeTheme;
use WPChooseYourTheme\Tests\Support\MonkeyTestCase;
use WPChooseYourTheme\Tests\Support\ThemeWorld;

final class BlockTest extends MonkeyTestCase
{
  use ThemeWorld;

  protected function setUp(): void
  {
    parent::setUp();

    Functions\when('wp_enqueue_script')->justReturn(true);
    Functions\when('plugins_url')->justReturn('https://example.test/p.js');
    Functions\when('selected')->alias(fn ($a, $b, $echo = true) => (string) $a === (string) $b ? ' selected=\'selected\'' : '');
    Functions\when('get_block_wrapper_attributes')->justReturn('class="wp-block-wp-chooseyourtheme-switcher"');
    Functions\when('shortcode_atts')->alias(fn ($pairs, $atts) => array_merge($pairs, array_intersect_key($atts, $pairs)));
    $this->site('b', [new FakeTheme('a', 'Alpha'), new FakeTheme('b', 'Beta <i>')], ['allowed_themes' => ['a']]);
  }

  /** @return array<string, mixed> */
  private function json(): array
  {
    return json_decode((string) file_get_contents(Block::dir() . '/block.json'), true, 512, JSON_THROW_ON_ERROR);
  }

  public function testTheBlockIsTheShortcodesSwitcherInsideTheWrapper(): void
  {
    foreach (['menu', 'list', 'roll'] as $mode) {
      // The menu's id counts the switchers on the page; the rest is identical.
      $this->assertSame(
        '<div class="wp-block-wp-chooseyourtheme-switcher">' . preg_replace('/wpcyt-\d+/', 'wpcyt-N', Shortcode::render(['mode' => $mode])) . '</div>',
        preg_replace('/wpcyt-\d+/', 'wpcyt-N', Block::render(['mode' => $mode])),
        $mode
      );
    }
  }

  public function testNoAttributesOrAnUnknownModeIsTheMenuLikeTheShortcode(): void
  {
    $this->assertStringContainsString('<form class="wpcyt_form"', Block::render([]));
    $this->assertStringContainsString('<form class="wpcyt_form"', Block::render(['mode' => 'rand']));
    $this->assertStringContainsString('<form class="wpcyt_form"', Block::render(['mode' => ['x']]));
    $this->assertStringContainsString('<ul class="wpcyt_list">', Block::render(['mode' => 'list']));
  }

  public function testTheTitleIsAboveTheSwitcherAndEscaped(): void
  {
    $html = Block::render(['mode' => 'roll', 'title' => ' Pick <b>one</b> ']);

    $this->assertStringContainsString('<p class="wp-block-wp-chooseyourtheme-switcher__title">Pick &lt;b&gt;one&lt;/b&gt;</p><div class="wpcyt_roll">', $html);
    $this->assertStringNotContainsString('__title"></p>', Block::render(['title' => '   ']));
    $this->assertStringNotContainsString('__title', Block::render(['title' => ['x']]));
  }

  public function testThemeNamesAreEscaped(): void
  {
    $html = Block::render(['mode' => 'list']) . Block::render(['mode' => 'menu']);

    $this->assertStringContainsString('Beta &lt;i&gt;', $html);
    $this->assertStringNotContainsString('<i>', $html);
  }

  public function testNothingIsPrintedWhenThereIsNothingToChooseFrom(): void
  {
    $this->site('b', [new FakeTheme('a'), new FakeTheme('b')], ['allowed_themes' => []]);

    $this->assertSame('', Block::render(['mode' => 'list', 'title' => 'Pick']), 'not even the title or the wrapper');
  }

  public function testTheCurrentThemeIsMarkedWhereTheSiteIsNotSwapped(): void
  {
    // What the editor's REST preview does: the plugin swaps nothing, get_stylesheet() is the active theme.
    $html = Block::render(['mode' => 'list']);

    $this->assertStringContainsString('<li class="beta-i selected">', $html);
    $this->assertStringContainsString('<li class="alpha"><a', $html, 'the allowed theme is listed');
  }

  public function testBlockJsonDeclaresWhatTheBriefAsks(): void
  {
    $json = $this->json();

    $this->assertSame(3, $json['apiVersion']);
    $this->assertSame(Block::NAME, $json['name']);
    $this->assertSame('Theme Switcher', $json['title']);
    $this->assertSame('widgets', $json['category']);
    $this->assertSame('wp-chooseyourtheme', $json['textdomain']);
    $this->assertSame(['theme', 'switcher', 'look', 'style'], $json['keywords']);
    $this->assertSame(['menu', 'list', 'roll'], $json['attributes']['mode']['enum']);
    $this->assertSame('menu', $json['attributes']['mode']['default']);
    $this->assertFileExists(Block::dir() . '/index.js');
    $this->assertFileExists(Block::dir() . '/style.css');
    $this->assertArrayNotHasKey('render', $json, 'rendered by the plugin, through the same renderer as the shortcode');
  }

  public function testTheAssetFileListsTheEditorsDependencies(): void
  {
    $asset = require Block::dir() . '/index.asset.php';

    foreach (['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'] as $handle) {
      $this->assertContains($handle, $asset['dependencies']);
    }
    $this->assertNotEmpty($asset['version']);
  }

  public function testRegisterGivesTheEditorScriptItsTranslationsFromThePluginsFolder(): void
  {
    Functions\expect('register_block_type')->once()->with(Block::dir(), ['render_callback' => [Block::class, 'render']]);
    Functions\expect('wp_set_script_translations')->once()->with(
      'wp-chooseyourtheme-switcher-editor-script',
      'wp-chooseyourtheme',
      \Mockery::on(fn ($path) => str_ends_with($path, '/languages'))
    );

    Block::register();
  }
}
