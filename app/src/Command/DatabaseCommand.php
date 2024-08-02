<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseCommand extends Command
{
    protected static $defaultName = 'database:sql';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('-------------------------------------------------------');
        passthru(sprintf('php app/bin/doctrine dbal:run-sql "%s"', $input->getArgument('sql')));

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->addArgument('sql', InputArgument::OPTIONAL, 'The code SQL');
        $this->setDescription('The code SQL.');
    }
}
