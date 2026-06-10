<?php

namespace App\controller;

/**
 * Legacy facade for request/* URLs.
 */
class ControllerRequest
{
    private ?ControllerRequestIntake $intake = null;

    private function intake(): ControllerRequestIntake
    {
        return $this->intake ??= new ControllerRequestIntake();
    }

    public function request()
    {
        $this->intake()->request();
    }

    public function store()
    {
        $this->intake()->store();
    }


    private ?ControllerRequestWorkflow $workflow = null;

    private function workflow(): ControllerRequestWorkflow
    {
        return $this->workflow ??= new ControllerRequestWorkflow();
    }

    public function updateStatus($id, $status = null)
    {
        $this->workflow()->updateStatus($id, $status);
    }

    public function cancel($id)
    {
        $this->workflow()->cancel($id);
    }

    public function confirmClosing($id)
    {
        $this->workflow()->confirmClosing($id);
    }

    public function contestClosing($id)
    {
        $this->workflow()->contestClosing($id);
    }

    public function confirmPaymentReceipt($id)
    {
        $this->workflow()->confirmPaymentReceipt($id);
    }

    public function contestPayment($id)
    {
        $this->workflow()->contestPayment($id);
    }

    public function openDispute($id)
    {
        $this->workflow()->openDispute($id);
    }

    public function approveAffiliate($id)
    {
        $this->workflow()->approveAffiliate($id);
    }

    public function rejectAffiliate($id)
    {
        $this->workflow()->rejectAffiliate($id);
    }


    private ?ControllerRequestChat $chat = null;

    private function chat(): ControllerRequestChat
    {
        return $this->chat ??= new ControllerRequestChat();
    }

    public function sendMessage($id)
    {
        $this->chat()->sendMessage($id);
    }
}
