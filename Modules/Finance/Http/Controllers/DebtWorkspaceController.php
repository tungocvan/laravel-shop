<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DebtWorkspaceController extends Controller
{
    public function __invoke(?string $section = null): View
    {
        $section = $section ?? 'dashboard';

        abort_unless(in_array($section, [
            'dashboard', 'partners', 'documents', 'ledger', 'summary', 'aging', 'settings',
        ], true), 404);

        return view('Finance::debts.workspace', ['section' => $section]);
    }
}
