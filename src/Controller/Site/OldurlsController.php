<?php

namespace App\Controller\Site;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class OldurlsController extends AbstractController
{
     /**
     * Gère la redirection permanente de l'ancienne page d'accueil.
     */
    #[Route("/accueil", name: "home_old", methods: ["GET"])]
    public function homeOld(): RedirectResponse
    {
        return $this->redirectToRoute('app_home', [], 301);
    }

    /**
     * Gère la redirection permanente de l'ancienne page 'Nous Soutenir'.
     */
    #[Route("/nous-soutenir", name: "support_us_old", methods: ["GET"])]
    public function supportUsOld(): RedirectResponse
    {
        return $this->redirectToRoute('app_support_us', [], 301);
    }

    /**
     * Gère la redirection permanente de l'ancienne page CGV.
     */
    #[Route("/conditions-generale-de-vente", name: "cgv_old", methods: ["GET"])]
    public function cgvOld(): RedirectResponse
    {
        return $this->redirectToRoute('app_conditions_generale_de_vente', [], 301);
    }

    /**
     * Gère la redirection permanente de l'ancienne page de don de jeux.
     */
    #[Route("/don-de-jeux", name: "give_game_old", methods: ["GET"])]
    public function giveGameOld(): RedirectResponse
    {
        return $this->redirectToRoute('app_give_your_games', [], 301);
    }

    /**
     * Gère la redirection permanente de l'ancienne page 'Qui Sommes Nous'.
     */
    #[Route("/projet/qui-sommes-nous", name: "were_we_are_old", methods: ["GET"])]
    public function wereWeAreOld(): RedirectResponse
    {
        return $this->redirectToRoute('app_support_us', [], 301);
    }

    /**
     * Gère la redirection permanente de l'ancienne URL /catalogues (pluriel) vers la page de centralisation.
     */
    #[Route("/catalogues", name: "catalogues_old", methods: ["GET"])]
    public function cataloguesOld(): RedirectResponse
    {
        return $this->redirectToRoute('app_store_page_centralisation', [], 301);
    }
    
    /**
     * Gère la redirection temporaire du catalogue principal.
     */
    #[Route("/catalogue", name: "catalogue_old", methods: ["GET"])]
    public function catalogueOld(): RedirectResponse
    {
        // Redirection temporaire (302) vers la nouvelle page de catalogue
        return $this->redirectToRoute('app_catalogue_switch', [], 302);
    }
    
    /**
     * Gère les anciennes URLs paramétrées comme /don-de-jeux/partenaires/{country}
     */
    #[Route("/don-de-jeux/partenaires/{country}", name: "give_game_by_country_old", methods: ["GET"])]
    public function giveGameByCountryOld(): RedirectResponse
    {
        // Redirection permanente (301)
        return $this->redirectToRoute('app_give_your_games', [], 301);
    }

    /**
     * Gère les redirections complexes avec plusieurs paramètres pour les fiches produits (pièces détachées).
     */
    #[Route("/jeu/{editor}/{id}/{slug}", name: "piecesDetacheesOld1", methods: ["GET"])]
    #[Route("/catalogue-pieces-detachees/{editor}/{id}/{slug}", name: "piecesDetacheesOld2", methods: ["GET"])]
    public function piecesDetacheesOld(int $id, string $editor, string $slug): RedirectResponse
    {
        // Redirection permanente (301) avec passage des paramètres
        return $this->redirectToRoute('catalogue_pieces_detachees_articles_d_une_boite', [
            'id' => $id,
            'editorSlug' => $editor,
            'boiteSlug' => $slug,
            'year' => null
        ], 301);
    }


    /**
     * Gère l'ancienne carte des partenaires.
     */
    #[Route("/carte-des-partenaires/{country}", name: "partnersMapOld", methods: ["GET"])]
    public function partnersMapOld(): RedirectResponse
    {
        // Redirection permanente (301)
        return $this->redirectToRoute('app_partners', [], 301);
    }

    /**
     * Gère les redirections permanentes des anciennes pages d'occasion vers la page de centralisation UX.
     * * NOTE IMPORTANTE: La route de destination a été mise à jour vers 'app_store_page_centralisation'.
     * Assurez-vous que cette route renvoie la page Twig de centralisation (old_occasion_landing.html.twig)
     * que nous avons créée ensemble.
     */
    #[Route('/catalogue-jeux-occasion/{category}', name: 'app_catalogue_occasions', methods: ['GET'], defaults: ['category' => null])]
    #[Route("/jeu-occasion/{reference_occasion}/{editor_slug}/{boite_slug}", name: "occasion", methods: ["GET"])]
    public function redirectOccasionsToStore(): RedirectResponse
    {
        // Redirige toutes les anciennes URLs vers la page de la boutique
        return $this->redirectToRoute('app_store_page_centralisation', [], 301);
    }
}
