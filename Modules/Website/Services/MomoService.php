<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\Http;
use Modules\Order\Contracts\PaymentResultVerifier;
use Modules\Order\Models\Order;
use RuntimeException;

class MomoService implements PaymentResultVerifier
{
    protected string $partnerCode;

    protected string $accessKey;

    protected string $secretKey;

    protected string $endpoint;

    protected int $timeout;

    public function __construct()
    {
        $this->partnerCode = (string) $this->config('partner_code', '');
        $this->accessKey = (string) $this->config('access_key', '');
        $this->secretKey = (string) $this->config('secret_key', '');
        $this->endpoint = (string) $this->config('endpoint', 'https://test-payment.momo.vn/v2/gateway/api/create');
        $this->timeout = (int) $this->config('timeout', 30);
    }

    public function createPayment(Order $order): array
    {
        $this->assertConfigured();

        $requestId = $order->order_code.'-'.now()->format('Hisv');
        $orderId = $order->order_code;
        $amount = (string) (int) round((float) $order->total);
        $orderInfo = 'Thanh toan '.$orderId;
        $redirectUrl = route('checkout.momo.callback');
        $ipnUrl = route('checkout.momo.ipn');
        $extraData = '';
        $requestType = 'captureWallet';

        $rawHash = 'accessKey='.$this->accessKey
            .'&amount='.$amount
            .'&extraData='.$extraData
            .'&ipnUrl='.$ipnUrl
            .'&orderId='.$orderId
            .'&orderInfo='.$orderInfo
            .'&partnerCode='.$this->partnerCode
            .'&redirectUrl='.$redirectUrl
            .'&requestId='.$requestId
            .'&requestType='.$requestType;

        $payload = [
            'partnerCode' => $this->partnerCode,
            'requestId' => $requestId,
            'amount' => (int) $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'autoCapture' => true,
            'signature' => hash_hmac('sha256', $rawHash, $this->secretKey),
        ];

        try {
            $response = Http::acceptJson()
                ->timeout($this->timeout)
                ->post($this->endpoint, $payload);
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException('Không thể kết nối cổng thanh toán MoMo. Vui lòng thử lại sau.');
        }

        if ($response->failed()) {
            throw new RuntimeException('MoMo đang tạm thời không phản hồi. Vui lòng thử lại sau.');
        }

        $result = $response->json();

        if (! is_array($result) || (int) ($result['resultCode'] ?? -1) !== 0 || empty($result['payUrl'])) {
            throw new RuntimeException((string) ($result['message'] ?? 'Không thể khởi tạo thanh toán MoMo.'));
        }

        return $result;
    }

    public function verifyResultSignature(array $payload): bool
    {
        $this->assertConfigured();

        $required = [
            'amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType',
            'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode',
            'transId', 'signature',
        ];

        foreach ($required as $key) {
            if (! array_key_exists($key, $payload)) {
                return false;
            }
        }

        if ((string) $payload['partnerCode'] !== $this->partnerCode) {
            return false;
        }

        $rawHash = 'accessKey='.$this->accessKey
            .'&amount='.$payload['amount']
            .'&extraData='.$payload['extraData']
            .'&message='.$payload['message']
            .'&orderId='.$payload['orderId']
            .'&orderInfo='.$payload['orderInfo']
            .'&orderType='.$payload['orderType']
            .'&partnerCode='.$payload['partnerCode']
            .'&payType='.$payload['payType']
            .'&requestId='.$payload['requestId']
            .'&responseTime='.$payload['responseTime']
            .'&resultCode='.$payload['resultCode']
            .'&transId='.$payload['transId'];

        $expected = hash_hmac('sha256', $rawHash, $this->secretKey);

        return hash_equals($expected, (string) $payload['signature']);
    }

    protected function assertConfigured(): void
    {
        if ($this->partnerCode === '' || $this->accessKey === '' || $this->secretKey === '' || $this->endpoint === '') {
            throw new RuntimeException('Cấu hình thanh toán MoMo chưa đầy đủ.');
        }
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return config(
            'website.website.payment.momo.'.$key,
            config('website.payment.momo.'.$key, $default)
        );
    }
}
