<?php

namespace App\Controller\Site;

use App\Repository\BoiteRepository;
use App\Repository\EditorRepository;
use App\Repository\OccasionRepository;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class SitemapController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger,
        private RouterInterface $routerInterface,
        private BoiteRepository $boiteRepository,
        private OccasionRepository $occasionRepository,
        private EditorRepository $editorRepository
        )
    {
    }

    #[Route('/sitemap.xml', name: 'site_sitemap_xml')]
    public function sitemapXml(Request $request): Response
    {
        // Tableau pour stocker toutes nos URLs
        $urls = [];
        $now = new DateTimeImmutable('now');
        $hostname = $request->getSchemeAndHttpHost();

        // 1. Récupération des routes statiques commençant par "app_"
        $collection = $this->routerInterface->getRouteCollection();
        $allRoutes = $collection->all();

        foreach($allRoutes as $key => $route){
            // On filtre pour ne prendre que les routes principales du site
            if(substr($key, 0, 4) == 'app_'){
                $urls[] = [
                    'loc'        => $this->generateUrl($key),
                    'lastmod'    => $now->format('Y-m-d'),
                    'changefreq' => "monthly",
                    'priority'   => 0.8
                ];
            }
        }      

        // 2. Ajout des URLs des jeux d'occasion (Occasions en ligne)
        $occasions = $this->occasionRepository->findBy(['isOnline' => true]);

        foreach($occasions as $occasion){
            // Sécurité : on vérifie que la boite et l'éditeur existent pour éviter un crash
            if ($occasion->getBoite() && $occasion->getBoite()->getEditor()) {
                $urls[] = [                
                    'loc'        => $this->generateUrl('occasion', [
                        'reference_occasion' => $occasion->getReference(), 
                        'editor_slug'        => $occasion->getBoite()->getEditor()->getSlug() ?? "inconnu", 
                        'boite_slug'         => strtolower($occasion->getBoite()->getSlug() ?? "jeu") 
                    ]),
                    'lastmod'    => ($occasion->getBoite()->getUpdatedAt() ?? $occasion->getBoite()->getCreatedAt() ?? $now)->format('Y-m-d'),
                    'changefreq' => "weekly",
                    'priority'   => 0.7
                ];
            }
        }

        // 3. Ajout des URLs des boites (Pièces détachées)
        $boites = $this->boiteRepository->findBoitesWhereThereIsItems();

        foreach($boites as $boite){
            // Sécurité : on vérifie que l'éditeur existe
            if ($boite->getEditor()) {
                $urls[] = [                
                    'loc'        => $this->generateUrl('catalogue_pieces_detachees_articles_d_une_boite', [
                        'id'         => $boite->getId(), 
                        'boiteSlug'  => strtolower($boite->getSlug() ?? "jeu"), 
                        'editorSlug' => strtolower($boite->getEditor()->getSlug() ?? "editeur")
                    ]),
                    // CORRECTION ICI : on utilise $boite et non $occasion (qui causait l'erreur 500)
                    // 'lastmod'    => ($boite->getUpdatedAt() ?? $boite->getCreatedAt() ?? $now)->format('Y-m-d'),
                    // CORRRECTION ICI : on utilise la date du scrolling de la boite (date de mise à jour des pièces détachées) ou la date de création de la boite
                    'lastmod' => $now->format('Y-m-d'),
                    'changefreq' => "monthly",
                    'priority'   => 1.0 // Priorité maximale pour les produits en vente
                ];
            }
        }

        // Génération de la réponse au format XML
        $response = new Response(
            $this->renderView('site/sitemap/sitemap.html.twig', [
                'urls'     => $urls,
                'hostname' => $hostname
            ]),
            200
        );

        $response->headers->set('Content-type', 'text/xml');
        
        return $response;
    }

    #[Route('/sitemap', name: 'site_sitemap')]
    public function index(Request $request): Response
    {
        return $this->redirectToRoute('site_sitemap_xml');
    }
}