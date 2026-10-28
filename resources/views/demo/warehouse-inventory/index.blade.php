@extends('demo.warehouse-inventory.layouts.app')
@section('content')
<div class="app">
  <!-- ========== BẢNG ĐIỀU KHIỂN BÊN TRÁI ========== -->
  @include('demo.warehouse-inventory.partials.sidebar')
  <div class="mask" id="mask"></div>

  <!-- ========== NỘI DUNG ========== -->
  <main class="main">
    @include('demo.warehouse-inventory.partials.header')

    <div class="content">
      <!-- DASHBOARD -->
      @include('demo.warehouse-inventory.pages.dashboard')

      <!-- SẢN PHẨM -->
      @include('demo.warehouse-inventory.pages.products')

      <!-- NHẬP / XUẤT -->
      @include('demo.warehouse-inventory.pages.stock-in')
      @include('demo.warehouse-inventory.pages.stock-out')

      <!-- LỊCH SỬ -->
      @include('demo.warehouse-inventory.pages.history')

      <!-- BÁO CÁO XNT -->
      @include('demo.warehouse-inventory.pages.report')

      <!-- CÀI ĐẶT -->
      @include('demo.warehouse-inventory.pages.settings')
    </div>
  </main>
</div>

<!-- MODAL -->
@include('demo.warehouse-inventory.partials.modal')
<div id="toasts"></div>
<div id="tip"></div>
<div id="printArea"></div>
@include('demo.warehouse-inventory.partials.embedded-data')
<x-demo.sidebar-menu-builder slug="quan-ly-xuat-nhap-ton" />
@endsection
