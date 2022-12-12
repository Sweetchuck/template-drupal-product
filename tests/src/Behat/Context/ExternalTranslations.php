<?php

declare(strict_types = 1);

namespace Sweetchuck\TemplateDrupalProduct\Tests\Behat\Context;

use Behat\Behat\Context\TranslatableContext;

class ExternalTranslations implements TranslatableContext {

  /**
   * {@inheritdoc}
   */
  public static function getTranslationResources() {
    $testsDir = dirname(__DIR__, 3);
    $i18nDir = "$testsDir/behat/i18n";

    return glob("$i18nDir/*.{xliff,php,yml}", GLOB_BRACE) ?: [];
  }

}
