<?php

namespace App\Support;

use Illuminate\Auth\SessionGuard;

class WorkspaceGuard extends SessionGuard
{
    public function getRecallerName()
    {
        $workspace = $this->request?->attributes->get('workspace');
        return parent::getRecallerName().($workspace ? '_'.$workspace : '');
    }
}
