<?php

namespace Modules\Pharma\Livewire\Concerns;

trait AuthorizesPharmaActions
{
    protected function authorizePharma(string $permission): void
    {
        abort_unless(
            auth('admin')->check() && auth('admin')->user()->can($permission),
            403
        );
    }

    protected function authorizePharmaView(): void
    {
        $this->authorizePharma('view_pharma');
    }

    protected function authorizePharmaCreate(): void
    {
        $this->authorizePharma('create_pharma');
    }

    protected function authorizePharmaEdit(): void
    {
        $this->authorizePharma('edit_pharma');
    }

    protected function authorizePharmaDelete(): void
    {
        $this->authorizePharma('delete_pharma');
    }
}
