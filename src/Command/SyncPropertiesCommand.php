<?php
namespace App\Command;

use App\Service\PropertyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'sync:properties',
    description: 'Synchronize properties from external APIs'
)]
class SyncPropertiesCommand extends Command
{
    public function __construct(
        private readonly PropertyService $propertyService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting property synchronization...');

        try {
            $this->propertyService->fetchProperties();
            $io->success('Properties synchronized successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error during synchronization: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}