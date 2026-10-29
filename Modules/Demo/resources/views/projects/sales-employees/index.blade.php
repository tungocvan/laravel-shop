@extends('demo::projects.sales-employees.layouts.app')

@section('content')
<body>
<div class="app">
  <!-- ========== BẢNG ĐIỀU KHIỂN BÊN TRÁI ========== -->
  @include('demo::projects.sales-employees.partials.sidebar')
  <div class="mask" id="mask"></div>

  <!-- ========== NỘI DUNG ========== -->
  <main class="main">
    @include('demo::projects.sales-employees.partials.header')
    <div class="content">
      <!-- DASHBOARD -->
      @include('demo::projects.sales-employees.pages.dashboard')

      <!-- NHẬT KÝ -->
      @include('demo::projects.sales-employees.pages.sales-journal')

      <!-- TỔNG HỢP THEO NHÂN VIÊN -->
      @include('demo::projects.sales-employees.pages.employee-summary')

      <!-- THEO THÁNG -->
      @include('demo::projects.sales-employees.pages.monthly-report')

      <!-- THEO SẢN PHẨM -->
      @include('demo::projects.sales-employees.pages.product-report')

      <!-- DANH MỤC -->
      @include('demo::projects.sales-employees.pages.employees')
      @include('demo::projects.sales-employees.pages.products')

      @include('demo::projects.sales-employees.pages.guide')
      @include('demo::projects.sales-employees.pages.settings')
    </div>
  </main>
</div>
@include('demo::projects.sales-employees.partials.modal')
<div id="toasts"></div>
<div id="tip"></div>

<!-- Dữ liệu mẫu mặc định. Dữ liệu người dùng được lưu trong localStorage. -->
<script id="du-lieu" type="application/json">{}</script>
<script src="{{ asset('demo-assets/doanh-so-nhan-vien/js/app.js') }}"></script>
<div data-demo-menu="doanh-so-nhan-vien" data-menu-storage-key="shared_demo_sidebar_menu_v1_doanh-so-nhan-vien"></div>
<script src="{{ asset('demo-assets/doanh-so-nhan-vien/shared/menu-editor.js') }}"></script>
</body>
@endsection
