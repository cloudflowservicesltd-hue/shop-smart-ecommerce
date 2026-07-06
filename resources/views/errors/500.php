<?php
$details = $details ?? null;
ErrorHandler::render(
    500,
    'Server Error',
    "Something went wrong on our end. Our team has been notified and we're working to fix it.",
    $details,
    true
);