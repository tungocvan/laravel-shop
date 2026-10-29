@extends('demo::projects.daily-work-report.layouts.app')
@section('content')
<div class="app">
<!-- ========== BẢNG ĐIỀU KHIỂN BÊN TRÁI ========== -->
@include('demo::projects.daily-work-report.partials.sidebar')
<div class="mask" id="mask"></div>
<!-- ========== NỘI DUNG ========== -->
<main class="main">
<header class="top">
<button aria-label="Mở menu" class="burger" id="burger"><svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-width="2.2" viewbox="0 0 24 24" width="20"><path d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
<div><h1 id="pTitle">Dashboard</h1><div class="sub" id="pSub"></div></div>
<div class="right">
<span class="chip" id="today"></span>
<button class="btn pri qa noprint" data-icon="plus" onclick="go('nhap')">Nhập báo cáo</button>
<button class="btn noprint" data-icon="xls" onclick="exportAll()" title="Xuất mọi bảng ra file Excel .xlsx"><span class="lbl">Xuất Excel</span></button>
<div class="savebox noprint">
<div class="savest" id="saveSt"></div>
<button class="btn save" data-icon="save" id="saveBtn" onclick="saveToFile()" title="Lưu dữ liệu vào chính file này (Ctrl + S)"><span class="lbl">Lưu đè dữ liệu</span></button>
</div>
</div>
</header>
<div class="content">
<!-- DASHBOARD -->
@include('demo::projects.daily-work-report.pages.dashboard')
<!-- NHẬP BÁO CÁO -->
@include('demo::projects.daily-work-report.pages.nhap')
<!-- NHẬT KÝ / BÁO CÁO ĐÃ NỘP -->
@include('demo::projects.daily-work-report.pages.viec')
@include('demo::projects.daily-work-report.pages.ngay')
<!-- TỔNG HỢP -->
@include('demo::projects.daily-work-report.pages.tonghop')
<!-- DANH MỤC -->
@include('demo::projects.daily-work-report.pages.nv')
@include('demo::projects.daily-work-report.pages.guide')
@include('demo::projects.daily-work-report.pages.settings')
</div>
</main>
</div>
<div class="ov" id="ov"><div class="modal" id="modal"></div></div>
<div id="toasts"></div><div id="tip"></div>
<script id="du-lieu" type="application/json">{}</script>

@endsection
