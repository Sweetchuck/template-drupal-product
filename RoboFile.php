<?php

declare(strict_types = 1);

use Consolidation\AnnotatedCommand\AnnotationData;
use NuvoleWeb\Robo\Task\Config\Robo\loadTasks as ConfigLoader;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboState;
use Robo\Tasks;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Yaml\Yaml;

class RoboFile extends Tasks {

  use ConfigLoader;

  protected int $jsonEncodeFlags = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT;

  protected Filesystem $fs;

  public function __construct() {
    $this->fs = new Filesystem();
  }

  /**
   * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
   *
   * @hook interact instance:create
   */
  public function cmdInstanceCreateInteract(
    InputInterface $input,
    OutputInterface $output,
    AnnotationData $annotationData,
  ): void {
    $value = $input->getOption('project-vendor');
    if (!$value) {
      $input->setOption(
        'project-vendor',
        $this->config('command.instance.create.options.project-vendor'),
      );
    }

    $value = $input->getOption('project-name');
    if (!$value) {
      $input->setOption(
        'project-name',
        $this->config('command.instance.create.options.project-name'),
      );
    }

    $value = $input->getOption('dst-dir');
    if (!$value) {
      $input->setOption(
        'dst-dir',
        Path::join(
          '..',
          '..',
          $input->getOption('project-vendor'),
          $input->getOption('project-name'),
        ),
      );
    }
  }

  /**
   * @command instance:create
   */
  public function cmdInstanceCreateExecute(
    array $options = [
      'project-vendor' => '',
      'project-name' => '',
      'dst-dir' => '',
    ],
  ): CollectionBuilder {
    $options['composer'] = preg_replace(
      '@^\./@',
      '',
      getenv('COMPOSER') ?: '',
    );

    $options['host-uri-pattern'] = $this->config('command.instance.create.options.host-uri-pattern');

    $cb = $this->collectionBuilder();
    $this
      ->instanceCreateInit($cb, $options)
      ->instanceCreateDstDirPrepare($cb)
      ->instanceCreateCollectFilesToCopy($cb)
      ->instanceCreateCopyFiles($cb)
      ->instanceCreateModifyFileContents($cb)
      ->instanceCreateGitPrepare($cb)
      ->instanceCreateBuild($cb)
      ->instanceCreateOnboarding($cb);

    return $cb;
  }

  protected function instanceCreateInit(CollectionBuilder $cb, array $options): static {
    $cb->addCode(
      function (RoboState $state) use ($options): int {
        foreach ($options as $key => $value) {
          $state["options.$key"] = $value;
        }

        $state['host.uri'] = strtr(
          $state['options.host-uri-pattern'],
          [
            '{{ project-vendor }}' => $state['options.project-vendor'],
            '{{ project-name }}' => $state['options.project-name'],
          ],
        );

        return 0;
      },
    );

    return $this;
  }

  protected function instanceCreateDstDirPrepare(CollectionBuilder $cb): static {
    $cb->addTask(
      $this
        ->taskFilesystemStack()
        ->deferTaskConfiguration('mkdir', 'options.dst-dir')
    );

    $cb->addCode(
      function (RoboState $state): int {
        $state['dst-dir.content'] = (new Finder())
          ->in($state['options.dst-dir'])
          ->ignoreVCS(FALSE)
          ->ignoreDotFiles(FALSE)
          ->depth(0);

        return 0;
      },
    );

    $cb->addTask(
      $this
        ->taskFilesystemStack()
        ->deferTaskConfiguration('remove', 'dst-dir.content')
    );

    return $this;
  }

  protected function instanceCreateCollectFilesToCopy(CollectionBuilder $cb): static {
    $cb->addCode(function (RoboState $state): int {
      $state['src-dir.files'] = [];
      foreach ($this->getGitFiles('.') as $filePath) {
        $state['src-dir.files'][$filePath] = "{$state['options.dst-dir']}/$filePath";
      }

      unset(
        $state['src-dir.files']['RoboFile.php'],
        $state['src-dir.files']['robo.yml.dist'],
      );

      if ($state['options.composer'] && $state['options.composer'] !== 'composer.json') {
        unset(
          $state['src-dir.files']['composer.json'],
          $state['src-dir.files']['composer.lock'],
        );
        $state['src-dir.files'][$state['options.composer']] = "{$state['options.dst-dir']}/composer.json";
      }

      return 0;
    });

    return $this;
  }

  protected function instanceCreateCopyFiles(CollectionBuilder $cb): static {
    $taskForEach = $this->taskForEach();
    $taskForEach
      ->iterationMessage('Copy source file: {key}')
      ->deferTaskConfiguration('setIterable', 'src-dir.files')
      ->withBuilder(function (
        CollectionBuilder $builder,
        string $srcFile,
        $dstFile,
      ): void {
        $builder
          ->addTask(
            $this
              ->taskFilesystemStack()
              ->copy($srcFile, $dstFile)
          );
      });

    $cb->addTask($taskForEach);

    return $this;
  }

