<div class="max-w-7xl mx-auto py-10 px-4 space-y-6"
    @if($isEdit)
        x-data="{ uiStep: {{ (int) $currentStep }} }"
        x-on:admission-validation-step-opened.window="uiStep = Number($event.detail?.step ?? $event.detail?.[0]?.step ?? uiStep)"
    @endif
>

    @include('Admission::livewire.admission.partials.error-summary')

    @include('Admission::livewire.admission.partials.stepper')

    <form wire:submit.prevent="save" class="space-y-8">

        @if ($isEdit)
            <div x-show="uiStep === 1" x-cloak>@include('Admission::livewire.admission.partials.step-1-student')</div>
            <div x-show="uiStep === 2" x-cloak>@include('Admission::livewire.admission.partials.step-2-address')</div>
            <div x-show="uiStep === 3" x-cloak>@include('Admission::livewire.admission.partials.step-3-extra')</div>
            <div x-show="uiStep === 4" x-cloak>@include('Admission::livewire.admission.partials.step-4-parent')</div>
            <div x-show="uiStep === 5" x-cloak>@include('Admission::livewire.admission.partials.step-5-confirm')</div>
        @else
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
        @endif

        @include('Admission::livewire.admission.partials.actions')

    </form>


    <div x-data="{
        open: false,
        step: 1,
        stepName: '',
        message: '',

        show(event) {
            const data = event.detail?.[0] ?? event.detail;
            this.step = data.step;
            this.stepName = data.stepName;
            this.message = data.message;
            this.open = true;
        },

        async goToStep() {
            const target = this.step;
            this.open = false;
            await $wire.goToValidationStep(target);
        }
    }"
        x-on:show-validation-modal.window="show($event)"
        x-on:admission-validation-step-opened.window="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
        <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 px-3 py-4 backdrop-blur-sm" x-transition>
            <div class="m-auto w-[calc(100%-24px)] max-w-[520px] rounded-[28px] bg-white p-6 shadow-2xl" @click.stop>
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-2xl text-amber-700">!</div>
                <h2 class="mt-4 text-center text-xl font-bold text-gray-900">Hồ sơ chưa đầy đủ thông tin</h2>
                <p class="mt-2 text-center font-semibold text-indigo-700">
                    Bước <span x-text="step"></span> – <span x-text="stepName"></span>
                </p>
                <p class="mt-3 text-center text-sm leading-6 text-gray-600" x-text="message"></p>
                <button type="button" @click="goToStep()"
                    class="mt-6 w-full rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700">
                    Về bước cần cập nhật
                </button>
            </div>
        </div>
    </div>

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
