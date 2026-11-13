@extends('ClientPortal::layouts.application')
@section('title', $pagePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<main class="mx-auto max-w-3xl space-y-4 px-2 py-4 sm:px-5">
 <a href="{{ route('client.partners.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-indigo-700">← Danh sách đối tác</a>
 <header><p class="text-xs font-semibold uppercase text-slate-500">{{ $pagePresentation['eyebrow'] }}</p><h1 class="text-2xl font-bold">{{ $pagePresentation['page_title'] }}</h1><p class="text-sm text-slate-500">{{ $pagePresentation['page_description'] }}</p></header>
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <form method="POST" action="{{ $partner ? route('client.partners.update',$partner) : route('client.partners.store') }}" class="space-y-4">
 @csrf @if($partner) @method('PUT') @endif
 <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
 <h2 class="sm:col-span-2 font-bold">Thông tin chính</h2>
 @foreach(['name'=>['Tên đối tác *','text'],'tax_code'=>['Mã số thuế','text']] as $field=>$meta)
 <label class="text-sm font-medium">{{ $meta[0] }}<input name="{{ $field }}" value="{{ old($field,$partner?->{$field}) }}" @required($field==='name') class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>@endforeach
 @foreach(['legal_type'=>['Loại pháp lý',\Modules\Partner\Models\Partner::LEGAL_TYPES],'source'=>['Nguồn dữ liệu',\Modules\Partner\Models\Partner::SOURCES],'status'=>['Trạng thái',\Modules\Partner\Models\Partner::STATUSES]] as $field=>$meta)
 <label class="text-sm font-medium">{{ $meta[0] }}<select name="{{ $field }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3">@foreach($meta[1] as $key=>$label)<option value="{{ $key }}" @selected(old($field,$partner?->{$field}??($field==='legal_type'?'company':($field==='source'?'manual':'active')))===$key)>{{ $label }}</option>@endforeach</select></label>@endforeach
 <fieldset class="sm:col-span-2"><legend class="text-sm font-medium">Vai trò đối tác *</legend><div class="mt-2 grid grid-cols-2 gap-3">@foreach(\Modules\Partner\Models\Partner::PARTNER_TYPES as $key=>$label)<label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 p-3 text-sm"><input type="checkbox" name="partner_types[]" value="{{ $key }}" @checked(in_array($key,old('partner_types',$partner?->partner_types??[]),true))>{{ $label }}</label>@endforeach</div></fieldset>
 </section>
 <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2"><h2 class="sm:col-span-2 font-bold">Thông tin liên hệ</h2>
 @foreach(['contact_person'=>'Người liên hệ','phone'=>'Số điện thoại','email'=>'Email','province_code'=>'Mã tỉnh/thành'] as $field=>$label)<label class="text-sm font-medium">{{ $label }}<input name="{{ $field }}" value="{{ old($field,$partner?->{$field}) }}" @if($field==='email') type="email" @endif class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>@endforeach
 @foreach(['address'=>'Địa chỉ','note'=>'Ghi chú'] as $field=>$label)<label class="sm:col-span-2 text-sm font-medium">{{ $label }}<textarea name="{{ $field }}" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 p-3">{{ old($field,$partner?->{$field}) }}</textarea></label>@endforeach
 </section>
 <div class="flex justify-end gap-3"><a href="{{ route('client.partners.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-5">Hủy</a><button class="min-h-11 rounded-xl bg-indigo-700 px-5 font-semibold text-white">Lưu đối tác</button></div>
 </form>
</main>
@endsection