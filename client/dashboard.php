<?php
// client/dashboard.php
// FOR STUDENT/FACULTY/STAFF
// UPDATED 7-MAR-2026
// SHOWS:
//   - Current status (timed in or out)
//  - Total days present and absent in the current trimester (3 months)
//   - A calendar for the current month with attendance marked
//   - Full 3-month (trimester) attendance history

require_once '../auth_guard.php';
guardClient();
require_once '../config.php';

$user_id = $_SESSION['user_id'];

// 3-MONTH RANGE (still yet to be fixed since wala pang data for trimester calendar)) ---
$today        = date('Y-m-d');
$threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));

// --- ATTENDANCE RECORDS FOR LAST 3 MONTHS (temporary)
$stmt = $pdo->prepare("
    SELECT date, time_in, time_out
    FROM attendance
    WHERE user_id = ? AND date BETWEEN ? AND ?
    ORDER BY date DESC, time_in DESC
");
$stmt->execute([$user_id, $threeMonthsAgo, $today]); // 
$records = $stmt->fetchAll();
// --- PROCESS: $stmt = $pdo->prepare block gets attedance records from columns DATE, TIME_IN, TIME_OUT
// from ATTENDANCE TABLE where USER_ID matches the logged in user and DATE is between 3 MONTHS AGO and TODAY
// ordered by DATE DESC and TIME_IN DESC
// $stmt = $pdo->prepare ay equivalent sa lumang syntax na $result = mysqli_query($conn, "SELECT ...") then fetching results with mysqli_fetch_assoc in a loop
// --- PROCESS: kunin ang data ng attendance records from columns DATE, TIME_IN, TIME_OUT from ATTENDANCE TABLE
// WHERE uses the column USER_ID para makuha lang yung record ng specific user na nakalogin  (ito yung rows)
// DATE BETWEEN para makuha lang yung records sa loob ng 3-month range (from $threeMonthsAgo to $today)
// USER_ID (sa USERS table, id lang yan. yan yung unique id given to user upon registration)
// -> execute makes the ? placeholders get replaced by the actual values
// $records = $stmt->fetchAll() is an array. ang silbi nito ay hindi ko alam. WAHAHAHAHA basta hirap iexplain
// ito yung variable na nagstore ng result ng query as an array of rows. bawat row ay may keys na 'date', 'time_in', 'time_out' (from SELECT statement) at values na galing sa database
// so $records[0]['date'] would give the date of the first record, $records[0]['time_in'] would give the time_in of the first record, etc. since we ordered by date DESC, the most recent attendance record will be at $records[0] 
// (tagalugin ko na nga)

// this is used to calculate total days present and absent for the last 3 mos.
// --- HOW IT WORKS: so again gumagamit to ng array to store the dates na nag-time-in yung user
// foreach means para sa bawat record sa $records array, gawin mo kunin yung DATE at i-store sa array
// kapag true yung value ng isang date, meaning nag-time in yung user sa date na yun, kapag wala naman ibig sabihin wala siyang attendance record sa date na yun (either hindi siya pumasok or hindi siya nag-time in kahit pumasok siya)
// matatapos lang loop kapag lahat ng records nakuha at na-determine kung anong dates yung may attendance at anong dates yung wala
$attendedDates = [];
foreach ($records as $rec) {
    $attendedDates[$rec['date']] = true;
}

// count total days present within 3 mos (trimester pero three months muna)
$totalDays = count($attendedDates);

// count days absent. since wala pang school calendar, included pa dito yung weekends, holidays (what if may suspension pa?) idk if i can find other ways to fix this. for now ito muna
// how can i change this to school days only? we have saturday classes. compli as shhhhhhiiiii
$start       = new DateTime($threeMonthsAgo);
$end         = new DateTime($today);
$totalInRange = (int)$start->diff($end)->days + 1; // +1 to include today
$daysAbsent  = $totalInRange - $totalDays;

// --- CURRENT STATUS (timed in or out)
$stmt = $pdo->prepare("
    SELECT id FROM attendance
    WHERE user_id = ? AND date = CURDATE() AND time_out IS NULL
");
$stmt->execute([$user_id]);
$isTimedIn = $stmt->fetch() ? true : false; // LOGIC: if may record na nag-time in today pero wala pang time out, ibig sabihin ay currently timed in pa yung user. kapag walang record na ganyan, ibig sabihin ay hindi siya timed in ngayon (either hindi siya pumasok or nag-time out na siya)

// --- CALENDAR DATA ---
// CURRENT month calendar
// Allow user to navigate months via ?cal_month=YYYY-MM in the URL
$calMonth = $_GET['cal_month'] ?? date('Y-m');

// Make sure the format is valid, fallback to current month
if (!preg_match('/^\d{4}-\d{2}$/', $calMonth)) {
    $calMonth = date('Y-m');
}
// nakita ko lang somewhere. for calendar navigation
// the logic ay kukunin yung year at month number from $calMonth then gagamitin yung cal_days_in_month function para malaman kung ilang days meron sa month na yun
// tapos kukunin yung weekday ng first day of the month para malaman kung ilang empty cells ang kailangan bago lumabas yung date 1 sa calendar grid
// can i just use an api for this? i'll try later. for now, manual muna
$calYear       = (int) substr($calMonth, 0, 4);
$calMonthNum   = (int) substr($calMonth, 5, 2);
$daysInMonth   = cal_days_in_month(CAL_GREGORIAN, $calMonthNum, $calYear);
$firstDayOfMonth = date('N', strtotime("$calMonth-01")); // 1=Mon, 7=Sun

// previous and next month links for navigation
$prevMonth = date('Y-m', strtotime("$calMonth-01 -1 month"));
$nextMonth = date('Y-m', strtotime("$calMonth-01 +1 month"));
// Don't allow going beyond current month
$canGoNext = $nextMonth <= date('Y-m');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/icas1.jpg">
</head>
<body class="dashboard-page">

    <!-- BURGER BUTTON (DRAFT FOR NOW)
    <button class="burger-btn" id="burgerBtn" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button> -->

    <!-- SIDEBAR OVERLAY (darkens page when sidebar is open on mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="sidebar-brand-icon"><img src="../assets/img/icas1.jpg" alt="ICAS-MACOY Logo"></span>
            <span class="sidebar-brand-name">QR Attendance</span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <span class="nav-icon">&#9676;</span> My Attendance
            </a>
        </nav>
        <a href="../logout.php" class="sidebar-logout">&#10006; Log Out</a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="page-header">
            <div>
                <h1 class="page-title">My Attendance</h1>
                <p class="page-sub">
                    <?= htmlspecialchars($_SESSION['full_name']) ?> &mdash;
                    <span class="badge badge--<?= $_SESSION['role'] ?>"><?= ucfirst($_SESSION['role']) ?></span>
                </p>
            </div>
            <div class="header-date"><?= date('l, F j, Y') ?></div>
        </header>

        <!-- STATUS BANNER -->
        <?php if ($isTimedIn): ?>
            <div class="alert alert--success">
                &#9679; You are currently <strong>timed in</strong> today. Tap your ID to time out.
            </div>
        <?php else: ?>
            <div class="alert alert--info">
                &#9675; You are not timed in. Tap your QR ID card at the scanner to time in.
            </div>
        <?php endif; ?>

        <!-- STAT CARDS: Row 1 = status, Row 2 = present/absent counts -->
        <div class="stat-grid stat-grid--2col">
            <!-- Row 1: CURRENT STATUS -->
            <div class="stat-card <?= $isTimedIn ? 'stat-card--in' : 'stat-card--out' ?> stat-card--full">
                <div class="stat-value"><?= $isTimedIn ? 'IN' : 'OUT' ?></div>
                <div class="stat-label">Current Status Today</div>
            </div>
        </div>
        <div class="stat-grid stat-grid--2col">
            <!-- Row 2: DAYS PRESENT AND DAYS ABSENT -->
            <div class="stat-card stat-card--accent">
                <div class="stat-value"><?= $totalDays ?></div>
                <div class="stat-label">Days Present</div>
            </div>
            <div class="stat-card stat-card--absent">
                <div class="stat-value"><?= $daysAbsent ?></div>
                <div class="stat-label">Days Absent</div>
            </div>
        </div>

        <!-- CALENDAR -->
        <section class="card">
            <div class="calendar-header">
                <a href="?cal_month=<?= $prevMonth ?>" class="btn btn--ghost btn--sm">&#8592; Prev</a>
                <h2 class="card-title" style="margin:0">
                    <?= date('F Y', strtotime("$calMonth-01")) ?>
                </h2>
                <?php if ($canGoNext): ?>
                    <a href="?cal_month=<?= $nextMonth ?>" class="btn btn--ghost btn--sm">Next &#8594;</a>
                <?php else: ?>
                    <span class="btn btn--ghost btn--sm" style="opacity:0.3;cursor:default;">Next &#8594;</span>
                <?php endif; ?>
            </div>

            <!-- LEGENDS OF THE CALENDAR -->
            <div class="cal-legend">
                <span class="cal-dot cal-dot--present"></span> Present
                <span class="cal-dot cal-dot--today"></span> Today
                <span class="cal-dot cal-dot--absent"></span> No record
            </div>

            <div class="calendar-grid">
                <!-- Day name headers: Mon to Sun -->
                <?php
                $dayNames = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                foreach ($dayNames as $d): ?>
                    <div class="cal-day-name"><?= $d ?></div>
                <?php endforeach; ?>

                <!-- Empty cells before the 1st of the month -->
                <!-- $firstDayOfMonth is 1=Mon...7=Sun, so we add ($firstDayOfMonth - 1) empty cells -->
                <?php for ($i = 1; $i < $firstDayOfMonth; $i++): ?>
                    <div class="cal-cell cal-cell--empty"></div>
                <?php endfor; ?>

                <!-- Day cells -->
                <?php for ($day = 1; $day <= $daysInMonth; $day++):
                    $dateStr  = sprintf('%s-%02d', $calMonth, $day);
                    $isToday  = ($dateStr === $today);
                    $attended = isset($attendedDates[$dateStr]);

                    $cellClass = 'cal-cell';
                    if ($isToday)    $cellClass .= ' cal-cell--today';
                    if ($attended)   $cellClass .= ' cal-cell--present';
                ?>
                    <div class="<?= $cellClass ?>">
                        <span class="cal-num"><?= $day ?></span>
                        <?php if ($attended): ?>
                            <span class="cal-tick">&#10003;</span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </section>

        <!-- 3-MONTH HISTORY TABLE -->
        <!-- MOVE TO NEW PAGE LATER -->
        <section class="card">
            <h2 class="card-title">
                Trimester History
                <small style="font-weight:400;font-size:0.8rem;color:#6b7280;">
                    (<?= date('M j', strtotime($threeMonthsAgo)) ?> – <?= date('M j, Y') ?>)
                </small>
            </h2>

            <?php if (empty($records)): ?>
                <p class="empty-state">No attendance records in the last 3 months.</p>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $rec):
                            $duration = '—';
                            if ($rec['time_in'] && $rec['time_out']) {
                                $in   = new DateTime($rec['time_in']);
                                $out  = new DateTime($rec['time_out']);
                                $diff = $in->diff($out);
                                $duration = $diff->h . 'h ' . $diff->i . 'm';
                            }
                        ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($rec['date'])) ?></td>
                            <td><?= $rec['time_in']  ? date('h:i A', strtotime($rec['time_in']))  : '—' ?></td>
                            <td><?= $rec['time_out'] ? date('h:i A', strtotime($rec['time_out'])) : '<span class="text-muted">Still in</span>' ?></td>
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
