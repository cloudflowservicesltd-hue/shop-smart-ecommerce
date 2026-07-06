<?php
ErrorHandler::render(
    404,
    'Page Not Found',
    "The page you're looking for doesn't exist or has been moved. Let's get you back on track.",
    'URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown')
);