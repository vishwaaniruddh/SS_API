# Column Mapping Applied - Legacy Database Sync

## ✅ Changes Made

The sync code has been updated to match your actual database structure.

---

## 📋 Table: `approval` (Purchase Orders)

**Your actual columns:**
- `bill_id` (auto_increment primary key)
- `cust_id` (customer ID reference)
- `bill_date` (date)
- `status` (varchar)
- `paid_amount` (int)
- `transaction_id` (text)
- `pay_by` (varchar)
- `amountTotal` (varchar)
- `new_bill_number` (varchar)
- `company_name` (varchar)

**What we're inserting:**
```sql
INSERT INTO approval (
    cust_id,           -- Set to 0 (no customer mapping yet)
    bill_date,         -- NOW()
    status,            -- 'S' (for Success/Sold)
    paid_amount,       -- Item total
    transaction_id,    -- Razorpay payment ID
    pay_by,            -- 'Online'
    amountTotal,       -- Item total
    new_bill_number,   -- 'SR-5XXX' format
    company_name       -- 'Online Order'
)
```

---

## 📋 Table: `approval_detail` (Purchase Items)

**Your actual columns:**
- `bill_id` (int - references approval.bill_id)
- `item_id` (varchar - SKU)
- `qty` (int)
- `aid` (auto_increment primary key)
- `price` (float)
- `amount` (varchar)
- `final_amount` (float)
- `new_bill_number` (varchar)

**What we're inserting:**
```sql
INSERT INTO approval_detail (
    bill_id,           -- approval.bill_id from above
    item_id,           -- SKU from order
    qty,               -- Quantity
    price,             -- Unit price
    amount,            -- Total amount
    final_amount,      -- Total amount
    new_bill_number    -- 'SR-5XXX' format
)
```

---

## 📋 Table: `phppos_rent` (Rental Orders)

**Your actual columns:**
- `bill_id` (auto_increment primary key)
- `cust_id` (int)
- `cust_name` (varchar) ← **NOT customer_name!**
- `bill_date` (date)
- `rent_amount` (int)
- `amount` (varchar)
- `status` (varchar)
- `pstatus` (varchar - payment status)
- `pick_date` (date)
- `delivery_date` (date)
- `booking_status` (varchar)
- `transaction_id` (varchar)
- `payment_mode_name` (varchar)
- `new_bill_number` (varchar)
- `company_name` (varchar)
- `is_online` (int)

**What we're inserting:**
```sql
INSERT INTO phppos_rent (
    cust_id,           -- 0 (no customer mapping)
    cust_name,         -- Customer full name ← FIXED!
    bill_date,         -- NOW()
    rent_amount,       -- Rental price per day
    amount,            -- Total amount
    status,            -- 'S' (Success)
    pstatus,           -- 'Paid'
    pick_date,         -- Start date
    delivery_date,     -- End date
    booking_status,    -- 'Confirmed'
    transaction_id,    -- Razorpay payment ID
    payment_mode_name, -- 'Razorpay'
    new_bill_number,   -- 'SR-5XXX' format
    company_name,      -- 'Online Order'
    is_online          -- 1
)
```

---

## 📋 Table: `order_detail` (Rental Items)

**Your actual columns:**
- `bill_id` (int - references phppos_rent.bill_id)
- `item_id` (varchar - SKU)
- `rent` (int - rental price)
- `deposit` (int)
- `id` (auto_increment primary key)
- `qty` (bigint)
- `total_amount` (bigint)
- `item_detail` (varchar - product description)
- `pickup_date` (date) ← **NOT pick_date!**
- `return_date` (date) ← **NOT delivery_date!**
- `new_bill_number` (varchar)

**What we're inserting:**
```sql
INSERT INTO order_detail (
    bill_id,           -- phppos_rent.bill_id from above
    item_id,           -- SKU
    rent,              -- Rental price
    deposit,           -- Deposit amount
    qty,               -- Quantity
    total_amount,      -- Total
    item_detail,       -- Product name ← FIXED!
    pickup_date,       -- Start date ← FIXED!
    return_date,       -- End date ← FIXED!
    new_bill_number    -- 'SR-5XXX' format
)
```

---

## 📋 Table: `phppos_items` (Inventory)

**Your actual columns:**
- `item_id` (auto_increment primary key)
- `item_number` (varchar - SKU) ← **This is the SKU field!**
- `name` (varchar)
- `quantity` (double)
- `updated_at` (datetime)

**What we're updating:**
```sql
UPDATE phppos_items 
SET quantity = GREATEST(0, quantity - {qty}),
    updated_at = NOW()
WHERE item_number = '{sku}'  ← FIXED! Was item_id, now item_number
```

---

## 🔑 Key Changes Made

1. **approval table:**
   - Removed non-existent columns (customer_name, customer_email, etc.)
   - Using actual columns (cust_id, bill_date, status, paid_amount, etc.)

2. **approval_detail table:**
   - Removed product_name and product_type (don't exist)
   - Using actual columns (bill_id, item_id, qty, price, amount, final_amount)

3. **phppos_rent table:**
   - Changed `customer_name` → `cust_name` ✅
   - Changed `customer_email` → removed (doesn't exist)
   - Changed `customer_phone` → removed (doesn't exist)
   - Changed `customer_address` → removed (doesn't exist)
   - Using actual columns that exist

4. **order_detail table:**
   - Changed `product_name` → `item_detail` ✅
   - Changed `pick_date` → `pickup_date` ✅
   - Changed `delivery_date` → `return_date` ✅
   - Removed `product_type` and `days` (don't exist)
   - Added `deposit` column ✅

5. **phppos_items table:**
   - Changed `WHERE item_id` → `WHERE item_number` ✅
   - This is the critical fix for quantity reduction!

---

## ⚠️ Important Notes

### Customer ID Mapping
The legacy system uses `cust_id` to reference customers in a separate customer table. Currently, we're setting `cust_id = 0` for online orders. 

**Options:**
1. Keep it as 0 (works, but no customer linkage)
2. Create a customer record first and use that ID
3. Use a default "Online Customer" ID if one exists

### Missing Customer Info
The legacy `approval` and `phppos_rent` tables don't store customer email, phone, or address directly. They rely on `cust_id` to link to a customer table.

For online orders, we're storing:
- Customer name in `cust_name` field
- Order reference in `new_bill_number` (SR-5XXX format)
- Transaction ID for payment tracking

---

## 🧪 Testing

Now you can test the sync:

```
http://localhost/ss/API/v1/sync-legacy-database.php?order_id=9
```

This should now work without column errors!

---

## 📊 What Gets Synced

### For Purchase Items:
1. ✅ Order record in `approval` table
2. ✅ Item details in `approval_detail` table
3. ✅ Quantity reduced in `phppos_items` table

### For Rental Items:
1. ✅ Order record in `phppos_rent` table
2. ✅ Item details in `order_detail` table (with deposit)
3. ✅ Quantity reduced in `phppos_items` table

---

## 🎯 Next Steps

1. Test the sync with order ID 9
2. Check the legacy database tables for the data
3. Verify quantity was reduced
4. Place a new test order to confirm automatic sync works
5. Done! ✅
