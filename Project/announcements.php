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

// 1. Fetch unread notification count for the navbar badge
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS unread_count FROM notification WHERE project_id = ? AND isRead = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $project_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$unread_count = $notif_data['unread_count'] ?? 0;
mysqli_stmt_close($notif_stmt);

// 2. Fetch all announcements ordered by newest first
$announcements = [];
$query = "SELECT announcement_id, title, content, date FROM announcement ORDER BY date DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
    mysqli_free_result($result);
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
    <title>Student Announcements | THEACUP</title>
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
                        <a class="nav-link" href="notifications.php">
                            Notifications
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-1"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="announcements.php">Announcements</a>
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
        <!-- Header -->
        <div class="mb-3">
            <h2 class="h4 mb-0">Announcements</h2>
            <small class="text-muted">View updates, deadlines, and reminders from your adviser</small>
        </div>

        <!-- Announcements Feed -->
        <div class="vstack gap-3">

            <?php if (empty($announcements)): ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 text-center text-muted">
                        No announcements have been posted yet.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $item): 
                    $formatted_date = !empty($item['date']) ? date('F j, Y', strtotime($item['date'])) : 'Unknown date';
                ?>
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-4">
                            <div class="mb-2">
                                <h5 class="card-title fw-bold text-dark mb-0">
                                    <?= htmlspecialchars($item['title']) ?>
                                </h5>
                            </div>
                            <p class="text-muted small mb-3">Posted on <?= htmlspecialchars($formatted_date) ?> • By Adviser</p>
                            <p class="card-text text-secondary mb-0">
                                <?= nl2br(htmlspecialchars($item['content'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>

</html>