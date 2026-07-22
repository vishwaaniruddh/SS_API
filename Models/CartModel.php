<?php
namespace API\Models;

use API\Core\Model;

class CartModel extends Model {

    public function addToCart($userId, $productId, $productType, $qty = 1, $days = 3, $startDate = '', $endDate = '', $bookingType = 'rent') {
        if (!$this->db_reporting) return 0;
        $userId = (int)$userId;
        $productId = (int)$productId;
        $productType = mysqli_real_escape_string($this->db_reporting, $productType);
        $bookingType = mysqli_real_escape_string($this->db_reporting, $bookingType);
        $days = (int)$days;
        $startDate = mysqli_real_escape_string($this->db_reporting, $startDate);
        $endDate = mysqli_real_escape_string($this->db_reporting, $endDate);

        // Fetch product details using ProductModel to ensure consistent pricing logic
        $productModel = new \API\Models\ProductModel();
        $product = $productModel->getProductById($productId, $productType);
        
        if (!$product) return 0;

        $sku = $product['code'];
        $basePrice = 0;

        if ($bookingType === 'buy') {
            $price = (float)$product['details']['sale_price'];
            $days = 0;
            $startDate = '';
            $endDate = '';
        } else {
            $basePrice = (float)$product['details']['rent_price'];
            $price = $basePrice;
            if ($days > 3) {
                $extraDays = $days - 3;
                $price = $basePrice + ($extraDays * ($basePrice * 0.05));
            }
            $price = $this->round_amount($price);
        }

        $price = (float)$price;
        $qty = (int)$qty;
        $totalAmt = $price * $qty;

        // Check if item exists (with same booking type)
        $check = mysqli_query($this->db_reporting, "SELECT cart_id, qty FROM cart WHERE user_id = $userId AND product_id = $productId AND product_type = '$productType' AND booking_type = '$bookingType'");
        
        if (mysqli_num_rows($check) > 0) {
            $row = mysqli_fetch_assoc($check);
            $cartId = $row['cart_id'];
            mysqli_query($this->db_reporting, "UPDATE cart SET qty = qty + $qty, product_amt = $price, total_amt = total_amt + $totalAmt, days = $days, start_date = '$startDate', end_date = '$endDate' WHERE cart_id = $cartId");
        } else {
            mysqli_query($this->db_reporting, "INSERT INTO cart (user_id, product_id, product_type, booking_type, sku, qty, product_amt, total_amt, days, start_date, end_date) 
                                VALUES ($userId, $productId, '$productType', '$bookingType', '$sku', $qty, $price, $totalAmt, $days, '$startDate', '$endDate')");
        }

        return $this->getCartCount($userId);
    }

    private function round_amount($amount) {
        $amount = (int) round($amount);
        $round_num = $amount % 100;
        $add_amount = 0;
        if ($round_num > 0 && $round_num < 50) {
            $add_amount = 50 - $round_num;
        } else if ($round_num > 50) {
            $add_amount = 100 - $round_num;
        }
        return $amount + $add_amount;
    }

    public function getCartCount($userId) {
        if (!$this->db_reporting) return 0;
        $userId = (int)$userId;
        $res = mysqli_query($this->db_reporting, "SELECT SUM(qty) as count FROM cart WHERE user_id = $userId");
        if (!$res) return 0;
        $row = mysqli_fetch_assoc($res);
        return (int)($row['count'] ?? 0);
    }

    public function migrateCart($guestId, $loggedInUserId) {
        if (!$this->db_reporting) return;
        $guestId = (int)$guestId;
        $loggedInUserId = (int)$loggedInUserId;

        if ($guestId === $loggedInUserId) return;

        // Get guest items
        $res = mysqli_query($this->db_reporting, "SELECT * FROM cart WHERE user_id = $guestId");
        if (!$res) return;
        while ($item = mysqli_fetch_assoc($res)) {
            $this->addToCart($loggedInUserId, $item['product_id'], $item['product_type'], $item['qty']);
        }

        // Delete guest items
        mysqli_query($this->db_reporting, "DELETE FROM cart WHERE user_id = $guestId");
    }

    public function getCartItems($userId) {
        if (!$this->db_reporting) return [];
        $userId = (int)$userId;
        $res = mysqli_query($this->db_reporting, "SELECT * FROM cart WHERE user_id = $userId");
        if (!$res) return [];
        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
        return $items;
    }

    public function removeFromCart($cartId) {
        if (!$this->db_reporting) return false;
        $cartId = (int)$cartId;
        return mysqli_query($this->db_reporting, "DELETE FROM cart WHERE cart_id = $cartId");
    }

    public function clearCart($userId) {
        if (!$this->db_reporting) return false;
        $userId = (int)$userId;
        return mysqli_query($this->db_reporting, "DELETE FROM cart WHERE user_id = $userId");
    }
}
