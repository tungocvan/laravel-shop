@extends('Admin::layouts.master')

@section('content')
    <style>
        .product-admin-workspace nav[role="navigation"] a,
        .product-admin-workspace nav[role="navigation"] span {
            color: rgb(75 85 99) !important;
            background-color: white !important;
            border-color: rgb(229 231 235) !important;
        }

        .product-admin-workspace nav[role="navigation"] a:hover {
            color: rgb(67 56 202) !important;
            background-color: rgb(238 242 255) !important;
            border-color: rgb(199 210 254) !important;
        }

        .product-admin-workspace nav[role="navigation"] [aria-current="page"] > span {
            background-color: rgb(79 70 229) !important;
            border-color: rgb(79 70 229) !important;
            color: white !important;
        }

        .product-admin-workspace nav[role="navigation"] span[aria-disabled="true"] > span {
            color: rgb(156 163 175) !important;
            background-color: white !important;
        }
    </style>

    <div class="product-admin-workspace">
        @livewire('product.products.product-table')
    </div>
@endsection
