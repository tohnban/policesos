<?php

namespace App\controller;

use App\model\Country;
use App\model\Favorite;
use App\model\Log;
use App\model\MetricEvent;
use App\model\Notification;
use App\model\Property;
use App\model\PropertyAffiliate;
use App\model\PropertyBehaviorEvent;
use App\model\PropertyBoostRequest;
use App\model\Region;
use App\model\Request;
use App\model\SubscriptionPlan;
use App\model\User;
use Src\classes\ClassAccess;
use Src\classes\ClassAuth;
use Src\classes\ClassCommissionGuard;
use Src\classes\ClassCookieConsent;
use Src\classes\ClassCsrf;
use Src\classes\ClassPlan;
use Src\classes\ClassRender;
use Src\classes\ClassSEO;
use Src\classes\ClassSession;
use Src\classes\ClassSettings;
use Src\classes\DiscoveryEngine;
use Src\classes\PageCache;
use Src\classes\PropertyTypeHelper;
use Src\traits\TraitUrlParser;

class ControllerProperty
{
    use TraitUrlParser;
    private function decodeCursor(?string $cursor): ?array
    {
        $cursor = trim((string) $cursor);
        if ($cursor === '') {
            return null;
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $data = json_decode($decoded, true);
        return is_array($data) ? $data : null;
    }

    private function encodeCursor(?string $createdAt, ?int $id): ?string
    {
        $createdAt = $createdAt !== null ? trim((string) $createdAt) : '';
        $id = (int) ($id ?? 0);
        if ($createdAt === '' || $id <= 0) {
            return null;
        }
        $payload = json_encode(['created_at' => $createdAt, 'id' => $id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $b64 = base64_encode((string) $payload);
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    private function normalizeUploadError(int $code): string
    {
        $map = [
            UPLOAD_ERR_INI_SIZE => 'Uma imagem excede o limite do servidor.',
            UPLOAD_ERR_FORM_SIZE => 'Uma imagem excede o limite permitido no formulário.',
            UPLOAD_ERR_PARTIAL => 'Uma imagem foi enviada parcialmente.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária de upload indisponível.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar imagem no disco.',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por uma extensão do servidor.',
        ];

        return $map[$code] ?? 'Erro ao enviar imagem.';
    }

    private function normalizePropertyImagePath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return null;
        }

        $path = preg_replace('#\?.*$#', '', $path);
        $path = preg_replace('#^https?://[^/]+/#i', '', $path);
        $path = ltrim($path, '/');

        if (strpos($path, 'storage/uploads/properties/') === 0) {
            $path = 'public/' . $path;
        }

        if (strpos($path, 'public/storage/uploads/properties/') !== 0) {
            $tail = preg_replace('#^.*?(public/storage/uploads/properties/.+)$#i', '$1', $path);
            if ($tail !== $path && strpos($tail, 'public/storage/uploads/properties/') === 0) {
                $path = $tail;
            }
        }

        if (!preg_match('#^public/storage/uploads/properties/[A-Za-z0-9._-]+$#', $path)) {
            return null;
        }

        return $path;
    }

    /**
     * @param array<int, mixed> $currentImages
     * @param array<string, string> $allowedCurrent
     * @return string[]
     */
    private function preserveCurrentPropertyImages(array $currentImages, array $allowedCurrent): array
    {
        $final = [];
        foreach ($currentImages as $rawPath) {
            $normalized = $this->normalizePropertyImagePath((string) $rawPath);
            if ($normalized !== null) {
                $final[] = $normalized;
            }
        }

        if ($final === []) {
            $final = array_values($allowedCurrent);
        }

        return array_values(array_unique($final));
    }

    private function deletePropertyImageFile(string $path): void
    {
        $normalized = $this->normalizePropertyImagePath($path);
        if ($normalized === null) {
            return;
        }

        $fullPath = DIRREQ . $normalized;
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    /**
     * @param array<string, mixed> $property
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @return array{paths: string[], errors: string[]}
     */
    private function resolvePropertyImagesForUpdate(array $property, array $post, array $files): array
    {
        $errors = [];
        $currentImages = json_decode((string) ($property['images'] ?? '[]'), true);
        if (!is_array($currentImages)) {
            $currentImages = [];
        }

        $allowedCurrent = [];
        foreach ($currentImages as $rawPath) {
            $normalized = $this->normalizePropertyImagePath((string) $rawPath);
            if ($normalized !== null) {
                $allowedCurrent[$normalized] = $normalized;
            }
        }

        $manifest = json_decode((string) ($post['images_manifest'] ?? ''), true);
        $maxNewUploads = 8;

        if (is_array($manifest) && $manifest !== []) {
            $maxNewUploads = 0;
            foreach ($manifest as $slot) {
                if (is_array($slot) && (string) ($slot['kind'] ?? '') === 'new') {
                    $maxNewUploads++;
                }
            }
        } else {
            $keptCount = 0;
            foreach ((array) ($post['existing_images'] ?? []) as $rawPath) {
                $path = $this->normalizePropertyImagePath((string) $rawPath);
                if ($path !== null && isset($allowedCurrent[$path])) {
                    $keptCount++;
                }
            }
            if ($keptCount === 0 && !array_key_exists('existing_images', $post)) {
                $keptCount = count($allowedCurrent);
            }
            $maxNewUploads = max(0, 8 - $keptCount);
        }

        if ($maxNewUploads === 0) {
            $attemptedUploads = $this->countSubmittedPropertyImageUploads($files);
            $uploaded = $attemptedUploads > 0
                ? ['paths' => [], 'errors' => ['Limite de 8 imagens atingido. Remova uma imagem antes de adicionar outra.']]
                : ['paths' => [], 'errors' => []];
        } else {
            $uploaded = $this->processPropertyImages($files, $maxNewUploads);
        }

        $final = [];

        if (is_array($manifest) && $manifest !== []) {
            $newIdx = 0;
            foreach ($manifest as $slot) {
                if (!is_array($slot)) {
                    continue;
                }

                $kind = (string) ($slot['kind'] ?? '');
                if ($kind === 'existing') {
                    $path = $this->normalizePropertyImagePath((string) ($slot['path'] ?? ''));
                    if ($path !== null && isset($allowedCurrent[$path])) {
                        $final[] = $path;
                    }
                } elseif ($kind === 'new' && isset($uploaded['paths'][$newIdx])) {
                    $final[] = $uploaded['paths'][$newIdx];
                    $newIdx++;
                }
            }
        } else {
            $kept = [];
            foreach ((array) ($post['existing_images'] ?? []) as $rawPath) {
                $path = $this->normalizePropertyImagePath((string) $rawPath);
                if ($path !== null && isset($allowedCurrent[$path])) {
                    $kept[] = $path;
                }
            }

            if ($kept === [] && !array_key_exists('existing_images', $post) && $allowedCurrent !== []) {
                $kept = array_values($allowedCurrent);
            }

            $final = array_merge($kept, $uploaded['paths']);
        }

        $final = array_values(array_unique($final));
        $hasNewUploads = $uploaded['paths'] !== [];
        $galleryTouched = ($post['images_gallery_touched'] ?? '') === '1';

        // Sem alterações na galeria nem ficheiros novos: manter imagens actuais.
        if (!$galleryTouched && !$hasNewUploads && $allowedCurrent !== []) {
            $final = $this->preserveCurrentPropertyImages($currentImages, $allowedCurrent);
        } elseif ($final === [] && !$hasNewUploads && $allowedCurrent !== [] && !$galleryTouched) {
            $final = $this->preserveCurrentPropertyImages($currentImages, $allowedCurrent);
        }

        if (count($final) > 8) {
            $errors[] = 'O imóvel pode ter no máximo 8 imagens.';
        }

        if ($final === []) {
            $errors[] = 'Mantenha pelo menos uma imagem no anúncio.';
        }

        foreach ($allowedCurrent as $path) {
            if (!in_array($path, $final, true)) {
                $this->deletePropertyImageFile($path);
            }
        }

        return [
            'paths' => $final,
            'errors' => array_merge($uploaded['errors'], $errors),
        ];
    }

    private function countSubmittedPropertyImageUploads(array $files): int
    {
        if (empty($files['name']) || !is_array($files['name'])) {
            return 0;
        }

        $actualUploadCount = 0;
        foreach ($files['name'] as $index => $_name) {
            $errorCode = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            if ($errorCode !== UPLOAD_ERR_NO_FILE) {
                $actualUploadCount++;
            }
        }

        return $actualUploadCount;
    }

    private function processPropertyImages(array $files, int $maxFiles = 8): array
    {
        $savedPaths = [];
        $errors = [];

        if (empty($files) || !isset($files['name']) || !is_array($files['name'])) {
            return ['paths' => [], 'errors' => []];
        }

        $allowedMime = ['image/webp'];
        $maxPerFile = 3 * 1024 * 1024;
        $count = count($files['name']);
        $maxFiles = max(0, $maxFiles);
        $actualUploadCount = $this->countSubmittedPropertyImageUploads($files);

        if ($actualUploadCount === 0) {
            return ['paths' => [], 'errors' => []];
        }

        if ($actualUploadCount > $maxFiles) {
            $message = $maxFiles === 8
                ? 'Envie no máximo 8 imagens.'
                : ($maxFiles === 0
                    ? 'Limite de 8 imagens atingido. Remova uma imagem antes de adicionar outra.'
                    : 'Pode adicionar no máximo ' . $maxFiles . ' imagem(ns) nesta actualização.');
            return ['paths' => [], 'errors' => [$message]];
        }

        $uploadDir = DIRREQ . 'public/storage/uploads/properties/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['paths' => [], 'errors' => ['Não foi possível preparar a pasta de imagens.']];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $count; $i++) {
            $errorCode = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($errorCode === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($errorCode !== UPLOAD_ERR_OK) {
                $errors[] = $this->normalizeUploadError($errorCode);
                continue;
            }

            $tmpName = (string) ($files['tmp_name'][$i] ?? '');
            $size = (int) ($files['size'][$i] ?? 0);

            if ($size <= 0 || $size > $maxPerFile) {
                $errors[] = 'Cada imagem deve ter até 3MB.';
                continue;
            }

            $mime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
            if (!in_array($mime, $allowedMime, true)) {
                $errors[] = 'Formato de imagem inválido. Envie imagens em WEBP.';
                continue;
            }

            try {
                $randomSuffix = bin2hex(random_bytes(4));
            } catch (\Exception $e) {
                $randomSuffix = substr(md5(uniqid('', true)), 0, 8);
            }

            $filename = 'property_' . time() . '_' . $randomSuffix . '.webp';
            $destination = $uploadDir . $filename;

            if (!move_uploaded_file($tmpName, $destination)) {
                $errors[] = 'Falha ao salvar uma imagem enviada.';
                continue;
            }

            $savedPaths[] = 'public/storage/uploads/properties/' . $filename;
        }

        if ($finfo) {
            finfo_close($finfo);
        }

        return ['paths' => $savedPaths, 'errors' => $errors];
    }
    public function index()
    {
        $filters = $_GET;
        $cursorRaw = isset($filters['cursor']) ? (string) $filters['cursor'] : '';
        $cursor = $this->decodeCursor($cursorRaw);
        unset($filters['cursor']);
        // Cursor pagination is forward-only and uses a stable order.
        unset($filters['page']);

        if (ClassCookieConsent::hasBehavioralConsent()) {
            $filters['viewer_visitor_key'] = ClassSession::getOrCreateVisitorKey();
            if (ClassAuth::check()) {
                $filters['viewer_user_id'] = (int) (ClassAuth::user()['id'] ?? 0);
            }
        }
        if (!empty($filters['country_id'])) {
            $filters['country_id'] = (int) $filters['country_id'];
        }
        if (!empty($filters['region_id'])) {
            $filters['region_id'] = (int) $filters['region_id'];
        }
        $perPage = 12;
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $discoveryPersonalized = false;
        $continueExploring = [];

        $cursorMode = $cursor !== null;
        $nextCursor = null;
        if ($cursorMode) {
            $properties = Property::getFilteredCursor(
                $filters,
                $perPage,
                isset($cursor['created_at']) ? (string) $cursor['created_at'] : null,
                isset($cursor['id']) ? (int) $cursor['id'] : null
            );
            if (!empty($properties)) {
                $last = $properties[count($properties) - 1];
                $nextCursor = $this->encodeCursor($last['created_at'] ?? null, isset($last['id']) ? (int) $last['id'] : null);
            }
            $totalProperties = count($properties);
            $totalPages = 1;
            $page = 1;
        } else {
            $totalProperties = Property::countFiltered($filters);
            $listing = DiscoveryEngine::propertyListingPage($filters, $page, $perPage);
            $properties = $listing['properties'];
            $discoveryPersonalized = (bool) ($listing['discoveryPersonalized'] ?? false);
            $continueExploring = $listing['continueExploring'] ?? [];
            $totalPages = max(1, (int) ceil($totalProperties / $perPage));
        }

        $favoriteIds = [];

        if (!$cursorMode && $page > $totalPages) {
            $page = $totalPages;
            $listing = DiscoveryEngine::propertyListingPage($filters, $page, $perPage);
            $properties = $listing['properties'];
            $discoveryPersonalized = (bool) ($listing['discoveryPersonalized'] ?? false);
            $continueExploring = $listing['continueExploring'] ?? [];
        }

        if (ClassAuth::check()) {
            $favoriteIds = Favorite::getPropertyIdsByUser(ClassAuth::user()['id']);
        }

        $render = new ClassRender();

        // SEO Configuration for listing page
        $pageNum = $page > 1 ? ' - Página ' . $page : '';
        $render->setTitle('Imóveis Disponíveis' . $pageNum);
        $render->setDescription('Encontre e compare imóveis disponíveis. Anuncie a sua propriedade com segurança e facilidade na Imobil Fácil.');
        $render->setKeywords(ClassSEO::DEFAULT_KEYWORDS);
        $render->setOgTitle('Imóveis Disponíveis' . $pageNum);
        $render->setOgDescription('Procure imóveis por localização, preço e características. Negocie com segurança na nossa plataforma.');
        $render->setCanonical(DIRPAGE . 'properties' . ($page > 1 ? '?page=' . $page : ''));

        // Add collection page structured data
        $itemsForSchema = array_map(function ($prop) {
            return ['url' => rtrim(DIRPAGE, '/') . '/property/' . $prop['id']];
        }, $properties);

        $collectionSchema = ClassSEO::getCollectionPageSchema(
            'Imóveis Disponíveis',
            'Encontre imóveis verificados na plataforma Imobil Fácil',
            $itemsForSchema,
            $page,
            $totalPages,
            'properties' . ($page > 1 ? '?page=' . $page : '')
        );
        $render->addStructuredData($collectionSchema);

        // Add breadcrumb
        $render->addStructuredData(ClassSEO::getBreadcrumbSchema([
            ['name' => 'Home', 'url' => rtrim(DIRPAGE, '/')],
            ['name' => 'Propriedades', 'url' => rtrim(DIRPAGE, '/') . '/properties'],
        ]));

        $render->setData([
            'properties' => $properties,
            'countries' => Country::getActive(),
            'regions' => Region::getActive(),
            'favoriteIds' => $favoriteIds,
            'page' => $page,
            'perPage' => $perPage,
            'totalProperties' => $totalProperties,
            'totalPages' => $totalPages,
            'discoveryPersonalized' => $discoveryPersonalized,
            'continueExploring' => $continueExploring,
            'cursorMode' => $cursorMode,
            'cursor' => $cursorRaw,
            'nextCursor' => $nextCursor,
        ]);
        $render->setDir('property/list');

        if (!ClassAuth::check() && !ClassCookieConsent::hasBehavioralConsent()) {
            $cacheTtl = max(30, ClassSettings::int('page_cache_property_list_ttl_seconds', 120));
            $html = PageCache::capture('property_list:' . $page . ':' . md5(http_build_query($filters)), $cacheTtl, function () use ($render) {
                ob_start();
                $render->renderLayout();
                return ob_get_clean();
            });
            echo $html;
            return;
        }

        $render->renderLayout();
    }

    public function properties()
    {
        // Alias para index()
        $this->index();
    }

    public function show($id)
    {
        $property = Property::find($id);
        if (!$property) {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        MetricEvent::track('property_page_view', [
            'entity_type' => 'property',
            'entity_id' => $property['id'] ?? null,
            'user_id' => ClassAuth::check() ? ClassAuth::user()['id'] : null,
            'metadata' => [
                'status' => $property['status'] ?? null,
                'affiliate_id' => $property['affiliate_id'] ?? null,
            ],
        ]);

        // Keep sold/rented details publicly accessible, but non-public moderation states remain restricted.
        $isModerator = ClassAuth::check() && ClassAccess::can('properties.moderate');
        $publicStatuses = ['disponivel', 'vendido', 'alugado'];
        $isPubliclyVisible = in_array((string) ($property['status'] ?? ''), $publicStatuses, true);
        $isOwner = ClassAuth::check()
            && (int) (ClassAuth::user()['id'] ?? 0) === (int) ($property['affiliate_id'] ?? 0);

        if (!$isPubliclyVisible && !$isModerator && !$isOwner) {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        $isFavorite = false;

        // Check for referral
        if (isset($_GET['ref'])) {
            $affiliate = User::findByAffiliateCode($_GET['ref']);
            if ($affiliate && !empty($affiliate['is_affiliate']) && ($affiliate['status'] ?? '') === 'ativo') {
                ClassSession::set('referred_by', $affiliate['id']);
            }
        }

        $isAffiliate = false;
        $hasAffiliateRequest = false;
        $affiliateStatus = null; // 'pendente', 'ativo', 'rejeitado'
        $hasActiveRequest = false;
        $viewerUserId = null;
        if (ClassAuth::check()) {
            $viewerUserId = (int) (ClassAuth::user()['id'] ?? 0);
            $isFavorite = Favorite::exists(ClassAuth::user()['id'], (int) $property['id']);
            $isAffiliate = PropertyAffiliate::isActiveAffiliate(ClassAuth::user()['id'], (int) $property['id']);
            $hasAffiliateRequest = PropertyAffiliate::exists(ClassAuth::user()['id'], (int) $property['id']);
            $affiliateStatus = $hasAffiliateRequest
                ? PropertyAffiliate::getStatusForUser((int) ClassAuth::user()['id'], (int) $property['id'])
                : null;
            $hasActiveRequest = Request::hasActiveRequest((int) ClassAuth::user()['id'], (int) $property['id']);

        }

        if (ClassCookieConsent::hasBehavioralConsent()) {
            $visitorKey = ClassSession::getOrCreateVisitorKey();
            // Lightweight behavioral signal for personalized discovery ranking.
            PropertyBehaviorEvent::track($viewerUserId, (int) ($property['id'] ?? 0), 'view', $visitorKey);
        }

        $render = new ClassRender();

        // SEO Configuration
        $title = (string) ($property['title'] ?? 'Imóvel');
        $description = ClassSEO::excerptFromText($property['description'] ?? '');
        $propertyType = (string) ($property['type'] ?? 'imóvel');
        $location = trim((string) ($property['location'] ?? ''));
        $titleSuffix = $location !== '' ? ' — ' . $location : '';

        $render->setTitle($title . $titleSuffix);
        $render->setDescription($description);
        $render->setKeywords('imóvel angola, ' . $propertyType . ', ' . $location . ', venda, aluguer');
        $render->setOgTitle($title);
        $render->setOgDescription($description);
        $render->setOgImage(ClassSEO::propertyImageUrl($property));
        $render->setOgType('product');
        $render->setCanonical(DIRPAGE . 'property/' . $property['id']);

        // Add property structured data (RealEstateProperty schema)
        $propertySchema = ClassSEO::getPropertySchema($property);
        $render->addStructuredData($propertySchema);

        // Add breadcrumb navigation
        $render->addStructuredData(ClassSEO::getBreadcrumbSchema([
            ['name' => 'Home', 'url' => rtrim(DIRPAGE, '/')],
            ['name' => 'Propriedades', 'url' => rtrim(DIRPAGE, '/') . '/properties'],
            ['name' => $title, 'url' => rtrim(DIRPAGE, '/') . '/property/' . $property['id']],
        ]));

        $render->setData([
            'property' => $property,
            'isFavorite' => $isFavorite,
            'isAffiliate' => $isAffiliate,
            'hasAffiliateRequest' => $hasAffiliateRequest,
            'affiliateStatus' => $affiliateStatus,
            'hasActiveRequest' => $hasActiveRequest,
            'hasBlockingOverdueCommissions' => ClassCommissionGuard::currentUserHasBlockingOverdue(),
            'canSubmitPropertyRequest' => ClassAuth::check() && ClassAccess::canSubmitPropertyRequest(),
            'hasLimitedAccountAccess' => ClassAuth::check() && ClassAccess::hasLimitedPlatformAccess(),
        ]);
        $render->setDir('property/show');

        if (!ClassAuth::check()) {
            $cacheTtl = max(60, ClassSettings::int('page_cache_property_show_ttl_seconds', 180));
            $html = PageCache::capture('property_show:' . (int) $id, $cacheTtl, function () use ($render) {
                ob_start();
                $render->renderLayout();
                return ob_get_clean();
            });
            echo $html;
            return;
        }

        $render->renderLayout();
    }

    public function create()
    {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não podem cadastrar imóveis');
        $userId = (int) ($user['id'] ?? 0);

        $render = new ClassRender();
        $render->setTitle('Cadastrar Imóvel');
        $render->setDescription('Adicione um novo imóvel');
        $render->setKeywords('cadastrar, imóvel');
        $render->setData([
            'countries' => Country::getActive(),
            'regions' => Region::getActive(),
            'userPlan' => ClassPlan::getOfficialPlanByUser($userId),
            'planCatalog' => SubscriptionPlan::getActiveCatalog(),
            'commissionSystemOnlyPct' => ClassSettings::float('commission_system_only_pct', 5.0),
        ]);
        $render->setDir('property/create');
        $render->renderLayout();
    }

    public function store()
    {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não podem cadastrar imóveis');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'properties');
            exit;
        }

        if (ClassCommissionGuard::currentUserHasBlockingOverdue()) {
            header('Location: ' . DIRPAGE . 'dashboard/commissionPayments');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'property/create?error=Token inválido');
            exit;
        }

        $currentCount = Property::countActiveByOwner((int) $user['id']);
        $limitCheck = ClassPlan::canPublishProperty((int) $user['id'], $currentCount);
        if (empty($limitCheck['allowed'])) {
            $maxProperties = isset($limitCheck['max']) ? (int) $limitCheck['max'] : 0;
            $planName = (string) (($limitCheck['plan']['name'] ?? 'Plano Essencial'));
            $errorMessage = 'O ' . $planName . ' permite ate ' . $maxProperties . ' imoveis ativos. Voce ja atingiu o limite.';
            header('Location: ' . DIRPAGE . 'property/create?error=' . urlencode($errorMessage));
            exit;
        }

        $plan = ClassPlan::getOfficialPlanByUser((int) $user['id']);

        $allowedRentTerms = ['mensal', 'trimestral', 'semestral', 'anual'];
        $selectedRentTerms = array_values(array_unique(array_filter(
            (array) ($_POST['rent_payment_terms'] ?? []),
            static function ($term) use ($allowedRentTerms) {
                return in_array((string) $term, $allowedRentTerms, true);
            }
        )));

        $purpose = (string) ($_POST['purpose'] ?? '');
        $rentPaymentTerms = $purpose === 'aluguer_longo'
            ? json_encode($selectedRentTerms, JSON_UNESCAPED_UNICODE)
            : null;
        $affiliateApprovalMode = (string) ($_POST['affiliate_approval_mode'] ?? Property::AFFILIATE_APPROVAL_AUTO);
        if (!in_array($affiliateApprovalMode, [Property::AFFILIATE_APPROVAL_MANUAL, Property::AFFILIATE_APPROVAL_AUTO, Property::AFFILIATE_APPROVAL_DISABLED], true)) {
            $affiliateApprovalMode = Property::AFFILIATE_APPROVAL_AUTO;
        }

        $uploadResult = $this->processPropertyImages($_FILES['images'] ?? []);
        if (!empty($uploadResult['errors'])) {
            header('Location: ' . DIRPAGE . 'property/create?error=' . urlencode(implode(' ', $uploadResult['errors'])));
            exit;
        }
        if (empty($uploadResult['paths'])) {
            header('Location: ' . DIRPAGE . 'property/create?error=' . urlencode('Envie pelo menos 1 imagem do imóvel.'));
            exit;
        }

        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'type' => $_POST['type'] ?? '',
            'purpose' => $purpose,
            'rent_payment_terms' => $rentPaymentTerms,
            'rental_days' => $purpose === 'aluguer_curto' ? (int) ($_POST['rental_days'] ?? 0) : null,
            'rental_months' => $purpose === 'aluguer_longo' ? (int) ($_POST['rental_months'] ?? 0) : null,
            'price' => $_POST['price'] ?? '',
            'country_id' => !empty($_POST['country_id']) ? (int) $_POST['country_id'] : null,
            'location' => $_POST['location'] ?? '',
            'region_id' => !empty($_POST['region_id']) ? (int) $_POST['region_id'] : null,
            'bedrooms' => (int) ($_POST['bedrooms'] ?? 0),
            'bathrooms' => (int) ($_POST['bathrooms'] ?? 0),
            'area' => ($_POST['area'] ?? '') === '' ? null : (float) $_POST['area'],
            'images' => json_encode($uploadResult['paths'], JSON_UNESCAPED_SLASHES),
            'video_url' => $_POST['video_url'] ?? '',
            'affiliate_approval_mode' => $affiliateApprovalMode,
            'visibility' => ClassPlan::mapPlanToPropertyVisibility($plan),
            'affiliate_id' => $user['id'],
            'status' => 'pendente',
        ];

        $errors = Property::validateData($data);
        if (!empty($errors)) {
            // Handle errors, perhaps redirect with errors
            header('Location: ' . DIRPAGE . 'property/create?error=' . urlencode(implode(', ', $errors)));
            exit;
        }

        $propertyId = Property::create($data);
        if ($propertyId) {
            Log::create([
                'user_id' => $user['id'],
                'action' => 'create_property',
                'entity_type' => 'property',
                'entity_id' => $propertyId,
                'details' => 'Imóvel cadastrado aguardando aprovação',
            ]);
            header('Location: ' . DIRPAGE . 'dashboard?success=Imóvel cadastrado com sucesso, aguardando aprovação');
            exit;
        } else {
            header('Location: ' . DIRPAGE . 'property/create?error=Erro ao cadastrar imóvel');
            exit;
        }
    }

    public function moderate()
    {
        $user = ClassAccess::requirePermission('properties.moderate', 'dashboard', 'Acesso disponível apenas para moderação');

        $perPage = 20;
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $pendingTotal  = Property::countPending();
        $pending       = Property::getPending($perPage, $offset);
        $totalPages    = (int) ceil($pendingTotal / $perPage);

        $pendingBoostsTotal = PropertyBoostRequest::countPending();
        $pendingBoosts      = PropertyBoostRequest::getPending(5, 0);

        $render = new ClassRender();
        $render->setTitle('Moderação de Imóveis');
        $render->setDescription('Aprovar ou rejeitar imóveis pendentes');
        $render->setKeywords('moderação, imóveis');
        $render->setData([
            'pending'             => $pending,
            'pendingTotal'        => $pendingTotal,
            'page'                => $page,
            'totalPages'          => $totalPages,
            'pendingBoosts'       => $pendingBoosts,
            'pendingBoostsTotal'  => $pendingBoostsTotal,
        ]);
        $render->setDir('property/moderate');
        $render->renderLayout();
    }

    public function startAnalysis($id)
    {
        $user = ClassAccess::requirePermission('properties.moderate', 'dashboard', 'Acesso disponível apenas para moderação');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'property/moderate?error=Token inválido');
            exit;
        }

        $id = (int) $id;
        if (Property::startReview($id)) {
            Log::create([
                'user_id' => $user['id'],
                'action' => 'start_property_review',
                'entity_type' => 'property',
                'entity_id' => $id,
                'details' => 'Imóvel movido para em análise',
            ]);
            header('Location: ' . DIRPAGE . 'property/moderate?success=Imóvel colocado em análise');
            exit;
        }

        header('Location: ' . DIRPAGE . 'property/moderate?error=Transição inválida: apenas imóveis pendentes podem entrar em análise');
        exit;
    }

