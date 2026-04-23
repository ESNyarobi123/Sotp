<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\Workspace;

trait UsesAuthWorkspace
{
    protected function authWorkspace(): Workspace
    {
        $workspace = auth()->user()?->workspace;

        abort_unless($workspace instanceof Workspace, 403, 'Workspace not found. Complete registration or contact support.');

        if ($workspace->is_suspended && ! auth()->user()?->isAdmin()) {
            abort(403, 'This workspace has been suspended. Contact support for assistance.');
        }

        return $workspace;
    }
}
