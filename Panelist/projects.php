<?php
session_start();
include __DIR__ . '/../database.php';

// Handle Grade Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_grade') {
    $project_id = intval($_POST['project_id']);
    $grade      = !empty($_POST['grade']) ? floatval($_POST['grade']) : null;

    if ($project_id > 0) {
        // 1. Update the project grade in the database
        $stmt = mysqli_prepare($conn, "UPDATE projects SET grade = ? WHERE project_id = ?");
        mysqli_stmt_bind_param($stmt, "di", $grade, $project_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 2. Insert a notification for the project
        if ($grade !== null) {
            $notif_content = "Your project has been graded. Your new grade is: " . number_format($grade, 2);
        } else {
            $notif_content = "Your project grade has been cleared or reset.";
        }

        $notif_stmt = mysqli_prepare($conn, "INSERT INTO notification (project_id, content, isRead) VALUES (?, ?, 0)");
        mysqli_stmt_bind_param($notif_stmt, "is", $project_id, $notif_content);
        mysqli_stmt_execute($notif_stmt);
        mysqli_stmt_close($notif_stmt);
    }

    header("Location: projects.php?success=grade_updated");
    exit();
}

// Handle Comment / Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'store_comment') {
    $project_id  = intval($_POST['project_id']);
    $comment     = trim($_POST['comment']);
    $panelist_id = isset($_SESSION['panelist_id']) ? intval($_SESSION['panelist_id']) : null;

    if ($project_id > 0 && !empty($comment)) {
        // 1. Insert the comment into the comments table
        $stmt = mysqli_prepare($conn, "INSERT INTO comments (project_id, adviser_id, comment) VALUES (?, ?, ?)");
        // Note: adviser_id column in comments table is used to store the evaluating staff/panelist ID
        mysqli_stmt_bind_param($stmt, "iis", $project_id, $panelist_id, $comment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 2. Create a specific notification for the project
        $short_comment = strlen($comment) > 60 ? substr($comment, 0, 57) . '...' : $comment;
        $notif_content = "A panelist left new feedback: \"" . $short_comment . "\"";

        $notif_stmt = mysqli_prepare($conn, "INSERT INTO notification (project_id, content, isRead) VALUES (?, ?, 0)");
        mysqli_stmt_bind_param($notif_stmt, "is", $project_id, $notif_content);
        mysqli_stmt_execute($notif_stmt);
        mysqli_stmt_close($notif_stmt);
    }

    header("Location: projects.php?success=comment_stored");
    exit();
}

// Fetch all projects from the database
$projects_query = "SELECT * FROM projects ORDER BY project_id ASC";
$projects_result = mysqli_query($conn, $projects_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <title>Panelist Dashboard | THEACUP</title>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <!-- Brand / Logo -->
            <a class="navbar-brand fw-bold" href="projects.php">THEACUP</a>

            <!-- Mobile Hamburger Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>" aria-current="page" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">Announcements</a>
                    </li>
                </ul>

                <!-- Right-aligned User/Logout Action -->
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
    <div class="container my-4">
        <!-- Header with Title and Search Bar -->
        <div class="row justify-content-between align-items-center mb-3">
            <div class="col-md-6 mb-2 mb-md-0">
                <h2 class="h4 mb-0">Project Evaluations & Grading</h2>
            </div>
            <div class="col-md-4">
                <!-- Search Bar Input -->
                <input type="text" id="projectSearchInput" class="form-control" placeholder="Search projects...">
            </div>
        </div>

        <!-- Projects Table Card -->
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="projectsTable">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Title / Document</th>
                            <th scope="col">Members</th>
                            <th scope="col">Credentials</th>
                            <th scope="col">Grade</th>
                            <th scope="col" class="text-end">Actions / Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($projects_result && mysqli_num_rows($projects_result) > 0): ?>
                            <?php while ($project = mysqli_fetch_assoc($projects_result)): 
                                $pid = $project['project_id'];
                            ?>
                                <tr>
                                    <th scope="row"><?php echo htmlspecialchars($pid); ?></th>
                                    <td>
                                        <strong><?php echo htmlspecialchars($project['title']); ?></strong><br>
                                        
                                        <!-- Section Badge -->
                                        <?php if (!empty($project['section'])): ?>
                                            <span class="badge bg-dark-subtle text-dark border border-dark-subtle me-1"><?php echo htmlspecialchars($project['section']); ?></span>
                                        <?php endif; ?>

                                        <!-- Capstone / Thesis Identifier Badge -->
                                        <?php if ($project['isCapstone'] == 1): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">Capstone</span>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle me-1">Thesis</span>
                                        <?php endif; ?><br>

                                        <!-- View Document Link -->
                                        <?php if (!empty($project['project_file'])): ?>
                                            <a href="../Documents/<?php echo htmlspecialchars($project['project_file']); ?>" target="_blank" class="small text-decoration-none fw-semibold">
                                                📄 View Document
                                            </a>
                                        <?php else: ?>
                                            <span class="small text-muted">No document uploaded</span>
                                        <?php endif; ?>

                                        <?php if (!empty($project['project'])): ?>
                                            <div class="small text-muted mt-1"><?php echo htmlspecialchars($project['project']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($project['leader_name']); ?></span> 
                                        <span class="badge bg-secondary">Leader</span><br>
                                        <small class="text-muted">
                                            <?php 
                                                $members = [];
                                                if (!empty($project['groupmate1'])) $members[] = $project['groupmate1'];
                                                if (!empty($project['groupmate2'])) $members[] = $project['groupmate2'];
                                                echo htmlspecialchars(implode(', ', $members));
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="d-block text-muted"><?php echo htmlspecialchars($project['email']); ?></small>
                                        <small class="text-muted">••••••••</small>
                                    </td>
                                    <td>
                                        <form action="projects.php" method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="action" value="update_grade">
                                            <input type="hidden" name="project_id" value="<?php echo $pid; ?>">
                                            <input type="number" step="0.01" min="0" max="100" name="grade" 
                                                value="<?php echo $project['grade'] !== null ? htmlspecialchars($project['grade']) : ''; ?>"
                                                class="form-control form-control-sm me-1" style="width: 85px;" placeholder="0.00">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Save</button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#commentModal<?php echo $pid; ?>">
                                            Comment / Feedback
                                        </button>
                                    </td>
                                </tr>

                                <!-- COMMENT / FEEDBACK MODAL FOR PROJECT <?php echo $pid; ?> -->
                                <div class="modal fade" id="commentModal<?php echo $pid; ?>" tabindex="-1" aria-labelledby="commentModalLabel<?php echo $pid; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="projects.php" method="POST">
                                                <input type="hidden" name="action" value="store_comment">
                                                <input type="hidden" name="project_id" value="<?php echo $pid; ?>">
                                                
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="commentModalLabel<?php echo $pid; ?>">Feedback for: <?php echo htmlspecialchars($project['title']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="mb-3">
                                                        <label for="panelistComment<?php echo $pid; ?>" class="form-label">Panelist Comments / Revision Notes</label>
                                                        <textarea class="form-control" id="panelistComment<?php echo $pid; ?>" name="comment" rows="4"
                                                            placeholder="Write your feedback or revision requirements here..." required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Send Comment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No projects found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>

    <!-- JavaScript for Table Search Filtering -->
    <script>
        document.getElementById('projectSearchInput').addEventListener('keyup', function () {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#projectsTable tbody tr');

            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>