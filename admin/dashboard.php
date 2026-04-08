<?php
// admin/dashboard.php
// first page the admin sees after logging in.
// summary of total users, attendance today, currently in, recent activity feed

require_once '../auth_guard.php';
guardAdmin();           // blocks anyone who isn't admin
require_once '../config.php';

// numbers for the stat cards

// count all registered users (excluding admins)
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin' AND is_registered = 1");
$totalUsers = $stmt->fetchColumn();

// Count how many people timed in today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = CURDATE()");
$stmt->execute();
$todayCount = $stmt->fetchColumn();

// Count how many are currently "in" (timed in but not yet timed out)
$stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND time_out IS NULL");
$currentlyIn = $stmt->fetchColumn();

// Get the 10 most recent attendance records for the activity feed
$stmt = $pdo->query("
    SELECT u.full_name, u.role, u.id_number,
           a.time_in, a.time_out, a.date
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.time_in DESC
    LIMIT 10
");
$recentLogs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Attendance System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/icas1.jpg">
</head>
<body class="dashboard-page">

    <!-- SIDEBAR -->
    <!-- BURGER BUTTON -->
    <button class="burger-btn" id="burgerBtn" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="sidebar-brand-icon"><img src="../assets/img/icas1.jpg" alt="ICAS-MACOY Logo"></span>
            <span class="sidebar-brand-name">QR Attendance</span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <span class="nav-icon">&#9676;</span> Dashboard
            </a>
            <a href="manage_users.php" class="nav-item">
                <span class="nav-icon">&#9632;</span> Manage Users
            </a>
            <a href="attendance_logs.php" class="nav-item">
                <span class="nav-icon">&#9650;</span> Attendance Logs
            </a>
            <a href="reports.php" class="nav-item">
                <span class="nav-icon">&#9660;</span> Reports
            </a>
        </nav>
        <a href="../logout.php" class="sidebar-logout">&#10006; Log Out</a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="page-header">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-sub">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
            </div>
            <div class="header-date"><?= date('l, F j, Y') ?></div>
        </header>

        <!-- STAT CARDS -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
            <div class="stat-card stat-card--accent">
                <div class="stat-value"><?= $todayCount ?></div>
                <div class="stat-label">Attendance Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $currentlyIn ?></div>
                <div class="stat-label">Currently Inside</div>
            </div>
        </div>

        <!-- RECENT ACTIVITY TABLE -->
        <section class="card">
            <h2 class="card-title">Recent Activity</h2>
            <?php if (empty($recentLogs)): ?>
                <p class="empty-state">No attendance records yet.</p>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID Number</th>
                            <th>Role</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['full_name']) ?></td>
                            <td><?= htmlspecialchars($log['id_number']) ?></td>
                            <td><span class="badge badge--<?= $log['role'] ?>"><?= ucfirst($log['role']) ?></span></td>
                            <td><?= $log['time_in'] ? date('h:i A', strtotime($log['time_in'])) : '—' ?></td>
                            <td><?= $log['time_out'] ? date('h:i A', strtotime($log['time_out'])) : '<span class="text-muted">Still in</span>' ?></td>
                            <td><?= $log['date'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
