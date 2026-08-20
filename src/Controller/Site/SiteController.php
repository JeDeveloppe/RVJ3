<?php

namespace App\Controller\Site;

use DateTimeImmutable;
use App\Form\ContactType;
use App\Service\MailService;
use App\Repository\ItemRepository;
use App\Service\AmbassadorService;
use App\Repository\MediaRepository;
use App\Entity\JobPost;
use App\Repository\JobPostRepository;
use App\Repository\PartnerRepository;
use App\Service\SiteControllerService;
use App\Service\MentionsLegalesService;
use App\Repository\AmbassadorRepository;
use App\Repository\DocumentLineRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\LegalInformationRepository;
use App\Repository\StoreRepository;
use App\Repository\SiteSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SiteController extends AbstractController
{
    public function __construct(
        private LegalInformationRepository $legalInformationRepository,
        private MailService $mailService,
        private PartnerRepository $partnerRepository,
        private AmbassadorService $ambassadorService,
        private AmbassadorRepository $ambassadorRepository,
        private UrlGeneratorInterface $urlGeneratorInterface,
        private SiteControllerService $siteControllerService,
        private MentionsLegalesService $mentionsLegalesService,
        private ItemRepository $itemRepository,
        private StoreRepository $storeRepository
    )
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $metas['description'] = "Association dédiée au réemploi des jeux et au lien social. Découvrez nos prestations, nos jeux d'occasion et nos pièces détachées pour vos jeux incomplets.";
        $totalPiecesDisponiblentSurLeSite = $this->itemRepository->countAllItemsWithStockForSaleNotNull();


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

    #[Route('/politique-de-confidentialite', name: 'app_politique_confidentialite')]
    public function rgpd(): Response
    {
        $legales = $this->legalInformationRepository->findOneBy(['isOnline' => true], ['id' => 'ASC']);
        $paragraphs = $this->mentionsLegalesService->rgpdParagraphs($legales);
        $metas['description'] = 'Politique de confidentialité et protection des données personnelles (RGPD) du site.';

        return $this->render('site/pages/legale/rgpd.html.twig', [
            'legales' => $legales,
            'metas' => $metas,
            'paragraphs' => $paragraphs
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

            $this->mailService->sendMail(
                true,
                $legales->getEmailCompany(),
                "Message du site en date du ".(new DateTimeImmutable('now'))->format('d-m-Y').": ".$form->get('sujet')->getData(),
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

    #[Route('/offres-d-emploi', name: 'app_job_posts')]
    public function jobPosts(JobPostRepository $jobPostRepository, SiteSettingRepository $siteSettingRepository, EntityManagerInterface $entityManager): Response
    {
        $this->closeExpiredJobPosts($jobPostRepository, $siteSettingRepository, $entityManager);

        $metas['description'] = "Consultez nos offres d'emploi et rejoignez notre association.";
        $jobPosts = $jobPostRepository->findPublished();

        return $this->render('site/pages/job_post/job_post.html.twig', [
            'metas' => $metas,
            'jobPosts' => $jobPosts,
        ]);
    }

    #[Route('/offres-d-emploi/{id}/{slug}', name: 'app_job_post_show', requirements: ['id' => '\d+', 'slug' => '[a-z0-9\-]+'])]
    public function jobPostShow(int $id, string $slug, Request $request, JobPostRepository $jobPostRepository, SiteSettingRepository $siteSettingRepository, EntityManagerInterface $entityManager): Response
    {
        $this->closeExpiredJobPosts($jobPostRepository, $siteSettingRepository, $entityManager);

        $jobPost = $jobPostRepository->findOnePublished($id, $slug);
        if (!$jobPost) {
            $this->addFlash('warning', 'Offre d\'emploi inconnue ou plus disponible');
            return $this->redirectToRoute('app_job_posts');
        }

        $metas['description'] = sprintf('%s (%s%s) - Rejoignez notre association.', $jobPost->getTitle(), $jobPost->getContractType(), $jobPost->getLocation() ? ' - '.$jobPost->getLocation() : '');
        $metas['index'] = 'index, follow';

        return $this->render('site/pages/job_post/job_post_show.html.twig', [
            'metas' => $metas,
            'jobPost' => $jobPost,
            'jobPostSchema' => $this->buildJobPostSchema($jobPost, $request),
        ]);
    }

    //?Balise <script type="application/ld+json"> JobPosting (schema.org) : c'est ce qui
    //?permet a Google de proposer l'offre dans "Google pour les emplois" plutot qu'un
    //?lien classique. Construit en PHP (pas en Twig) pour controler proprement les
    //?options de json_encode (unicode/slashes non echappes).
    private const EMPLOYMENT_TYPES = [
        'CDI' => 'FULL_TIME',
        'CDD' => 'CONTRACTOR',
        'Bénévole' => 'VOLUNTEER',
        'Stage' => 'INTERN',
        'Alternance' => 'INTERN',
    ];

    private function buildJobPostSchema(JobPost $jobPost, Request $request): string
    {
        $hostname = $request->getSchemeAndHttpHost();

        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => $jobPost->getTitle(),
            'description' => $jobPost->getDescription(),
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => 'Refaites Vos Jeux',
                'value' => $jobPost->getId(),
            ],
            'datePosted' => $jobPost->getStartPublished()->format('Y-m-d'),
            'validThrough' => $jobPost->getEndPublished()->format('Y-m-d\TH:i:s'),
            'employmentType' => self::EMPLOYMENT_TYPES[$jobPost->getContractType()] ?? 'OTHER',
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => 'Refaites Vos Jeux',
                'sameAs' => $hostname,
                'logo' => $hostname.'/build/images/design/logoSite.svg',
            ],
        ];

        if ($jobPost->getLocation()) {
            $schema['jobLocation'] = [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $jobPost->getLocation(),
                    'addressCountry' => 'FR',
                ],
            ];
        }

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    //?Meme principe que PanierService::deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock() :
    //?pas de cron sur l'hebergement, donc on "ferme" les offres expirees a la volee quand
    //?la page publique est consultee, avec un throttle via SiteSetting pour eviter de
    //?repeter la requete a chaque page vue en cas de trafic simultane. 1x/jour suffit ici
    //?(contrairement au panier) : la fenetre de publication se raisonne en jours, pas en minutes.
    private function closeExpiredJobPosts(JobPostRepository $jobPostRepository, SiteSettingRepository $siteSettingRepository, EntityManagerInterface $entityManager): void
    {
        $now = new DateTimeImmutable('now');
        $siteSetting = $siteSettingRepository->find(1);

        if ($siteSetting !== null && $siteSetting->getLastJobPostCleanupAt() !== null
            && $siteSetting->getLastJobPostCleanupAt() > $now->modify('-1 day')) {
            return;
        }

        $expiredJobPosts = $jobPostRepository->createQueryBuilder('j')
            ->andWhere('j.isOnLine = true')
            ->andWhere('j.endPublished < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        foreach ($expiredJobPosts as $jobPost) {
            $jobPost->setIsOnLine(false);
        }

        if ($siteSetting !== null) {
            $siteSetting->setLastJobPostCleanupAt($now);
            $entityManager->persist($siteSetting);
        }

        $entityManager->flush();
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
            'btn_link' => $this->urlGeneratorInterface->generate('app_store_page_centralisation'),
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

    #[Route('/nos-partenaires', name: 'app_partners')]
    public function partners(Request $request): Response
    {

        $metas['description'] = "Découvrez nos partenaires du réemploi des jeux !";
        
        return $this->render('site/pages/partners/partners.html.twig', [
            'metas' => $metas,            
            'partners' => $this->partnerRepository->findBy(['isOnline' => true], ['name' => 'ASC'])
        ]);

    }

    #[Route('/boutique', name: 'app_store_page')]
    public function boutique(Request $request): Response
    {

        $metas['description'] = "Découvrez notre boutique solidaire de jeux d'occasion et pièces détachées. Achetez malin, soutenez une cause !";
        $stores = $this->storeRepository->findAll();
        
        return $this->render('site/pages/boutique/index.html.twig', [
            'metas' => $metas,
            'stores' => $stores
        ]);

    }

    #[Route('/boutique/centralisation-des-jeux', name: 'app_store_page_centralisation')]
    public function boutiqueCentralisation(Request $request): Response
    {

        $metas['description'] = "Information importante : Notre boutique de jeux de société d'occasion en ligne est désormais fermée. Retrouvez tout notre stock dans notre boutique physique à Caen.";
        $totalPiecesDisponiblentSurLeSite = $this->itemRepository->countAllItemsWithStockForSaleNotNull();

        return $this->render('site/pages/boutique/oldOccasionRedirectPage.html.twig', [
            'metas' => $metas,
            'totalPiecesDisponiblentSurLeSite' => $totalPiecesDisponiblentSurLeSite
        ]);

    }

}