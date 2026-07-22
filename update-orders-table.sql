-- Add shipping_charge, coupon_code, and discount_amount columns to orders table
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS shipping_charge DECIMAL(10,2) DEFAULT 0 AFTER total_amount,
ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(50) DEFAULT NULL AFTER shipping_charge,
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0 AFTER coupon_code;
