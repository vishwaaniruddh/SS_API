-- Add deposit_amount column to orders table
ALTER TABLE orders ADD COLUMN deposit_amount DECIMAL(10,2) DEFAULT 0.00 AFTER discount_amount;
