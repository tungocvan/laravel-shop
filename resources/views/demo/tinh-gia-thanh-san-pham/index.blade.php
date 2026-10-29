@extends('demo.tinh-gia-thanh-san-pham.layouts.app')
@section('content')

<div class="app">
<!-- ========== BẢNG ĐIỀU KHIỂN BÊN TRÁI ========== -->
@include('demo.tinh-gia-thanh-san-pham.partials.sidebar')
<div class="mask" id="mask"></div>
<!-- ========== NỘI DUNG ========== -->
<main class="main">
<header class="top">
<button aria-label="Mở menu" class="burger" id="burger"><svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-width="2.2" viewbox="0 0 24 24" width="20"><path d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
<div><h1 id="pTitle">Dashboard</h1><div class="sub" id="pSub"></div></div>
<div class="right">
<span class="chip" id="today"></span>
<button class="btn pri noprint hide-m" onclick="go('period')"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2.4" viewbox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>Nhập chi phí kỳ</button>
<button class="btn noprint hide-m" id="xlBtn" title="Xuất toàn bộ bảng ra file .xlsx"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"></path></svg>Xuất Excel</button>
<div class="savebox noprint">
<div class="savest" id="saveSt"></div>
<button class="btn save" id="saveBtn" onclick="saveToFile()" title="Lưu dữ liệu vào chính file này (Ctrl + S)"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>Lưu đè dữ liệu</button>
</div>
</div>
</header>
<div class="content">
@include('demo.tinh-gia-thanh-san-pham.pages.dashboard')
@include('demo.tinh-gia-thanh-san-pham.pages.period')
@include('demo.tinh-gia-thanh-san-pham.pages.bom')
@include('demo.tinh-gia-thanh-san-pham.pages.products')
@include('demo.tinh-gia-thanh-san-pham.pages.mats')
@include('demo.tinh-gia-thanh-san-pham.pages.report')
@include('demo.tinh-gia-thanh-san-pham.pages.trend')
@include('demo.tinh-gia-thanh-san-pham.pages.settings')
@include('demo.tinh-gia-thanh-san-pham.pages.guide')
</div>
</main>
</div>
@include('demo.tinh-gia-thanh-san-pham.partials.overlay')
<div id="toasts"></div>
<div id="tip"></div>
<div id="printArea"></div>
<!-- DỮ LIỆU LƯU TRONG FILE: nút "Lưu đè dữ liệu" sẽ ghi đè phần này. null = chưa lưu lần nào, mở ra sẽ dùng dữ liệu mẫu -->
<script id="du-lieu" type="application/json">null</script>


<x-demo.sidebar-menu-builder slug="tinh-gia-thanh-san-pham" />

@endsection
