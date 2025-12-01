<?php

declare(strict_types=1);

namespace Drush\Commands\app;

use Drush\Attributes\Bootstrap as CliBootstrap;
use Drush\Attributes\Formatter as CliFormatter;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\AutowireTrait;
use Drush\Formatters\DrushFormatterManager;
use Drush\Formatters\FormatterTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
  name: self::NAME,
  description: 'Dummy command for testing purposes.',
)]
#[CliFormatter(
  returnType: 'array',
  defaultFormatter: 'yaml',
)]
#[CliBootstrap(level: DrupalBootLevels::NONE)]
final class DummyCommand extends Command {

  use AutowireTrait;
  use FormatterTrait;

  public const string NAME = 'app:dummy';

  public function __construct(
    #[Autowire('formatterManager')]
    protected DrushFormatterManager $formatterManager,
  ) {
    parent::__construct();
  }

  public function execute(InputInterface $input, OutputInterface $output): int {
    $data = [
      'status' => 'OK',
      'payload' => 'dummy',
    ];

    $this->writeFormattedOutput($input, $output, $data);

    return Command::SUCCESS;
  }

}
