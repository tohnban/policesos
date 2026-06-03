<?php

namespace Src\classes;

use App\services\DashboardMenuBuilder;
use App\services\HeaderShellService;

final class LayoutPresenter
{
    public static function prime(ClassRender $render): void
    {
        if (!ClassAuth::check()) {
            $render->setData([
                'headerShell' => HeaderShellService::forGuest(),
                'dashboardMenu' => [],
            ]);
            return;
        }

        $user = ClassAuth::user();
        if (!is_array($user)) {
            $render->setData([
                'headerShell' => HeaderShellService::forGuest(),
                'dashboardMenu' => [],
            ]);
            return;
        }

        $render->setData([
            'headerShell' => HeaderShellService::forAuthenticatedUser($user),
            'dashboardMenu' => DashboardMenuBuilder::build($user),
        ]);
    }
}
