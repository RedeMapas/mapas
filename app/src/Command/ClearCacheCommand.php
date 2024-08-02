<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ClearCacheCommand extends Command
{
    protected static $defaultName = 'cache:clear';

    private const DIRECTORIES = [
        'var/DoctrineProxies',
        'var/sessions',
    ];

    public function __construct(
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            PHP_EOL,
            '=================================',
            '=== CLEARING CACHE ',
            '=================================',
            PHP_EOL,
        ]);

        foreach (self::DIRECTORIES as $directory) {
            $this->clearDirectory($directory);
        }

        return Command::SUCCESS;
    }

    private function clearDirectory(string $directory): void
    {
        if (false === $this->filesystem->exists($directory)) {
            return;
        }

        $finder = new Finder();
        $finder->files()->in($directory);

        foreach ($finder as $file) {
            $this->filesystem->remove($file->getRealPath());
        }
    }
}
