<?php
// admin/manage_users.php
// Admin uses this page to:
// 1. Add a new ID number to the system (pre-enrollment)
// 2. View all users and their registration status
// 3. Delete a user if needed

require_once '../auth_guard.php';
guardAdmin();
require_once '../config.php';

$error = "";
$success = "";

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Which action was submitted?
    $action = $_POST['action'] ?? '';

    // ACTION: Add a new pre-enrolled ID
    if ($action === 'add_user') {
        $id_number = trim(htmlspecialchars($_POST['id_number']));
        $full_name = trim(htmlspecialchars($_POST['full_name']));
        $role = $_POST['role'];

        // Validate role — only accept known values (prevents tampering)
        $allowed_roles = ['student', 'faculty', 'staff', 'admin'];
        if (!in_array($role, $allowed_roles)) {
            $error = "Invalid role selected.";
        } elseif (empty($id_number) || empty($full_name)) {
            $error = "ID number and full name are required.";
        } else {
            // Check if ID already exists
            $check = $pdo->prepare("SELECT id FROM users WHERE id_number = ?");
            $check->execute([$id_number]);
            if ($check->fetch()) {
                $error = "ID number '$id_number' already exists in the system.";
            } else {
                // Insert with no password yet — user will set it on register.php
                $stmt = $pdo->prepare("
                    INSERT INTO users (id_number, full_name, role, is_registered)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt->execute([$id_number, $full_name, $role]);
                $success = "ID number '$id_number' has been added successfully.";
            }
        }
    }

    // ACTION: Allow password reset
    if ($action === 'allow_reset') {
        $user_id = (int) $_POST['user_id'];
        $pdo->prepare("UPDATE users SET reset_allowed = 1 WHERE id = ?")->execute([$user_id]);
        $success = "Password reset approved. The user can now set a new password.";
    }

    // ACTION: Delete a user
    if ($action === 'delete_user') {
        $user_id = (int) $_POST['user_id']; // Cast to int for safety

        // Prevent admin from deleting themselves
        if ($user_id === (int) $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            // Also delete their attendance records first (foreign key)
            $pdo->prepare("DELETE FROM attendance WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            $success = "User has been removed from the system.";
        }
    }
}

// --- FETCH ALL USERS FOR THE TABLE ---
// Optional search/filter
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE full_name LIKE ? OR id_number LIKE ?
        ORDER BY role, full_name
    ");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role, full_name");
}
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — Attendance System</title>
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
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">&#9676;</span> Dashboard
            </a>
            <a href="manage_users.php" class="nav-item active">
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

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1 class="page-title">Manage Users</h1>
                <p class="page-sub">Pre-enroll ID numbers so users can self-register.</p>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="alert alert--error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert--success"><?= $success ?></div>
        <?php endif; ?>

        <!-- ADD USER FORM -->
        <section class="card">
            <h2 class="card-title">Add New ID Number</h2>
            <form method="POST" action="manage_users.php" class="inline-form">
                <input type="hidden" name="action" value="add_user">

                <div class="form-group">
                    <label for="id_number">ID Number</label>
                    <input type="text" id="id_number" name="id_number" placeholder="e.g. 2021-00123" required>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="e.g. Juan Dela Cruz" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <!-- Admin controls the role — users cannot choose this themselves -->
                    <select id="role" name="role" class="form-select">
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn btn--primary">Add to System</button>
            </form>
        </section>

        <!-- SEARCH + USER TABLE -->
        <section class="card">
            <div class="card-header-row">
                <h2 class="card-title">All Users (<?= count($users) ?>)</h2>
                <form method="GET" action="manage_users.php" class="search-form">
                    <input type="text" name="search" placeholder="Search name or ID..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn--primary btn--sm">Search</button>
                    <?php if ($search): ?>
                        <a href="manage_users.php" class="btn btn--ghost btn--sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($users)): ?>
                <p class="empty-state">No users found. Add an ID number above to get started.</p>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id_number']) ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><span class="badge badge--<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                            <td>
                                <?php if ($user['is_registered']): ?>
                                    <span class="badge badge--success">Registered</span>
                                <?php else: ?>
                                    <span class="badge badge--pending">Pending</span>
                                <?php endif; ?>
                                <!-- Show extra badge if reset was requested -->
                                <?php if (!empty($user['reset_requested']) && empty($user['reset_allowed'])): ?>
                                    <span class="badge badge--reset">Reset Requested</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center;">

                                    <!-- Show orange Allow Reset button if user requested a reset and admin hasn't approved yet -->
                                    <?php if (!empty($user['reset_requested']) && empty($user['reset_allowed'])): ?>
                                    <form method="POST" action="manage_users.php">
                                        <input type="hidden" name="action" value="allow_reset">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn--sm btn--reset">&#128274; Allow Reset</button>
                                    </form>
                                    <?php endif; ?>

                                    <!-- Remove button (hidden for own account) -->
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="manage_users.php"
                                          onsubmit="return confirm('Remove <?= htmlspecialchars($user['full_name']) ?> from the system?')">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Remove</button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted">You</span>
                                    <?php endif; ?>

                                </div>
                            </td>
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