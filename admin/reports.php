<?php
// admin/reports.php
// Lets admin generate attendance reports and export them as CSV.
// CSV export is useful for submitting reports to school administration.

require_once '../auth_guard.php';
guardAdmin();
require_once '../config.php';

$filter_month = $_GET['month'] ?? date('Y-m');
$filter_role  = $_GET['role']  ?? '';

// Build the month range (first day to last day of selected month)
$date_from = $filter_month . '-01';
$date_to   = date('Y-m-t', strtotime($date_from));  // 't' = last day of month

$conditions = ["a.date BETWEEN ? AND ?"];
$params     = [$date_from, $date_to];

if ($filter_role !== '') {
    $conditions[] = "u.role = ?";
    $params[]     = $filter_role;
}

$where = implode(" AND ", $conditions);

$stmt = $pdo->prepare("
    SELECT u.full_name, u.id_number, u.role,
           a.date, a.time_in, a.time_out
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE $where
    ORDER BY a.date ASC, u.full_name ASC
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// --- CSV EXPORT ---
// If the user clicked "Export CSV", output raw CSV and stop the page
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers to force download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . $filter_month . '.csv"');

    $out = fopen('php://output', 'w');
    // Write the column headers
    fputcsv($out, ['Full Name', 'ID Number', 'Role', 'Date', 'Time In', 'Time Out']);

    foreach ($logs as $row) {
        fputcsv($out, [
            $row['full_name'],
            $row['id_number'],
            ucfirst($row['role']),
            $row['date'],
            $row['time_in']  ? date('h:i A', strtotime($row['time_in']))  : '',
            $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : 'Still in',
        ]);
    }
    fclose($out);
    exit(); // Stop — don't render the HTML page
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — Attendance System</title>
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
            <a href="attendance_logs.php" class="nav-item">
                <span class="nav-icon">&#9650;</span> Attendance Logs
            </a>
            <a href="reports.php" class="nav-item active">
                <span class="nav-icon">&#9660;</span> Reports
            </a>
        </nav>
        <a href="../logout.php" class="sidebar-logout">&#10006; Log Out</a>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1 class="page-title">Reports</h1>
                <p class="page-sub">Generate monthly attendance reports and export to CSV.</p>
            </div>
        </header>

        <!-- FILTER + EXPORT FORM -->
        <section class="card">
            <form method="GET" action="reports.php" class="filter-form">
                <div class="form-group">
                    <label>Month</label>
                    <input type="month" name="month" value="<?= $filter_month ?>">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="student" <?= $filter_role === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="faculty" <?= $filter_role === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                        <option value="staff"   <?= $filter_role === 'staff'   ? 'selected' : '' ?>>Staff</option>
                    </select>
                </div>
                <div class="form-group form-group--btn">
                    <button type="submit" class="btn btn--primary">Generate</button>
                    <!-- Same form but with export=csv added to URL -->
                    <button type="submit" name="export" value="csv" class="btn btn--ghost">Export CSV</button>
                </div>
            </form>
        </section>

        <!-- REPORT TABLE -->
        <section class="card">
            <h2 class="card-title">
                Report: <?= date('F Y', strtotime($date_from)) ?>
                (<?= count($logs) ?> records)
            </h2>
            <?php if (empty($logs)): ?>
                <p class="empty-state">No records for this period.</p>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['full_name']) ?></td>
                            <td><?= htmlspecialchars($log['id_number']) ?></td>
                            <td><span class="badge badge--<?= $log['role'] ?>"><?= ucfirst($log['role']) ?></span></td>
                            <td><?= $log['date'] ?></td>
                            <td><?= $log['time_in']  ? date('h:i A', strtotime($log['time_in']))  : '—' ?></td>
                            <td><?= $log['time_out'] ? date('h:i A', strtotime($log['time_out'])) : '<span class="text-muted">Still in</span>' ?></td>
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
