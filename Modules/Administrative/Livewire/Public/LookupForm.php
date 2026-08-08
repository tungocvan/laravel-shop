<?php

namespace Modules\Administrative\Livewire\Public;

use Livewire\Component;
use Modules\Administrative\Services\LookupService;

class LookupForm extends Component
{
    public string $submission_code = '';

    public string $lookup_token = '';

    public function lookup(LookupService $service)
    {
        $validated = $this->validate([
            'submission_code' => ['required', 'string', 'max:32', 'regex:/^HC-[0-9]{8}-[0-9]{5,}$/i'],
            'lookup_token' => ['required', 'string', 'max:64'],
        ], [
            'submission_code.regex' => 'Mã hồ sơ không đúng định dạng.',
        ]);

        $submission = $service->verify($validated['submission_code'], $validated['lookup_token']);
        $accessToken = $service->issueAccess($submission);

        return redirect()->route('administrative.lookup.show', $accessToken);
    }

    public function render()
    {
        return view('Administrative::livewire.public.lookup-form');
    }
}
