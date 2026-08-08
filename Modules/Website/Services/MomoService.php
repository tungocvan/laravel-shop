<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\Http;

class MomoService
{
    protected $partnerCode;
    protected $accessKey;
    protected $secretKey;
    protected $endpoint;

    public function __construct()
    {
        $this->partnerCode = config('website.website.payment.momo.partner_code');
        $this->accessKey   = config('website.website.payment.momo.access_key');
        $this->secretKey   = config('website.website.payment.momo.secret_key');
        $this->endpoint    = config('website.website.payment.momo.endpoint');
    }

    public function createPayment($order)
    {
        // 1. CẤU HÌNH (Dùng Key Mặc Định Chuẩn)
        $endpoint = 'https://test-payment.momo.vn/v2/gateway/api/create';
        $partnerCode = 'MOMOBKUN20180529';
        $accessKey = 'klm05TvNBzhg7h7j';
        $secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEI2bsa';

        // 2. CHUẨN BỊ DỮ LIỆU
        $requestId = (string) time();
        $orderId = $order->order_code;
        $amount = (string) (int) $order->total; // Ép kiểu string số nguyên
        $orderInfo = "Thanh toan " . $orderId;

        // Lưu ý: Dùng route() có thể sinh ra http hoặc https tùy server
        // Hãy đảm bảo url này chính xác
        $redirectUrl = route('website.checkout.momo.callback');
        $ipnUrl = route('website.checkout.momo.callback');

        $extraData = ""; // Để rỗng, không null
        $requestType = "captureWallet";

        // 3. TẠO CHUỖI ĐỂ KÝ (RAW HASH)
        // Bắt buộc đúng thứ tự A-Z
        $rawHash = "accessKey=" . $accessKey .
                   "&amount=" . $amount .
                   "&extraData=" . $extraData .
                   "&ipnUrl=" . $ipnUrl .
                   "&orderId=" . $orderId .
                   "&orderInfo=" . $orderInfo .
                   "&partnerCode=" . $partnerCode .
                   "&redirectUrl=" . $redirectUrl .
                   "&requestId=" . $requestId .
                   "&requestType=" . $requestType;

        // 4. TẠO CHỮ KÝ
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        // 5. ĐÓNG GÓI DỮ LIỆU (QUAN TRỌNG NHẤT)
        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'Test',
            'storeId' => 'MomoTestStore',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        ];

        // MẸO: Tự encode JSON để kiểm soát dấu gạch chéo (/)
        // JSON_UNESCAPED_SLASHES: Giữ nguyên https:// thay vì https:\/\/
        // JSON_UNESCAPED_UNICODE: Giữ nguyên tiếng Việt
        $jsonPayload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            // Gửi Raw Body thay vì để Laravel tự xử lý
            $response = Http::withBody($jsonPayload, 'application/json')
                ->withoutVerifying()
                ->post($endpoint);

            // Debug nếu vẫn lỗi
            if ($response->failed() || ($response->json()['errorCode'] ?? 0) != 0) {
                dd(
                    'Momo Failed:', $response->json(),
                    'Signature Created From:', $rawHash,
                    'JSON Sent:', $jsonPayload
                );
            }

            return $response->json();

        } catch (\Exception $e) {
            dd('Connection Error:', $e->getMessage());
        }
    }
}
