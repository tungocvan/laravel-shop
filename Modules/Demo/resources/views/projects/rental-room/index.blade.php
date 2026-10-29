@extends('demo::projects.rental-room.layouts.app')
@section('content')
<div class="app">
  <!-- ========== BẢNG ĐIỀU KHIỂN BÊN TRÁI ========== -->
  @include('demo::projects.rental-room.partials.sidebar')
  <div class="mask" id="mask"></div>

  <!-- ========== NỘI DUNG ========== -->
  <main class="main">
    @include('demo::projects.rental-room.partials.header')

    <div class="content">
      <!-- DASHBOARD -->
      @include('demo::projects.rental-room.pages.dashboard')

      <!-- SƠ ĐỒ PHÒNG -->
      @include('demo::projects.rental-room.pages.rooms')

      <!-- GHI ĐIỆN NƯỚC -->
      @include('demo::projects.rental-room.pages.meter')

      <!-- HÓA ĐƠN -->
      @include('demo::projects.rental-room.pages.invoices')

      <!-- THU TIỀN & CÔNG NỢ -->
      @include('demo::projects.rental-room.pages.debts')

      <!-- NGƯỜI THUÊ -->
      @include('demo::projects.rental-room.pages.tenants')

      <!-- BÁO CÁO -->
      @include('demo::projects.rental-room.pages.report')

      <!-- CÀI ĐẶT -->
      @include('demo::projects.rental-room.pages.settings')

      <!-- HƯỚNG DẪN -->
      @include('demo::projects.rental-room.pages.guide')
    </div>
  </main>
</div>

<div class="ov" id="ov"><div class="modal" id="modal"></div></div>
<div id="toasts"></div>
<div id="tip"></div>
<div id="printArea"></div>

<!-- DỮ LIỆU LƯU TRONG FILE: nút "Lưu đè dữ liệu" sẽ ghi đè phần này -->
@endsection
