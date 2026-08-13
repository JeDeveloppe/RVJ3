<?php

namespace App\Command;

use App\Repository\BoiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(name: 'app:temp-test-boite-2769-image')]
class TempTestBoite2769ImageCommand extends Command
{
    public function __construct(
        private BoiteRepository $boiteRepository,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $boite = $this->boiteRepository->find(2769);
        if (!$boite) {
            $output->writeln('Boite 2769 not found');
            return Command::FAILURE;
        }

        $output->writeln('Boite found: ' . $boite->getName());

        // On copie une image existante du catalogue pour simuler un vrai upload
        $sourceDir = __DIR__ . '/../../public/uploads/images/boites';
        $sample = null;
        foreach (scandir($sourceDir) as $f) {
            if (str_ends_with($f, '.jpg') || str_ends_with($f, '.png')) {
                $sample = $sourceDir . '/' . $f;
                break;
            }
        }

        if (!$sample) {
            $output->writeln('No sample image found');
            return Command::FAILURE;
        }

        $tmpCopy = sys_get_temp_dir() . '/test_upload_2769.' . pathinfo($sample, PATHINFO_EXTENSION);
        copy($sample, $tmpCopy);

        $output->writeln('Using sample: ' . $sample);

        try {
            $uploadedFile = new UploadedFile($tmpCopy, basename($tmpCopy), null, null, true);
            $boite->setImageFile($uploadedFile);
            $this->em->persist($boite);
            $this->em->flush();
            $output->writeln('FLUSH OK - no exception. Image set to: ' . $boite->getImage());
        } catch (\Throwable $e) {
            $output->writeln('EXCEPTION: ' . get_class($e));
            $output->writeln('MESSAGE: ' . $e->getMessage());
            $output->writeln('FILE: ' . $e->getFile() . ':' . $e->getLine());
            $output->writeln('TRACE:');
            $output->writeln($e->getTraceAsString());
            if ($e->getPrevious()) {
                $output->writeln('PREVIOUS: ' . get_class($e->getPrevious()) . ' - ' . $e->getPrevious()->getMessage());
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
