<?php

namespace App\Service;

use App\Entity\LegalInformation;
use Symfony\Component\Routing\RouterInterface;

class MentionsLegalesService
{
    public function __construct(
        private RouterInterface $routerInterface
    )
    {
        
    }
    public function mentionsParagraphs(LegalInformation $legales){

        $paragraphs = [
            [
            'title' => 'LIENS VERS D’AUTRES SITES',
            'text' => $legales->getCompanyName().' peut insérer sur le Site des liens vers des sites internet tiers.<br/>'.
                    $legales->getCompanyName().' ne pourra être tenue responsable du fonctionnement et du contenu de ces sites, et des dommages pouvant être subis par tout utilisateur lors d’une visite de ces sites.<br/>
                    Des sites tiers peuvent également contenir des liens hypertextes vers le site.'
            ]
            ,
            [
            'title' => 'PROPRIÉTÉ INTELLECTUELLE',
            'text' => ' Le Site et son contenu sont protégés en vertu du droit de la propriété intellectuelle.<br/>
                    Le logo de '.$legales->getCompanyName().', le nom commercial, ainsi que l’intégralité du contenu du Site, sont la propriété exclusive de '.$legales->getCompanyName().', seule habilitée à utiliser les droits de propriété intellectuelle attachés. Toute reproduction totale ou partielle du Site est strictement interdite sauf accord préalable de '.$legales->getCompanyName().'.<br/>
                    L’accès au Site confère uniquement à l’utilisateur un droit d’usage privé et non exclusif du Site. '.$legales->getCompanyName().' est libre de modifier, à tout moment et sans préavis, le contenu du Site ainsi que les présentes mentions.<br/>
                    '.$legales->getCompanyName().' ne pourra être tenu responsable des conséquences de ces modifications.<br/>
                    Toute modification sera considérée comme étant acceptée sans réserve par l’utilisateur dès lors qu’il accèdera au Site postérieurement à leur mise en ligne.'
            ]
            ,
            [
            'title' => 'UTILISATION DU SITE',
            'text' => 'Le site est accessible à tout utilisateur disposant d’un accès à internet.<br/>
            L’utilisateur est responsable de son équipement informatique, de son accès à internet et reconnaît avoir la compétence et les moyens adaptés pour utiliser le site.<br/>
            Tous les coûts relatifs à l’accès au site restent à la charge de l’utilisateur.'
            ]
            ,
            [
            'title' => 'INDISPONIBILITÉ DU SITE',
            'text' => ''.$legales->getCompanyName().' se réserve le droit d’interrompre ou de suspendre, à tout moment et sans préavis, tout ou partie du site.<br/>
            '.$legales->getCompanyName().' ne pourra, en aucune façon, être tenue responsable en cas d’indisponibilité du site pour quelque cause que ce soit.'
            ]
            ,
            [
            'title' => 'INFORMATIONS FIGURANT SUR LE SITE',
            'text' => 'Les informations et éléments figurant sur le Site sont disponibles à des fins exclusivement d’information.<br/>'
            .$legales->getCompanyName().' fait son possible afin de contrôler la réalité de ces informations et de maintenir le Sste à jour.<br/> Toutefois, le contenu du site n’est en aucune façon garantie.'
            ]
            ,
            [
            'title' => 'RESPONSABILITÉ',
            'text' => $legales->getCompanyName().' ne peut, en aucune façon, être tenue responsable des dommages directs et/ou indirects qui résulteraient de l’utilisation ou de l’accès au site.'
            .$legales->getCompanyName().' ne saurait notamment voir sa responsabilité engagée en cas d’un dommage ou d’un virus qui pourrait infecter l’ordinateur de l’utilisateur ou son matériel informatique à la suite de l’accès ou de l’utilisation du site.'
            ]

        ];

        return $paragraphs;
    }

