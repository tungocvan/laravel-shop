<?php

namespace Modules\Invoices\Livewire;

use Livewire\Component;

class InvoiceList extends Component
{
    public function render()
    {
        return view('Invoices::livewire.invoice-list');
    }
}
