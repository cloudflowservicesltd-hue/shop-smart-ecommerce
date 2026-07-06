<?php
$userInfo = (class_exists('Auth') && Auth::check()) ? (Auth::user()['email'] ?? 'guest') : 'guest';
ErrorHandler::render(
    403,
    'Access Denied',
    "You don't have permission to access this page. Please contact support if you believe this is an error.",
    'URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ' | User: ' . $userInfo
);