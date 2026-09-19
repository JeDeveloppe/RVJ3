<?php

namespace App\Command;

use App\Repository\PaymentRepository;
use App\Service\PaiementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

//?Retrouve chez HelloAsso le numero de paiement des commandes deja reglees (Payment::helloAssoPaymentId),
//?en partant de l'identifiant de checkout enregistre (courant ET anciens). Sans --force : simulation, rien n'est ecrit.
//?Signale aussi les ecarts entre le montant encaisse chez HelloAsso et le total enregistre sur le site.
#[AsCommand(
    name: 'app:helloasso:backfill-payment-numbers',
    description: 'Recupere le numero de paiement HelloAsso des commandes deja payees (simulation par defaut, --force pour enregistrer)'
)]
class HelloAssoBackfillPaymentNumbersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private PaymentRepository $paymentRepository,
        private PaiementService $paiementService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Enregistre les numeros trouves (sans cette option : simulation)')
            //?Le compte HelloAsso remonte a fevrier 2024 (adhesions, dons, 1 commande). Les paiements d'avant HelloAsso
            //?(Payplug 'pay_...') sont ignores sans appel a l'API, donc une date large ne coute rien.
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Ne traite que les paiements depuis cette date (AAAA-MM-JJ)', '2024-01-01');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        try {
            $since = new \DateTimeImmutable((string) $input->getOption('since'));
        } catch (\Exception) {
            $io->error('Date invalide pour --since (format AAAA-MM-JJ).');
            return Command::INVALID;
        }

        if($_ENV['PAIEMENT_MODULE'] != 'HELLOASSO'){
            $io->error('PAIEMENT_MODULE n\'est pas HELLOASSO : rien à faire.');
            return Command::FAILURE;
        }

        $io->title($force ? 'Récupération des numéros de paiement HelloAsso (ENREGISTREMENT)' : 'Récupération des numéros de paiement HelloAsso (simulation, rien n\'est écrit)');

        $payments = $this->paymentRepository->findPaidWithoutHelloAssoPaymentNumber($since);
        $io->text(count($payments).' paiement(s) réglé(s) depuis le '.$since->format('d/m/Y').' sans numéro HelloAsso (CB uniquement).');

        if(count($payments) === 0){
            return Command::SUCCESS;
        }

        $bearer = $this->paiementService->helloAssoAuth();

        $counts = ['fromDetails' => 0, 'found' => 0, 'unpaid' => 0, 'byReference' => 0, 'manual' => 0, 'error' => 0, 'conflict' => 0];
        $errorReasons = [];
        $amountGaps = [];
        $anomalies = [];
        $toWrite = 0;

        $io->progressStart(count($payments));

        foreach($payments as $payment){
            $io->progressAdvance();
            $document = $payment->getDocument();
            $label = $document->getBillNumber() ?? $document->getQuoteNumber();

            //rapprochement deja fait par le bouton admin : le numero est dans le detail, pas besoin de l'API
            if(preg_match('/HelloAsso n°(\d+)/', (string) $payment->getDetails(), $matches)){
                $paymentId = $matches[1];
                $counts['fromDetails']++;
            }else{
                $result = $this->paiementService->lookupHelloAssoPayment($payment, $bearer);

                //le checkout enregistre ne montre aucun paiement (identifiant ecrase par une nouvelle tentative ?) :
                //on cherche autour de la date de transaction un paiement dont le checkout d'origine porte le numero de devis
                if($result['status'] === 'unpaid' && $payment->getTimeOfTransaction() !== null){
                    $byReference = $this->paiementService->lookupHelloAssoPaymentByReference($document, $bearer, $payment->getTimeOfTransaction());
                    if($byReference !== null){
                        $result = ['status' => 'found', 'paymentId' => $byReference['paymentId'], 'amount' => $byReference['amount']];
                        $counts['byReference']++;
                        $anomalies[] = [$label, 'retrouvé par la référence du devis (le checkout enregistré n\'était pas celui qui a été réglé)'];
                    }
                }

                if($result['status'] !== 'found'){
                    $counts[$result['status']]++;

                    if($result['status'] === 'error'){
                        //on regroupe les raisons (la meme erreur se repete des centaines de fois en cas de limite de debit)
                        $reason = mb_substr(preg_replace('/\s+/', ' ', (string) ($result['message'] ?? 'inconnue')), 0, 110);
                        $errorReasons[$reason] = ($errorReasons[$reason] ?? 0) + 1;
                        $anomalies[] = [$label, 'erreur API HelloAsso'];
                    }elseif($result['status'] === 'unpaid'){
                        $anomalies[] = [$label, 'aucun paiement HelloAsso trouvé (paiement pris en direct ? aucun numéro enregistré)'];
                    }
                    //'manual' : identifiant non numerique (Payplug 'pay_...', 'AUCUN' saisi a la main) : pas un checkout HelloAsso, rien a signaler

                    usleep(50000);
                    continue;
                }

                $paymentId = $result['paymentId'];
                $counts['found']++;

                if($result['amount'] !== (int) $document->getTotalWithTax()){
                    $amountGaps[] = [$label, number_format($document->getTotalWithTax() / 100, 2, ',', ' ').' €', number_format($result['amount'] / 100, 2, ',', ' ').' €', $paymentId];
                }
                usleep(50000);
            }

            //un paiement HelloAsso ne peut regler qu'une commande
            $other = $this->paymentRepository->findOneBy(['helloAssoPaymentId' => $paymentId]);
            if($other !== null && $other->getId() !== $payment->getId()){
                $counts['conflict']++;
                $anomalies[] = [$label, 'le paiement HelloAsso n°'.$paymentId.' est déjà rattaché à une autre commande'];
                continue;
            }

            $toWrite++;
            if($force){
                $payment->setHelloAssoPaymentId($paymentId);
                if($toWrite % 25 === 0){
                    $this->em->flush();
                }
            }
        }

        if($force){
            $this->em->flush();
        }

        $io->progressFinish();

        $io->section('Résultat');
        $io->definitionList(
            ['Numéro retrouvé chez HelloAsso' => $counts['found']],
            ['Numéro déjà présent dans le détail (rapprochement)' => $counts['fromDetails']],
            ['Retrouvé par la référence du devis (checkout différent)' => $counts['byReference']],
            ['Aucun paiement autorisé, même par la référence' => $counts['unpaid']],
            ['Hors HelloAsso (ancien prestataire Payplug ou saisie manuelle), ignoré' => $counts['manual']],
            ['Erreur API' => $counts['error']],
            ['Conflit (déjà rattaché ailleurs)' => $counts['conflict']],
            ['Numéros ' . ($force ? 'enregistrés' : 'à enregistrer (simulation)') => $toWrite],
        );

        if(count($errorReasons) > 0){
            $io->section('Raisons des erreurs API');
            arsort($errorReasons);
            $io->table(['Message', 'Nombre'], array_map(fn($message, $count) => [$message, $count], array_keys($errorReasons), $errorReasons));
        }

        if(count($amountGaps) > 0){
            $io->section('Écarts entre le total du site et le montant encaissé chez HelloAsso (rien n\'est modifié)');
            $io->table(['Document', 'Total du site', 'Encaissé HelloAsso', 'N° paiement'], $amountGaps);
        }

        if(count($anomalies) > 0){
            $io->section('À examiner');
            $io->table(['Document', 'Raison'], array_slice($anomalies, 0, 40));
            if(count($anomalies) > 40){
                $io->text('… et '.(count($anomalies) - 40).' autre(s).');
            }
        }

        if(!$force && $toWrite > 0){
            $io->note('Simulation : relancer avec --force pour enregistrer ces '.$toWrite.' numéro(s).');
        }

        return Command::SUCCESS;
    }
}
