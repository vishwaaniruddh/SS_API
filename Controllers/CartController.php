<?php
namespace API\Controllers;

use API\Models\CartModel;

class CartController {
    private $model;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new CartModel($db);
    }

    public function add() {
        // Handle JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $_POST = array_merge($_POST, $input);
        }

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
        $productType = $_POST['product_type'] ?? $_POST['type'] ?? '';
        $bookingType = $_POST['booking_type'] ?? 'rent';
        $days = isset($_POST['days']) ? (int)$_POST['days'] : 3;
        $startDate = $_POST['rent_from'] ?? $_POST['start_date'] ?? '';
        $endDate = $_POST['rent_to'] ?? $_POST['end_date'] ?? '';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if (!$productId || !$productType) {
            return ['status' => 'error', 'message' => 'Invalid product parameters.'];
        }

        $activeUserId = $_SESSION['userid'] ?? $_SESSION['gid'] ?? 0;

        if (!$activeUserId) {
            return ['status' => 'error', 'message' => 'Your session has expired. Please refresh the page.'];
        }

        $count = $this->model->addToCart($activeUserId, $productId, $productType, $quantity, $days, $startDate, $endDate, $bookingType);

        return [
            'status' => 'success',
            'message' => 'Added to Bag!',
            'cart_count' => $count
        ];
    }

    public function count() {
        $activeUserId = $_SESSION['userid'] ?? $_SESSION['gid'] ?? 0;
        $count = $this->model->getCartCount($activeUserId);
        return ['status' => 'success', 'cart_count' => $count];
    }

    public function get() {
        $activeUserId = $_SESSION['userid'] ?? $_SESSION['gid'] ?? 0;
        $items = $this->model->getCartItems($activeUserId);

        $productModel = new \API\Models\ProductModel();
        $enrichedItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $product = $productModel->getProductById($item['product_id'], $item['product_type']);
            if ($product) {
                $displayPrice = (float)$item['product_amt'];
                $itemTotal = $displayPrice * (int)$item['qty'];
                $subtotal += $itemTotal;

                $enrichedItems[] = [
                    'cart_id' => $item['cart_id'],
                    'product_id' => $item['product_id'],
                    'product_type' => $item['product_type'],
                    'booking_type' => $item['booking_type'] ?? 'rent',
                    'name' => $product['name'],
                    'sku' => $item['sku'],
                    'qty' => $item['qty'],
                    'days' => $item['days'] ?? 3,
                    'start_date' => $item['start_date'] ?? '',
                    'end_date' => $item['end_date'] ?? '',
                    'price' => $displayPrice,
                    'total' => $itemTotal,
                    'image' => $product['details']['image_path'] ?? 'main_logo.png'
                ];
            }
        }

        $discount = 0;
        $appliedCoupon = $_SESSION['applied_coupon'] ?? null;
        if ($appliedCoupon) {
            if ($appliedCoupon['discount_type'] === 'percent') {
                $discount = ($subtotal * $appliedCoupon['coupon_amount']) / 100;
            } else {
                $discount = $appliedCoupon['coupon_amount'];
            }
        }

        $total = $subtotal - $discount;
        if ($total < 0) $total = 0;

        return [
            'status' => 'success',
            'data' => [
                'items' => $enrichedItems,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'coupon' => $appliedCoupon,
                'total' => $total,
                'tax_included' => true,
                'shipping' => 0
            ]
        ];
    }

    public function applyCoupon() {
        $code = $_POST['code'] ?? '';
        if (!$code) return ['status' => 'error', 'message' => 'Please enter a coupon code.'];

        global $con;
        $safeCode = mysqli_real_escape_string($con, $code);
        $sql = "SELECT * FROM coupons WHERE code = '$safeCode' AND status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURDATE())";
        $res = mysqli_query($con, $sql);

        if ($res && mysqli_num_rows($res) > 0) {
            $coupon = mysqli_fetch_assoc($res);
            
            // Check usage limits if any
            if ($coupon['usage_limit'] !== null && $coupon['usage_count'] >= $coupon['usage_limit']) {
                return ['status' => 'error', 'message' => 'This coupon has reached its usage limit.'];
            }

            // Check minimum spend
            $activeUserId = $_SESSION['userid'] ?? $_SESSION['gid'] ?? 0;
            $items = $this->model->getCartItems($activeUserId);
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += (float)$item['total_amt'];
            }

            if ($coupon['minimum_amount'] !== null && $subtotal < $coupon['minimum_amount']) {
                return ['status' => 'error', 'message' => 'Minimum spend of ₹' . number_format($coupon['minimum_amount'], 2) . ' required.'];
            }

            if ($coupon['maximum_amount'] !== null && $subtotal > $coupon['maximum_amount']) {
                return ['status' => 'error', 'message' => 'Maximum spend allowed for this coupon is ₹' . number_format($coupon['maximum_amount'], 2) . '.'];
            }

            $_SESSION['applied_coupon'] = [
                'id' => $coupon['id'],
                'code' => $coupon['code'],
                'discount_type' => $coupon['discount_type'],
                'coupon_amount' => (float)$coupon['coupon_amount']
            ];

            return ['status' => 'success', 'message' => 'Coupon applied successfully!', 'coupon' => $_SESSION['applied_coupon']];
        }

        return ['status' => 'error', 'message' => 'Invalid or expired coupon code.'];
    }

    public function removeCoupon() {
        unset($_SESSION['applied_coupon']);
        return ['status' => 'success', 'message' => 'Coupon removed.'];
    }

    public function remove() {
        $cartId = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
        if (!$cartId) return ['status' => 'error', 'message' => 'Invalid item'];

        $activeUserId = $_SESSION['userid'] ?? $_SESSION['gid'] ?? 0;
        $this->model->removeFromCart($cartId);
        $count = $this->model->getCartCount($activeUserId);
        
        return [
            'status' => 'success', 
            'message' => 'Item removed',
            'cart_count' => $count
        ];
    }
}
