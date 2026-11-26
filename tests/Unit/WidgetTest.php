<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use Brain\Monkey\Functions;
use WPChooseYourTheme\Tests\Support\FakeTheme;
use WPChooseYourTheme\Tests\Support\MonkeyTestCase;
use WPChooseYourTheme\Tests\Support\ThemeWorld;
use WPChooseYourTheme\Widgets\ThemeSwitcherWidget;

require_once __DIR__ . '/../Support/WpWidgetStub.php';

final class WidgetTest extends MonkeyTestCase
{
  use ThemeWorld;

  protected function setUp(): void
  {
    parent::setUp();

    Functions\when('sanitize_text_field')->alias(fn ($value) => trim(strip_tags((string) $value)));
    Functions\when('apply_filters')->alias(fn ($tag, $value) => $value);
    Functions\when('wp_enqueue_script')->justReturn(true);
    Functions\when('plugins_url')->justReturn('https://example.test/p.js');
    Functions\when('selected')->alias(fn ($a, $b, $echo = true) => '');
  }

  private function args(): array
  {
    return ['before_widget' => '<div class="w">', 'after_widget' => '</div>', 'before_title' => '<h2>', 'after_title' => '</h2>'];
  }

  public function testUpdateStoresATitleAndAKnownMode(): void
  {
    $widget = new ThemeSwitcherWidget();

    $this->assertSame(['title' => 'Pick', 'mode' => 'list'], $widget->update(['title' => '<b>Pick</b>', 'mode' => 'list'], []));
    $this->assertSame(['title' => '', 'mode' => 'menu'], $widget->update(['mode' => 'evil"><script>'], []));
    $this->assertSame('menu', $widget->update(['mode' => ['x']], [])['mode']);
  }

  public function testItShowsTitleAndSwitcherWhenThereIsSomethingToChooseFrom(): void
  {
    $this->site('a', [new FakeTheme('a'), new FakeTheme('b')], ['allowed_themes' => ['b']]);

    ob_start();
    (new ThemeSwitcherWidget())->widget($this->args(), ['title' => 'Pick <one>', 'mode' => 'list']);
    $html = (string) ob_get_clean();

    $this->assertStringStartsWith('<div class="w"><h2>Pick &lt;one&gt;</h2><ul class="wpcyt_list">', $html);
    $this->assertStringEndsWith('</ul></div>', $html);
  }

  public function testItShowsNothingAtAllWhenThereIsOneTheme(): void
  {
    $this->site('a', [new FakeTheme('a'), new FakeTheme('b')], ['allowed_themes' => []]);

    ob_start();
    (new ThemeSwitcherWidget())->widget($this->args(), ['title' => 'Pick', 'mode' => 'menu']);

    $this->assertSame('', ob_get_clean(), 'not even the title');
  }
}
