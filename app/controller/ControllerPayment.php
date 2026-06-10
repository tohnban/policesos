<?php

namespace App\controller;

/**
 * Legacy facade for payment_methods, payment_transactions and payment_channels URLs.
 */
class ControllerPayment
{
    private ?ControllerPaymentMethods $paymentMethods = null;

    private function paymentMethods(): ControllerPaymentMethods
    {
        return $this->paymentMethods ??= new ControllerPaymentMethods();
    }

    public function payment_methods()
    {
        $this->paymentMethods()->payment_methods();
    }

    public function toggleMethod($id)
    {
        $this->paymentMethods()->toggleMethod($id);
    }

    public function createMethod()
    {
        $this->paymentMethods()->createMethod();
    }

    public function updateMethod($id)
    {
        $this->paymentMethods()->updateMethod($id);
    }

    public function deleteMethod($id)
    {
        $this->paymentMethods()->deleteMethod($id);
    }


    private ?ControllerPaymentTransactions $paymentTransactions = null;

    private function paymentTransactions(): ControllerPaymentTransactions
    {
        return $this->paymentTransactions ??= new ControllerPaymentTransactions();
    }

    public function payment_transactions()
    {
        $this->paymentTransactions()->payment_transactions();
    }

    public function exportTransactionsCsv()
    {
        $this->paymentTransactions()->exportTransactionsCsv();
    }

    public function exportTransactionsPdf()
    {
        $this->paymentTransactions()->exportTransactionsPdf();
    }

    public function confirmTransaction($id)
    {
        $this->paymentTransactions()->confirmTransaction($id);
    }

    public function cancelTransaction($id)
    {
        $this->paymentTransactions()->cancelTransaction($id);
    }

    public function rejectTransaction($id)
    {
        $this->paymentTransactions()->rejectTransaction($id);
    }


    private ?ControllerPaymentChannels $paymentChannels = null;

    private function paymentChannels(): ControllerPaymentChannels
    {
        return $this->paymentChannels ??= new ControllerPaymentChannels();
    }

    public function payment_channels()
    {
        $this->paymentChannels()->payment_channels();
    }

    public function createChannel()
    {
        $this->paymentChannels()->createChannel();
    }

    public function updateChannel($id)
    {
        $this->paymentChannels()->updateChannel($id);
    }

    public function setDefaultChannel($id)
    {
        $this->paymentChannels()->setDefaultChannel($id);
    }

    public function deactivateChannel($id)
    {
        $this->paymentChannels()->deactivateChannel($id);
    }
}
