<?php

declare(strict_types = 1);

namespace Sweetchuck\TemplateDrupalProduct\Tests\Unit;

use Drupal\app_core\DeleteMe;
use PHPUnit\Framework\TestCase;

class DeleteMeTest extends TestCase
{

  public function testEcho(): void {
    $subject = new DeleteMe();
    $this->assertSame('a', $subject->echo('a'));
  }

}
