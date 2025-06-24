<?php

namespace App\Controller\Member;

use App\Entity\Address;
use App\Form\AddressType;
use App\Repository\AddressRepository;
use App\Repository\PanierRepository;
use App\Service\MemberService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class AdresseController extends AbstractController
{
    public function __construct(
        private Security $security,
        private PanierRepository $panierRepository,
        private AddressRepository $addressRepository,
        private MemberService $memberService
    )
    { 
    }


    #[Route('/membre/adresses/new/', name: 'adresse_new')]
    public function new(
        Request $request,
        Security $security,
        ): Response
    {

        $form = $this->createForm(AddressType::class);
        $form->handleRequest($request);
        $nbrOfAdressesMax = $_ENV['NBR_MAX_ADDRESSES_FOR_MEMBER'];

        if($form->isSubmitted() && $form->isValid()) {
            $user = $security->getUser();
            $isFacturation = $form->get('isFacturation')->getData();

            $adressesFacturation = $this->addressRepository->findBy(['user' => $user, 'isFacturation' => true]);
            $adressesLivraison = $this->addressRepository->findBy(['user' => $user, 'isFacturation' => false]);

            if(count($adressesFacturation) >= $nbrOfAdressesMax && $isFacturation == true || count($adressesLivraison) >= $nbrOfAdressesMax && $isFacturation == false){

                $this->addFlash('warning', 'Vous avez atteint(e) le nombre maximum d\'adresses ! ('.$nbrOfAdressesMax.')');

            }else{

                $adresse = $form->getData();
                $adresse->setUser($user);
    
                $this->addressRepository->add($adresse);
                $this->addFlash('success', 'Adresse créée !');
            }

            if($request->getSession()->has('quoteRequestStep') && $request->getSession()->get('quoteRequestStep') == 'adresses'){

                return $this->redirectToRoute('structure_adherente_demandes_adresses_choices', [], Response::HTTP_SEE_OTHER);
                
            }else{

                return $this->redirectToRoute('member_adresses', [], Response::HTTP_SEE_OTHER);
            }
        }

        $themes = $this->memberService->memberThemes();  

        return $this->render('member/adresse/new.html.twig', [
            'form' => $form,
            'nbrOfAdressesMax' => $nbrOfAdressesMax,
            'themes' => $themes
        ]);
    }

    #[Route('/membre/adresses/{id}', name: 'adresse_show')]
    public function show(Address $adresse): Response
    {
        return $this->render('adresse/show.html.twig', [
            'adresse' => $adresse,
        ]);
    }

    #[Route('/membre/adresses/{id}/edit', name: 'adresse_edit')]
    public function edit(
        $id,
        Request $request,
        Address $adresse,
        Security $security,
        ): Response
    {
        $user = $security->getUser();

        $adresse = $this->addressRepository->findOneBy(['id' => $id, 'user' => $user]);

        if(!$adresse){

            $this->addFlash('warning', 'Cette adresse ne vous appartient pas !');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(AddressType::class, $adresse, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Adresse mise à jour!');

            $this->addressRepository->add($adresse);
            return $this->redirectToRoute('member_adresses', [], Response::HTTP_SEE_OTHER);
        }

        $themes = $this->memberService->memberThemes();  

        return $this->renderForm('member/adresse/edit.html.twig', [
            'form' => $form,
            'address' => $id,
            'themes' => $themes
        ]);
    }

    #[Route('/membre/adresses/{id}/delete', name: 'adresse_delete')]
    public function delete(Request $request, Address $adresse): Response
    {
        if ($this->isCsrfTokenValid('delete'.$adresse->getId(), $request->request->get('_token'))) {
            $this->addressRepository->remove($adresse);
        }

        $this->addFlash('success', 'Adresse supprimée!');

        return $this->redirectToRoute('member_adresses', [], Response::HTTP_SEE_OTHER);
    }
}
