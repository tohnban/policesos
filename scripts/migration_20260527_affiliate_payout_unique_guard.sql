-- Documentação: regras anti-pagamento duplo ao afiliado no mesmo imóvel.
-- Aplicação: Commission::hasActiveAffiliateCommissionForProperty + PaymentTransaction::countAffiliatePayouts
--
-- Já existente: UNIQUE (request_id) em commissions — uma comissão por pedido.
-- Não é possível UNIQUE parcial só em (property_id, affiliate_id) sem excluir comissões canceladas/histórico.

-- Opcional: índice para filas admin (MariaDB 10.2+)
-- CREATE INDEX IF NOT EXISTS idx_commissions_affiliate_payout_queue
--   ON commissions (affiliate_payout_status, status, paid_at);
