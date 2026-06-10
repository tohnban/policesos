<?php

namespace App\controller;

use App\model\Log;
use App\model\Notification;
use App\model\Property;
use App\model\PropertyBoostRequest;
use App\model\User;
use Src\classes\ClassAccess;
use Src\classes\ClassAuth;
use Src\classes\ClassRender;

class ControllerPropertyModeration
{

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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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


    public function approveBoost($id)
    {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponivel apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

}