    public function approve($id)
    {
        $user = ClassAccess::requirePermission('properties.moderate', 'dashboard', 'Acesso disponível apenas para moderação');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'property/moderate?error=Token inválido');
            exit;
        }

        $id = (int) $id;

        if (Property::approve($id)) {
            Log::create([
                'user_id' => $user['id'],
                'action' => 'approve_property',
                'entity_type' => 'property',
                'entity_id' => $id,
                'details' => 'Imóvel aprovado',
            ]);
            header('Location: ' . DIRPAGE . 'property/moderate?success=Imóvel aprovado e visível');
            exit;
        }

        header('Location: ' . DIRPAGE . 'property/moderate?error=Transição inválida: apenas imóveis em análise podem ser aprovados');
        exit;
    }

    public function reject($id)
    {
        $user = ClassAccess::requirePermission('properties.moderate', 'dashboard', 'Acesso disponível apenas para moderação');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'property/moderate?error=Token inválido');
            exit;
        }

        $id = (int) $id;

        if (Property::reject($id)) {
            Log::create([
                'user_id' => $user['id'],
                'action' => 'reject_property',
                'entity_type' => 'property',
                'entity_id' => $id,
                'details' => 'Imóvel rejeitado',
            ]);
            header('Location: ' . DIRPAGE . 'property/moderate?success=Imóvel rejeitado na moderação');
            exit;
        }

        header('Location: ' . DIRPAGE . 'property/moderate?error=Transição inválida: apenas imóveis em análise podem ser rejeitados');
        exit;
    }

    // ─── Fase 4: Página institucional / agência (Enterprise) ─────────────────

    public function agency($agencyUserId = null)
    {
        $agencyUserId = $this->resolveRouteInt($agencyUserId, 2);

        if ($agencyUserId <= 0) {
            header('Location: ' . DIRPAGE . 'properties');
            exit;
        }

        $agencyUser = User::findById($agencyUserId);
        if (!$agencyUser || (string) ($agencyUser['status'] ?? '') !== 'ativo') {
            header('HTTP/1.0 404 Not Found');
            $render = new ClassRender();
            $render->setTitle('Página não encontrada');
            $render->setDir('404');
            $render->renderLayout();
            return;
        }

        if (!ClassPlan::canUseInstitutionalPage($agencyUserId)) {
            header('Location: ' . DIRPAGE . 'property/owner/' . $agencyUserId, true, 302);
            exit;
        }

        $trustMetrics = User::getTrustMetrics($agencyUserId);
        $officialPlan = ClassPlan::getOfficialPlanByUser($agencyUserId);
        $properties = Property::getByAffiliate($agencyUserId);
        $properties = array_values(array_filter($properties, static fn ($p) => ($p['status'] ?? '') === 'disponivel'));

        $favoriteIds = [];
        if (ClassAuth::check()) {
            $favoriteIds = Favorite::getPropertyIdsByUser((int) (ClassAuth::user()['id'] ?? 0));
        }

        $portfolioStats = [
            'total' => count($properties),
            'for_sale' => 0,
            'for_rent' => 0,
            'min_price' => null,
            'max_price' => null,
        ];
        $locationCounts = [];
        $typeCounts = [];
        $featuredProperties = [];

        foreach ($properties as $property) {
            $purpose = (string) ($property['purpose'] ?? '');
            if (str_starts_with($purpose, 'aluguer')) {
                $portfolioStats['for_rent']++;
            } else {
                $portfolioStats['for_sale']++;
            }
            $price = (float) ($property['price'] ?? 0);
            if ($price > 0) {
                $portfolioStats['min_price'] = $portfolioStats['min_price'] === null
                    ? $price
                    : min($portfolioStats['min_price'], $price);
                $portfolioStats['max_price'] = $portfolioStats['max_price'] === null
                    ? $price
                    : max($portfolioStats['max_price'], $price);
            }

            $location = trim((string) ($property['location'] ?? ''));
            if ($location !== '') {
                $locationCounts[$location] = ($locationCounts[$location] ?? 0) + 1;
            }

            $typeCode = trim((string) ($property['type'] ?? ''));
            if ($typeCode !== '') {
                $typeCounts[$typeCode] = ($typeCounts[$typeCode] ?? 0) + 1;
            }

            if (!empty($property['featured']) && count($featuredProperties) < 3) {
                $featuredProperties[] = $property;
            }
        }

        arsort($locationCounts);
        $topLocations = array_slice($locationCounts, 0, 6, true);

        arsort($typeCounts);
        $topTypes = [];
        $typeLabels = PropertyTypeHelper::getTypeLabels();
        foreach (array_slice($typeCounts, 0, 5, true) as $code => $count) {
            $topTypes[] = [
                'code' => $code,
                'label' => $typeLabels[$code] ?? ucfirst($code),
                'count' => $count,
            ];
        }

        $memberSinceYear = null;
        if (!empty($agencyUser['created_at'])) {
            $ts = strtotime((string) $agencyUser['created_at']);
            if ($ts) {
                $memberSinceYear = (int) date('Y', $ts);
            }
        }

        $isJuridica = (string) ($agencyUser['user_type'] ?? '') === 'pessoa_juridica';
        $documentNumber = trim((string) ($agencyUser['document_number'] ?? ''));
        $documentLabel = '';
        if ($isJuridica && $documentNumber !== '') {
            $documentLabel = strlen($documentNumber) > 6
                ? substr($documentNumber, 0, 3) . '···' . substr($documentNumber, -3)
                : $documentNumber;
        }

        $agencyName = (string) ($agencyUser['name'] ?? 'Agência');
        $agencyDescription = ($isJuridica ? 'Empresa imobiliária' : 'Profissional imobiliário')
            . ' com catálogo de imóveis disponíveis: '
            . $agencyName
            . '.';
        $render = new ClassRender();
        $render->setTitle($agencyName . ($isJuridica ? ' — Empresa' : ' — Profissional'));
        $render->setDescription($agencyDescription);
        $render->setKeywords('agência imobiliária angola, promotor, imóveis, ' . $agencyName);
        $render->setCanonical(ClassPlan::getPublicProfileUrl($agencyUserId));
        $render->setOgTitle($agencyName . ' — Imobil Fácil');
        $render->setOgDescription($agencyDescription);
        $render->setOgImage(ClassSEO::defaultOgImage());
        $render->addStructuredData(ClassSEO::getBreadcrumbSchema([
            ['name' => 'Início', 'url' => DIRPAGE],
            ['name' => 'Imóveis', 'url' => DIRPAGE . 'properties'],
            ['name' => $agencyName, 'url' => ClassPlan::getPublicProfileUrl($agencyUserId)],
        ]));
        $render->setData([
            'agencyUser' => $agencyUser,
            'properties' => $properties,
            'trustMetrics' => $trustMetrics,
            'portfolioStats' => $portfolioStats,
            'favoriteIds' => $favoriteIds,
            'publicProfileUrl' => ClassPlan::getPublicProfileUrl($agencyUserId),
            'topLocations' => $topLocations,
            'topTypes' => $topTypes,
            'featuredProperties' => $featuredProperties,
            'memberSinceYear' => $memberSinceYear,
            'documentLabel' => $documentLabel,
            'isJuridica' => $isJuridica,
        ]);
        $render->setDir('property/agency');
        $render->renderLayout();
    }

    public function featured()
    {
        $perPage = 12;
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $totalFeatured = Property::countFeatured();
        $hasBehaviorConsent = ClassCookieConsent::hasBehavioralConsent();
        $viewerUserId = ($hasBehaviorConsent && ClassAuth::check()) ? (int) (ClassAuth::user()['id'] ?? 0) : null;
        $visitorKey = $hasBehaviorConsent ? ClassSession::getOrCreateVisitorKey() : null;

        $listing = DiscoveryEngine::featuredListingPage($page, $perPage, $viewerUserId, $visitorKey);
        $properties = $listing['properties'];
        $discoveryPersonalized = (bool) ($listing['discoveryPersonalized'] ?? false);
        $continueExploring = $listing['continueExploring'] ?? [];

        $favoriteIds = [];
        $totalPages = max(1, (int) ceil($totalFeatured / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $listing = DiscoveryEngine::featuredListingPage($page, $perPage, $viewerUserId, $visitorKey);
            $properties = $listing['properties'];
            $discoveryPersonalized = (bool) ($listing['discoveryPersonalized'] ?? false);
            $continueExploring = $listing['continueExploring'] ?? [];
        }

        if (ClassAuth::check()) {
            $favoriteIds = Favorite::getPropertyIdsByUser(ClassAuth::user()['id']);
        }

        $pageLabel = $page > 1 ? ' — Página ' . $page : '';
        $render = new ClassRender();
        $render->setTitle('Imóveis em Destaque' . $pageLabel);
        $render->setDescription('Imóveis patrocinados e em destaque na Imobil Fácil. Descubra as melhores oportunidades em Angola.');
        $render->setKeywords('imóveis destaque, patrocinados, angola, ' . ClassSEO::DEFAULT_KEYWORDS);
        $render->setOgTitle('Imóveis em Destaque — Imobil Fácil');
        $render->setOgDescription('Seleção de imóveis em destaque com maior visibilidade na plataforma.');
        $render->setOgImage(ClassSEO::defaultOgImage());
        $render->setCanonical(DIRPAGE . 'featured' . ($page > 1 ? '?page=' . $page : ''));
        $render->addStructuredData(ClassSEO::getBreadcrumbSchema([
            ['name' => 'Início', 'url' => rtrim(DIRPAGE, '/')],
            ['name' => 'Destaques', 'url' => rtrim(DIRPAGE, '/') . '/featured'],
        ]));
        $render->setData([
            'properties' => $properties,
            'favoriteIds' => $favoriteIds,
            'page' => $page,
            'perPage' => $perPage,
            'totalFeatured' => $totalFeatured,
            'totalPages' => $totalPages,
            'discoveryPersonalized' => $discoveryPersonalized,
            'continueExploring' => $continueExploring,
        ]);
        $render->setDir('property/featured');
        $render->renderLayout();
    }

    public function favorite($id)
    {
        ClassAuth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('properties', 'Token inválido');
        }

        Favorite::add(ClassAuth::user()['id'], (int) $id);
        if (ClassCookieConsent::hasBehavioralConsent()) {
            PropertyBehaviorEvent::track(
                (int) (ClassAuth::user()['id'] ?? 0),
                (int) $id,
                'favorite',
                ClassSession::getOrCreateVisitorKey()
            );
        }
        $redirect = $_SERVER['HTTP_REFERER'] ?? (DIRPAGE . 'property/' . (int) $id);
        header('Location: ' . $redirect);
        exit;
    }

    public function unfavorite($id)
    {
        ClassAuth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('properties', 'Token inválido');
        }

        Favorite::remove(ClassAuth::user()['id'], (int) $id);
        $redirect = $_SERVER['HTTP_REFERER'] ?? (DIRPAGE . 'property/' . (int) $id);
        header('Location: ' . $redirect);
        exit;
    }

    public function affiliateRequest($id)
    {
        ClassAuth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('property/' . (int) $id, 'Token inválido');
        }

        $user = ClassAuth::user();
        $propertyId = (int) $id;

        // Check if property exists
        $property = Property::find($propertyId);
        if (!$property) {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        // Can't request affiliation to own property
        if ($property['affiliate_id'] == $user['id']) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Você é o proprietário deste imóvel');
            exit;
        }

        // Must have the affiliate profile enabled first
        if (empty($user['is_affiliate'])) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Active o perfil de promotor no seu dashboard antes de solicitar afiliação');
            exit;
        }

        // Check if already affiliate (pendente or ativo)
        if (PropertyAffiliate::exists($user['id'], $propertyId)) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Você já tem uma solicitação de afiliação para este imóvel');
            exit;
        }

        $approvalMode = (string) ($property['affiliate_approval_mode'] ?? Property::AFFILIATE_APPROVAL_AUTO);
        if (!in_array($approvalMode, [Property::AFFILIATE_APPROVAL_MANUAL, Property::AFFILIATE_APPROVAL_AUTO, Property::AFFILIATE_APPROVAL_DISABLED], true)) {
            $approvalMode = Property::AFFILIATE_APPROVAL_AUTO;
        }
        if ($approvalMode === Property::AFFILIATE_APPROVAL_DISABLED) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Este+imovel+nao+aceita+afiliacoes');
            exit;
        }
        $initialStatus = $approvalMode === Property::AFFILIATE_APPROVAL_AUTO ? 'ativo' : 'pendente';

        // Create affiliate request
        PropertyAffiliate::create([
            'user_id' => $user['id'],
            'property_id' => $propertyId,
            'status' => $initialStatus,
        ]);

        // Log action
        Log::create([
            'user_id' => $user['id'],
            'action' => 'Solicitou afiliação em um imóvel',
            'entity_type' => 'property_affiliate',
            'entity_id' => $propertyId,
            'details' => 'Solicitação de afiliação para a propriedade: ' . $property['title'] . ' | Modo: ' . $approvalMode,
        ]);

        if ($initialStatus === 'ativo') {
            Notification::notifyUser(
                (int) ($user['id'] ?? 0),
                'affiliate_approved',
                'Afiliação aprovada automaticamente',
                'A sua afiliação ao imóvel "' . ($property['title'] ?? '') . '" foi aprovada automaticamente pelo proprietário.',
                ['property_id' => (int) $propertyId],
                (int) ($property['affiliate_id'] ?? 0)
            );

            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?success=Afiliacao aprovada automaticamente. Pode começar a indicar este imóvel');
            exit;
        }

        header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?success=Solicitação de afiliação enviada com sucesso');
        exit;
    }

    public function getAffiliationTerms()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(PropertyAffiliate::getAffiliationTerms());
        exit;
    }

    public function owner($id)
    {
        $ownerId = $this->resolveRouteInt($id, 2);
        if ($ownerId > 0 && ClassPlan::canUseInstitutionalPage($ownerId)) {
            header('Location: ' . ClassPlan::getPublicProfileUrl($ownerId), true, 302);
            exit;
        }

        $owner = User::findById($ownerId);
        if (!$owner || ($owner['status'] ?? '') !== 'ativo') {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        $trustMetrics = User::getTrustMetrics($ownerId);
        $officialPlan = ClassPlan::getOfficialPlanByUser($ownerId);
        $properties = Property::getByAffiliate($ownerId);
        $favoriteIds = [];
        // Only show available properties to public
        $properties = array_filter($properties, fn ($p) => ($p['status'] ?? '') === 'disponivel');

        if (ClassAuth::check()) {
            $favoriteIds = Favorite::getPropertyIdsByUser((int) (ClassAuth::user()['id'] ?? 0));
        }

        $render = new ClassRender();
        $ownerTitle = \Src\classes\UserDisplay::handleWithAt($owner);
        if ($ownerTitle === '') {
            $ownerTitle = \Src\classes\UserDisplay::publicLabel($owner);
        }
        $render->setTitle('Perfil de ' . $ownerTitle);
        $render->setDescription('Conheça o proprietário e os seus imóveis disponíveis.');
        $render->setKeywords('proprietário, imóveis, perfil');
        $render->setData([
            'owner' => $owner,
            'trustMetrics' => $trustMetrics,
            'officialPlan' => $officialPlan,
            'properties' => array_values($properties),
            'favoriteIds' => $favoriteIds,
        ]);
        $render->setDir('property/owner');
        $render->renderLayout();
    }

    // -----------------------------------------------------------------------
    // Owner property management
    // -----------------------------------------------------------------------

    public function edit($id)
    {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores nao podem editar imoveis');

        $property = Property::find((int) $id);
        if (!$property) {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        if ((int) ($property['affiliate_id'] ?? 0) !== (int) $user['id']) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Sem+permissao+para+editar+este+imovel');
            exit;
        }

        $boostRequests   = PropertyBoostRequest::getByProperty((int) $id);
        $hasPendingBoost = PropertyBoostRequest::alreadyPending((int) $id);

        $render = new ClassRender();
        $render->setTitle('Editar Imovel');
        $render->setDescription('Actualize os dados do seu imovel');
        $render->setKeywords('editar, imovel');
        $render->setData([
            'property'        => $property,
            'countries'       => Country::getActive(),
            'regions'         => Region::getActive(),
            'boostRequests'   => $boostRequests,
            'hasPendingBoost' => $hasPendingBoost,
        ]);
        $render->setDir('property/edit');
        $render->renderLayout();
    }

    public function update($id)
    {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores nao podem editar imoveis');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'property/edit/' . (int) $id . '?error=Token+invalido');
            exit;
        }

        $property = Property::find((int) $id);
        if (!$property) {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        if ((int) ($property['affiliate_id'] ?? 0) !== (int) $user['id']) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Sem+permissao+para+editar+este+imovel');
            exit;
        }

        $lockedStatuses = ['vendido', 'alugado'];
        if (in_array($property['status'] ?? '', $lockedStatuses, true)) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Imovel+vendido+ou+alugado+nao+pode+ser+editado');
            exit;
        }

        $allowedRentTerms = ['mensal', 'trimestral', 'semestral', 'anual'];
        $selectedRentTerms = array_values(array_unique(array_filter(
            (array) ($_POST['rent_payment_terms'] ?? []),
            static function ($term) use ($allowedRentTerms) {
                return in_array((string) $term, $allowedRentTerms, true);
            }
        )));

        $imageResult = $this->resolvePropertyImagesForUpdate($property, $_POST, $_FILES['images'] ?? []);
        if (!empty($imageResult['errors'])) {
            header('Location: ' . DIRPAGE . 'property/edit/' . (int) $id . '?error=' . urlencode(implode(' ', $imageResult['errors'])));
            exit;
        }

        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $type        = trim($_POST['type']        ?? '');
        $purpose     = trim($_POST['purpose']     ?? '');
        $affiliateApprovalMode = (string) ($_POST['affiliate_approval_mode'] ?? Property::AFFILIATE_APPROVAL_AUTO);
        if (!in_array($affiliateApprovalMode, [Property::AFFILIATE_APPROVAL_MANUAL, Property::AFFILIATE_APPROVAL_AUTO, Property::AFFILIATE_APPROVAL_DISABLED], true)) {
            $affiliateApprovalMode = Property::AFFILIATE_APPROVAL_AUTO;
        }
        $location    = trim($_POST['location']    ?? '');
        $rentPaymentTerms = $purpose === 'aluguer_longo'
            ? json_encode($selectedRentTerms, JSON_UNESCAPED_UNICODE)
            : null;

        $newStatus = 'pendente';

        $updateData = [
            'title'           => $title,
            'description'     => $description,
            'type'            => $type,
            'purpose'         => $purpose,
            'rent_payment_terms' => $rentPaymentTerms,
            'rental_days' => $purpose === 'aluguer_curto' ? (int) ($_POST['rental_days'] ?? 0) : null,
            'rental_months' => $purpose === 'aluguer_longo' ? (int) ($_POST['rental_months'] ?? 0) : null,
            'price'           => (float) ($_POST['price'] ?? 0),
            'country_id'      => !empty($_POST['country_id']) ? (int) $_POST['country_id'] : null,
            'location'        => $location,
            'region_id'       => !empty($_POST['region_id']) ? (int) $_POST['region_id'] : null,
            'bedrooms'        => (int)   ($_POST['bedrooms']  ?? 0),
            'bathrooms'       => (int)   ($_POST['bathrooms'] ?? 0),
            'area'            => ($_POST['area'] ?? '') !== '' ? (float) $_POST['area'] : null,
            'video_url'       => trim($_POST['video_url'] ?? '') ?: null,
            'affiliate_approval_mode' => $affiliateApprovalMode,
            'status'          => $newStatus,
            'images'          => json_encode($imageResult['paths'], JSON_UNESCAPED_SLASHES),
        ];

        $errors = Property::validateData($updateData);
        if (!empty($errors)) {
            header('Location: ' . DIRPAGE . 'property/edit/' . (int) $id . '?error=' . urlencode(implode('. ', $errors)));
            exit;
        }

        Property::update((int) $id, $updateData);

        Log::create([
            'user_id'     => $user['id'],
            'action'      => 'update_property',
            'entity_type' => 'property',
            'entity_id'   => (int) $id,
            'details'     => 'Imovel actualizado - voltou para pendente de moderacao',
        ]);

        $msg = 'Imovel actualizado. Aguarda nova moderacao.';

        header('Location: ' . DIRPAGE . 'dashboard/myProperties?success=' . urlencode($msg));
        exit;
    }

    public function setStatus($id)
    {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores nao podem alterar estado de imoveis desta forma');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Token+invalido');
            exit;
        }

        $property = Property::find((int) $id);
        if (!$property) {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        if ((int) ($property['affiliate_id'] ?? 0) !== (int) $user['id']) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Sem+permissao');
            exit;
        }

        $newStatus = trim($_POST['new_status'] ?? '');
        if (!in_array($newStatus, ['vendido', 'alugado'], true)) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Estado+invalido');
            exit;
        }

        if (($property['status'] ?? '') !== 'disponivel') {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=So+e+possivel+marcar+imoveis+disponiveis+como+vendido+ou+alugado');
            exit;
        }

        Property::setStatus((int) $id, $newStatus);

        Request::closeActiveByPropertyClosure((int) $id, null);

        Log::create([
            'user_id'     => $user['id'],
            'action'      => 'set_property_status',
            'entity_type' => 'property',
            'entity_id'   => (int) $id,
            'details'     => 'Estado alterado para: ' . $newStatus,
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/myProperties?success=' . urlencode('Imovel marcado como ' . $newStatus . '.'));
        exit;
    }

    public function requestBoost($id)
    {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores nao podem solicitar destaque');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Token+invalido');
            exit;
        }

        $property = Property::find((int) $id);
        if (!$property) {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        if ((int) ($property['affiliate_id'] ?? 0) !== (int) $user['id']) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Sem+permissao');
            exit;
        }

        if (($property['status'] ?? '') !== 'disponivel') {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=So+e+possivel+solicitar+destaque+para+imoveis+disponiveis');
            exit;
        }

        if (PropertyBoostRequest::alreadyPending((int) $id)) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Ja+existe+uma+solicitacao+de+destaque+pendente+para+este+imovel');
            exit;
        }

        $config   = PropertyBoostRequest::getBoostPricingConfig();
        $days     = max($config['min_days'], min($config['max_days'], (int) ($_POST['duration_days'] ?? $config['default_days'])));
        $feeRequired = PropertyBoostRequest::calculateBoostFee($days);

        // Handle proof upload
        $proofFile = $_FILES['boost_payment_proof'] ?? null;
        if (empty($proofFile['tmp_name']) || ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Comprovativo+de+pagamento+obrigatorio');
            exit;
        }

        $finfo     = new \finfo(FILEINFO_MIME_TYPE);
        $proofMime = (string) $finfo->file((string) $proofFile['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($proofMime, $allowedMimes, true)) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Formato+invalido+use+JPG+PNG+ou+WebP');
            exit;
        }
        if ((int) ($proofFile['size'] ?? 0) > 512 * 1024) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Comprovativo+demasiado+grande+max+512KB');
            exit;
        }

        $proofUploadDirRelative = 'public/storage/uploads/boost_proofs/';
        $proofUploadDir = DIRREQ . $proofUploadDirRelative;
        if (!is_dir($proofUploadDir)) {
            mkdir($proofUploadDir, 0755, true);
        }
        $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $ext      = $extMap[$proofMime] ?? 'jpg';
        try {
            $suffix = bin2hex(random_bytes(6));
        } catch (\Throwable $e) {
            $suffix = substr(md5(uniqid('', true)), 0, 12);
        }
        $filename = 'boost_' . (int) $user['id'] . '_' . (int) $id . '_' . time() . '_' . $suffix . '.' . $ext;
        if (!move_uploaded_file((string) $proofFile['tmp_name'], $proofUploadDir . $filename)) {
            header('Location: ' . DIRPAGE . 'dashboard/myProperties?error=Erro+ao+guardar+comprovativo');
            exit;
        }
        $proofPath = $proofUploadDirRelative . $filename;

        PropertyBoostRequest::create((int) $id, (int) $user['id'], 'destaque', $days, $feeRequired, $proofPath);

        Log::create([
            'user_id'     => $user['id'],
            'action'      => 'request_boost',
            'entity_type' => 'property',
            'entity_id'   => (int) $id,
            'details'     => 'Destaque ' . $days . ' dias, ' . $feeRequired . ' Kz. Comprovativo: ' . $filename,
        ]);

        $financeiroUsers = User::getByRole('financeiro');
        foreach ($financeiroUsers as $fin) {
            Notification::notifyUser(
                (int) $fin['id'],
                'boost_request',
                'Nova solicitação de destaque',
                $user['name'] . ' solicitou destaque para "' . $property['title'] . '" (' . $days . ' dias, ' . number_format($feeRequired, 0, ',', '.') . ' Kz).',
                ['property_id' => (int) $id],
                (int) $user['id']
            );
        }

        header('Location: ' . DIRPAGE . 'dashboard/myProperties?success=' . urlencode('Solicitação de destaque enviada. A equipa financeira irá validar o pagamento.'));
        exit;
    }

    public function approveBoost($id)
    {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponivel apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Token+invalido');
            exit;
        }

        $boost = PropertyBoostRequest::find((int) $id);
        if (!$boost || $boost['status'] !== 'pendente') {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Solicitacao+nao+encontrada+ou+ja+processada');
            exit;
        }

        PropertyBoostRequest::approve((int) $id, (int) ($boost['duration_days'] ?? 30));
        Property::setFeatured((int) $boost['property_id'], true);

        Log::create([
            'user_id'     => $admin['id'],
            'action'      => 'approve_boost',
            'entity_type' => 'property',
            'entity_id'   => (int) $boost['property_id'],
            'details'     => 'Destaque aprovado. Boost ID: ' . (int) $id,
        ]);

        Notification::notifyUser(
            (int) $boost['user_id'],
            'boost_approved',
            'Destaque aprovado!',
            'O seu imovel "' . $boost['property_title'] . '" foi destacado com sucesso por ' . ($boost['duration_days'] ?? 30) . ' dias.',
            ['property_id' => (int) $boost['property_id'], 'boost_id' => (int) $id],
            (int) $admin['id']
        );

        header('Location: ' . DIRPAGE . 'dashboard/payments?success=' . urlencode('Destaque aprovado e imovel destacado.'));
        exit;
    }

    public function rejectBoost($id)
    {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponivel apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Token+invalido');
            exit;
        }

        $boost = PropertyBoostRequest::find((int) $id);
        if (!$boost || $boost['status'] !== 'pendente') {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Solicitacao+nao+encontrada+ou+ja+processada');
            exit;
        }

        $notes = trim($_POST['reject_reason'] ?? '');
        PropertyBoostRequest::reject((int) $id, $notes);

        Log::create([
            'user_id'     => $admin['id'],
            'action'      => 'reject_boost',
            'entity_type' => 'property',
            'entity_id'   => (int) $boost['property_id'],
            'details'     => 'Destaque rejeitado. Motivo: ' . ($notes ?: 'N/A'),
        ]);

        Notification::notifyUser(
            (int) $boost['user_id'],
            'boost_rejected',
            'Solicitacao de destaque rejeitada',
            'A solicitacao de destaque para "' . $boost['property_title'] . '" foi rejeitada.' . ($notes ? ' Motivo: ' . $notes : ''),
            ['property_id' => (int) $boost['property_id'], 'boost_id' => (int) $id],
            (int) $admin['id']
        );

        header('Location: ' . DIRPAGE . 'dashboard/payments?success=' . urlencode('Solicitacao de destaque rejeitada.'));
        exit;
    }

    /** Resolve numeric route segment (Dispatch may pass URL index keys instead of positional args). */
    private function resolveRouteInt($primary, int $segmentIndex = 2): int
    {
        if ($primary !== null && $primary !== '' && is_numeric($primary)) {
            return (int) $primary;
        }

        $parts = $this->parseUrl();
        if (isset($parts[$segmentIndex]) && is_numeric($parts[$segmentIndex])) {
            return (int) $parts[$segmentIndex];
        }

        return (int) ($_GET['id'] ?? 0);
    }
}
