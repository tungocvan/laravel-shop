@extends('demo::projects.warehouse-inventory.layouts.app')
@section('content')
<div class="app">
  <!-- ========== BẢNG ĐIỀU KHIỂN BÊN TRÁI ========== -->
  @include('demo::projects.warehouse-inventory.partials.sidebar')
  <div class="mask" id="mask"></div>

  <!-- ========== NỘI DUNG ========== -->
  <main class="main">
    @include('demo::projects.warehouse-inventory.partials.header')

    <div class="content">
      <!-- DASHBOARD -->
      @include('demo::projects.warehouse-inventory.pages.dashboard')

      <!-- SẢN PHẨM -->
      @include('demo::projects.warehouse-inventory.pages.products')

      <!-- NHẬP / XUẤT -->
      @include('demo::projects.warehouse-inventory.pages.stock-in')
      @include('demo::projects.warehouse-inventory.pages.stock-out')

      <!-- LỊCH SỬ -->
      @include('demo::projects.warehouse-inventory.pages.history')

      <!-- BÁO CÁO XNT -->
      @include('demo::projects.warehouse-inventory.pages.report')

      <!-- CÀI ĐẶT -->
      @include('demo::projects.warehouse-inventory.pages.settings')
    </div>
  </main>
</div>

<!-- MODAL -->
@include('demo::projects.warehouse-inventory.partials.modal')
<div id="toasts"></div>
<div id="tip"></div>
<div id="printArea"></div>
@include('demo::projects.warehouse-inventory.partials.embedded-data')
<div data-demo-menu="quan-ly-xuat-nhap-ton" data-menu-storage-key="shared_demo_sidebar_menu_v1_quan-ly-xuat-nhap-ton"></div>
<script src="{{ asset('demo-assets/quan-ly-xuat-nhap-ton/shared/menu-editor.js') }}"></script>
@endsection
