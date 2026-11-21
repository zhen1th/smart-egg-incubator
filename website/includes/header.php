<?php
/**
 * Simple & Clean Header & Navigation
 * Smart Egg Incubator IoT System
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'Dashboard Monitoring';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Smart Egg Incubator</title>
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Clean Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">

<div class="app-layout">
    <!-- Sidebar -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-header">
            <div class="brand-container">
                <div class="brand-logo">
                    <i class="bi bi-egg-fill"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name">Egg Incubator</span>
                    <span class="brand-tag">IoT System</span>
                </div>
            </div>
            <button type="button" class="sidebar-close-btn d-lg-none" id="sidebarCloseBtn" aria-label="Tutup">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="#dashboard-top" class="nav-item active" data-nav-target="dashboard-top">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
            <a href="#stepper-mode-section" class="nav-item" data-nav-target="stepper-mode-section">
                <i class="bi bi-arrow-repeat"></i>
                <span>Mode Stepper</span>
            </a>
            <a href="#monitoring-section" class="nav-item" data-nav-target="monitoring-section">
                <i class="bi bi-speedometer2"></i>
                <span>Monitoring</span>
            </a>
            <a href="#chart-section" class="nav-item" data-nav-target="chart-section">
                <i class="bi bi-graph-up"></i>
                <span>Grafik Sensor</span>
            </a>
            <a href="#activity-log-section" class="nav-item" data-nav-target="activity-log-section">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile-widget">
                <div class="user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?? 'Admin') ?></span>
                    <span class="user-role">Administrator</span>
                </div>
                <a href="logout.php" class="btn-logout" title="Keluar">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Main Canvas -->
    <div class="app-main">
        <!-- Topbar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-icon sidebar-toggle-btn d-lg-none" id="sidebarToggleBtn" aria-label="Menu">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h1 class="page-title mb-0">Smart Egg Incubator</h1>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="status-pill mode-pill" id="header-mode-pill">
                    <i class="bi bi-arrow-repeat text-amber"></i>
                    <span id="header-mode-name">Mode 1: Putar Terus</span>
                </div>

                <div class="status-pill connection-pill" id="header-conn-pill">
                    <span class="status-dot offline" id="header-conn-dot"></span>
                    <span id="header-conn-text">OFFLINE</span>
                </div>
            </div>
        </header>

        <main class="workspace-container" id="dashboard-top">
