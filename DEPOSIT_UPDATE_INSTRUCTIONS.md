# Deposit Charges Display Update

## Overview
This update adds deposit charges display to both the order history page and order confirmation emails.

## Database Changes Required

### Step 1: Add deposit_amount column to orders table
Run the SQL file: `add-deposit-column.sql`

```sql
ALTER TABLE orders ADD COLUMN deposit_amount DECIMAL(10,2) DEFAULT 0.00 AFTER discount_amount;
```

**How to run:**
1. Open phpMyAdmin or your MySQL client
2. Select your database
3. Go to SQL tab
4. Copy and paste the SQL command above
5. Click "Go" or "Execute"

## Files Modified

### Backend (PHP)
1. **API/v1/verify-payment.php**
   - Added calculation of deposit amount from cart items
   - Updated INSERT query to include deposit_amount column

2. **API/v1/order-history.php**
   - Added depositAmount field to the order response

3. **API/v1/send-order-email.php**
   - Added depositAmount parameter to email template function

4. **API/v1/email-templates/order-confirmation-template.php**
   - Added depositAmount parameter to function signature
   - Added deposit row in order summary table (displayed in gold color)

### Frontend (React)
1. **client/src/pages/Account.jsx**
   - Added deposit display in order breakdown section
   - Shows "Refundable Deposit" line item when depositAmount > 0

## What Changed

### Order History Page (Account.jsx)
The order breakdown now shows:
- Items Subtotal
- **Refundable Deposit** (NEW - only shown if > 0)
- Shipping Charge
- Coupon Discount (if applicable)
- Total Paid

### Order Confirmation Email
The order summary now shows:
- Items Subtotal
- **💰 Refundable Deposit** (NEW - displayed in gold color, only shown if > 0)
- Shipping Charge
- Discount (if applicable)
- Total Paid

## Testing

### Test New Orders
1. Add rental items to cart (items with deposits)
2. Complete checkout and payment
3. Check order confirmation email - should show deposit line
4. Go to Account → Orders - should show deposit in order breakdown

### Existing Orders
- Old orders without deposit_amount will show 0 (won't display the deposit line)
- New orders will properly track and display deposits

## Rollback
If you need to rollback:
```sql
ALTER TABLE orders DROP COLUMN deposit_amount;
```

Then revert the code changes using git.