  protected function instanceCreateModifyFileContents(CollectionBuilder $cb): static {
    $cb->addCode(function (RoboState $state): int {
      $dstDir = $state['options.dst-dir'];

      $this->fs->remove([
        "$dstDir/README.md",
      ]);

      $this->fs->rename("$dstDir/README.app.md", "$dstDir/README.md");

      $filePath = Path::join($dstDir, 'composer.json');
      $content = file_get_contents($filePath) ?: '{}';
      $data = json_decode($content, TRUE);
      $data['name'] = sprintf(
        '%s/%s',
        $state['options.project-vendor'],
        $state['options.project-name'],
      );
      unset(
        $data['_comment'],
        $data['require-dev']['consolidation/robo'],
        $data['require-dev']['nuvoleweb/robo-config'],
      );
      $this->fs->dumpFile($filePath, json_encode($data, $this->jsonEncodeFlags));

      $filePath = "$dstDir/drush/drush.host.yml";
      $this->fs->copy(
        "$dstDir/drush/drush.local.example.yml",
        $filePath,
      );
      $data = Yaml::parseFile($filePath);
      $data['options']['uri'] = $state['host.uri'];
      $this->fs->dumpFile($filePath, Yaml::dump($data, 99, 2));

      $projectVendorUpperCamel = (new UnicodeString('a_' . $state['options.project-vendor']))
        ->camel()
        ->trimPrefix('a')
        ->toString();
      $projectVendorSnake = (new UnicodeString($projectVendorUpperCamel))
        ->snake()
        ->toString();

      $projectNameUpperCamel = (new UnicodeString('a_' . $state['options.project-name']))
        ->camel()
        ->trimPrefix('a')
        ->toString();
      $projectNameSnake = (new UnicodeString($projectNameUpperCamel))
        ->snake()
        ->toString();

      // @todo Get the current project name from composer.json#/name.
      $pairs = [
        '@\bSweetchuck(\\\\+)TemplateDrupalProduct(\\\\+)@' => "$projectVendorUpperCamel\${1}$projectNameUpperCamel\${2}",
        '@github\.com/Sweetchuck@' => "github.com/{$state['options.project-vendor']}",
        '@\btemplate-drupal-product\b@' => $state['options.project-name'],
        '@APP_PROJECT_VENDOR_SNAKE@' => $projectVendorSnake,
        '@APP_PROJECT_NAME_SNAKE@' => $projectNameSnake,
        //'@APP_PRIMARY_URI@' => $state['primaryUri'],
        '@/sweetchuck-template-drupal-product-\*\.@' => sprintf('%s-%s-*.', $state['options.project-vendor'], $state['options.project-name']),
      ];

      $files = (new Finder())
        ->in($state['options.dst-dir'])
        ->files()
        ->append(new ArrayIterator([
          new SplFileInfo("{$state['options.dst-dir']}/.gitignore", '', '.gitignore'),
        ]));
      /** @var \Symfony\Component\Finder\SplFileInfo $file */
      foreach ($files as $file) {
        $this->fs->dumpFile(
          $file->getPathname(),
          preg_replace(
            array_keys($pairs),
            array_values($pairs),
            file_get_contents($file->getPathname()) ?: '',
          ),
        );
      }

      return 0;
    });

    return $this;
  }

  protected function instanceCreateGitPrepare(CollectionBuilder $cb): static {
    $cb->addTask(
      $this
        ->taskGitStack()
        ->exec('init --initial-branch="1.x"')
        ->exec('add .')
        ->exec('commit --message="Initial commit"')
        ->deferTaskConfiguration('dir', 'options.dst-dir')
    );

    return $this;
  }

  protected function instanceCreateBuild(CollectionBuilder $cb): static {
    $cb->addTask(
      $this
        ->taskComposerUpdate()
        ->envVars([
          'COMPOSER' => 'composer.json',
        ])
        ->deferTaskConfiguration('dir', 'options.dst-dir')
    );

    return $this;
  }

  protected function instanceCreateOnboarding(CollectionBuilder $cb): static {
    $cb->addCode(function (RoboState $state): int {
      $state['onboarding.command'] = vsprintf(
        './vendor/bin/drush --config=%s --uri=%s @app.local marvin:onboarding',
        [
          escapeshellarg('drush'),
          escapeshellarg($state['host.uri']),
        ],
      );

      return 0;
    });

    $cb->addTask(
      $this
        ->taskExecStack()
        ->envVars([
          'COMPOSER' => 'composer.json',
        ])
        ->deferTaskConfiguration('dir', 'options.dst-dir')
        ->deferTaskConfiguration('exec', 'onboarding.command')
    );

    return $this;
  }

  protected function getGitFiles(string $dir): array {
    // @todo Error handler.
    $process = new Process(['git', 'ls-files']);
    $process
      ->setWorkingDirectory($dir)
      ->run();

    return preg_split(
      '/[\n\r]+/',
      trim($process->getOutput()),
    );
  }

}
