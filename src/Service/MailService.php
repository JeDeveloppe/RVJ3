<?php

namespace App\Service;

use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use App\Repository\LegalInformationRepository;
use App\Repository\DocumentParametreRepository;
use App\Repository\DocumentRepository;
use App\Repository\SiteSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

//email changement statut du document aussi
class MailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LegalInformationRepository $legalInformationRepository,
        private DocumentParametreRepository $documentParametreRepository,
        private SiteSettingRepository $siteSettingRepository,
        private EntityManagerInterface $em,
        private DocumentRepository $documentRepository
        ){
    }

    // public function sendMail(bool $allwaysSend, string  $recipient,string $subject,string $template, array $donnees, $replyTo, string $dnsCommande)
    // {

    //     $siteSettings = $this->siteSettingRepository->findOneBy([]);

    //     if(is_null($donnees)){
    //         $donnees = [];
    //     }

    //     $legales = $this->legalInformationRepository->findOneBy([]);

    //     //? parametre du site envoi des emails bloque si besoin de mettre a jour des statut ou autre
    //     if($allwaysSend == true || $siteSettings->getBlockEmailSending() == false){
            
    //         $mail = (new TemplatedEmail())
    //         ->from(new Address($legales->getEmailCompany(), $legales->getCompanyName()))
    //         ->to($recipient)
    //         ->replyTo($replyTo ? $replyTo : 'noreply@refaitesvosjeux.fr')
    //         ->subject($subject)
    //         ->htmlTemplate('email/templates/'.$template.'.html.twig')
    //         ->context($donnees);

    //         try{
    //             //?utilisation de la boite email spéciale COMMANDES
    //             if($dnsCommande == true){

    //                 $mail->getHeaders()->addTextHeader('X-Transport', 'commande');
    //             }
    //             $this->mailer->send($mail);
    //         } catch (TransportExceptionInterface $e) {
    //             dump($e->getDebug());
    //         }

    //     }
    // }

    public function sendMail(bool $allwaysSend, string  $recipient,string $subject,string $template, array $donnees, $replyTo, string $dnsCommande)
    {

        $siteSettings = $this->siteSettingRepository->findOneBy([]);

        if(is_null($donnees)){
            $donnees = [];
        }

        $legales = $this->legalInformationRepository->findOneBy([]);

        //? parametre du site envoi des emails bloque si besoin de mettre a jour des statut ou autre
        if($allwaysSend == true || $siteSettings->getBlockEmailSending() == false){
            
            $mail = (new TemplatedEmail())
            ->from(new Address($legales->getEmailCompany(), $legales->getCompanyName()))
            ->to($recipient)
            ->replyTo($replyTo ? $replyTo : 'noreply@refaitesvosjeux.fr')
            ->subject($subject)
            ->htmlTemplate('email/templates/'.$template.'.html.twig')
            ->context($donnees);

            try{
                //?utilisation de la boite email spéciale COMMANDES
                if($dnsCommande == true){

                    $mail->getHeaders()->addTextHeader('X-Transport', 'commande');
                }
                $this->mailer->send($mail);
            } catch (TransportExceptionInterface $e) {
                dump('Erreur de transport ! Détails :', $e->getDebug()); // Ajout de cette ligne
            }

        }
    }

}