<?php
/**
 * Authentication and Session Management
 * Smart Egg Incubator IoT System
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is authenticated.
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['username']);
}

/**
 * Enforce authentication on protected pages.
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Retrieve current logged-in user details.
 */
function getCurrentUser(): ?array
{
    if (!isAuthenticated()) {
        return null;
    }

    return [
        'id'        => (int) $_SESSION['user_id'],
        'username'  => (string) $_SESSION['username'],
        'full_name' => (string) ($_SESSION['full_name'] ?? 'Administrator'),
        'role'      => (string) ($_SESSION['role'] ?? 'admin'),
    ];
}

