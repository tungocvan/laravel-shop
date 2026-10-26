@extends('demo.sales-employees.layouts.app')

@section('content')
<body>
<div class="app">
  <!-- ========== BẢNG ĐIỀU KHIỂN BÊN TRÁI ========== -->
  @include('demo.sales-employees.partials.sidebar')
  <div class="mask" id="mask"></div>

  <!-- ========== NỘI DUNG ========== -->
  <main class="main">
    @include('demo.sales-employees.partials.header')
    <div class="content">
      <!-- DASHBOARD -->
      @include('demo.sales-employees.pages.dashboard')

      <!-- NHẬT KÝ -->
      @include('demo.sales-employees.pages.sales-journal')

      <!-- TỔNG HỢP THEO NHÂN VIÊN -->
      @include('demo.sales-employees.pages.employee-summary')

      <!-- THEO THÁNG -->
      @include('demo.sales-employees.pages.monthly-report')

      <!-- THEO SẢN PHẨM -->
      @include('demo.sales-employees.pages.product-report')

      <!-- DANH MỤC -->
      @include('demo.sales-employees.pages.employees')
      @include('demo.sales-employees.pages.products')

      @include('demo.sales-employees.pages.guide')
      @include('demo.sales-employees.pages.settings')
    </div>
  </main>
</div>
@include('demo.sales-employees.partials.modal')
<div id="toasts"></div>
<div id="tip"></div>

<!-- Dữ liệu mẫu mặc định. Dữ liệu người dùng được lưu trong localStorage. -->
<script id="du-lieu" type="application/json">{}</script>
<script src="{{ asset('demo/sales-employees/js/app.js') }}"></script>
</body>
@endsection
