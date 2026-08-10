<?php

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use League\Csv\Reader;
use App\Repository\UserRepository;
use App\Repository\AddressRepository;
use App\Repository\CountryRepository;
use App\Repository\PartnerRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\DocumentParametreRepository;
use App\Repository\PanierRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $userPasswordHasher,
        private UserRepository $userRepository,
        private UtilitiesService $utilitiesService,
        private CountryRepository $countryRepository,
        private DocumentRepository $documentRepository,
        private AddressRepository $addressRepository,
        private PartnerRepository $partnerRepository,
        private DocumentParametreRepository $documentParametreRepository,
        private Security $security,
        private PanierRepository $panierRepository,
        private RequestStack $requestStack,
        private LoggerInterface $logger
        ){
    }

    public function initForProd_adminUser($io): void
    {
        $io->title('Création / mise à jour de l\'user ADMIN');

        //on vérifié si on a déjà créé l'admin
        $user = $this->userRepository->findOneBy(['email' => $_ENV['ADMIN_EMAIL']]);

        if(!$user){

            $user = new User();
        }

        $user->setCreatedAt(new DateTimeImmutable('now'))
            ->setLastvisite(new DateTimeImmutable('now'))
            ->setEmail($_ENV['ADMIN_EMAIL'])
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setNickname('JeDéveloppe')
            ->setAccountnumber('init')
            ->setPhone($_ENV['ADMIN_PHONE'])
            ->setCountry($this->countryRepository->findOneBy(['isocode' => 'FR']))
            ->setPassword(
                $this->userPasswordHasher->hashPassword(
                        $user,
                        $_ENV['ADMIN_PASSWORD']
                    )
                );

        $this->em->persist($user);
        $this->em->flush();

        $user->setAccountnumber($this->utilitiesService->generateAccountNumber($user->getId()));
        $this->em->persist($user);
        $this->em->flush();

        //on vérifié si on a déjà créé le client de passage
        $user = $this->userRepository->findOneBy(['email' => 'client_de_passage@refaitesvosjeux.fr']);

        if(!$user){

            $user = new User();
        }

        $user->setCreatedAt(new DateTimeImmutable('now'))
            ->setLastvisite(new DateTimeImmutable('now'))
            ->setEmail('client_de_passage@refaitesvosjeux.fr')
            ->setRoles(['ROLE_USER'])
            ->setAccountnumber('init')
            ->setPhone(0000000000)
            ->setCountry($this->countryRepository->findOneBy(['isocode' => 'FR']))
            ->setPassword(
                $this->userPasswordHasher->hashPassword(
                        $user,
                        $this->utilitiesService->generateRandomString(20)
                    )
                );

        $this->em->persist($user);
        $this->em->flush();

        $member = $this->generateAccountNumber($user);
        $this->em->persist($member);
        $this->em->flush();

        $io->success('Admin créé / mise à jour!');

    }

    public function constructionMapOfFranceWithUserWhoHaveCommanded()
    {

        $donnees = []; //? toutes les réponses seront dans ce tableau final
        $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);
        $now = new DateTimeImmutable('now');
        $year = $now->format('Y');
        $year -= 1; //null
        
        $documents = $this->documentRepository->findByDocumentWithPaiementInYear($docParams->getBillingTag(), $year);

        $users = [];

        foreach($documents as $document){
            $users[] = $document->getUser();
        }
    
        $count = 0;
        foreach($users as $user){
            if(!is_null($user->getaddresses())){

                $adress = $this->addressRepository->findOneBy(['user' => $user, 'isFacturation' => false]);
                
                $countries = ['FR','BE'];
                if(!is_null($adress) AND in_array($adress->getCity()->getCountry()->getIsocode(), $countries)){
                    $count += 1;
                    $color = $this->utilitiesService->generateRandomHtmlColor();
                    
                    $places[] = 
                    [
                        "lat" => $adress->getCity()->getLatitude(),
                        "lng" => $adress->getCity()->getLongitude(),
                        "color" => "#".$color,
                        "name" => "MERCI"
                    ];
                }
            }
        }

        $jsonPlaces = json_encode($places, JSON_FORCE_OBJECT); 

        $donnees['places'] = $jsonPlaces;
        $donnees['count'] = $count;
        $donnees['year'] = $year;

        return $donnees;
    }

    public function disabledFieldWhenBenevole()
    {

        $user = $this->security->getUser();

        //?gestion possibilité d'afficher ou pas en function du role
        $roles = $user->getRoles();
        $disabledWhenBenevole = true;
        if(in_array("ROLE_ADMIN", $roles)){
            $disabledWhenBenevole = false;
        }

        return $disabledWhenBenevole;
    }

    public function importClients(SymfonyStyle $io): void
    {
        $io->title('Importation des clients');

        $clients = $this->readCsvFileClients();
        
        $io->progressStart(count($clients));

        foreach($clients as $arrayClient){
            $io->progressAdvance();
            $client = $this->createOrUpdateClient($arrayClient);
            $this->em->persist($client);
        }

        $this->em->flush();

        $io->progressFinish();
        $io->success('Importation terminée');

        $io->title('Création du numéro client');

        $users = $this->userRepository->findAll();
        $io->progressStart(count($users));

        foreach($users as $member){
            $user = $this->generateAccountNumber($member);
            $this->em->persist($user);
            $io->progressAdvance();
        }
        $this->em->flush();

        $io->progressFinish();
        $io->success('Importation terminée');
    }

    //lecture des fichiers exportes dans le dossier import
    private function readCsvFileClients(): Reader
    {
        $csvClients = Reader::createFromPath('%kernel.root.dir%/../import/_table_clients.csv','r');
        $csvClients->setHeaderOffset(0);

        return $csvClients;
    }

    private function createOrUpdateClient(array $arrayClient): User
    {
        $client = $this->userRepository->findOneBy(['email' => $arrayClient['email']]);

        if(!$client){
            $client = new User();
        }

        if($arrayClient['pseudo'] == "NULL"){
            $pseudo = null;
        }else{
            $pseudo = $arrayClient['pseudo'];
        }

        switch($arrayClient['userLevel']){
            case 4:
                $role = ['ROLE_ADMIN'];
                break;
            case 5:
                $role = ['ROLE_SUPER_ADMIN'];
                break;
            default:
                $role = ['ROLE_USER'];
        };


        $client->setEmail($arrayClient['email'])
                ->setRvj2Id($arrayClient['idClient'])
                ->setPassword($arrayClient['password'])
                ->setRoles($role)
                ->setAccountnumber('init')
                ->setNickname($pseudo)
                ->setPhone($arrayClient['telephone'])
                ->setMembership($this->utilitiesService->getDateTimeImmutableFromTimestamp($arrayClient['isAssociation']))
                ->setCountry($this->countryRepository->findOneBy(['isocode' => $arrayClient['paysFacturation']]) ?? $this->countryRepository->findOneBy(['isocode' => 'INC']) );

                if($arrayClient['timeInscription'] != 0){
                    $time = $arrayClient['timeInscription'];
                    $client->setCreatedAt($this->utilitiesService->getDateTimeImmutableFromTimestamp($time));
                }

                $time = $arrayClient['lastVisite'];
                $client->setLastvisite($this->utilitiesService->getDateTimeImmutableFromTimestamp($time));


        return $client;
    }

    public function createUndefinedUser($io)
    {
        //on vérifié si pn a déjà créer l'administrateur spécial
        $user = $this->userRepository->findOneBy(['email' => $_ENV['UNDEFINED_USER_EMAIL']]);

        if(!$user){
            $user = new User();
        }

        $io->title('Création / mise à jour de l\'UNDEFINED_USER');

        $random = $this->utilitiesService->generateRandomString(25);

            $user->setCreatedAt(new DateTimeImmutable('now'))
            ->setLastvisite(new DateTimeImmutable('now'))
            ->setEmail($_ENV['UNDEFINED_USER_EMAIL'])
            ->setRoles(['ROLE_USER'])
            ->setAccountnumber('init')
            ->setNickname('INCONNU(E)')
            ->setPhone(0000000000)
            ->setCountry($this->countryRepository->findOneBy(['isocode' => 'FR']))
            ->setPassword(
                $this->userPasswordHasher->hashPassword(
                        $user,
                        $random
                    )
                );

        $this->em->persist($user);
        $this->em->flush();

        $member = $this->generateAccountNumber($user);
        $this->em->persist($member);
        $this->em->flush();

        $io->success('Création undéfined user ok');
    }

    public function generateAccountNumber(User $user)
    {
        $user->setAccountnumber($this->utilitiesService->generateAccountNumber($user->getId()));

        return $user;

    }

    public function transformPaniersCreatedWhitoutUserToNewUserLogged(User $newUser)
    {

        $session = $this->requestStack->getSession();
        $paniers = $session->get('paniers');

        foreach($paniers as $panier_ligne){
            $panier_ligne->setUser($newUser)->setTokenSession($session->get('tokenSession'));
            $this->em->persist($panier_ligne);
        }

        $this->em->flush();

    }

    public function deleteInactiveAccounts(): void
    {
        $users = $this->userRepository->findInactiveAccountsToDelete();
        $deletedEmails = [];

        foreach ($users as $user) {
            try {
                $this->em->wrapInTransaction(function () use ($user) {
                    foreach ($user->getAddresses() as $address) {
                        $this->em->remove($address);
                    }
                    $this->em->remove($user);
                });
                $deletedEmails[] = $user->getEmail();
            } catch (\Throwable $e) {
                $this->logger->warning('Compte inactif non supprimé (probablement référencé ailleurs) : '.$user->getEmail().' - '.$e->getMessage());
            }
        }

        if (count($deletedEmails) > 0) {
            $this->logger->info('Nettoyage des comptes inactifs : '.count($deletedEmails).' compte(s) supprimé(s) - '.implode(', ', $deletedEmails));
        }
    }
}