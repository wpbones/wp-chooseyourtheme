<?php

declare(strict_types=1);

namespace WPChooseYourTheme\Tests\Support;

/**
 * Just enough of WP_Theme for the code under test.
 */
final class FakeTheme
{
  public function __construct(
    public string $slug,
    public string $name = '',
    public bool $block = false,
    public string $template = '',
    public bool $exists = true,
    public bool $broken = false,
    public bool $allowed = true,
  ) {
    $this->name = $name !== '' ? $name : ucfirst($slug);
    $this->template = $template !== '' ? $template : $slug;
  }

  public function exists(): bool
  {
    return $this->exists;
  }

  /** @return false|object */
  public function errors()
  {
    return $this->broken ? new \stdClass() : false;
  }

  public function is_allowed(): bool
  {
    return $this->allowed;
  }

  public function is_block_theme(): bool
  {
    return $this->block;
  }

  public function get_template(): string
  {
    return $this->template;
  }

  public function get(string $header): string
  {
    return $header === 'Name' ? $this->name : '';
  }
}
