<?php

namespace App\Tests\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EasyAdminSmokeTest extends WebTestCase
{
    private const CONTROLLERS = [
        'App\Controller\Admin\EasyAdmin\AddressCrudController',
        'App\Controller\Admin\EasyAdmin\AmbassadorCrudController',
        'App\Controller\Admin\EasyAdmin\BadgeForMediaTimelineCrudController',
        'App\Controller\Admin\EasyAdmin\BoiteCrudController',
        'App\Controller\Admin\EasyAdmin\CatalogOccasionSearchCrudController',
        'App\Controller\Admin\EasyAdmin\CityCrudController',
        'App\Controller\Admin\EasyAdmin\CollectionPointCrudController',
        'App\Controller\Admin\EasyAdmin\ColorCrudController',
        'App\Controller\Admin\EasyAdmin\ConditionOccasionCrudController',
        'App\Controller\Admin\EasyAdmin\CountryCrudController',
        'App\Controller\Admin\EasyAdmin\DeliveryCrudController',
        'App\Controller\Admin\EasyAdmin\DepartmentCrudController',
        'App\Controller\Admin\EasyAdmin\DiscountCrudController',
        'App\Controller\Admin\EasyAdmin\DocumentCrudController',
        'App\Controller\Admin\EasyAdmin\DocumentParametreCrudController',
        'App\Controller\Admin\EasyAdmin\DocumentStatusCrudController',
        'App\Controller\Admin\EasyAdmin\DurationOfGameCrudController',
        'App\Controller\Admin\EasyAdmin\EditorCrudController',
        'App\Controller\Admin\EasyAdmin\EnvelopeCrudController',
        'App\Controller\Admin\EasyAdmin\GranderegionCrudController',
        'App\Controller\Admin\EasyAdmin\ItemCrudController',
        'App\Controller\Admin\EasyAdmin\ItemGroupCrudController',
        'App\Controller\Admin\EasyAdmin\LegalInformationCrudController',
        'App\Controller\Admin\EasyAdmin\LevelCrudController',
        'App\Controller\Admin\EasyAdmin\MeansOfPayementCrudController',
        'App\Controller\Admin\EasyAdmin\MediaCrudController',
        'App\Controller\Admin\EasyAdmin\MovementOccasionCrudController',
        'App\Controller\Admin\EasyAdmin\NumbersOfPlayersCrudController',
        'App\Controller\Admin\EasyAdmin\OccasionCrudController',
        'App\Controller\Admin\EasyAdmin\OffSiteOccasionSaleCrudController',
        'App\Controller\Admin\EasyAdmin\PanierCrudController',
        'App\Controller\Admin\EasyAdmin\PartnerCrudController',
        'App\Controller\Admin\EasyAdmin\PaymentCrudController',
        'App\Controller\Admin\EasyAdmin\QuoteRequestCrudController',
        'App\Controller\Admin\EasyAdmin\ReserveCrudController',
        'App\Controller\Admin\EasyAdmin\ResetPasswordCrudController',
        'App\Controller\Admin\EasyAdmin\ReturndetailstostockCrudController',
        'App\Controller\Admin\EasyAdmin\SearchBoiteLogCrudController',
        'App\Controller\Admin\EasyAdmin\ShippingMethodCrudController',
        'App\Controller\Admin\EasyAdmin\SiteSettingCrudController',
        'App\Controller\Admin\EasyAdmin\StockCrudController',
        'App\Controller\Admin\EasyAdmin\StoreCrudController',
        'App\Controller\Admin\EasyAdmin\TaxCrudController',
        'App\Controller\Admin\EasyAdmin\UserCrudController',
        'App\Controller\Admin\EasyAdmin\VoucherDiscountCrudController',
    ];

    public function testAllCrudPagesRenderWithoutFatalError(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $admin = $em->getRepository(User::class)->find(3);
        $client->loginUser($admin);

        $failures = [];
        $checked = 0;

        foreach (self::CONTROLLERS as $fqcn) {
            if (!class_exists($fqcn)) {
                $failures[] = sprintf('%s -> classe introuvable', $fqcn);
                continue;
            }

            $entityFqcn = $fqcn::getEntityFqcn();
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $sample = $em->getRepository($entityFqcn)->findOneBy([]);
            $id = ($sample && method_exists($sample, 'getId')) ? $sample->getId() : null;

            $urls = ['index' => '/admin?crudControllerFqcn=' . urlencode($fqcn) . '&crudAction=index'];
            if ($id !== null) {
                $urls['detail'] = '/admin?crudControllerFqcn=' . urlencode($fqcn) . '&crudAction=detail&entityId=' . $id;
            }
            $urls['new'] = '/admin?crudControllerFqcn=' . urlencode($fqcn) . '&crudAction=new';

            foreach ($urls as $actionName => $url) {
                $checked++;
                try {
                    $client->request('GET', $url);
                    $status = $client->getResponse()->getStatusCode();
                    if ($status >= 500) {
                        $failures[] = sprintf('%s [%s] -> HTTP %d (%s)', $fqcn, $actionName, $status, $url);
                    }
                } catch (\Throwable $e) {
                    $failures[] = sprintf('%s [%s] -> %s: %s', $fqcn, $actionName, get_class($e), $e->getMessage());
                }
            }
        }

        fwrite(STDERR, "\n--- Smoke test EasyAdmin : {$checked} pages verifiees, " . count($failures) . " en echec ---\n");

        $this->assertSame([], $failures, "Pages en erreur :\n" . implode("\n", $failures));
    }
}
