<?php
session_start();
include __DIR__ . '/../database.php';

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
    <title>Panelist Announcements | THEACUP</title>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="projects.php">THEACUP</a>
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
                        Welcome, <?php echo isset($_SESSION['panelist_name']) ? htmlspecialchars($_SESSION['panelist_name']) : 'Panelist'; ?>!
                    </span>
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
            <small class="text-muted">View updates, deadlines, and reminders from advisers</small>
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

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>

</html>