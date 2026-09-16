<div>
    @include('Pharma::livewire.price-list.export-configurator-v31')
    @include('Pharma::livewire.price-list.export-media-dimensions')

    @if($open && $activeSection === 'brand')
        <div
            x-data
            x-init="$nextTick(() => {
                const labels = [...document.querySelectorAll('label')];
                const label = labels.find(el => [...el.childNodes].some(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim() === 'Năm'));
                if (!label) return;

                const textNode = [...label.childNodes].find(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim() === 'Năm');
                if (textNode) textNode.textContent = 'Ngày tháng năm';

                const input = label.querySelector('input');
                if (!input || input.value.trim() !== '') return;

                const now = new Date();
                const pad = value => String(value).padStart(2, '0');
                input.value = `Ngày ${pad(now.getDate())} tháng ${pad(now.getMonth() + 1)} năm ${now.getFullYear()}`;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            })"
            class="hidden"
            aria-hidden="true"
        ></div>
    @endif
</div>
