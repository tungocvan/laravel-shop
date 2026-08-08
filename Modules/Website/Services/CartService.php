<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Modules\Website\Models\Cart;
use Modules\Website\Models\CartItem;
use Modules\Website\Models\Coupon;
use Modules\Website\Models\WpProduct;

class CartService
{
    /**
     * Lấy giỏ hàng hiện tại
     */
    public function getCart()
    {
        if (Auth::check()) {
            // 1. Tìm hoặc tạo giỏ hàng theo User ID
            $cart = Cart::with(['items.product', 'coupon'])->firstOrCreate(
                ['user_id' => Auth::id()],
                ['session_id' => Session::getId()]
            );

            // --- FIX QUAN TRỌNG ---
            // Nếu giỏ hàng đã tồn tại từ trước (session_id cũ),
            // CẦN cập nhật lại session_id hiện tại để tránh lỗi lạc mất giỏ hàng.
            if ($cart->session_id !== Session::getId()) {
                $cart->session_id = Session::getId();
                $cart->save();
            }
            // ----------------------

            // 2. Gộp giỏ hàng Guest (nếu có)
            $this->mergeGuestCartToUserCart($cart);

            return $cart;
        }

        // Guest
        return Cart::with(['items.product', 'coupon'])->firstOrCreate([
            'session_id' => Session::getId()
        ]);
    }

    /**
     * Gộp giỏ hàng Guest vào User (Bọc Transaction an toàn)
     */
    protected function mergeGuestCartToUserCart(Cart $userCart)
    {
        $sessionId = Session::getId();
        $guestCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->first();

        if ($guestCart && $guestCart->id !== $userCart->id) {
            DB::transaction(function () use ($guestCart, $userCart) {
                foreach ($guestCart->items as $item) {
                    $this->addItemToCart($userCart, $item->product_id, $item->quantity);
                }
                $guestCart->items()->delete(); // Xóa items cũ
                $guestCart->delete(); // Xóa cart guest
            });
        }
    }

    public function addItem($productId, $quantity = 1)
    {
        $cart = $this->getCart();
        return $this->addItemToCart($cart, $productId, $quantity);
    }

    /**
     * Logic Core thêm item (Đã fix logic giá & tồn kho)
     */
    protected function addItemToCart(Cart $cart, $productId, $quantity)
    {
        $product = WpProduct::findOrFail($productId);

        // 1. Logic Giá: Ưu tiên giá Sale nếu có và hợp lệ
        // Giả sử logic là: nếu sale_price > 0 thì dùng, ko thì dùng regular
        $realPrice = $this->getProductPrice($product);

        // 2. Kiểm tra item đã có trong giỏ chưa
        $cartItem = $cart->items()->where('product_id', $productId)->first();
        $currentQty = $cartItem ? $cartItem->quantity : 0;
        $newQty = $currentQty + $quantity;

        // 3. CHECK TỒN KHO (Quan trọng)
        // Cột tồn kho của bạn là 'quantity' hay 'stock'? Hãy thay đúng tên cột
        if ($product->quantity < $newQty) {
             throw new \Exception("Sản phẩm '{$product->title}' chỉ còn {$product->quantity} cái.");
        }

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $newQty,
                'price'    => $realPrice, // Cập nhật lại giá mới nhất (lỡ admin đổi giá)
                'total'    => $newQty * $realPrice
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $productId,
                'price'      => $realPrice,
                'quantity'   => $quantity,
                'total'      => $quantity * $realPrice
            ]);
        }

        return $cart->refresh();
    }

    public function updateQuantity($itemId, $quantity)
    {
        if ($quantity <= 0) return $this->removeItem($itemId);

        $item = $this->getCartItemForCurrentCart($itemId, ['product']);

        // Check tồn kho khi update
        if ($item->product->quantity < $quantity) {
             throw new \Exception("Kho chỉ còn {$item->product->quantity} sản phẩm.");
        }

        // Lấy lại giá hiện tại để tính total chính xác
        $realPrice = $this->getProductPrice($item->product);

        $item->update([
            'quantity' => $quantity,
            'price'    => $realPrice,
            'total'    => $quantity * $realPrice
        ]);

        return true;
    }

    public function incrementItem($itemId)
    {
        $item = $this->getCartItemForCurrentCart($itemId);

        return $this->updateQuantity($itemId, $item->quantity + 1);
    }

    public function decrementItem($itemId)
    {
        $item = $this->getCartItemForCurrentCart($itemId);

        return $this->updateQuantity($itemId, $item->quantity - 1);
    }

    public function removeItem($itemId)
    {
        return $this->getCartItemForCurrentCart($itemId)->delete();
    }

    public function applyCoupon($code)
    {
        $coupon = Coupon::where('code', $code)->first();
        $cart = $this->getCart();

        if (!$coupon || !$coupon->is_valid) {
            throw new \Exception("Mã giảm giá không hợp lệ hoặc đã hết hạn.");
        }

        // Check min order
        $subtotal = $cart->items->sum('total');
        if ($subtotal < $coupon->min_order_value) {
            throw new \Exception("Đơn hàng phải từ " . number_format($coupon->min_order_value) . "đ mới được dùng mã này.");
        }

        $cart->coupon_id = $coupon->id;
        $cart->save();

        return $coupon;
    }

    public function removeCoupon()
    {
        $cart = $this->getCart();
        $cart->coupon_id = null;
        $cart->save();
    }

    public function getCartSummary()
    {
        $cart = $this->getCart();

        // Tính Subtotal
        $subtotal = $cart->items->sum('total');

        // Tính Discount
        $discountAmount = 0;

        if ($cart->coupon) {
            // Validate lại lần nữa (phòng trường hợp xóa bớt sản phẩm làm subtotal giảm)
            if ($cart->coupon->is_valid && $subtotal >= $cart->coupon->min_order_value) {
                if ($cart->coupon->type === 'percent') {
                    $discountAmount = $subtotal * ($cart->coupon->value / 100);
                    // Nếu bạn muốn giới hạn giảm tối đa (ví dụ giảm 10% nhưng tối đa 50k)
                    // $discountAmount = min($discountAmount, $cart->coupon->max_discount_amount ?? 999999999);
                } else {
                    $discountAmount = $cart->coupon->value;
                }
            } else {
                // Tự động gỡ nếu không đủ điều kiện
                $cart->coupon_id = null;
                $cart->save();
                $cart->refresh(); // Refresh để view nhận null
            }
        }

        $total = max(0, $subtotal - $discountAmount);

        return [
            'cart'        => $cart,
            'items'       => $cart->items,
            'subtotal'    => $subtotal,
            'discount'    => $discountAmount,
            'coupon_code' => $cart->coupon ? $cart->coupon->code : null,
            'total'       => $total
        ];
    }

    protected function getCartItemForCurrentCart($itemId, array $with = [])
    {
        $query = $this->getCart()->items();

        if ($with) {
            $query->with($with);
        }

        return $query->whereKey($itemId)->firstOrFail();
    }

    protected function getProductPrice(WpProduct $product)
    {
        return ($product->sale_price > 0 && $product->sale_price < $product->regular_price)
            ? $product->sale_price
            : $product->regular_price;
    }
}
