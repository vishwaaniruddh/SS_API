# How to Scan Legacy Database Tables

## The Problem
The sync code is failing because the column names in the code don't match the actual column names in the legacy database tables.

Error: `Unknown column 'customer_name' in 'field list'`

This means the `phppos_rent` table doesn't have a column called `customer_name` - it might be named differently (like `cust_name`, `name`, `customer`, etc.)

## Solution: Scan the Actual Tables

We need to see the ACTUAL column names in the legacy database before we can write the correct INSERT queries.

---

## Method 1: Browser Access (EASIEST)

1. Make sure XAMPP Apache is running
2. Open your browser
3. Go to: **http://localhost/ss/API/scan-legacy-tables-html.php**
4. You'll see a detailed view of all table structures with:
   - Column names
   - Data types
   - Sample data
   - Copy-paste ready column arrays

---

## Method 2: phpMyAdmin (RECOMMENDED)

1. Open phpMyAdmin: **http://localhost/phpmyadmin**
2. Select database: **u464193275_srishringarr** (from left sidebar)
3. For each table, click on it and then click "Structure" tab
4. Take note of ALL column names

**Tables to check:**
- `approval`
- `approval_detail`
- `phppos_rent`
- `order_detail`
- `phppos_items`

---

## Method 3: Direct SQL Query

Run these queries in phpMyAdmin SQL tab:

```sql
USE u464193275_srishringarr;

DESCRIBE approval;
DESCRIBE approval_detail;
DESCRIBE phppos_rent;
DESCRIBE order_detail;
DESCRIBE phppos_items;
```

---

## Method 4: Command Line

If you have MySQL command line access:

```bash
mysql -u root -p
USE u464193275_srishringarr;
DESCRIBE approval;
DESCRIBE approval_detail;
DESCRIBE phppos_rent;
DESCRIBE order_detail;
DESCRIBE phppos_items;
```

---

## What to Look For

### For `approval` table (Purchase orders):
Look for columns related to:
- Bill/Order ID
- Customer name (might be one field or split into first_name/last_name)
- Customer email
- Customer phone
- Customer address
- Total amount
- Payment status
- Razorpay IDs
- Created date
- Status

### For `approval_detail` table (Purchase items):
Look for columns related to:
- Approval ID (foreign key)
- Bill ID
- Item/Product ID or SKU
- Product name
- Quantity
- Price
- Total
- Product type

### For `phppos_rent` table (Rental orders):
Look for columns related to:
- Bill/Order ID
- Customer info (name, email, phone, address)
- Pickup date
- Delivery/Return date
- Days
- Total amount
- Payment info
- Booking status

### For `order_detail` table (Rental items):
Look for columns related to:
- Bill ID
- Item/Product ID or SKU
- Product name
- Quantity
- Price
- Total
- Dates
- Days

### For `phppos_items` table (Inventory):
**MOST IMPORTANT:** Find the column that stores the SKU/Item Code
- Could be: `item_id`, `sku`, `product_code`, `item_code`, `code`, etc.
- Also need: `quantity` column

---

## After Scanning

Once you have the actual column names, we need to update `sync-legacy-database.php` to use the correct column names in all INSERT queries.

**Share the results with me in this format:**

```
TABLE: approval
Columns: id, bill_no, cust_name, cust_email, cust_phone, address, amount, status, created_date, ...

TABLE: approval_detail
Columns: id, approval_id, bill_no, item_code, product_name, qty, price, total, ...

TABLE: phppos_rent
Columns: id, bill_no, customer_name, email, phone, address, pickup_date, return_date, ...

TABLE: order_detail
Columns: id, bill_no, item_code, product_name, qty, price, total, ...

TABLE: phppos_items
Columns: id, item_id, name, quantity, price, ...
```

Or just take screenshots of the phpMyAdmin structure view for each table.

---

## Quick Start

**EASIEST WAY:**

1. Open: http://localhost/ss/API/scan-legacy-tables-html.php
2. Copy all the column names shown
3. Share them with me
4. I'll update the sync code with the correct column names

That's it!
