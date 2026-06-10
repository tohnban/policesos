<?php

namespace App\controller;

/**
 * Legacy facade for notification/* URLs.
 */
class ControllerNotification
{
    private ?ControllerNotificationInbox $inboxController = null;

    private function inboxController(): ControllerNotificationInbox
    {
        return $this->inboxController ??= new ControllerNotificationInbox();
    }

    public function inbox()
    {
        $this->inboxController()->inbox();
    }

    public function archive()
    {
        $this->inboxController()->archive();
    }


    private ?ControllerNotificationActions $actions = null;

    private function actions(): ControllerNotificationActions
    {
        return $this->actions ??= new ControllerNotificationActions();
    }

    public function markAsRead()
    {
        $this->actions()->markAsRead();
    }

    public function markAllAsRead()
    {
        $this->actions()->markAllAsRead();
    }

    public function archiveItem()
    {
        $this->actions()->archiveItem();
    }

    public function archiveAll()
    {
        $this->actions()->archiveAll();
    }

    public function unarchive()
    {
        $this->actions()->unarchive();
    }

    public function delete()
    {
        $this->actions()->delete();
    }
}
