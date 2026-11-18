<?php
session_start();
require '../database.php'; // Your MySQLi connection file ($conn)

// Redirect to login if user is not authenticated
if (!isset($_SESSION['project_id'])) {
    header("Location: login.php");
    exit;
}

$project_id = $_SESSION['project_id'];
$leader_name = $_SESSION['leader_name'] ?? 'Student';

// 1. Handle "Mark All as Read" POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $markAllStmt = mysqli_prepare($conn, "UPDATE notification SET isRead = 1 WHERE project_id = ? AND isRead = 0");
    mysqli_stmt_bind_param($markAllStmt, "i", $project_id);
    mysqli_stmt_execute($markAllStmt);
    mysqli_stmt_close($markAllStmt);

    header("Location: notifications.php");
    exit;
}

// 2. Handle Single Notification "Mark as Read" GET request
if (isset($_GET['read_id'])) {
    $notif_id = intval($_GET['read_id']);
    $markOneStmt = mysqli_prepare($conn, "UPDATE notification SET isRead = 1 WHERE notification_id = ? AND project_id = ?");
    mysqli_stmt_bind_param($markOneStmt, "ii", $notif_id, $project_id);
    mysqli_stmt_execute($markOneStmt);
    mysqli_stmt_close($markOneStmt);

    header("Location: notifications.php");
    exit;
}

// 3. Fetch all notifications for the logged-in project (newest first)
$stmt = mysqli_prepare($conn, "SELECT notification_id, content, date, isRead FROM notification WHERE project_id = ? ORDER BY date DESC");
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
$unread_count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
    if ($row['isRead'] == 0) {
        $unread_count++;
    }
}
mysqli_stmt_close($stmt);

// Helper function to format datetime into relative time (e.g., "10 mins ago")
function timeAgo($datetime) {
    if (!$datetime) return '';
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ($mins == 1 ? ' min ago' : ' mins ago');
    }
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ($hours == 1 ? ' hour ago' : ' hours ago');
    }
    if ($diff < 172800) return 'Yesterday';
    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' days ago';
    }
    return date('M j, Y', $time);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <title>Student Notification | THEACUP</title>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">THEACUP</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="notifications.php">
                            Notifications
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-1"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="announcements.php">Announcements</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <span class="navbar-text text-white me-3">Welcome, <?= htmlspecialchars($leader_name) ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container my-4" style="max-width: 800px;">
        <!-- Header & Actions -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 mb-0">Notifications</h2>
                <small class="text-muted">Updates on your checklist progress, feedback, and grades</small>
            </div>
            
            <?php if ($unread_count > 0): ?>
                <form action="notifications.php" method="POST" class="m-0">
                    <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                        Mark all as read
                    </button>
                </form>
            <?php endif; ?>
        </div>

<!-- Notifications List -->
        <div class="list-group shadow-sm rounded-3">

            <?php if (empty($notifications)): ?>
                <div class="list-group-item p-4 text-center text-muted">
                    You have no notifications yet.
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): 
                    $isUnread = ($notif['isRead'] == 0);
                ?>
                    <a href="<?= $isUnread ? 'notifications.php?read_id=' . $notif['notification_id'] : '#' ?>"
                       class="list-group-item list-group-item-action p-3 notification-item <?= $isUnread ? 'bg-primary-subtle border-start border-primary border-4' : '' ?>">
                        <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                            <div>
                                <?php if ($isUnread): ?>
                                    <span class="badge bg-primary unread-badge">New</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars(timeAgo($notif['date'])) ?></small>
                        </div>
                        <p class="mb-0 small <?= $isUnread ? 'fw-semibold text-dark' : 'text-muted' ?>">
                            <?= htmlspecialchars($notif['content']) ?>
                        </p>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>

</html>