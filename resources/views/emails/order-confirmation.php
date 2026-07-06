<?php
$name = $name ?? 'Customer';
$storeName = $storeName ?? 'ShopSmart';
$orderNumber = $orderNumber ?? '';
$total = $total ?? 0;
$items = $items ?? [];
$currency = $currency ?? 'KES';
$symbol = $currencySymbol ?? 'KSh';
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="font-family:'Inter',Arial,sans-serif;background:#f9fafb;margin:0;padding:40px 20px;">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">
  <div style="background:linear-gradient(135deg,#d97706,#b45309);padding:32px;text-align:center;">
    <h1 style="color:#fff;margin:0;font-size:24px;font-weight:700;"><?= e($storeName) ?></h1>
    <p style="color:#fde68a;margin:8px 0 0;font-size:14px;">Order Confirmation</p>
  </div>
  <div style="padding:32px;">
    <p style="color:#374151;font-size:16px;margin:0 0 4px;">Hello <strong><?= e($name) ?></strong>,</p>
    <p style="color:#6b7280;font-size:14px;margin:0 0 24px;">Thank you for your order! Here are the details:</p>
    <div style="background:#f9fafb;border-radius:8px;padding:16px;margin-bottom:20px;">
      <p style="color:#374151;font-size:14px;margin:0 0 4px;"><strong>Order Number:</strong> <?= e($orderNumber) ?></p>
      <p style="color:#374151;font-size:14px;margin:0;"><strong>Total:</strong> <?= e($symbol) ?> <?= number_format($total, 2) ?></p>
    </div>
    <?php if (!empty($items)): ?>
    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;font-size:14px;">
      <thead><tr style="border-bottom:2px solid #e5e7eb;">
        <th style="text-align:left;padding:8px 0;color:#6b7280;font-weight:500;">Item</th>
        <th style="text-align:center;padding:8px 0;color:#6b7280;font-weight:500;">Qty</th>
        <th style="text-align:right;padding:8px 0;color:#6b7280;font-weight:500;">Price</th>
      </tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr style="border-bottom:1px solid #f3f4f6;">
          <td style="padding:8px 0;color:#374151;"><?= e($item['product_name'] ?? $item['name'] ?? 'Item') ?></td>
          <td style="padding:8px 0;color:#6b7280;text-align:center;"><?= (int)($item['quantity'] ?? 1) ?></td>
          <td style="padding:8px 0;color:#374151;text-align:right;"><?= e($symbol) ?> <?= number_format($item['total'] ?? $item['price'] ?? 0, 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
    <a href="<?= e($orderUrl ?? '/account/orders') ?>" style="display:inline-block;background:#d97706;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">View Order Details</a>
  </div>
  <div style="background:#f9fafb;padding:20px 32px;text-align:center;border-top:1px solid #e5e7eb;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">&copy; <?= date('Y') ?> <?= e($storeName) ?>. All rights reserved.</p>
  </div>
</div>
</body></html>