    public function rgpdParagraphs(LegalInformation $legales){

        $paragraphs = [
            [
            'title' => 'RESPONSABLE DU TRAITEMENT',
            'text' => 'Le responsable du traitement des données à caractère personnel collectées sur le site est '.$legales->getCompanyName().', association loi 1901 gérée par des bénévoles.<br/>
                    '.$legales->getStreetCompany().' '.$legales->getPostalCodeCompany().' '.$legales->getCityCompany().'<br/>
                    Contact : '.$legales->getEmailCompany()
            ]
            ,
            [
            'title' => 'DONNÉES COLLECTÉES',
            'text' => 'Dans le cadre de la création d\'un compte, d\'une commande ou d\'une demande de contact, nous collectons :
                    <ul>
                        <li>votre identité (nom, prénom)</li>
                        <li>vos coordonnées (adresse email, téléphone, adresse postale de livraison/facturation)</li>
                        <li>l\'historique de vos commandes, devis et factures</li>
                        <li>le contenu des messages envoyés via le formulaire de contact</li>
                    </ul>
                    Nous ne collectons aucune donnée bancaire : les paiements en ligne sont traités directement par nos prestataires de paiement (voir « Destinataires des données » ci-dessous), qui ne nous transmettent jamais votre numéro de carte.'
            ]
            ,
            [
            'title' => 'FINALITÉS DU TRAITEMENT',
            'text' => 'Vos données sont utilisées pour :
                    <ul>
                        <li>la gestion de votre compte et de vos commandes (pièces détachées, jeux d\'occasion)</li>
                        <li>l\'émission des devis et factures</li>
                        <li>la réponse à vos demandes via le formulaire de contact</li>
                        <li>le fonctionnement statistique interne de l\'association (nombre de ventes, articles disponibles, etc.)</li>
                    </ul>'
            ]
            ,
            [
            'title' => 'BASE LÉGALE',
            'text' => 'Le traitement de vos données repose selon les cas sur :
                    <ul>
                        <li>l\'exécution du contrat de vente lorsque vous passez commande</li>
                        <li>le respect d\'une obligation légale, notamment la conservation des documents comptables (devis et factures)</li>
                        <li>l\'intérêt légitime de '.$legales->getCompanyName().' à assurer le bon fonctionnement du site et à répondre à vos demandes</li>
                    </ul>'
            ]
            ,
            [
            'title' => 'DESTINATAIRES DES DONNÉES',
            'text' => 'Vos données sont traitées par les bénévoles de '.$legales->getCompanyName().' dans le cadre de leur mission associative.<br/>
                    Elles peuvent également être transmises à des prestataires techniques strictement nécessaires au fonctionnement du site :
                    <ul>
                        <li>notre hébergeur : '.$legales->getHostName().' ('.$legales->getHostStreet().' '.$legales->getHostPostalCode().' '.$legales->getHostCity().')</li>
                        <li>nos prestataires de paiement en ligne : PayPlug (paiements de la boutique) et HelloAsso (dons et paiements associatifs)</li>
                        <li>Google reCAPTCHA, utilisé pour protéger nos formulaires contre les robots et le spam ; ce service peut entraîner un transfert de données techniques (adresse IP notamment) vers Google, hors de l\'Union européenne</li>
                        <li>Google Analytics, utilisé pour mesurer la fréquentation du site (voir la section « Cookies » ci-dessous) — uniquement si vous y avez consenti</li>
                    </ul>
                    Nous ne vendons ni ne cédons vos données à des tiers à des fins commerciales ou publicitaires.'
            ]
            ,
            [
            'title' => 'DURÉE DE CONSERVATION',
            'text' => 'Vos données de compte sont conservées tant que votre compte reste actif.<br/>
                    Les devis et factures sont conservés conformément à l\'obligation légale de conservation des documents comptables (10 ans).<br/>
                    Un compte n\'ayant jamais donné lieu à une commande, disposant d\'au plus une adresse enregistrée, et resté inactif plus d\'un mois, est automatiquement et définitivement supprimé. Vous pouvez à tout moment demander la suppression anticipée de votre compte (voir « Vos droits » ci-dessous).'
            ]
            ,
            [
            'title' => 'VOS DROITS',
            'text' => 'Conformément au Règlement Général sur la Protection des Données (RGPD), vous disposez d\'un droit d\'accès, de rectification, d\'effacement, de limitation et d\'opposition sur vos données personnelles.<br/>
                    Pour exercer ces droits, contactez-nous à l\'adresse '.$legales->getEmailCompany().'.<br/>
                    Vous pouvez également supprimer votre compte vous-même, à tout moment, depuis le lien ci-dessous : vos adresses seront anonymisées et votre compte définitivement désactivé. Cette action est irréversible.<br/>
                    Vous disposez enfin d\'un droit de réclamation auprès de la CNIL (<a href="https://www.cnil.fr" target="_blank" rel="noopener">www.cnil.fr</a>).'
            ]
            ,
            [
            'title' => 'COOKIES',
            'text' => 'Le site dépose un cookie technique indispensable à son fonctionnement (gestion de votre connexion et de votre panier d\'achat), qui ne nécessite pas votre consentement.<br/>
                    Le site utilise également Google Analytics pour mesurer sa fréquentation (pages visitées, provenance des visiteurs). Ce cookie n\'est déposé qu\'après votre consentement, recueilli via le bandeau affiché lors de votre première visite. Vous pouvez à tout moment revenir sur votre choix en effaçant les cookies de votre navigateur.'
            ]

        ];

        return $paragraphs;
    }
}