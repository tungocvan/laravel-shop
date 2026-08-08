<div class="max-w-7xl mx-auto py-10 px-4 space-y-6">

    @include('Admission::livewire.admission.partials.error-summary')

    @include('Admission::livewire.admission.partials.stepper')

    <form wire:submit.prevent="save" class="space-y-8">

        @if ($currentStep == 1)
            @include('Admission::livewire.admission.partials.step-1-student')
        @endif

        @if ($currentStep == 2)
            @include('Admission::livewire.admission.partials.step-2-address')
        @endif

        @if ($currentStep == 3)
            @include('Admission::livewire.admission.partials.step-3-extra')
        @endif

        @if ($currentStep == 4)
            @include('Admission::livewire.admission.partials.step-4-parent')
        @endif

        @if ($currentStep == 5)
            @include('Admission::livewire.admission.partials.step-5-confirm')
        @endif

        @include('Admission::livewire.admission.partials.actions')

    </form>


    <div x-data="{
        open: false,
        name: '',
        redirectUrl: '',

        show(event) {
            const data = event.detail?.[0] ?? event.detail;

            this.open = true;
            this.name = data.name;
            this.redirectUrl = data.redirectUrl;
        },

        confirm() {
            window.location.href = this.redirectUrl;
        }
    }" x-on:show-success-modal.window="show($event)">
        <!-- BACKDROP -->
        <div x-show="open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-transition>
            <!-- MODAL -->
            <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md text-center">

                <div class="text-green-600 text-5xl mb-3">✔</div>

                <h2 class="text-xl font-bold text-gray-800">
                    Đăng ký thành công!
                </h2>

                <p class="text-gray-600 mt-2">
                    Học sinh: <span class="font-semibold text-gray-900" x-text="name"></span>
                </p>

                <p class="text-sm text-gray-500 mt-1">
                    Hồ sơ đã được ghi nhận thành công.
                </p>

                <button @click="confirm()"
                    class="mt-5 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                    OK
                </button>

            </div>
        </div>
    </div>

</div>
