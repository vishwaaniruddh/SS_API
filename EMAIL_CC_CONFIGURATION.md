# Email CC Configuration

## Overview
All order confirmation emails will automatically CC three email addresses for order tracking and management.

## CC Recipients (Always Included)

Every order confirmation email will be sent with CC to:

1. **Rajani Podar** - rajanipodar@gmail.com
2. **Yosshita Neha** - yosshita.neha@gmail.com  
3. **Vishwa Aniruddh** - vishwaaniruddh@gmail.com

## Files Updated

### 1. `API/v1/send-order-email.php` (Main Email Script)
```php
// Always CC these emails for all orders
$mail->addCC('rajanipodar@gmail.com', 'Rajani Podar');
$mail->addCC('yosshita.neha@gmail.com', 'Yosshita Neha');
$mail->addCC('vishwaaniruddh@gmail.com', 'Vishwa Aniruddh');
```

### 2. `API/v1/send-order-email-new.php` (Backup Email Script)
```php
// Always CC these emails for all orders
$mail->addCC('rajanipodar@gmail.com', 'Rajani Podar');
$mail->addCC('yosshita.neha@gmail.com', 'Yosshita Neha');
$mail->addCC('vishwaaniruddh@gmail.com', 'Vishwa Aniruddh');
```

## Email Flow

When a customer places an order:

**TO:** Customer Email (e.g., customer@example.com)

**CC:** 
- rajanipodar@gmail.com
- yosshita.neha@gmail.com
- vishwaaniruddh@gmail.com

**Subject:** Order Confirmed #SR-XXXX - Sri Shringarr

## How to View CC in Gmail

1. Open the order confirmation email
2. Click the **dropdown arrow (▼)** next to "to me" at the top
3. You'll see:
   ```
   to: Customer Name <customer@example.com>
   cc: Rajani Podar <rajanipodar@gmail.com>,
       Yosshita Neha <yosshita.neha@gmail.com>,
       Vishwa Aniruddh <vishwaaniruddh@gmail.com>
   date: [timestamp]
   ```

## Benefits

✅ **Order Tracking:** All team members receive order notifications
✅ **Backup:** Multiple copies ensure no order is missed
✅ **Transparency:** Everyone stays informed about new orders
✅ **Customer Service:** Quick access to order details for support

## Testing

To verify CC is working:
1. Place a test order
2. Check all three CC email inboxes
3. All should receive the order confirmation email
4. Verify CC field shows all three addresses

## Notes

- CC recipients are visible to all email recipients (including customer)
- If you need hidden copies, use BCC instead
- Names are included for better email client display
- Configuration is permanent - applies to all future orders

## Maintenance

To add/remove CC recipients:
1. Edit both files mentioned above
2. Add/remove `$mail->addCC()` lines
3. Test with a new order

## Security Note

CC email addresses are visible to customers. If you prefer hidden copies, consider using BCC:
```php
$mail->addBCC('email@example.com', 'Name');
```
