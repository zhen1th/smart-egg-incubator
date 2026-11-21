<?php
/**
 * Application Entry Point
 * Redirects to dashboard or login
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isAuthenticated()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;

