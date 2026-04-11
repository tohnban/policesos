<?php
namespace App\controller;

use Src\classes\ClassRender;
use Src\classes\ClassAuth;
use Src\classes\ClassAccess;
use Src\classes\ClassCsrf;
use App\model\Property;
use App\model\User;
use App\model\Log;
use App\model\Favorite;
use App\model\PropertyAffiliate;

class ControllerProperty {
    private function normalizeUploadError(int $code): string {
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

    private function processPropertyImages(array $files): array {
        $savedPaths = [];
        $errors = [];

        if (empty($files) || !isset($files['name']) || !is_array($files['name'])) {
            return ['paths' => [], 'errors' => []];
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $maxPerFile = 3 * 1024 * 1024;

        $count = count($files['name']);
        if ($count > 8) {
            return ['paths' => [], 'errors' => ['Envie no máximo 8 imagens.']];
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
            $originalName = (string) ($files['name'][$i] ?? '');
            $size = (int) ($files['size'][$i] ?? 0);

            if ($size <= 0 || $size > $maxPerFile) {
                $errors[] = 'Cada imagem deve ter até 3MB.';
                continue;
            }

            $mime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
            if (!in_array($mime, $allowedMime, true)) {
                $errors[] = 'Formato de imagem inválido. Use JPG, PNG, WEBP ou GIF.';
                continue;
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                switch ($mime) {
                    case 'image/jpeg':
                        $ext = 'jpg';
                        break;
                    case 'image/png':
                        $ext = 'png';
                        break;
                    case 'image/webp':
                        $ext = 'webp';
                        break;
                    case 'image/gif':
                        $ext = 'gif';
                        break;
                    default:
                        $ext = '';
                        break;
                }
            }
            if ($ext === '') {
                $errors[] = 'Não foi possível determinar a extensão de uma imagem.';
                continue;
            }

            try {
                $randomSuffix = bin2hex(random_bytes(4));
            } catch (\Exception $e) {
                $randomSuffix = substr(md5(uniqid('', true)), 0, 8);
            }

            $filename = 'property_' . time() . '_' . $randomSuffix . '.' . $ext;
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

    public function index() {
        $filters = $_GET;
        $perPage = 12;
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $totalProperties = Property::countFiltered($filters);
        $properties = Property::getFiltered($filters, $perPage, $offset);
        $favoriteIds = [];
        $totalPages = max(1, (int) ceil($totalProperties / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
            $properties = Property::getFiltered($filters, $perPage, $offset);
        }

        if (ClassAuth::check()) {
            $favoriteIds = Favorite::getPropertyIdsByUser(ClassAuth::user()['id']);
        }

        $render = new ClassRender();
        $render->setTitle("Imóveis Disponíveis");
        $render->setDescription("Encontre o imóvel ideal");
        $render->setKeywords("imóveis, casas, apartamentos");
        $render->setData([
            'properties' => $properties,
            'favoriteIds' => $favoriteIds,
            'page' => $page,
            'perPage' => $perPage,
            'totalProperties' => $totalProperties,
            'totalPages' => $totalPages,
        ]);
        $render->setDir("property/list");
        $render->renderLayout();
    }

    public function properties() {
        // Alias para index()
        $this->index();
    }

    public function show($id) {
        $property = Property::find($id);
        if (!$property) {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        // Allow moderators/admins to view pending properties
        $isModerator = ClassAuth::check() && ClassAccess::can('properties.moderate');
        $isAvailable = $property['status'] === 'disponivel';
        
        if (!$isAvailable && !$isModerator) {
            header('Location: ' . DIRPAGE . '404');
            exit;
        }

        $isFavorite = false;

        // Check for referral
        if (isset($_GET['ref'])) {
            $affiliate = User::findByAffiliateCode($_GET['ref']);
            if ($affiliate) {
                Src\classes\ClassSession::set('referred_by', $affiliate['id']);
            }
        }

        $isAffiliate = false;
        if (ClassAuth::check()) {
            $isFavorite = Favorite::exists(ClassAuth::user()['id'], (int) $property['id']);
            $isAffiliate = PropertyAffiliate::isActiveAffiliate(ClassAuth::user()['id'], (int) $property['id']);
        }

        $render = new ClassRender();
        $render->setTitle($property['title']);
        $render->setDescription(substr($property['description'], 0, 150));
        $render->setKeywords("imóvel, " . $property['type']);
        $render->setData(['property' => $property, 'isFavorite' => $isFavorite, 'isAffiliate' => $isAffiliate]);
        $render->setDir("property/show");
        $render->renderLayout();
    }

    public function create() {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não podem cadastrar imóveis');

        $render = new ClassRender();
        $render->setTitle("Cadastrar Imóvel");
        $render->setDescription("Adicione um novo imóvel");
        $render->setKeywords("cadastrar, imóvel");
        $render->setData([]);
        $render->setDir("property/create");
        $render->renderLayout();
    }

    public function store() {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não podem cadastrar imóveis');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'properties');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'property/create?error=Token inválido');
            exit;
        }

        // Check property limits based on account plan
        $maxProperties = ($user['account_plan'] ?? 'free') === 'premium' ? 20 : 3;
        $currentCount = Property::countByOwner($user['id']);
        if ($currentCount >= $maxProperties) {
            $planLabel = ($user['account_plan'] === 'premium') ? 'premium' : 'gratuita';
            header('Location: ' . DIRPAGE . 'property/create?error=Sua conta ' . $planLabel . ' permite até ' . $maxProperties . ' imóveis. Você já atingiu o limite.');
            exit;
        }

        $ownerBonusPct = (float) ($_POST['owner_bonus_pct'] ?? 0);
        if ($ownerBonusPct < 0) {
            $ownerBonusPct = 0;
        }

        $uploadResult = $this->processPropertyImages($_FILES['images'] ?? []);
        if (!empty($uploadResult['errors'])) {
            header('Location: ' . DIRPAGE . 'property/create?error=' . urlencode(implode(' ', $uploadResult['errors'])));
            exit;
        }

        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'type' => $_POST['type'] ?? '',
            'purpose' => $_POST['purpose'] ?? '',
            'price' => $_POST['price'] ?? '',
            'location' => $_POST['location'] ?? '',
            'bedrooms' => (int) ($_POST['bedrooms'] ?? 0),
            'bathrooms' => (int) ($_POST['bathrooms'] ?? 0),
            'area' => ($_POST['area'] ?? '') === '' ? null : (float) $_POST['area'],
            'images' => json_encode($uploadResult['paths'], JSON_UNESCAPED_SLASHES),
            'video_url' => $_POST['video_url'] ?? '',
            'owner_bonus_pct' => $ownerBonusPct,
            'visibility' => (($user['account_plan'] ?? 'free') === 'premium') ? 'premium' : 'basic',
            'affiliate_id' => $user['id'],
            'status' => 'pendente'
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
                'details' => 'Imóvel cadastrado aguardando aprovação'
            ]);
            header('Location: ' . DIRPAGE . 'dashboard?success=Imóvel cadastrado com sucesso, aguardando aprovação');
            exit;
        } else {
            header('Location: ' . DIRPAGE . 'property/create?error=Erro ao cadastrar imóvel');
            exit;
        }
    }

    public function moderate() {
        $user = ClassAccess::requirePermission('properties.moderate', 'dashboard', 'Acesso disponível apenas para moderação');

        $pending = Property::getPending();

        $render = new ClassRender();
        $render->setTitle("Moderação de Imóveis");
        $render->setDescription("Aprovar ou rejeitar imóveis pendentes");
        $render->setKeywords("moderação, imóveis");
        $render->setData(['pending' => $pending]);
        $render->setDir("property/moderate");
        $render->renderLayout();
    }

    public function approve($id) {
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
                'details' => 'Imóvel aprovado'
            ]);
        }
        header('Location: ' . DIRPAGE . 'property/moderate');
        exit;
    }

    public function reject($id) {
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
                'details' => 'Imóvel rejeitado'
            ]);
        }
        header('Location: ' . DIRPAGE . 'property/moderate');
        exit;
    }

    public function featured() {
        $properties = Property::getFeatured();
        $favoriteIds = [];

        if (ClassAuth::check()) {
            $favoriteIds = Favorite::getPropertyIdsByUser(ClassAuth::user()['id']);
        }

        $render = new ClassRender();
        $render->setTitle("Imóveis em Destaque");
        $render->setDescription("Imóveis em destaque");
        $render->setKeywords("destaque, imóveis");
        $render->setData(['properties' => $properties, 'favoriteIds' => $favoriteIds]);
        $render->setDir("property/featured");
        $render->renderLayout();
    }

    public function favorite($id) {
        ClassAuth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'property/' . (int) $id . '?error=Token inválido');
            exit;
        }

        Favorite::add(ClassAuth::user()['id'], (int) $id);
        $redirect = $_SERVER['HTTP_REFERER'] ?? (DIRPAGE . 'property/' . (int) $id);
        header('Location: ' . $redirect);
        exit;
    }

    public function unfavorite($id) {
        ClassAuth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'property/' . (int) $id . '?error=Token inválido');
            exit;
        }

        Favorite::remove(ClassAuth::user()['id'], (int) $id);
        $redirect = $_SERVER['HTTP_REFERER'] ?? (DIRPAGE . 'property/' . (int) $id);
        header('Location: ' . $redirect);
        exit;
    }

    public function affiliateRequest($id) {
        ClassAuth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'property/' . (int) $id . '?error=Token inválido');
            exit;
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

        // Check if already affiliate (pendente or ativo)
        if (PropertyAffiliate::exists($user['id'], $propertyId)) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Você já tem uma solicitação de afiliação para este imóvel');
            exit;
        }

        // Create affiliate request
        PropertyAffiliate::create([
            'user_id' => $user['id'],
            'property_id' => $propertyId
        ]);

        // Log action
        Log::create([
            'user_id' => $user['id'],
            'action' => 'Solicitou afiliação em um imóvel',
            'entity_type' => 'property_affiliate',
            'entity_id' => $propertyId,
            'details' => 'Solicitação de afiliação para a propriedade: ' . $property['title']
        ]);

        header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?success=Solicitação de afiliação enviada com sucesso');
        exit;
    }
}