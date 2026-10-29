{{-- Shared sidebar shell; per-demo partials preserve DOM IDs, badge nodes and JavaScript bindings. --}}
<aside class="sb" id="sb">
    @include($brandView)
    @include($navView)
    @if(!empty($cardView))
        @include($cardView)
    @endif
    @if(!empty($footerView))
        @include($footerView)
    @endif
</aside>
