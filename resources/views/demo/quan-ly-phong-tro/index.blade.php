@extends('demo.quan-ly-phong-tro.layouts.app')
@section('content')
<div class="app">
  <!-- ========== BẢNG ĐIỀU KHIỂN BÊN TRÁI ========== -->
  @include('demo.quan-ly-phong-tro.partials.sidebar')
  <div class="mask" id="mask"></div>

  <!-- ========== NỘI DUNG ========== -->
  <main class="main">
    @include('demo.quan-ly-phong-tro.partials.header')

    <div class="content">
      <!-- DASHBOARD -->
      @include('demo.quan-ly-phong-tro.pages.dashboard')

      <!-- SƠ ĐỒ PHÒNG -->
      @include('demo.quan-ly-phong-tro.pages.rooms')

      <!-- GHI ĐIỆN NƯỚC -->
      @include('demo.quan-ly-phong-tro.pages.meter')

      <!-- HÓA ĐƠN -->
      @include('demo.quan-ly-phong-tro.pages.invoices')

      <!-- THU TIỀN & CÔNG NỢ -->
      @include('demo.quan-ly-phong-tro.pages.debts')

      <!-- NGƯỜI THUÊ -->
      @include('demo.quan-ly-phong-tro.pages.tenants')

      <!-- BÁO CÁO -->
      @include('demo.quan-ly-phong-tro.pages.report')

      <!-- CÀI ĐẶT -->
      @include('demo.quan-ly-phong-tro.pages.settings')

      <!-- HƯỚNG DẪN -->
      @include('demo.quan-ly-phong-tro.pages.guide')
    </div>
  </main>
</div>

<div class="ov" id="ov"><div class="modal" id="modal"></div></div>
<div id="toasts"></div>
<div id="tip"></div>
<div id="printArea"></div>

<!-- DỮ LIỆU LƯU TRONG FILE: nút "Lưu đè dữ liệu" sẽ ghi đè phần này -->
@endsection
