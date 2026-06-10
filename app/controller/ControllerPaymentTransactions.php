<?php

namespace App\controller;

use App\model\Log;
use App\model\PaymentTransaction;
use App\model\UserSubscription;
use Dompdf\Dompdf;
use Dompdf\Options;
use Src\classes\ClassAccess;
use Src\classes\ClassCsrf;
use Src\classes\ClassRender;

class ControllerPaymentTransactions
{

    public function payment_transactions()
    {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        $status = trim($_GET['status'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $allowedStatuses = ['pendente', 'processando', 'confirmado', 'cancelado', 'falhado', 'rejeitado'];
        $allowedTypes = ['commission_payout', 'boost_fee', 'trust_badge_fee', 'manual_adjustment', 'subscription_fee'];
        $status = in_array($status, $allowedStatuses, true) ? $status : '';
        $type = in_array($type, $allowedTypes, true) ? $type : '';

        $transactions = PaymentTransaction::getList($status !== '' ? $status : null, $type !== '' ? $type : null, $perPage, $offset);
        $total = PaymentTransaction::countList($status !== '' ? $status : null, $type !== '' ? $type : null);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $render = new ClassRender();
        $render->setTitle('Transações de Pagamento');
        $render->setDescription('Gestão de transações financeiras');
        $render->setKeywords('transações, pagamentos, financeiro');
        $render->setData([
            'user' => $admin,
            'transactions' => $transactions,
            'summary' => PaymentTransaction::getAdminSummary(),
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'perPage' => $perPage,
            'filterStatus' => $status,
            'filterType' => $type,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/payment_transactions');
        $render->renderLayout();
    }


    public function exportTransactionsCsv()
    {
        ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        $status = trim((string) ($_GET['status'] ?? ''));
        $type = trim((string) ($_GET['type'] ?? ''));

        $allowedStatuses = ['pendente', 'processando', 'confirmado', 'cancelado', 'falhado'];
        $allowedTypes = ['commission_payout', 'boost_fee', 'trust_badge_fee', 'manual_adjustment', 'subscription_fee'];

        $status = in_array($status, $allowedStatuses, true) ? $status : '';
        $type = in_array($type, $allowedTypes, true) ? $type : '';

        $rows = PaymentTransaction::getListForExport($status !== '' ? $status : null, $type !== '' ? $type : null, 10000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="payment-transactions-' . date('Ymd-His') . '.csv"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fputcsv($out, [
            'id',
            'tipo',
            'estado',
            'direcao',
            'montante',
            'moeda',
            'metodo',
            'utilizador_id',
            'utilizador_nome',
            'utilizador_email',
            'referencia',
            'entidade_relacionada',
            'entidade_id',
            'criada_em',
            'confirmada_em',
            'notas',
        ]);

        foreach ($rows as $row) {
            fputcsv($out, [
                (int) ($row['id'] ?? 0),
                (string) ($row['transaction_type'] ?? ''),
                (string) ($row['status'] ?? ''),
                (string) ($row['direction'] ?? ''),
                (string) ($row['amount'] ?? ''),
                (string) ($row['currency'] ?? ''),
                (string) ($row['method_name'] ?? ''),
                (string) ($row['counterparty_user_id'] ?? ''),
                (string) ($row['counterparty_name'] ?? ''),
                (string) ($row['counterparty_email'] ?? ''),
                (string) ($row['reference_code'] ?? ''),
                (string) ($row['related_entity_type'] ?? ''),
                (string) ($row['related_entity_id'] ?? ''),
                (string) ($row['created_at'] ?? ''),
                (string) ($row['confirmed_at'] ?? ''),
                (string) ($row['notes'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
    }


    public function exportTransactionsPdf()
    {
        ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        $status = trim((string) ($_GET['status'] ?? ''));
        $type = trim((string) ($_GET['type'] ?? ''));

        $allowedStatuses = ['pendente', 'processando', 'confirmado', 'cancelado', 'falhado'];
        $allowedTypes = ['commission_payout', 'boost_fee', 'trust_badge_fee', 'manual_adjustment', 'subscription_fee'];

        $status = in_array($status, $allowedStatuses, true) ? $status : '';
        $type = in_array($type, $allowedTypes, true) ? $type : '';

        $rows = PaymentTransaction::getListForExport($status !== '' ? $status : null, $type !== '' ? $type : null, 10000);

        $typeLabels = [
            'commission_payout' => 'Comissão',
            'boost_fee' => 'Destaque',
            'trust_badge_fee' => 'Selo',
            'manual_adjustment' => 'Ajuste manual',
            'subscription_fee' => 'Subscrição',
        ];
        $statusLabels = [
            'pendente' => 'Pendente',
            'processando' => 'Processando',
            'confirmado' => 'Confirmado',
            'cancelado' => 'Cancelado',
            'falhado' => 'Falhado',
            'rejeitado' => 'Rejeitado',
        ];

        $esc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $generatedAt = date('d/m/Y H:i');
        $statusLabel = $status !== '' ? ($statusLabels[$status] ?? ucfirst($status)) : 'Todos';
        $typeLabel = $type !== '' ? ($typeLabels[$type] ?? $type) : 'Todos';

        $statusTotals = [];
        $typeTotals = [];
        $grandTotalAmount = 0.0;
        foreach ($rows as $row) {
            $rowStatus = (string) ($row['status'] ?? '');
            $rowType = (string) ($row['transaction_type'] ?? '');
            $statusTotals[$rowStatus] = ($statusTotals[$rowStatus] ?? 0) + 1;
            $typeTotals[$rowType] = ($typeTotals[$rowType] ?? 0) + 1;
            $grandTotalAmount += (float) ($row['amount'] ?? 0);
        }

        $statusItems = '';
        foreach ($statusTotals as $key => $count) {
            $statusItems .= '<li>' . $esc($statusLabels[$key] ?? ucfirst($key)) . ': ' . (int) $count . '</li>';
        }

        $typeItems = '';
        foreach ($typeTotals as $key => $count) {
            $typeItems .= '<li>' . $esc($typeLabels[$key] ?? $key) . ': ' . (int) $count . '</li>';
        }

        $tableRows = '';
        foreach ($rows as $row) {
            $tableRows .= '<tr>'
                . '<td>#' . (int) ($row['id'] ?? 0) . '</td>'
                . '<td>' . $esc((string) ($typeLabels[$row['transaction_type'] ?? ''] ?? ($row['transaction_type'] ?? 'Outro'))) . '</td>'
                . '<td>' . $esc((string) ($statusLabels[$row['status'] ?? ''] ?? ($row['status'] ?? ''))) . '</td>'
                . '<td>' . $esc((string) ($row['direction'] ?? '')) . '</td>'
                . '<td>' . $esc(number_format((float) ($row['amount'] ?? 0), 2, ',', '.') . ' ' . (string) ($row['currency'] ?? 'AOA')) . '</td>'
                . '<td>' . $esc((string) ($row['method_name'] ?? 'N/A')) . '</td>'
                . '<td>' . $esc((string) ($row['counterparty_name'] ?? 'N/A')) . '</td>'
                . '<td>' . $esc((string) ($row['reference_code'] ?? '')) . '</td>'
                . '<td>' . $esc((string) ($row['created_at'] ?? '')) . '</td>'
                . '<td>' . $esc((string) ($row['confirmed_at'] ?? '')) . '</td>'
                . '</tr>';
        }

        if ($tableRows === '') {
            $tableRows = '<tr><td colspan="10" style="text-align:center;">Sem transações para os filtros selecionados.</td></tr>';
        }

        $logoHtml = '<div class="brand-text"><span class="brand-imobil">Imobil</span><span class="brand-facil">Fácil</span></div>';

        $html = '<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 24px 20px 36px 20px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
    .header { margin-bottom: 10px; }
    .brand { display: table; width: 100%; margin-bottom: 8px; }
    .brand-left, .brand-right { display: table-cell; vertical-align: middle; }
    .brand-right { text-align: right; }
    .brand-text { font-size: 24px; font-weight: 700; }
    .brand-imobil { color: #0b2f7a; }
    .brand-facil { color: #f2b705; }
    .title { margin: 0; font-size: 16px; }
    .meta { margin: 4px 0 0; color: #4b5563; }
    .summary { margin: 10px 0 12px; border: 1px solid #d1d5db; background: #f9fafb; }
    .summary-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .summary-table td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
    .summary-label { width: 150px; font-weight: 700; background: #f3f4f6; }
    .summary-list { margin: 0; padding-left: 16px; }
    .summary-list li { margin: 0 0 2px; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #d1d5db; padding: 5px; vertical-align: top; word-wrap: break-word; }
    th { background: #f3f4f6; text-align: left; }
  </style>
</head>
<body>
  <div class="header">
    <div class="brand">
      <div class="brand-left">' . $logoHtml . '</div>
      <div class="brand-right">
        <h1 class="title">Relatório de Transações de Pagamento</h1>
        <p class="meta">Gerado em ' . $esc($generatedAt) . '</p>
      </div>
    </div>
    <p class="meta">Filtros aplicados: Estado = ' . $esc($statusLabel) . ' | Tipo = ' . $esc($typeLabel) . ' | Registos = ' . count($rows) . '</p>
  </div>

    <div class="summary">
        <table class="summary-table">
            <tr>
                <td class="summary-label">Total financeiro</td>
                <td>' . $esc(number_format($grandTotalAmount, 2, ',', '.') . ' AOA') . '</td>
            </tr>
            <tr>
                <td class="summary-label">Totais por estado</td>
                <td>' . ($statusItems !== '' ? '<ul class="summary-list">' . $statusItems . '</ul>' : 'Sem dados') . '</td>
            </tr>
            <tr>
                <td class="summary-label">Totais por tipo</td>
                <td>' . ($typeItems !== '' ? '<ul class="summary-list">' . $typeItems . '</ul>' : 'Sem dados') . '</td>
            </tr>
        </table>
    </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Tipo</th>
        <th>Estado</th>
        <th>Direção</th>
        <th>Montante</th>
        <th>Método</th>
        <th>Utilizador</th>
        <th>Referência</th>
        <th>Criada em</th>
        <th>Confirmada em</th>
      </tr>
    </thead>
    <tbody>' . $tableRows . '</tbody>
  </table>
</body>
</html>';

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $footerFont = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(20, 575, 'ImobilFácil - Simples para anunciar, seguro para negociar', $footerFont, 8, [0.45, 0.45, 0.45]);
        $canvas->page_text(760, 575, 'Página {PAGE_NUM} de {PAGE_COUNT}', $footerFont, 8, [0.45, 0.45, 0.45]);

        $dompdf->stream('payment-transactions-' . date('Ymd-His') . '.pdf', ['Attachment' => true]);
        exit;
    }


    public function confirmTransaction($id)
    {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Token+inválido');
            exit;
        }

        $transaction = PaymentTransaction::findById((int) $id);
        if (!$transaction) {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Transação+não+encontrada');
            exit;
        }

        if (!in_array($transaction['status'], ['pendente', 'processando'], true)) {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Transação+não+encontrada+ou+já+processada');
            exit;
        }

        $referenceCode = trim($_POST['reference_code'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($referenceCode === '' || $notes === '') {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Referência+e+observação+são+obrigatórias+na+confirmação');
            exit;
        }

        if (!PaymentTransaction::markAsConfirmed((int) $id, (int) $admin['id'], $referenceCode, $notes)) {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Erro+ao+confirmar+transação');
            exit;
        }

        if (
            (string) ($transaction['transaction_type'] ?? '') === 'subscription_fee'
            && (string) ($transaction['related_entity_type'] ?? '') === 'user_subscription'
            && (int) ($transaction['related_entity_id'] ?? 0) > 0
        ) {
            $subscriptionId = (int) $transaction['related_entity_id'];
            $activationNotes = 'Ativado após confirmação financeira da transação #' . (int) $id;

            $activated = UserSubscription::activatePendingSubscriptionById(
                $subscriptionId,
                (int) $admin['id'],
                $activationNotes
            );

            if (!$activated) {
                $activated = UserSubscription::renewActiveSubscriptionByPayment(
                    $subscriptionId,
                    (int) $id,
                    (int) $admin['id'],
                    'Renovado após confirmação financeira da transação #' . (int) $id
                );
            }

            if (!$activated) {
                header('Location: ' . DIRPAGE . 'payment_transactions?error=' . urlencode('Transação confirmada, mas não foi possível ativar ou renovar a subscrição ligada.'));
                exit;
            }
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'confirm_payment_transaction',
            'entity_type' => 'payment_transaction',
            'entity_id' => (int) $id,
            'details' => 'Transação confirmada. Ref: ' . ($referenceCode ?: 'N/A'),
        ]);

        header('Location: ' . DIRPAGE . 'payment_transactions?success=' . urlencode('Transação confirmada'));
        exit;
    }


    public function cancelTransaction($id)
    {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Token+inválido');
            exit;
        }

        $transaction = PaymentTransaction::findById((int) $id);
        if (!$transaction) {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Transação+não+encontrada');
            exit;
        }

        if (!in_array($transaction['status'], ['pendente', 'processando'], true)) {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Transação+não+pode+ser+cancelada+no+estado+atual');
            exit;
        }

        $notes = trim($_POST['notes'] ?? '');

        if ($notes === '') {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Observação+é+obrigatória+no+cancelamento');
            exit;
        }

        if (!PaymentTransaction::markAsCancelled((int) $id, $notes)) {
            header('Location: ' . DIRPAGE . 'payment_transactions?error=Erro+ao+cancelar+transação');
            exit;
        }

        if (
            (string) ($transaction['transaction_type'] ?? '') === 'subscription_fee'
            && (string) ($transaction['related_entity_type'] ?? '') === 'user_subscription'
            && (int) ($transaction['related_entity_id'] ?? 0) > 0
        ) {
            UserSubscription::cancelPendingSubscriptionById(
                (int) $transaction['related_entity_id'],
                'Cancelado após rejeição/cancelamento da transação #' . (int) $id
            );
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'cancel_payment_transaction',
            'entity_type' => 'payment_transaction',
            'entity_id' => (int) $id,
            'details' => 'Transação cancelada. Motivo: ' . ($notes ?: 'N/A'),
        ]);

        header('Location: ' . DIRPAGE . 'payment_transactions?success=' . urlencode('Transação cancelada'));
        exit;
    }


    public function rejectTransaction($id)
    {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=subscriptions&error=' . urlencode('Token inválido'));
            exit;
        }

        $transaction = PaymentTransaction::findById((int) $id);
        if (!$transaction) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=subscriptions&error=' . urlencode('Transação não encontrada'));
            exit;
        }

        if (!in_array($transaction['status'], ['pendente', 'processando'], true)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=subscriptions&error=' . urlencode('Transação não pode ser rejeitada no estado atual'));
            exit;
        }

        $notes = trim($_POST['notes'] ?? '');
        if ($notes === '') {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=subscriptions&error=' . urlencode('Motivo é obrigatório na rejeição'));
            exit;
        }

        if (!PaymentTransaction::markAsRejected((int) $id, $notes)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=subscriptions&error=' . urlencode('Erro ao rejeitar transação'));
            exit;
        }

        if (
            (string) ($transaction['transaction_type'] ?? '') === 'subscription_fee'
            && (string) ($transaction['related_entity_type'] ?? '') === 'user_subscription'
            && (int) ($transaction['related_entity_id'] ?? 0) > 0
        ) {
            UserSubscription::cancelPendingSubscriptionById(
                (int) $transaction['related_entity_id'],
                'Subscrição cancelada após rejeição da transação #' . (int) $id
            );
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'reject_payment_transaction',
            'entity_type' => 'payment_transaction',
            'entity_id' => (int) $id,
            'details' => 'Transação rejeitada. Motivo: ' . $notes,
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/payments?tab=subscriptions&success=' . urlencode('Transação rejeitada'));
        exit;
    }

}
