<a href="{{ route('client.request.dashboard') }}" @class(['flex flex-col items-center gap-1 text-xs font-semibold', 'text-slate-900' => request()->routeIs('client.request.dashboard'), 'text-slate-500' => ! request()->routeIs('client.request.dashboard')]) @if(request()->routeIs('client.request.dashboard')) aria-current="page" @endif>
    <span class="text-lg">⌂</span>
    <span>Tổng quan</span>
</a>
<a href="{{ route('client.request.catalog') }}" @class(['flex flex-col items-center gap-1 text-xs font-semibold', 'text-slate-900' => request()->routeIs('client.request.catalog', 'client.request.create'), 'text-slate-500' => ! request()->routeIs('client.request.catalog', 'client.request.create')]) @if(request()->routeIs('client.request.catalog', 'client.request.create')) aria-current="page" @endif>
    <span class="text-lg">＋</span>
    <span>Tạo đề nghị</span>
</a>
<a href="{{ route('client.request.mine') }}" @class(['flex flex-col items-center gap-1 text-xs font-semibold', 'text-slate-900' => request()->routeIs('client.request.mine', 'client.request.show'), 'text-slate-500' => ! request()->routeIs('client.request.mine', 'client.request.show')]) @if(request()->routeIs('client.request.mine', 'client.request.show')) aria-current="page" @endif>
    <span class="text-lg">☷</span>
    <span>Của tôi</span>
</a>
