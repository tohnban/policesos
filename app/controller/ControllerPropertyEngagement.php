<?php

namespace App\controller;

use App\model\Favorite;
use App\model\Log;
use App\model\Notification;
use App\model\Property;
use App\model\PropertyAffiliate;
use App\model\PropertyBehaviorEvent;
use Src\classes\ClassAuth;
use Src\classes\ClassCookieConsent;
use Src\classes\ClassCsrf;
use Src\classes\ClassSession;

class ControllerPropertyEngagement
{

    public function favorite($id)
    {
        ClassAuth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'properties');
            exit;
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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'properties');
            exit;
        }

        Favorite::remove(ClassAuth::user()['id'], (int) $id);
        $redirect = $_SERVER['HTTP_REFERER'] ?? (DIRPAGE . 'property/' . (int) $id);
        header('Location: ' . $redirect);
        exit;
    }


    public function affiliateRequest($id)
    {
        ClassAuth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

}
