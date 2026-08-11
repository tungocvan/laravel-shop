<?php

namespace Tests\Feature\Website;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Modules\Website\Http\Controllers\CheckoutController;
use Modules\Website\Http\Requests\CheckoutRequest;
use Modules\Website\Models\Order;
use Modules\Website\Services\MomoService;
use RuntimeException;
use Tests\TestCase;

class WebsiteCheckoutConfigurationTest extends TestCase
{
    public function test_checkout_accepts_only_approved_payment_methods(): void
    {
        $base = [
            'customer_name' => 'Test User',
            'customer_phone' => '0900000000',
            'customer_email' => 'test@example.com',
            'customer_address' => 'Test address',
            'note' => null,
        ];

        $rules = (new CheckoutRequest())->rules();

        foreach (['cod', 'bank_transfer', 'momo'] as $method) {
            $this->assertFalse(Validator::make($base + ['payment_method' => $method], $rules)->fails());
        }

        foreach (['vnpay', 'unknown', ''] as $method) {
            $this->assertTrue(Validator::make($base + ['payment_method' => $method], $rules)->fails());
        }
    }

    public function test_momo_routes_map_to_real_controller_actions(): void
    {
        $callback = Route::getRoutes()->getByName('checkout.momo.callback');
        $ipn = Route::getRoutes()->getByName('checkout.momo.ipn');

        $this->assertNotNull($callback);
        $this->assertSame(CheckoutController::class . '@momoCallback', $callback->getActionName());
        $this->assertContains('GET', $callback->methods());

        $this->assertNotNull($ipn);
        $this->assertSame(CheckoutController::class . '@momoIpn', $ipn->getActionName());
        $this->assertContains('POST', $ipn->methods());

        $this->assertTrue(method_exists(CheckoutController::class, 'momoCallback'));
        $this->assertTrue(method_exists(CheckoutController::class, 'momoIpn'));
    }

    public function test_momo_create_payment_uses_config_and_returns_pay_url(): void
    {
        $this->setMomoConfig();

        Http::fake([
            'https://test-payment.momo.vn/*' => Http::response([
                'partnerCode' => 'TESTPARTNER',
                'requestId' => 'request-id',
                'orderId' => 'ORD-TEST-001',
                'amount' => 120000,
                'resultCode' => 0,
                'message' => 'Success',
                'payUrl' => 'https://test-payment.momo.vn/pay/test-token',
            ], 200),
        ]);

        $order = new Order([
            'order_code' => 'ORD-TEST-001',
            'total' => 120000,
            'payment_method' => 'momo',
            'status' => 'pending_payment',
        ]);

        $result = app(MomoService::class)->createPayment($order);

        $this->assertSame(0, $result['resultCode']);
        $this->assertSame('https://test-payment.momo.vn/pay/test-token', $result['payUrl']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://test-payment.momo.vn/v2/gateway/api/create'
                && $data['partnerCode'] === 'TESTPARTNER'
                && $data['orderId'] === 'ORD-TEST-001'
                && $data['amount'] === 120000
                && $data['requestType'] === 'captureWallet'
                && !empty($data['signature'])
                && str_contains($data['redirectUrl'], '/checkout/momo-callback')
                && str_contains($data['ipnUrl'], '/checkout/momo-ipn');
        });
    }

    public function test_momo_gateway_failure_throws_controlled_exception(): void
    {
        $this->setMomoConfig();

        Http::fake([
            'https://test-payment.momo.vn/*' => Http::response([
                'resultCode' => 99,
                'message' => 'Gateway unavailable',
            ], 200),
        ]);

        $order = new Order([
            'order_code' => 'ORD-TEST-002',
            'total' => 100000,
            'payment_method' => 'momo',
            'status' => 'pending_payment',
        ]);

        $this->expectException(RuntimeException::class);
        app(MomoService::class)->createPayment($order);
    }

    public function test_momo_result_signature_is_verified(): void
    {
        $this->setMomoConfig();

        $payload = [
            'partnerCode' => 'TESTPARTNER',
            'orderId' => 'ORD-TEST-003',
            'requestId' => 'REQ-003',
            'amount' => 150000,
            'orderInfo' => 'Thanh toan ORD-TEST-003',
            'orderType' => 'momo_wallet',
            'transId' => 123456789,
            'resultCode' => 0,
            'message' => 'Successful.',
            'payType' => 'qr',
            'responseTime' => 1710000000000,
            'extraData' => '',
        ];

        $rawHash = 'accessKey=TESTACCESS'
            . '&amount=' . $payload['amount']
            . '&extraData=' . $payload['extraData']
            . '&message=' . $payload['message']
            . '&orderId=' . $payload['orderId']
            . '&orderInfo=' . $payload['orderInfo']
            . '&orderType=' . $payload['orderType']
            . '&partnerCode=' . $payload['partnerCode']
            . '&payType=' . $payload['payType']
            . '&requestId=' . $payload['requestId']
            . '&responseTime=' . $payload['responseTime']
            . '&resultCode=' . $payload['resultCode']
            . '&transId=' . $payload['transId'];

        $payload['signature'] = hash_hmac('sha256', $rawHash, 'TESTSECRET');

        $service = app(MomoService::class);
        $this->assertTrue($service->verifyResultSignature($payload));

        $payload['signature'] = str_repeat('0', 64);
        $this->assertFalse($service->verifyResultSignature($payload));
    }

    private function setMomoConfig(): void
    {
        config()->set('website.website.payment.momo', [
            'endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create',
            'partner_code' => 'TESTPARTNER',
            'access_key' => 'TESTACCESS',
            'secret_key' => 'TESTSECRET',
            'timeout' => 30,
        ]);
    }
}
