<?php

namespace App\controller;

use App\model\Log;
use App\model\PaymentMethod;
use Src\classes\ClassAccess;
use Src\classes\ClassCsrf;
use Src\classes\ClassRender;

class ControllerPaymentMethods
{

    public function payment_methods()
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        $methods = PaymentMethod::getAll();

        $render = new ClassRender();
        $render->setTitle('Métodos de Pagamento');
        $render->setDescription('Catálogo de métodos de pagamento');
        $render->setKeywords('pagamentos, métodos');
        $render->setData([
            'user' => $admin,
            'methods' => $methods,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/payment_methods');
        $render->renderLayout();
    }


    public function toggleMethod($id)
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Token+inválido');
            exit;
        }

        $method = PaymentMethod::findById((int) $id);
        if (!$method) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Método+não+encontrado');
            exit;
        }

        $newStatus = !$method['is_active'];
        if (!PaymentMethod::setActiveStatus((int) $id, $newStatus)) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Erro+ao+atualizar+método');
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'toggle_payment_method',
            'entity_type' => 'payment_method',
            'entity_id' => (int) $id,
            'details' => 'Método ' . $method['name'] . ' ' . ($newStatus ? 'ativado' : 'desativado'),
        ]);

        header('Location: ' . DIRPAGE . 'payment_methods?success=' . urlencode('Método atualizado'));
        exit;
    }


    public function createMethod()
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Token+inválido');
            exit;
        }

        $code = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['code'] ?? '')));
        $name = trim($_POST['name'] ?? '');

        if (!$code || !$name) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Código+e+nome+são+obrigatórios');
            exit;
        }

        if (PaymentMethod::findByCode($code)) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Já+existe+um+método+com+esse+código');
            exit;
        }

        $data = [
            'code'               => $code,
            'name'               => $name,
            'direction'          => in_array($_POST['direction'] ?? '', ['incoming','outgoing','both'], true) ? $_POST['direction'] : 'both',
            'audience'           => in_array($_POST['audience'] ?? '', ['system','user','both'], true) ? $_POST['audience'] : 'both',
            'requires_reference' => !empty($_POST['requires_reference']),
            'is_active'          => !empty($_POST['is_active']),
            'fields_config'      => $_POST['fields'] ?? [],
        ];

        $id = PaymentMethod::create($data);
        if (!$id) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Erro+ao+criar+método');
            exit;
        }

        Log::create([
            'user_id'     => (int) $admin['id'],
            'action'      => 'create_payment_method',
            'entity_type' => 'payment_method',
            'entity_id'   => $id,
            'details'     => "Método criado: {$name} ({$code})",
        ]);

        header('Location: ' . DIRPAGE . 'payment_methods?success=' . urlencode('Método criado com sucesso'));
        exit;
    }


    public function updateMethod($id)
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Token+inválido');
            exit;
        }

        $method = PaymentMethod::findById((int) $id);
        if (!$method) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Método+não+encontrado');
            exit;
        }

        $code = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['code'] ?? '')));
        $name = trim($_POST['name'] ?? '');

        if (!$code || !$name) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Código+e+nome+são+obrigatórios');
            exit;
        }

        // Check code uniqueness (ignore self)
        $existing = PaymentMethod::findByCode($code);
        if ($existing && (int) $existing['id'] !== (int) $id) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Código+já+em+uso+por+outro+método');
            exit;
        }

        $data = [
            'code'               => $code,
            'name'               => $name,
            'direction'          => in_array($_POST['direction'] ?? '', ['incoming','outgoing','both'], true) ? $_POST['direction'] : 'both',
            'audience'           => in_array($_POST['audience'] ?? '', ['system','user','both'], true) ? $_POST['audience'] : 'both',
            'requires_reference' => !empty($_POST['requires_reference']),
            'is_active'          => !empty($_POST['is_active']),
            'fields_config'      => $_POST['fields'] ?? [],
        ];

        if (!PaymentMethod::update((int) $id, $data)) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Erro+ao+atualizar+método');
            exit;
        }

        Log::create([
            'user_id'     => (int) $admin['id'],
            'action'      => 'update_payment_method',
            'entity_type' => 'payment_method',
            'entity_id'   => (int) $id,
            'details'     => "Método atualizado: {$name} ({$code})",
        ]);

        header('Location: ' . DIRPAGE . 'payment_methods?success=' . urlencode('Método atualizado com sucesso'));
        exit;
    }


    public function deleteMethod($id)
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Token+inválido');
            exit;
        }

        $method = PaymentMethod::findById((int) $id);
        if (!$method) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Método+não+encontrado');
            exit;
        }

        $result = PaymentMethod::delete((int) $id);
        if ($result === false) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=Erro+ao+remover+método');
            exit;
        }
        if (is_string($result)) {
            header('Location: ' . DIRPAGE . 'payment_methods?error=' . urlencode($result));
            exit;
        }

        Log::create([
            'user_id'     => (int) $admin['id'],
            'action'      => 'delete_payment_method',
            'entity_type' => 'payment_method',
            'entity_id'   => (int) $id,
            'details'     => "Método removido: {$method['name']} ({$method['code']})",
        ]);

        header('Location: ' . DIRPAGE . 'payment_methods?success=' . urlencode('Método removido'));
        exit;
    }

}
