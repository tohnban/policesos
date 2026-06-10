<?php

namespace App\controller;

use App\model\Notification;
use App\model\Property;
use App\model\PropertyAffiliate;
use App\model\PropertyBehaviorEvent;
use App\model\Request;
use App\model\RequestChatMessage;
use App\model\RequestChatThread;
use Src\classes\ClassAccess;
use Src\classes\ClassAuth;
use Src\classes\ClassCommissionGuard;
use Src\classes\ClassCookieConsent;
use Src\classes\ClassSession;

class ControllerRequestIntake
{
    use RequestControllerSupport;

    public function request()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        ClassAuth::requireAuth();
        header('Location: ' . DIRPAGE . 'requests');
        exit;
    }


    public function store()
    {
        if (!ClassAuth::check()) {
            header('Location: ' . DIRPAGE . 'login');
            exit;
        }

        ClassCommissionGuard::requireCanSubmitPropertyRequest();

        $currentUser = ClassAuth::user();
        if (!ClassAccess::canSubmitPropertyRequest($currentUser)) {
            $propertyId = (int) ($_POST['property_id'] ?? 0);
            $target = $propertyId > 0 ? ('property/' . $propertyId) : 'properties';
            header('Location: ' . DIRPAGE . $target . '?error=' . rawurlencode('Contas da equipa não podem enviar pedidos de compra ou aluguer.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'properties');
            exit;
        }

        $propertyId = (int) ($_POST['property_id'] ?? 0);
        $property = Property::find($propertyId);
        if (!$property || ($property['status'] ?? '') !== 'disponivel') {
            header('Location: ' . DIRPAGE . 'properties?error=Imóvel indisponível para solicitação');
            exit;
        }

        if ((int) ($property['affiliate_id'] ?? 0) === (int) ($currentUser['id'] ?? 0)) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Não pode solicitar o seu próprio imóvel');
            exit;
        }

        if (Request::hasActiveRequest((int) $currentUser['id'], $propertyId)) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Já tem uma solicitação ativa para este imóvel');
            exit;
        }

        $requestedType = (string) ($_POST['type'] ?? '');
        $purposeToRequestType = [
            'venda' => 'compra',
            'aluguer_curto' => 'aluguer_curto',
            'aluguer_longo' => 'aluguer_longo',
        ];
        $expectedType = $purposeToRequestType[(string) ($property['purpose'] ?? '')] ?? null;
        if ($expectedType === null || $requestedType !== $expectedType) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Tipo de solicitação incompatível com a finalidade do imóvel');
            exit;
        }

        $allowedTermMonths = [
            'mensal' => 1,
            'trimestral' => 3,
            'semestral' => 6,
            'anual' => 12,
        ];

        $selectedTerm = null;
        $monthsCount = 1;
        $monthlyReferenceAmount = (float) ($property['price'] ?? 0);
        $modalityTotalAmount = $monthlyReferenceAmount;

        if (($property['purpose'] ?? '') === 'aluguer_longo') {
            $selectedTerm = (string) ($_POST['payment_term'] ?? '');
            $termsRaw = json_decode((string) ($property['rent_payment_terms'] ?? '[]'), true);
            $allowedTermsForProperty = is_array($termsRaw) ? array_values(array_filter($termsRaw, static function ($term) use ($allowedTermMonths) {
                return is_string($term) && isset($allowedTermMonths[$term]);
            })) : [];

            if ($selectedTerm === '' || !in_array($selectedTerm, $allowedTermsForProperty, true)) {
                header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Modalidade de pagamento inválida para este imóvel');
                exit;
            }

            $monthsCount = $allowedTermMonths[$selectedTerm];
            $modalityTotalAmount = $monthlyReferenceAmount * $monthsCount;
        }

        $referredBy = (int) ClassSession::get('referred_by');
        if (
            $referredBy <= 0
            || $referredBy === (int) ($currentUser['id'] ?? 0)
            || !PropertyAffiliate::isActiveAffiliate($referredBy, $propertyId)
        ) {
            $referredBy = null;
        }

        $data = [
            'user_id' => $currentUser['id'],
            'property_id' => $propertyId,
            'affiliate_id' => $referredBy,
            'type' => $requestedType,
            'payment_term' => $selectedTerm,
            'months_count' => $monthsCount,
            'monthly_reference_amount' => $monthlyReferenceAmount,
            'modality_total_amount' => $modalityTotalAmount,
            'message' => $_POST['message'] ?? '',
        ];

        $requestId = Request::create($data);
        if (is_int($requestId) && $requestId > 0) {
            if (ClassCookieConsent::hasBehavioralConsent()) {
                PropertyBehaviorEvent::track(
                    (int) ($currentUser['id'] ?? 0),
                    (int) $propertyId,
                    'request',
                    ClassSession::getOrCreateVisitorKey()
                );
            }
            RequestChatThread::getOrCreateByRequestId((int) $requestId);

            $initialMessage = trim((string) ($data['message'] ?? ''));
            if ($initialMessage !== '') {
                $thread = RequestChatThread::findByRequestId((int) $requestId);
                if ($thread && !empty($thread['id'])) {
                    RequestChatMessage::createForThread(
                        (int) ($thread['id'] ?? 0),
                        (int) ($currentUser['id'] ?? 0),
                        $initialMessage,
                        'text'
                    );
                }
            }

            $this->appendChatSystemMessage(
                (int) $requestId,
                (int) ($currentUser['id'] ?? 0),
                'Solicitação criada e negociação iniciada.'
            );

            // Notify property owner about a new request
            if (!empty($property['affiliate_id'])) {
                Notification::notifyUser(
                    (int) $property['affiliate_id'],
                    'new_request',
                    'Nova solicitação recebida',
                    'Você recebeu uma nova solicitação para o imóvel "' . ($property['title'] ?? '') . '".',
                    ['request_id' => (int) $requestId, 'property_id' => (int) $data['property_id']],
                    (int) $data['user_id']
                );
            }

            // Clear referral after use
            ClassSession::remove('referred_by');
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?success=' . urlencode('Solicitação enviada com sucesso'));
            exit;
        } else {
            header('Location: ' . DIRPAGE . 'property/' . $data['property_id'] . '?error=1');
            exit;
        }
    }

}
