<?php
namespace App\controller;
use Src\classes\ClassRender;
use Src\classes\ClassAuth;
use Src\classes\ClassSession;
use Src\classes\ClassSettings;
use Src\classes\ClassCookieConsent;
use Src\classes\ClassSEO;
use Src\classes\PageCache;
use Src\classes\DiscoveryEngine;
use Src\interfaces\InterfaceView;
use App\model\Property;
use App\model\Favorite;
class ControllerHome {
use \Src\traits\TraitUrlParser;
	public function __construct() 
	{
		$Render=new ClassRender();
		if (count($this->parseUrl())==1) {
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

			// SEO Configuration
			$Render->setTitle("Imobil Fácil - Encontre e Negocie Imóveis com Segurança");
			$Render->setDescription("Plataforma de negociação de imóveis verificados. Encontre casas, apartamentos e propriedades comerciais com garantia de segurança e negociação fácil.");
			$Render->setKeywords("imóveis, casas, apartamentos, aluguel, venda, propriedades, portugal");
			$Render->setOgTitle("Imobil Fácil - Plataforma de Negociação de Imóveis");
			$Render->setOgDescription("Encontre, anuncie e negocie imóveis de forma simples e segura. Pague com segurança através da nossa plataforma.");
			$Render->setOgImage(DIRIMG . 'og-home.jpg');
			$Render->setOgType('website');
			$Render->setCanonical(rtrim(DIRPAGE, '/'));
			
			// Add organization structured data
			$Render->addStructuredData(ClassSEO::getOrganizationSchema());
			
			// Add breadcrumb structured data
			$Render->addStructuredData(ClassSEO::getBreadcrumbSchema([
				['name' => 'Home', 'url' => rtrim(DIRPAGE, '/')]
			]));
			
			$Render->setData([
				'featuredProperties' => $featuredProperties,
				'continueExploring' => $continueExploring,
				'discoveryPersonalized' => $discoveryPersonalized,
				'propertyStats' => $propertyStats,
				'favoriteIds' => $favoriteIds,
			]);
			$Render->setDir("home");

			$canPageCache = !ClassAuth::check() && !ClassCookieConsent::hasBehavioralConsent();
			if ($canPageCache) {
				$cacheTtl = max(60, ClassSettings::int('page_cache_home_ttl_seconds', 300));
				$html = PageCache::capture('home', $cacheTtl, function() use ($Render) {
					ob_start();
					$Render->renderLayout();
					return ob_get_clean();
				});
				echo $html;
				return;
			}

			$Render->renderLayout();
		}	
	}
	
}
