<?php

namespace App\Command;

use App\Repository\DocumentRepository;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:temp-backfill-delivery-city')]
class TempBackfillDeliveryCityCommand extends Command
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private CityRepository $cityRepository,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    private function canonicalize(string $str): string
    {
        $str = mb_strtoupper($str);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $str = $ascii !== false ? $ascii : $str;
        $str = preg_replace('/[^A-Z0-9]+/', ' ', $str);
        $str = preg_replace('/\bSTE\b/', 'SAINTE', $str);
        $str = preg_replace('/\bST\b/', 'SAINT', $str);
        return trim(preg_replace('/\s+/', ' ', $str));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $documents = $this->documentRepository->findBy(['deliveryCity' => null]);
        $output->writeln('Documents sans deliveryCity : ' . count($documents));

        $matched = 0;
        $unmatched = 0;
        $caenMatched = 0;

        $caen = $this->cityRepository->findOneBy(['postalcode' => '14000', 'name' => 'Caen']);

        $i = 0;
        foreach ($documents as $document) {
            $address = html_entity_decode($document->getDeliveryAddress() ?? '', ENT_QUOTES);
            $lines = explode('<br/>', $address);

            if (count($lines) < 2) {
                if ($caen && trim($address) === 'Vente emportée') {
                    $document->setDeliveryCity($caen);
                    $caenMatched++;
                    $matched++;
                } else {
                    $unmatched++;
                }
                $i++;
                if ($i % 200 === 0) {
                    $this->em->flush();
                }
                continue;
            }

            $cityLine = trim($lines[count($lines) - 2]);
            $countryIso = strtoupper(trim($lines[count($lines) - 1]));

            if (!preg_match('/^(\d{4,5})\s+(.+)$/u', $cityLine, $m)) {
                $unmatched++;
                continue;
            }

            $postalcode = $m[1];
            $cityName = trim($m[2]);

            $postalcodeCandidates = [$postalcode];
            if (strlen($postalcode) === 5 && $postalcode[0] === '0') {
                $postalcodeCandidates[] = ltrim($postalcode, '0');
            }

            $city = null;
            $wantedCanon = $this->canonicalize($cityName);

            foreach ($postalcodeCandidates as $pcCandidate) {
                $qb = $this->cityRepository->createQueryBuilder('c')
                    ->join('c.country', 'co')
                    ->where('c.postalcode = :pc')
                    ->setParameter('pc', $pcCandidate);

                if ($countryIso !== '') {
                    $qb->andWhere('co.isocode = :iso')->setParameter('iso', $countryIso);
                }

                $candidates = $qb->getQuery()->getResult();

                foreach ($candidates as $candidateCity) {
                    if ($this->canonicalize($candidateCity->getName()) === $wantedCanon) {
                        $city = $candidateCity;
                        break 2;
                    }
                }
            }

            if ($city) {
                $document->setDeliveryCity($city);
                $matched++;
            } else {
                $unmatched++;
            }

            $i++;
            if ($i % 200 === 0) {
                $this->em->flush();
            }
        }

        $this->em->flush();

        $output->writeln('Matches : ' . $matched . ' (dont ' . $caenMatched . ' ventes emportees -> Caen)');
        $output->writeln('Non trouves : ' . $unmatched);

        return Command::SUCCESS;
    }
}
