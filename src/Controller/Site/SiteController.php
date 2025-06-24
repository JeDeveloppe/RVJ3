<?php

namespace App\Controller\Site;

use Dompdf\Dompdf;
use DateTimeImmutable;
use App\Form\ContactType;
use App\Form\AcceptCartType;
use App\Service\MailService;
use App\Service\UserService;
use App\Entity\ResetPassword;
use App\Service\PanierService;
use App\Form\ResetPasswordType;
use App\Service\PartnerService;
use App\Service\DocumentService;
use App\Service\PasswordService;
use App\Service\UtilitiesService;
use App\Repository\ItemRepository;
use App\Repository\UserRepository;
use App\Service\AmbassadorService;
use App\Repository\MediaRepository;
use Symfony\Bundle\MakerBundle\Str;
use App\Service\QuoteRequestService;
use App\Repository\PartnerRepository;
use Symfony\Component\Form\FormError;
use App\Repository\DocumentRepository;
use App\Service\SiteControllerService;
use App\Service\MentionsLegalesService;
use App\Repository\AmbassadorRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DocumentLineRepository;
use App\Repository\QuoteRequestRepository;
use App\Form\EmailForSendResetPasswordType;
use App\Repository\ResetPasswordRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\LegalInformationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SiteController extends AbstractController
{
    public function __construct(
        private LegalInformationRepository $legalInformationRepository,
        private PanierService $panierService,
        private MailService $mailService,
        private DocumentService $documentService,
        private UtilitiesService $utilitiesService,
        private UserRepository $userRepository,
        private PasswordService $passwordService,
        private DocumentRepository $documentRepository,
        private UserService $userService,
        private ResetPasswordRepository $resetPasswordRepository,
        private PartnerService $partnerService,
        private PartnerRepository $partnerRepository,
        private AmbassadorService $ambassadorService,
        private AmbassadorRepository $ambassadorRepository,
        private UrlGeneratorInterface $urlGeneratorInterface,
        private SiteControllerService $siteControllerService,
        private MentionsLegalesService $mentionsLegalesService,
        private ItemRepository $itemRepository,
        private QuoteRequestRepository $quoteRequestRepository,
        private QuoteRequestService $quoteRequestService
    )
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $metas['description'] = "Association dédiée au réemploi des jeux et au lien social. Découvrez nos prestations, nos jeux d'occasions et nos pièces détachées pour vos jeux incomplet.";
        $totalPiecesDisponiblentSurLeSite = count($this->itemRepository->findAllItemsWithStockForSaleNotNull());


        return $this->render('site/pages/home.html.twig', [
            'metas' => $metas,
            'totalPiecesDisponiblentSurLeSite' => $totalPiecesDisponiblentSurLeSite,

        ]);
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        $legales = $this->legalInformationRepository->findOneBy(['isOnline' => true], ['id' => 'ASC']);
        $paragraphs = $this->mentionsLegalesService->mentionsParagraphs($legales);
        $metas['description'] = 'Mentions légales du site.';

        return $this->render('site/pages/legale/mentions_legales.html.twig', [
            'legales' => $legales,
            'metas' => $metas,
            'paragraphs' => $paragraphs
        ]);
    }

    #[Route('/conditions-generales-de-vente', name: 'app_conditions_generale_de_vente')]
    public function cgv(): Response
    {
        $legales = $this->legalInformationRepository->findOneBy(['isOnline' => true], ['id' => 'ASC']);
        $metas['description'] = 'Nos conditions générales de ventes concernant le site.';

        return $this->render('site/pages/legale/cgv.html.twig', [
            'legales' => $legales,
            'metas' => $metas
        ]);
    }

    #[Route('/conditions-generale-d-utilisation', name: 'app_conditions_generale_utilisation')]
    public function cgu(): Response
    {
        $legales = $this->legalInformationRepository->findOneBy(['isOnline' => true], ['id' => 'ASC']);
        $metas['description'] = 'Nos conditions générales d\'utilisation du site.';

        return $this->render('site/pages/legale/cgu.html.twig', [
            'legales' => $legales,
            'metas' => $metas
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);
        $metas['description'] = 'Si vous avez la moindre question sur le site, une demande de partenariat ou autre, n\'hésitez pas !';
        $legales = $this->legalInformationRepository->findOneBy([]);

        if($form->isSubmitted() && $form->isValid()) {
    
            $legales = $this->legalInformationRepository->findOneBy([]);
            $quoteRequestNumber = '';
            if($form->get('uuid')->getData() != null){
                $quoteRequest = $this->quoteRequestRepository->findOneBy(['uuid' => $form->get('uuid')->getData()]);
                if($quoteRequest){
                    $quoteRequestNumber = ' ('.$quoteRequest->getNumber().')';
                }
            }

            $this->mailService->sendMail(
                true,
                $legales->getEmailCompany(),
                "Message du site en date du ".(new DateTimeImmutable('now'))->format('d-m-Y').": ".$form->get('sujet')->getData().$quoteRequestNumber,
                'contact',
                [
                    'mail' => $form->get('email')->getData(),
                    'question' => $form->get('message')->getData(),
                    'legales' => $legales
                ],
                $form->get('email')->getData(),
                false
            );
    
            $this->addFlash('success', 'Message bien envoyé!');
            return $this->redirectToRoute('app_contact');
        }
    
        return $this->render('site/pages/contact/contact.html.twig', [
            'form' => $form->createView(),
            'metas' => $metas,
            'legales' => $legales
        ]);
    }

    #[Route('/coin-presse', name: 'app_press')]
    public function press(MediaRepository $mediaRepository, DocumentLineRepository $documentLineRepository): Response
    {
        $metas['description'] = "Plongez dans l'historique de notre association avec notre présence sur le web. Revivez les temps forts et les actualités qui ont marqué notre histoire !";
        $medias = $mediaRepository->findBy(['isOnLine' => true],['publishedAt' => 'DESC']);
        $items = $documentLineRepository->countTotalOfItemsBilled();
        return $this->render('site/pages/presse/presse.html.twig', [
            'metas' => $metas,
            'medias' => $medias,
            'itemBilleds' => $items
        ]);

    }

    #[Route('/organiser-une-collecte', name: 'app_organize_a_collection')]
    public function organizeCollection(Request $request): Response
    {
        $metas['description'] = "Particuliers, structures, écoles, entreprises… où que vous soyez en France, aidez-nous en collectant des jeux et faites-les nous parvenir gratuitement. ";
        $legales = $this->legalInformationRepository->findOneBy([]);

        $steps[] = [
            'title' => 'Complétez le formulaire',
            'description' => 'Vous pouvez le scanner ou le prendre en photo avant de nous l\'envoyer par mail : '.$legales->getEmailCompany()
        ];
        $steps[] = [
            'title' => 'Collectez les jeux',
            'description' => 'Nous vous enverrons des documents pour que vous puissiez présenter la démarche autour de vous.'
        ];
        $steps[] = [
            'title' => 'Pesez votre colis',
            'description' => 'Pesez votre colis et envoyez-nous un mail avec le poids. Nous vous envoyons alors le bon de livraison à imprimer et à coller sur le carton.'
        ];
        $steps[] = [
            'title' => 'Envoyez gratuitement les jeux',
            'description' => 'Et voilà, le tour est joué ! L’envoi des jeux se fait via Mondial Relay. Les frais de port sont pris en charge par l’association.'
        ];

        return $this->render('site/pages/collecte/organiser_une_collecte.twig', [
            'metas' => $metas,
            'siteControllerServiceContent' => $this->siteControllerService->pageOganizeCollection(),
            'steps' => $steps
        ]);

    }

    #[Route('/devenir-ambassadeur-rice/quide', name: 'app_download_ambassador_quide')]
    public function downloadQuide()
    {
        // load the file from the filesystem
        $file = new File('../public/download/quide_ambassadeur_ambassadrice_rvj.pdf');
        if(!$file){

            $this->addFlash('warning','Année du document non connue !!!');

            return $this->redirectToRoute('app_site_home');

        }else{

            return $this->file($file);

        }
    }

    #[Route('/nos-prestations', name: 'app_prestations')]
    public function prestations(Request $request): Response
    {

        $metas['description'] = "Profitez de nos prestations sur mesure : animations, ateliers et inventaires. Complétez vos boites de jeux grâce à notre équipe dédiée et expérimentée !";
        
        return $this->render('site/pages/prestations/nos_prestations.html.twig', [
            'metas' => $metas,            
            'legales' => $this->legalInformationRepository->findOneBy([]),
            'siteControllerServiceContent' => $this->siteControllerService->pagePrestations()
        ]);

    }

    #[Route('/donner-ses-jeux', name: 'app_give_your_games')]
    public function giveYourGames(Request $request): Response
    {
        $baseUrl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $ambassadors = $this->ambassadorRepository->findAmbassadorsForCarte();

        $donnees = $this->ambassadorService->constructionMapOfFranceWithAmbassadors($baseUrl, $ambassadors);

        $metas['description'] = "Transformez vos vieux jeux en sourires en faisant un don à notre association. Aidez-nous à partager la passion du jeu avec ceux qui en ont besoin.";
        
        return $this->render('site/pages/donner_jeux/donner_ses_jeux.html.twig', [
            'metas' => $metas,
            'legales' => $this->legalInformationRepository->findOneBy([]),
            'donnees' => $donnees,
            'ambassadors' => $ambassadors,
            'siteControllerServiceContent' => $this->siteControllerService->pageDonnerSesJeux()
        ]);

    }

    #[Route('/soutenir-association', name: 'app_support_us')]
    public function supportUs(Request $request): Response
    {

        $metas['description'] = "Participez à notre mission en soutenant l'association. Chaque geste compte ! Explorez nos initiatives et trouvez une façon de vous impliquer facilement.";

        $missions[] = [
            'img' => 'Groupe de masques 17.png',
            'img_alt' => 'boites de jeux',
            'title' => 'VENTE DE JEUX',
            'text' => 'Les jeux collectés sont, pour la plupart, complétés grâce à notre stock de jeux incomplets. Ils sont alors remis en vente à prix solidaires (maximum 50 % du prix d’un jeu neuf) ou donnés à des associations.',
            'btn_link' => $this->urlGeneratorInterface->generate('app_catalogue_occasions'),
            'btn_text' => 'ACHETER UN JEU'
        ];
        $missions[] = [
            'img' => 'colorful-game-pieces-with-dice-on-board-2023-11-27-05-32-20-utc.png',
            'img_alt' => 'Pièces détachées',
            'title' => 'VENTE DE PIÈCES DÉTACHÉES',
            'text' => 'Certaines pièces détachées sont proposées à la vente à l’unité et permettent aux particuliers et aux professionnels de compléter leurs jeux. L’inventaire est encore en cours, le catalogue se remplira bientôt !',
            'btn_link' => $this->urlGeneratorInterface->generate('app_catalogue_pieces_detachees'),
            'btn_text' => 'VISITER LA BOUTIQUE'
        ];
        $missions[] = [
            'img' => '450900705_1106120011238310_5624333892940681501_n.png',
            'img_alt' => 'Créations originales',
            'title' => 'PRESTATIONS',
            'text' => 'L\'association vous propose différentes prestations autour du jeu et du réemploi : animations, atelier de sensibilisation, inventaires...',
            'btn_link' => $this->urlGeneratorInterface->generate('app_prestations'),
            'btn_text' => 'VOIR NOS PRESTATIONS'
        ];

        $donnees[] = [
            'title' => 'DEVENIR BÉNÉVOLE',
            'description' => 'Ce projet repose en grande partie sur l\'implication des bénévoles : collecte et tri des jeux, communication, tenue des stands... ',
            'img' => 'IMG20240517105745.png',
            'link' => $this->urlGeneratorInterface->generate('app_contact'),
            'button_text' => 'NOUS CONTACTER'
        ];
        $donnees[] = [
                'title' => 'DONNER SES JEUX',
                'description' => "Le service récupère les jeux complets et incomplets ainsi que les pièces détachées (pions, dés, sabliers…).",
                'img' => 'IMG20240513152149b.png',
                'link' => $this->urlGeneratorInterface->generate('app_give_your_games'),
                'button_text' => 'DONNER SES JEUX'
        ];
        $donnees[] = [
            'title' => 'DEVENIR AMBASSADEUR·ICE',
            'description' => "Vous souhaitez contribuer activement au projet porté par l’association ? Particuliers ou structures… où que vous soyez en France, collectez des jeux près de chez vous et faites-les nous parvenir !",
            'img' => 'Collecte Ad Normandie 2023.png',
            'link' => $this->urlGeneratorInterface->generate('app_organize_a_collection', ['_fragment' => 'devenir-ambassadeur']),
            'button_text' => 'DEVENIR AMBASSADEUR·ICE'
        ];
        $donnees[] = [
            'title' => 'NOUS SOUTENIR FINANCIÈREMENT',
            'description' => "Vous avez la possibilité de soutenir financièrement l'association via la plateforme Helloasso. Nous vous remercions de votre soutien !",
            'img' => 'cropped-view-of-female-hand-putting-red-heart-in-b-2023-11-27-05-08-19-utc.png',
            'link' => 'https://www.helloasso.com/associations/refaites-vos-jeux/formulaires/1',
            'button_text' => 'FAIRE UN DON'
        ];


        return $this->render('site/pages/association/nous_soutenir.html.twig', [
            'metas' => $metas,
            'donnees' => $donnees,
            'missions' => $missions,
            'siteControllerServiceContent' => $this->siteControllerService->pageNousSoutenir()
        ]);

    }

    #[Route('/document/{tokenDocument}', name: 'document_view')]
    public function lectureDevis(
        $tokenDocument,
        Request $request
        ): Response
    {

        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);

        if(!$document){

            return $this->documentService->renderIfDocumentNoExist();

        }else{

            $acceptCartForm = $this->createForm(AcceptCartType::class);
            $acceptCartForm->handleRequest($request);

            if($acceptCartForm->isSubmitted() && $acceptCartForm->isValid())
            {
                return $this->redirectToRoute('paiement', ['tokenDocument' => $document->getToken()]);
            }
            
            $results = $this->documentService->generateValuesForDocument($document);
            return $this->render('site/document_view/_document_view.html.twig', [
                'document' => $document,
                'acceptCartForm' => $acceptCartForm,
                'docLines' => $results,
                'tva' => $results['tauxTva']
            ]);
        }
    }

    #[Route('/demande-de-devis/lecture/{tokenDocument}', name: 'qr_view')]
    public function qrView(
        $tokenDocument,
        Request $request
        ): Response
    {

        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);

        if($document){

            $acceptCartForm = $this->createForm(AcceptCartType::class);
            $acceptCartForm->handleRequest($request);

            if($acceptCartForm->isSubmitted() && $acceptCartForm->isValid())
            {
                return $this->redirectToRoute('paiement', ['tokenDocument' => $document->getToken()]);
            }
            
            $results = $this->documentService->generateValuesForDocument($document);
            return $this->render('site/document_view/quoteRequest/_qr_view.html.twig', [
                'document' => $document,
                'acceptCartForm' => $acceptCartForm,
                'docLines' => $results,
                'tva' => $results['tauxTva']
            ]);
        }else{

            $this->addFlash('danger', 'La demande de devis n\'existe pas...');
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/demande-de-devis/impression/{tokenDocument}', name: 'qr_print')]
    public function qrPrint(string $tokenDocument, DompdfWrapperInterface $dompdfWrapper): Response // Le type de retour est StreamedResponse
    {
        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);

        if (!$document) {
            $this->addFlash('danger', 'La demande de devis n\'existe pas...');
            return $this->redirectToRoute('app_home'); // Redirection si le document n'existe pas
        }

        $legales = $this->legalInformationRepository->findOneBy(['isOnline' => true], ['id' => 'ASC']);

        // Récupérez le HTML de votre template Twig
        $html = $this->renderView('site/document_view/quoteRequest/_qr_print.html.twig', [
            'document' => $document,
            'legales' => $legales
        ]);
        
        return new Response($html); // Décommentez ceci pour voir le HTML dans votre navigateur
        exit; // Et ajoutez un exit pour arrêter l'exécution après l'affichage du HTML

        // Utilisez directement le wrapper pour obtenir la StreamedResponse
        return $dompdfWrapper->getStreamResponse($html, 'document.pdf');
    }



    #[Route('/check-email', name: 'check_email')]
    public function checkEmail(Request $request): Response
    {

        $form = $this->createForm(EmailForSendResetPasswordType::class, null);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $user = $this->userRepository->findOneBy(['email' => $form->get('email')->getData()]);

            if(!$user){

                $form->get('email')->addError(new FormError('Aucun compte n\'est associé à cette adresse email...'));

            }else{

                $resetPassword = new ResetPassword();
                $resetPassword->setEmail($form->get('email')->getData());
                $this->passwordService->saveResetPasswordInDatabaseAndSendEmail($resetPassword);

                $this->addFlash('success', 'Un lien viens de vous être envoyé...');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('member/email_to_send_link_for_reset_password.html.twig', [
            'emailForSendResetPasswordForm' => $form->createView()
        ]);
    }

    #[Route('/reset-password/{uuid}', name: 'reset_password')]
    public function resetPassword($uuid, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {

        $resetPassword = $this->resetPasswordRepository->findOneBy(['uuid' => $uuid]);

        if(!$resetPassword OR $resetPassword->isIsUsed() != false){

            $this->addFlash('warning', 'Demande inconnue ou déjà utilisée !');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ResetPasswordType::class, null);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {

            if($form->get('password')->getData() !== $form->get('passwordVerify')->getData()){

                $form->get('password')->addError(new FormError('Les mots de passe ne sont pas identiques...'));

            }else{

                // encode the plain password
                $user = $this->userRepository->findOneBy(['email' => $resetPassword->getEmail()]);
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                    );

                $entityManager->persist($user);

                //update invitation
                $resetPassword->setIsUsed(true);

                $entityManager->persist($resetPassword);

                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe mis à jour !');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('site/pages/password/reset_password.html.twig', [
            'resetPasswordForm' => $form->createView()
        ]);
    }

    #[Route('/download/facture/{tokenDocument}', name: 'download_billing_document')]
    public function factureDownload($tokenDocument)
    {

        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);

        if(!$document){

            return $this->documentService->renderIfDocumentNoExist();

        }else{

            $pdf = $this->documentService->generatePdf($document);

            return new Response($pdf->Output(), 200, array(
                'Content-Type' => 'application/pdf'));
        }
    }

    #[Route('/nos-partenaires', name: 'app_partners')]
    public function partners(Request $request): Response
    {

        $metas['description'] = "Découvrez nos partenaires du réemploi des jeux !";
        
        return $this->render('site/pages/partners/partners.html.twig', [
            'metas' => $metas,            
            'partners' => $this->partnerRepository->findBy(['isOnline' => true], ['name' => 'ASC'])
        ]);

    }
}