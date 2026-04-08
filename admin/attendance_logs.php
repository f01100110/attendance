<?php
// admin/attendance_logs.php
// Shows ALL attendance records with filters for date and role.
// Admin can search by name, filter by date range, and filter by role.

require_once '../auth_guard.php';
guardAdmin();
require_once '../config.php';

// --- READ FILTER VALUES FROM URL (GET parameters) ---
$filter_date_from = $_GET['date_from'] ?? date('Y-m-d');  // Default: today
$filter_date_to   = $_GET['date_to']   ?? date('Y-m-d');
$filter_role      = $_GET['role']      ?? '';
$filter_name      = trim($_GET['name'] ?? '');

// --- BUILD QUERY DYNAMICALLY BASED ON FILTERS ---
// We use an array of conditions and merge them to keep the query clean
$conditions = ["a.date BETWEEN ? AND ?"];
$params     = [$filter_date_from, $filter_date_to];

if ($filter_role !== '') {
    $conditions[] = "u.role = ?";
    $params[]     = $filter_role;
}

if ($filter_name !== '') {
    $conditions[] = "(u.full_name LIKE ? OR u.id_number LIKE ?)";
    $params[]     = "%$filter_name%";
    $params[]     = "%$filter_name%";
}

$where = implode(" AND ", $conditions);  // Joins all conditions with AND

$stmt = $pdo->prepare("
    SELECT u.full_name, u.id_number, u.role,
           a.time_in, a.time_out, a.date, a.id as attendance_id
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE $where
    ORDER BY a.date DESC, a.time_in DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Logs — Attendance System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/icas1.jpg">
</head>
<body class="dashboard-page">

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
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">&#9676;</span> Dashboard
            </a>
            <a href="manage_users.php" class="nav-item">
                <span class="nav-icon">&#9632;</span> Manage Users
            </a>
            <a href="attendance_logs.php" class="nav-item active">
                <span class="nav-icon">&#9650;</span> Attendance Logs
            </a>
            <a href="reports.php" class="nav-item">
                <span class="nav-icon">&#9660;</span> Reports
            </a>
        </nav>
        <a href="../logout.php" class="sidebar-logout">&#10006; Log Out</a>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1 class="page-title">Attendance Logs</h1>
                <p class="page-sub">Filter and view all time-in/time-out records.</p>
            </div>
        </header>

        <!-- FILTER FORM -->
        <section class="card">
            <form method="GET" action="attendance_logs.php" class="filter-form">
                <div class="form-group">
                    <label>Name / ID</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($filter_name) ?>" placeholder="Search name or ID...">
                </div>
                <div class="form-group">
                    <label>From</label>
                    <input type="date" name="date_from" value="<?= $filter_date_from ?>">
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input type="date" name="date_to" value="<?= $filter_date_to ?>">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="student"  <?= $filter_role === 'student'  ? 'selected' : '' ?>>Student</option>
                        <option value="faculty"  <?= $filter_role === 'faculty'  ? 'selected' : '' ?>>Faculty</option>
                        <option value="staff"    <?= $filter_role === 'staff'    ? 'selected' : '' ?>>Staff</option>
                    </select>
                </div>
                <div class="form-group form-group--btn">
                    <button type="submit" class="btn btn--primary">Filter</button>
                    <a href="attendance_logs.php" class="btn btn--ghost">Reset</a>
                </div>
            </form>
        </section>

        <!-- RESULTS TABLE -->
        <section class="card">
            <h2 class="card-title">Results (<?= count($logs) ?> records)</h2>
            <?php if (empty($logs)): ?>
                <p class="empty-state">No records found for the selected filters.</p>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID Number</th>
                            <th>Role</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log):
                            // Calculate duration only if both time_in and time_out exist
                            $duration = '—';
                            if ($log['time_in'] && $log['time_out']) {
                                $in  = new DateTime($log['time_in']);
                                $out = new DateTime($log['time_out']);
                                $diff = $in->diff($out);
                                $duration = $diff->h . 'h ' . $diff->i . 'm';
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($log['full_name']) ?></td>
                            <td><?= htmlspecialchars($log['id_number']) ?></td>
                            <td><span class="badge badge--<?= $log['role'] ?>"><?= ucfirst($log['role']) ?></span></td>
                            <td><?= $log['date'] ?></td>
                            <td><?= $log['time_in'] ? date('h:i A', strtotime($log['time_in'])) : '—' ?></td>
                            <td><?= $log['time_out'] ? date('h:i A', strtotime($log['time_out'])) : '<span class="text-muted">Still in</span>' ?></td>
                            <td><?= $duration ?></td>
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
