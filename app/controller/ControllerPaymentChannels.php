<?php

namespace App\controller;

use App\model\Log;
use App\model\PaymentMethod;
use App\model\SystemPaymentChannel;
use Src\classes\ClassAccess;
use Src\classes\ClassCsrf;
use Src\classes\ClassRender;

class ControllerPaymentChannels
{

    public function payment_channels()
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        $methods = PaymentMethod::getAll();
        $selectedMethodId = max(0, (int) ($_GET['method_id'] ?? 0));
        if ($selectedMethodId === 0 && !empty($methods)) {
            $selectedMethodId = (int) $methods[0]['id'];
        }

        $selectedMethod = null;
        foreach ($methods as $method) {
            if ((int) ($method['id'] ?? 0) === $selectedMethodId) {
                $selectedMethod = $method;
                break;
            }
        }

        $channels = $selectedMethodId > 0 ? SystemPaymentChannel::getByMethodId($selectedMethodId) : [];

        $render = new ClassRender();
        $render->setTitle('Canais do Sistema');
        $render->setDescription('Gestão de canais para recebimento e pagamento do sistema');
        $render->setKeywords('canais, pagamentos, financeiro');
        $render->setData([
            'user' => $admin,
            'methods' => $methods,
            'selectedMethodId' => $selectedMethodId,
            'selectedMethod' => $selectedMethod,
            'channels' => $channels,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/payment_channels');
        $render->renderLayout();
    }


    public function createChannel()
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_channels?error=Token+inválido');
            exit;
        }

        $methodId = (int) ($_POST['method_id'] ?? 0);
        $method = PaymentMethod::findById($methodId);
        if (!$method) {
            header('Location: ' . DIRPAGE . 'payment_channels?error=Método+inválido');
            exit;
        }

        $channelName = trim((string) ($_POST['channel_name'] ?? ''));
        if ($channelName === '') {
            header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . $methodId . '&error=Nome+do+canal+é+obrigatório');
            exit;
        }

        $channelId = SystemPaymentChannel::create([
            'method_id' => $methodId,
            'channel_name' => $channelName,
            'account_name' => $_POST['account_name'] ?? null,
            'account_number' => $_POST['account_number'] ?? null,
            'iban' => $_POST['iban'] ?? null,
            'bank_name' => $_POST['bank_name'] ?? null,
            'wallet_provider' => $_POST['wallet_provider'] ?? null,
            'instructions' => $_POST['instructions'] ?? null,
            'is_default' => !empty($_POST['is_default']) ? 1 : 0,
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ]);

        if ($channelId === false) {
            header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . $methodId . '&error=Erro+ao+criar+canal');
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'create_system_payment_channel',
            'entity_type' => 'system_payment_channel',
            'entity_id' => is_int($channelId) ? $channelId : 0,
            'details' => 'Canal criado: ' . $channelName . ' (método ' . $method['name'] . ')',
        ]);

        header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . $methodId . '&success=' . urlencode('Canal criado com sucesso'));
        exit;
    }


    public function updateChannel($id)
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_channels?error=Token+inválido');
            exit;
        }

        $channel = SystemPaymentChannel::findById((int) $id);
        if (!$channel) {
            header('Location: ' . DIRPAGE . 'payment_channels?error=Canal+não+encontrado');
            exit;
        }

        $channelName = trim((string) ($_POST['channel_name'] ?? ''));
        if ($channelName === '') {
            header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . (int) $channel['method_id'] . '&error=Nome+do+canal+é+obrigatório');
            exit;
        }

        $ok = SystemPaymentChannel::update((int) $id, [
            'channel_name' => $channelName,
            'account_name' => $_POST['account_name'] ?? null,
            'account_number' => $_POST['account_number'] ?? null,
            'iban' => $_POST['iban'] ?? null,
            'bank_name' => $_POST['bank_name'] ?? null,
            'wallet_provider' => $_POST['wallet_provider'] ?? null,
            'instructions' => $_POST['instructions'] ?? null,
            'is_default' => !empty($_POST['is_default']) ? 1 : 0,
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ]);

        if (!$ok) {
            header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . (int) $channel['method_id'] . '&error=Erro+ao+atualizar+canal');
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'update_system_payment_channel',
            'entity_type' => 'system_payment_channel',
            'entity_id' => (int) $id,
            'details' => 'Canal atualizado: ' . $channelName,
        ]);

        header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . (int) $channel['method_id'] . '&success=' . urlencode('Canal atualizado com sucesso'));
        exit;
    }


    public function setDefaultChannel($id)
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_channels?error=Token+inválido');
            exit;
        }

        $channel = SystemPaymentChannel::findById((int) $id);
        if (!$channel) {
            header('Location: ' . DIRPAGE . 'payment_channels?error=Canal+não+encontrado');
            exit;
        }

        if (!SystemPaymentChannel::setDefault((int) $id, (int) $channel['method_id'])) {
            header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . (int) $channel['method_id'] . '&error=Erro+ao+definir+canal+padrão');
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'set_default_system_payment_channel',
            'entity_type' => 'system_payment_channel',
            'entity_id' => (int) $id,
            'details' => 'Canal marcado como padrão para o método ' . $channel['method_name'],
        ]);

        header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . (int) $channel['method_id'] . '&success=' . urlencode('Canal padrão atualizado'));
        exit;
    }


    public function deactivateChannel($id)
    {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_channels?error=Token+inválido');
            exit;
        }

        $channel = SystemPaymentChannel::findById((int) $id);
        if (!$channel) {
            header('Location: ' . DIRPAGE . 'payment_channels?error=Canal+não+encontrado');
            exit;
        }

        if (!SystemPaymentChannel::deactivate((int) $id)) {
            header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . (int) $channel['method_id'] . '&error=Erro+ao+desativar+canal');
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'deactivate_system_payment_channel',
            'entity_type' => 'system_payment_channel',
            'entity_id' => (int) $id,
            'details' => 'Canal desativado: ' . $channel['channel_name'],
        ]);

        header('Location: ' . DIRPAGE . 'payment_channels?method_id=' . (int) $channel['method_id'] . '&success=' . urlencode('Canal desativado'));
        exit;
    }

}
