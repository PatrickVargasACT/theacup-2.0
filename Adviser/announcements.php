<?php
session_start();
include __DIR__ . '/../database.php';

// Handle Posting New Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_announcement') {
    $title   = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title) && !empty($content)) {
        // 1. Insert both title and content into the announcement table
        $stmt = mysqli_prepare($conn, "INSERT INTO announcement (title, content) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $title, $content);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 2. Broadcast notification to all projects so students see it in their notifications portal
        $projects_query = "SELECT project_id FROM projects";
        $projects_result = mysqli_query($conn, $projects_query);

        if ($projects_result && mysqli_num_rows($projects_result) > 0) {
            $notif_stmt = mysqli_prepare($conn, "INSERT INTO notification (project_id, content, isRead) VALUES (?, ?, 0)");
            
            while ($proj = mysqli_fetch_assoc($projects_result)) {
                $proj_id = $proj['project_id'];
                $notif_content = "New Announcement Posted: \"" . $title . "\"";
                
                mysqli_stmt_bind_param($notif_stmt, "is", $proj_id, $notif_content);
                mysqli_stmt_execute($notif_stmt);
            }
            mysqli_stmt_close($notif_stmt);
        }
    }

    header("Location: announcements.php");
    exit();
}

// Fetch all announcements from the database (newest first)
$ann_query = "SELECT * FROM announcement ORDER BY announcement_id DESC";
$ann_result = mysqli_query($conn, $ann_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <title>Adviser Announcements | Theacup</title>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="projects.php">Theacup</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>" aria-current="page" href="announcements.php">Announcements</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <span class="navbar-text text-white me-3">
                        Welcome, <?php echo isset($_SESSION['adviser_name']) ? htmlspecialchars($_SESSION['adviser_name']) : 'Adviser'; ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container my-4" style="max-width: 800px;">
        <!-- Header & Post Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 mb-0">Announcements</h2>
                <small class="text-muted">Broadcast updates, deadlines, and reminders to your students</small>
            </div>
            <!-- Trigger Modal -->
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                data-bs-target="#postAnnouncementModal">
                + Post Announcement
            </button>
        </div>

        <!-- Announcements Feed -->
        <div class="vstack gap-3">
<?php if ($ann_result && mysqli_num_rows($ann_result) > 0): ?>
    <?php while ($ann = mysqli_fetch_assoc($ann_result)): ?>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="mb-2">
                    <h5 class="card-title fw-bold text-dark mb-0"><?php echo htmlspecialchars($ann['title']); ?></h5>
                </div>
                <p class="text-muted small mb-3">
                    Posted on <?php echo date('F j, Y, g:i a', strtotime($ann['date'])); ?> • By Adviser
                </p>
                <p class="card-text text-secondary mb-0">
                    <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                </p>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4 text-center text-muted">
            No announcements posted yet.
        </div>
    </div>
<?php endif; ?>
        </div>
    </div>

    <!-- POST ANNOUNCEMENT MODAL -->
    <div class="modal fade" id="postAnnouncementModal" tabindex="-1"
        aria-labelledby="postAnnouncementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="announcements.php" method="POST">
                    <input type="hidden" name="action" value="post_announcement">
                    <div class="modal-header">
                        <h5 class="modal-title" id="postAnnouncementModalLabel">New Announcement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label for="announcementTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="announcementTitle" name="title"
                                placeholder="e.g., Midterm Consultation Schedule" required>
                        </div>
                        <div class="mb-3">
                            <label for="announcementContent" class="form-label">Content / Message</label>
                            <textarea class="form-control" id="announcementContent" name="content" rows="4"
                                placeholder="Write your announcement details here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Publish Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>

</html>