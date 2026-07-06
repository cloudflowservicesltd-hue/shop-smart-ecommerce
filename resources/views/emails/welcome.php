<?php
$name = $name ?? 'Customer';
$storeName = $storeName ?? 'ShopSmart';
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="font-family:'Inter',Arial,sans-serif;background:#f9fafb;margin:0;padding:40px 20px;">
<div style="max-width:480px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">
  <div style="background:linear-gradient(135deg,#d97706,#b45309);padding:32px;text-align:center;">
    <h1 style="color:#fff;margin:0;font-size:24px;font-weight:700;"><?= e($storeName) ?></h1>
    <p style="color:#fde68a;margin:8px 0 0;font-size:14px;">Welcome to our store!</p>
  </div>
  <div style="padding:32px;">
    <p style="color:#374151;font-size:16px;margin:0 0 8px;">Hello <strong><?= e($name) ?></strong>,</p>
    <p style="color:#6b7280;font-size:14px;line-height:1.6;margin:0 0 24px;">Your account has been created successfully. You can now browse products, place orders, track shipments, and manage your profile.</p>
    <a href="<?= e($loginUrl ?? '/') ?>" style="display:inline-block;background:#d97706;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">Go to My Account</a>
  </div>
  <div style="background:#f9fafb;padding:20px 32px;text-align:center;border-top:1px solid #e5e7eb;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">&copy; <?= date('Y') ?> <?= e($storeName) ?>. All rights reserved.</p>
  </div>
</div>
</body></html>