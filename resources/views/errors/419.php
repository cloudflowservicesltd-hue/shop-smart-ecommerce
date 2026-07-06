<?php
ErrorHandler::render(
    419,
    'Session Expired',
    "Your session has expired due to inactivity. Please refresh the page and try again.",
    'CSRF token mismatch or session expired.'
);