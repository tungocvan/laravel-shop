<?php

namespace Tests\Feature\Order;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Order\Contracts\CheckoutContext;
use Modules\Order\Data\AffiliateAttribution;
use Modules\Order\Data\CheckoutCart;
use Modules\Order\Data\CheckoutCartItem;
use Modules\Order\Models\Order;
use Modules\Order\Services\CheckoutService;
use Modules\Product\Models\Product;
use Tests\TestCase;

class OrderCheckoutServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach (['order_histories', 'order_items', 'wp_orders', 'wp_products'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_order_workflow_creates_complete_order_updates_stock_and_consumes_cart(): void
    {
        $product = Product::create([
            'title' => 'Sản phẩm thử nghiệm',
            'slug' => 'san-pham-thu-nghiem',
            'regular_price' => 100000,
            'quantity' => 5,
            'sold_count' => 1,
            'is_active' => true,
        ]);
        $context = new FakeCheckoutContext(new CheckoutCart(
            id: 10,
            items: [new CheckoutCartItem($product->id, 100000, 2, 200000)],
            couponId: 7,
            couponCode: 'GIAM10',
            couponType: 'percent',
            couponValue: 10,
            couponMinOrderValue: 100000,
        ));

        $order = (new CheckoutService($context))->createOrder($this->checkoutData());

        $this->assertSame('pending', $order->status);
        $this->assertSame('GIAM10', $order->coupon_code);
        $this->assertSame(20000.0, (float) $order->discount);
        $this->assertSame(180000.0, (float) $order->total);
        $this->assertSame(99, $order->affiliate_id);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'quantity' => 2]);
        $this->assertDatabaseHas('order_histories', ['order_id' => $order->id, 'action' => 'created']);
        $this->assertSame(3, $product->fresh()->quantity);
        $this->assertSame(3, $product->fresh()->sold_count);
        $this->assertTrue($context->consumed);
        $this->assertTrue($context->couponApplied);
    }

    public function test_insufficient_stock_rolls_back_without_consuming_cart(): void
    {
        $product = Product::create([
            'title' => 'Sản phẩm sắp hết',
            'slug' => 'san-pham-sap-het',
            'regular_price' => 100000,
            'quantity' => 1,
            'sold_count' => 0,
            'is_active' => true,
        ]);
        $context = new FakeCheckoutContext(new CheckoutCart(
            id: 11,
            items: [new CheckoutCartItem($product->id, 100000, 2, 200000)],
        ));

        try {
            (new CheckoutService($context))->createOrder($this->checkoutData());
            $this->fail('Checkout thiếu tồn kho phải bị từ chối.');
        } catch (Exception $exception) {
            $this->assertStringContainsString('không đủ hàng', $exception->getMessage());
        }

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(1, $product->fresh()->quantity);
        $this->assertFalse($context->consumed);
    }

    public function test_order_creation_remains_compatible_when_coupon_code_column_is_absent(): void
    {
        Schema::table('wp_orders', fn (Blueprint $table) => $table->dropColumn('coupon_code'));
        $product = Product::create([
            'title' => 'Sản phẩm schema cũ',
            'slug' => 'san-pham-schema-cu',
            'regular_price' => 50000,
            'quantity' => 2,
            'sold_count' => 0,
            'is_active' => true,
        ]);
        $context = new FakeCheckoutContext(new CheckoutCart(
            id: 12,
            items: [new CheckoutCartItem($product->id, 50000, 1, 50000)],
            couponId: 8,
            couponCode: 'SCHEMA-CU',
            couponType: 'fixed',
            couponValue: 5000,
        ));

        $order = (new CheckoutService($context))->createOrder($this->checkoutData());

        $this->assertSame(45000.0, (float) $order->total);
        $this->assertDatabaseHas('wp_orders', ['id' => $order->id, 'total' => 45000]);
        $this->assertTrue($context->consumed);
    }

    public function test_bank_transfer_order_starts_in_pending_payment_state(): void
    {
        $product = Product::create([
            'title' => 'Sản phẩm chuyển khoản',
            'slug' => 'san-pham-chuyen-khoan',
            'regular_price' => 75000,
            'quantity' => 2,
            'sold_count' => 0,
            'is_active' => true,
        ]);
        $context = new FakeCheckoutContext(new CheckoutCart(
            id: 13,
            items: [new CheckoutCartItem($product->id, 75000, 1, 75000)],
        ));

        $order = (new CheckoutService($context))->createOrder(array_merge(
            $this->checkoutData(),
            ['payment_method' => 'bank_transfer'],
        ));

        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertTrue($context->consumed);
    }

    private function checkoutData(): array
    {
        return [
            'customer_name' => 'Khách thử nghiệm',
            'customer_phone' => '0900000000',
            'customer_email' => 'checkout@example.test',
            'customer_address' => 'Địa chỉ thử nghiệm',
            'payment_method' => 'cod',
        ];
    }

    private function createSchema(): void
    {
        Schema::create('wp_products', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->decimal('regular_price', 15, 2);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('sold_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('wp_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('affiliate_id')->nullable();
            $table->string('commission_status');
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->string('order_code')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('customer_address');
            $table->text('note')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->string('coupon_code')->nullable();
            $table->string('payment_method');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->decimal('price', 15, 2);
            $table->integer('quantity');
            $table->decimal('total', 15, 2);
            $table->json('options')->nullable();
            $table->timestamps();
        });
        Schema::create('order_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->text('description');
            $table->timestamps();
        });
    }
}

class FakeCheckoutContext implements CheckoutContext
{
    public bool $consumed = false;

    public bool $couponApplied = false;

    public function __construct(private readonly CheckoutCart $cart) {}

    public function currentCartId(): int
    {
        return $this->cart->id;
    }

    public function currentUserId(): ?int
    {
        return 5;
    }

    public function lockCart(int $cartId): CheckoutCart
    {
        return $this->cart;
    }

    public function affiliateAttribution(float $subtotal, ?int $userId): AffiliateAttribution
    {
        return new AffiliateAttribution(99, 20000, 'pending');
    }

    public function consume(CheckoutCart $cart, bool $couponApplied): void
    {
        $this->consumed = true;
        $this->couponApplied = $couponApplied;
    }
}
