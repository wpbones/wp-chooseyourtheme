<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPChooseYourTheme\Support\Url;

final class UrlTest extends TestCase
{
  public function testRelativeNeverLeavesTheSite(): void
  {
    $this->assertSame('/page/', Url::relative('/page/'));
    $this->assertSame('/evil.com', Url::relative('//evil.com'));
    $this->assertSame('/evil.com', Url::relative('/\\evil.com'));
    $this->assertSame('/evil.com', Url::relative('\\\\evil.com'));
    $this->assertDoesNotMatchRegularExpression('/[\r\n]/', Url::relative("/a\r\nSet-Cookie: x=1"), 'a header cannot be injected');
  }

  public function testWithoutDropsTheArgumentAndKeepsTheRest(): void
  {
    $this->assertSame('/blog/?s=cat&paged=2', Url::without('/blog/?s=cat&wpcyt_theme=a&paged=2', 'wpcyt_theme'));
    $this->assertSame('/blog/', Url::without('/blog/?wpcyt_theme=a', 'wpcyt_theme'));
    $this->assertSame('/blog/', Url::without('/blog/', 'wpcyt_theme'));
  }

  public function testWithoutDropsEveryFormPhpReadsAsTheArgument(): void
  {
    // PHP fills $_GET['wpcyt_theme'] from all of these; a redirect that kept one would loop.
    foreach (['wpcyt_theme[]=a', 'wpcyt_theme[x]=a', 'wpcyt.theme=a', 'wpcyt%5Ftheme=a', 'wpcyt+theme=a', 'wpcyt_theme=a&wpcyt_theme=b'] as $query) {
      $this->assertSame('/p', Url::without('/p?' . $query, 'wpcyt_theme'), $query);
    }
  }

  public function testWithoutKeepsALookalike(): void
  {
    $this->assertSame('/p?wpcyt_theme_other=1', Url::without('/p?wpcyt_theme_other=1', 'wpcyt_theme'));
  }

  public function testWithSetsTheArgumentOnce(): void
  {
    $this->assertSame('/p?x=1&wpcyt_theme=twentytwentyfive', Url::with('/p?x=1&wpcyt_theme=old', 'wpcyt_theme', 'twentytwentyfive'));
    $this->assertSame('/p?wpcyt_theme=a%2Fb', Url::with('/p', 'wpcyt_theme', 'a/b'));
  }

  public function testFieldsCarryThePagesOwnQueryIntoAGetForm(): void
  {
    $this->assertSame(
      [['s', 'a b'], ['cat[0]', '3'], ['cat[1]', '4']],
      Url::fields('/?s=a+b&wpcyt_theme=x&cat%5B%5D=3&cat%5B%5D=4', 'wpcyt_theme')
    );
    $this->assertSame([], Url::fields('/', 'wpcyt_theme'));
  }
}
