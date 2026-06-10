<?php

namespace App\controller;

use App\model\Favorite;
use App\model\Property;
use Src\classes\ClassAuth;
use Src\classes\ClassCookieConsent;
use Src\classes\ClassRender;
use Src\classes\ClassSEO;
use Src\classes\ClassSession;
use Src\classes\ClassSettings;
use Src\classes\DiscoveryEngine;
use Src\classes\PageCache;
use Src\traits\TraitUrlParser;

class ControllerHome
{
    use TraitUrlParser;

    public function index(): void
    {
        $Render = new ClassRender();
        $featuredProperties = [];
        $continueExploring = [];
        $discoveryPersonalized = false;
        $propertyStats = [];
        $favoriteIds = [];
        $hasBehaviorConsent = ClassCookieConsent::hasBehavioralConsent();
        $viewerUserId = ($hasBehaviorConsent && ClassAuth::check()) ? (int) (ClassAuth::user()['id'] ?? 0) : null;
        $visitorKey = $hasBehaviorConsent ? ClassSession::getOrCreateVisitorKey() : null;
        $carouselSize = max(4, ClassSettings::int('behavior_home_carousel_size', 8));

        try {
            if (DiscoveryEngine::isActive($viewerUserId, $visitorKey)) {
                $featuredProperties = DiscoveryEngine::homeCarousel($viewerUserId, $visitorKey, $carouselSize);
                $continueExploring = DiscoveryEngine::continueExploring(
                    $viewerUserId,
                    $visitorKey,
                    max(3, ClassSettings::int('behavior_continue_exploring_size', 6))
                );
                $discoveryPersonalized = true;
            } else {
                $featuredProperties = Property::getFeatured($carouselSize, 0, $viewerUserId, $visitorKey);
            }
        } catch (\Throwable $e) {
            $featuredProperties = [];
            $continueExploring = [];
        }

        try {
            $propertyStats = Property::getStatusStats();
        } catch (\Throwable $e) {
            $propertyStats = [];
        }

        if (ClassAuth::check()) {
            $favoriteIds = Favorite::getPropertyIdsByUser((int) (ClassAuth::user()['id'] ?? 0));
        }

        $Render->setTitle('Imobil Fácil — Imóveis em Angola sem Intermediários');
        $Render->setDescription('Compre ou arrende imóveis em Angola falando directamente com o proprietário. Sem intermediários, anúncios verificados e pagamento seguro.');
        $Render->setKeywords(ClassSEO::DEFAULT_KEYWORDS);
        $Render->setOgTitle('Imobil Fácil — Negocie directamente com o proprietário');
        $Render->setOgDescription('Imóveis verificados para venda e aluguer em Angola. Contacto directo com o dono — sem intermediários nem comissões escondidas.');
        $Render->setOgImage(ClassSEO::defaultOgImage());
        $Render->setOgType('website');
        $Render->setCanonical(rtrim(DIRPAGE, '/'));

        $Render->addStructuredData(ClassSEO::getWebSiteSchema());
        $Render->addStructuredData(ClassSEO::getOrganizationSchema());
        $Render->addStructuredData(ClassSEO::getBreadcrumbSchema([
            ['name' => 'Home', 'url' => rtrim(DIRPAGE, '/')],
        ]));

        $Render->setData([
            'featuredProperties' => $featuredProperties,
            'continueExploring' => $continueExploring,
            'discoveryPersonalized' => $discoveryPersonalized,
            'propertyStats' => $propertyStats,
            'favoriteIds' => $favoriteIds,
        ]);
        $Render->setDir('home');

        $canPageCache = !ClassAuth::check() && !ClassCookieConsent::hasBehavioralConsent();
        if ($canPageCache) {
            $cacheTtl = max(60, ClassSettings::int('page_cache_home_ttl_seconds', 300));
            $html = PageCache::capture('home', $cacheTtl, function () use ($Render) {
                ob_start();
                $Render->renderLayout();
                return ob_get_clean();
            });
            echo $html;
            return;
        }

        $Render->renderLayout();
    }

    /** Legacy alias when /home is resolved via ClassRoutes. */
    public function home(): void
    {
        $this->index();
    }
}
