<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #{{ $order->order_code }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 13px; color: #333; line-height: 1.5; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .company-info { text-align: right; }
        .company-name { font-size: 20px; font-weight: bold; color: #4f46e5; }

        /* Table Styles */
        table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        table td { padding: 5px; vertical-align: top; }
        table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        table tr.item td { border-bottom: 1px solid #eee; }
        table tr.total td { border-top: 2px solid #eee; font-weight: bold; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .text-red { color: #d00; }

        /* Nút in (ẩn khi in thật) */
        .no-print { margin-bottom: 20px; text-align: center; }
        @media print {
            .no-print { display: none; }
            .invoice-box { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>

    {{-- Chỉ hiện nút khi KHÔNG PHẢI là xuất PDF --}}
    @if(!isset($isPdf))
    <div class="no-print">
        <button onclick="window.print()" style="font-family: 'DejaVu Sans', sans-serif; padding: 10px 20px; background: #4f46e5; color: white; border: none; cursor: pointer; border-radius: 5px;">
            🖨️ In Hóa Đơn Ngay
        </button>
    </div>
    @endif

    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="4">
                    <table>
                        <tr>
                            <td class="title">
                                <h1 style="margin:0; color: #4f46e5;">FLEXBIZ STORE</h1>
                            </td>
                            <td class="text-right">
                                <strong>Mã đơn: #{{ $order->order_code }}</strong><br>
                                Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}<br>
                                Trạng thái: {{ $order->status }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="4">
                    <table>
                        <tr>
                            <td>
                                <strong>Người gửi:</strong><br>
                                FlexBiz Store<br>
                                123 Đường ABC, Quận 1, HCM<br>
                                hotline@flexbiz.com
                            </td>
                            <td class="text-right">
                                <strong>Người nhận:</strong><br>
                                {{ $order->customer_name }}<br>
                                {{ $order->customer_phone }}<br>
                                {{ $order->customer_address }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr style="height: 20px;"></tr>

            <tr class="heading">
                <td>Sản phẩm</td>
                <td class="text-center">Đơn giá</td>
                <td class="text-center">SL</td>
                <td class="text-right">Thành tiền</td>
            </tr>

            @foreach($order->items as $item)
                <tr class="item">
                    <td class="px-6 py-4">
                        {{ $item->product_name }}

                        @php
                            // 1. Lấy dữ liệu raw
                            $opts = $item->options;

                            // 2. Nếu nó là chuỗi (String) -> Thử Decode JSON
                            if (is_string($opts)) {
                                $opts = json_decode($opts, true);
                            }

                            // 3. Đảm bảo nó là mảng trước khi loop (Nếu null hoặc lỗi thì thành mảng rỗng)
                            if (!is_array($opts)) {
                                $opts = [];
                            }
                        @endphp

                        {{-- Giờ thì biến $opts chắc chắn là mảng, loop thoải mái --}}
                        @if(!empty($opts))
                            <br>
                            <small style="color: #666; font-style: italic;">
                                @foreach($opts as $key => $value)
                                    <span style="margin-right: 5px;">
                                        {{ $key }}: <strong>{{ $value }}</strong>
                                        @if(!$loop->last) | @endif
                                    </span>
                                @endforeach
                            </small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->price) }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->total) }} ₫</td>
                </tr>
            @endforeach

            <tr style="height: 10px;"></tr>

            <tr>
                <td colspan="2"></td>
                <td class="text-right">Tạm tính:</td>
                <td class="text-right">{{ number_format($order->subtotal) }} ₫</td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td class="text-right">Phí vận chuyển:</td>
                <td class="text-right">{{ number_format($order->shipping_fee) }} ₫</td>
            </tr>
            @if($order->discount > 0)
            <tr>
                <td colspan="2"></td>
                <td class="text-right">Giảm giá:</td>
                <td class="text-right text-red">- {{ number_format($order->discount) }} ₫</td>
            </tr>
            @endif
            <tr class="total">
                <td colspan="2"></td>
                <td class="text-right" style="padding-top: 10px;">TỔNG CỘNG:</td>
                <td class="text-right" style="padding-top: 10px; font-size: 16px; color: #4f46e5;">
                    {{ number_format($order->total) }} ₫
                </td>
            </tr>
        </table>

        <div style="margin-top: 50px; text-align: center; font-style: italic; color: #777;">
            Cảm ơn quý khách đã mua hàng tại FlexBiz Store!
        </div>
    </div>
</body>
</html>
