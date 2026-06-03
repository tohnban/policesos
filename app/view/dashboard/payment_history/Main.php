<div class="dashboard-content-wrapper payment-transactions-view payment-history-dashboard-view">
    <?php
        $filterStatus = (string) ($filterStatus ?? '');
        $filterType = (string) ($filterType ?? '');
        $exportQuery = http_build_query([
            'status' => $filterStatus,
            'type' => $filterType,
        ]);

        function paymentHistoryStatusChipClass(string $status): string {
            return match ($status) {
                'confirmado' => 'dashboard-chip-success',
                'cancelado', 'rejeitado' => 'dashboard-chip-neutral',
                'falhado' => 'dashboard-chip-danger',
                default => 'dashboard-chip-warning',
            };
        }
    ?>
    <div class="dashboard-header">
        <h1>Histórico de Pagamentos</h1>
        <p>Consulte todas as transações de pagamento recebidas.</p>
    </div>

    <div class="dashboard-card payment-transactions-card">
        <div class="dashboard-card-title">
            <h2>Minhas Transações</h2>
        </div>

        <form method="GET" action="<?php echo DIRPAGE; ?>dashboard/paymentHistory" class="dashboard-form payment-transactions-filters payment-history-filters filter-toolbar-form">
            <div class="filter-toolbar filter-toolbar-sticky filter-toolbar-dashboard payment-history-actions">
                <div class="filter-toolbar-inline-fields payment-history-filters-grid">
                    <div class="filter-toolbar-field">
                        <label class="filter-toolbar-field-label" for="history-status">Estado</label>
                        <select name="status" id="history-status" class="filter-toolbar-select">
                            <option value="">Todos</option>
                            <option value="pendente" <?php echo $filterStatus === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="processando" <?php echo $filterStatus === 'processando' ? 'selected' : ''; ?>>Processando</option>
                            <option value="confirmado" <?php echo $filterStatus === 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                            <option value="cancelado" <?php echo $filterStatus === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            <option value="falhado" <?php echo $filterStatus === 'falhado' ? 'selected' : ''; ?>>Falhado</option>
                            <option value="rejeitado" <?php echo $filterStatus === 'rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                        </select>
                    </div>
                    <div class="filter-toolbar-field">
                        <label class="filter-toolbar-field-label" for="history-type">Tipo</label>
                        <select name="type" id="history-type" class="filter-toolbar-select">
                            <option value="">Todos</option>
                            <option value="commission_owner_payment" <?php echo $filterType === 'commission_owner_payment' ? 'selected' : ''; ?>>Comissão (proprietário)</option>
                            <option value="commission_payout" <?php echo $filterType === 'commission_payout' ? 'selected' : ''; ?>>Pagamento ao afiliado</option>
                            <option value="system_commission" <?php echo $filterType === 'system_commission' ? 'selected' : ''; ?>>Taxa do sistema</option>
                            <option value="boost_fee" <?php echo $filterType === 'boost_fee' ? 'selected' : ''; ?>>Destaque</option>
                            <option value="trust_badge_fee" <?php echo $filterType === 'trust_badge_fee' ? 'selected' : ''; ?>>Selo</option>
                            <option value="subscription_fee" <?php echo $filterType === 'subscription_fee' ? 'selected' : ''; ?>>Subscrição</option>
                            <option value="manual_adjustment" <?php echo $filterType === 'manual_adjustment' ? 'selected' : ''; ?>>Ajuste manual</option>
                        </select>
                    </div>
                </div>
                <div class="filter-toolbar-actions payment-history-primary-actions">
                    <button type="submit" class="dashboard-btn dashboard-btn-primary filter-toolbar-submit">Filtrar</button>
                    <a href="<?php echo DIRPAGE; ?>dashboard/paymentHistory" class="dashboard-btn">Limpar</a>
                </div>
                <div class="payment-history-export-actions">
                    <a href="<?php echo DIRPAGE; ?>dashboard/exportPaymentHistoryPdf?<?php echo htmlspecialchars($exportQuery); ?>" class="dashboard-btn dashboard-btn-small dashboard-btn-primary">
                        <i class="fa fa-file-pdf-o" aria-hidden="true"></i> PDF
                    </a>
                    <a href="<?php echo DIRPAGE; ?>dashboard/exportPaymentHistoryCsv?<?php echo htmlspecialchars($exportQuery); ?>" class="dashboard-btn dashboard-btn-small dashboard-btn-primary">
                        <i class="fa fa-download" aria-hidden="true"></i> CSV
                    </a>
                </div>
            </div>
        </form>

        <?php if (!empty($transactions)): ?>
            <div class="dashboard-table-wrap payment-transactions-table-wrap payment-history-table-wrap">
            <table class="dashboard-table payment-transactions-table payment-history-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Montante</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Referência</th>
                        <th>Data</th>
                        <th>Confirmada em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                        <?php
                            $typeLabel = [
                                'commission_owner_payment' => 'Comissão (proprietário)',
                                'commission_payout' => 'Pagamento ao afiliado',
                                'system_commission' => 'Taxa sistema',
                                'boost_fee' => 'Destaque',
                                'trust_badge_fee' => 'Selo',
                                'manual_adjustment' => 'Ajuste',
                                'subscription_fee' => 'Subscrição',
                            ][$tx['transaction_type']] ?? 'Outro';
                            $statusLabel = ucfirst((string) ($tx['status'] ?? ''));
                        ?>
                        <tr class="payment-transactions-row payment-history-row">
                            <td class="payment-transactions-id" data-label="ID">#<?php echo (int)$tx['id']; ?></td>
                            <td data-label="Tipo">
                                <span class="payment-transactions-type-pill"><?php echo htmlspecialchars((string) $typeLabel); ?></span>
                            </td>
                            <td class="dashboard-value" data-label="Montante">
                                <strong><?php echo number_format((float)$tx['amount'], 2, ',', '.'); ?></strong> <?php echo htmlspecialchars($tx['currency']); ?>
                            </td>
                            <td class="dashboard-inline-note" data-label="Método"><?php echo htmlspecialchars($tx['method_name'] ?? 'N/A'); ?></td>
                            <td data-label="Estado">
                                <span class="dashboard-chip <?php echo paymentHistoryStatusChipClass((string) ($tx['status'] ?? '')); ?>">
                                    <?php echo htmlspecialchars($statusLabel); ?>
                                </span>
                            </td>
                            <td class="dashboard-inline-note" data-label="Referência"><?php echo htmlspecialchars($tx['reference_code'] ?? '–'); ?></td>
                            <td data-label="Data"><?php echo date('d/m/Y H:i', strtotime($tx['created_at'])); ?></td>
                            <td data-label="Confirmada"><?php echo $tx['confirmed_at'] ? date('d/m/Y H:i', strtotime($tx['confirmed_at'])) : '–'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php else: ?>
            <p class="dashboard-empty-copy dashboard-empty-copy-spaced">Nenhuma transação registada.</p>
        <?php endif; ?>
    </div>
</div>
