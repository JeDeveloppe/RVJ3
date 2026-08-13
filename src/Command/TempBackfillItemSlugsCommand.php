<?php

namespace App\Command;

use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(name: 'app:temp-backfill-item-slugs')]
class TempBackfillItemSlugsCommand extends Command
{
    public function __construct(
        private ItemRepository $itemRepository,
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $items = $this->itemRepository->findBy(['slug' => null]);
        $output->writeln('Items sans slug : ' . count($items));

        $count = 0;
        foreach ($items as $item) {
            if (!$item->getName()) {
                continue;
            }
            $item->setSlug((string) $this->slugger->slug($item->getName())->lower());
            $count++;
        }

        $this->em->flush();
        $output->writeln('Slugs generes : ' . $count);

        return Command::SUCCESS;
    }
}
