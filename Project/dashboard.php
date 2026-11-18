<?php
session_start();
require '../database.php'; // Ensure this points to your MySQLi connection file ($conn)

// Redirect to login if user is not authenticated
if (!isset($_SESSION['project_id'])) {
    header("Location: login.php");
    exit;
}

$project_id = $_SESSION['project_id'];

// Fetch checklist status for this project
$checklist_query = "SELECT chapter_number, is_approved FROM checklist WHERE project_id = ?";
$chk_stmt = mysqli_prepare($conn, $checklist_query);
mysqli_stmt_bind_param($chk_stmt, "i", $project_id);
mysqli_stmt_execute($chk_stmt);
$chk_result = mysqli_stmt_get_result($chk_stmt);

$approvals = [];
$approved_count = 0;
while ($row = mysqli_fetch_assoc($chk_result)) {
    $approvals[$row['chapter_number']] = $row['is_approved'];
    if ($row['is_approved'] == 1) {
        $approved_count++;
    }
}
mysqli_stmt_close($chk_stmt);

// Total items is 6 (Title [0] + Chapters 1-5)
$total_items = 6;
$progress_percentage = round(($approved_count / $total_items) * 100);

// Handle Title Update Form Submission
$update_error = '';
$update_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_title'])) {
    $new_title = trim($_POST['title'] ?? '');
    
    if (!empty($new_title)) {
        $update_stmt = mysqli_prepare($conn, "UPDATE projects SET title = ? WHERE project_id = ?");
        mysqli_stmt_bind_param($update_stmt, "si", $new_title, $project_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['title'] = $new_title;
            $update_success = "Project title updated successfully!";
        } else {
            $update_error = "Failed to update title. Please try again.";
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $update_error = "Title cannot be empty.";
    }
}

// Handle Document Upload Form Submission (Integrated directly)
$upload_error = '';
$upload_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['project_file'])) {
    // Need current title for file renaming
    $title_fetch = mysqli_prepare($conn, "SELECT title FROM projects WHERE project_id = ?");
    mysqli_stmt_bind_param($title_fetch, "i", $project_id);
    mysqli_stmt_execute($title_fetch);
    $current_title_res = mysqli_fetch_assoc(mysqli_stmt_get_result($title_fetch));
    mysqli_stmt_close($title_fetch);
    
    $file = $_FILES['project_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validate extension (Only allow PDF)
        if ($fileExtension !== 'pdf') {
            $upload_error = "Invalid file extension. Only PDF files are allowed.";
        } 
        // Limit file size (max 15MB)
        elseif ($fileSize > 15 * 1024 * 1024) {
            $upload_error = "File size is too large. Maximum size is 15MB.";
        } else {
            $rawTitle = $current_title_res['title'] ?? 'project';

            // Clean the title: replace spaces with underscores and remove unsafe file characters
            $cleanTitle = preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', $rawTitle));
            if (empty($cleanTitle)) {
                $cleanTitle = 'project_' . $project_id;
            }

            $newFileName = $cleanTitle . '.pdf';
            $uploadDir = '../Documents/';
            $destPath = $uploadDir . $newFileName;

            // Ensure the ../Documents directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Overwrite existing file if it exists with the same title
            if (file_exists($destPath)) {
                unlink($destPath);
            }

            // Move uploaded file to ../Documents directory
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $updateStmt = mysqli_prepare($conn, "UPDATE projects SET project_file = ? WHERE project_id = ?");
                mysqli_stmt_bind_param($updateStmt, "si", $newFileName, $project_id);
                
                if (mysqli_stmt_execute($updateStmt)) {
                    mysqli_stmt_close($updateStmt);
                    
                    // Insert notification for the project document submission/update
                    $notif_content = "An updated project document (" . $newFileName . ") has been successfully uploaded.";
                    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notification (project_id, content, isRead) VALUES (?, ?, 0)");
                    mysqli_stmt_bind_param($notif_stmt, "is", $project_id, $notif_content);
                    mysqli_stmt_execute($notif_stmt);
                    mysqli_stmt_close($notif_stmt);

                    $upload_success = "Document successfully uploaded and renamed!";
                } else {
                    mysqli_stmt_close($updateStmt);
                    $upload_error = "Database update failed. Please try again.";
                }
            } else {
                $upload_error = "Failed to move the uploaded file to the destination folder.";
            }
        }
    } else {
        $upload_error = "An error occurred during file upload. Please try again.";
    }
}

// Fetch total unread notification count for this project
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS unread_count FROM notification WHERE project_id = ? AND isRead = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $project_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$unread_count = $notif_data['unread_count'] ?? 0;
mysqli_stmt_close($notif_stmt);

// Fetch comments/feedback for this specific project
$comments_stmt = mysqli_prepare($conn, "SELECT comment, created_at, adviser_id FROM comments WHERE project_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($comments_stmt, "i", $project_id);
mysqli_stmt_execute($comments_stmt);
$comments_result = mysqli_stmt_get_result($comments_stmt);

$comments = [];
while ($row = mysqli_fetch_assoc($comments_result)) {
    $comments[] = $row;
}
mysqli_stmt_close($comments_stmt);

// Fetch project record along with adviser and panelists names
$sql = "SELECT p.*, 
               a.name AS adviser_name, 
               p1.name AS panelist1_name, 
               p2.name AS panelist2_name
        FROM projects p
        LEFT JOIN advisers a ON p.adviser_id = a.adviser_id
        LEFT JOIN panelist p1 ON p.panelist1_id = p1.panelist_id
        LEFT JOIN panelist p2 ON p.panelist2_id = p2.panelist_id
        WHERE p.project_id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// If record somehow doesn't exist, destroy session and redirect
if (!$project) {
    session_destroy();
    header("Location: login.php");
    exit;
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
    <title>Student Dashboard | THEACUP</title>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">THEACUP</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="dashboard.php">Dashboard</a>
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
                        <a class="nav-link" href="announcements.php">Announcements</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <span class="navbar-text text-white me-3">Welcome, <?= htmlspecialchars($project['leader_name']) ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container my-4">

        <!-- Display Alerts for Title Update -->
        <?php if (!empty($update_success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($update_success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($update_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($update_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Display Alerts for File Upload -->
        <?php if (!empty($upload_success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($upload_success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($upload_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($upload_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 1. HERO / PROJECT OVERVIEW CARD -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                    <div>
                        <!-- Dynamic Badge based on isCapstone column -->
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2">
                            <?= $project['isCapstone'] ? 'Capstone Project' : 'Thesis Project' ?> 
                            (<?= htmlspecialchars($project['section'] ?? 'N/A') ?>)
                        </span>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h1 class="h3 fw-bold text-dark mb-0"><?= htmlspecialchars($project['title']) ?></h1>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTitleModal">
                                Edit Title
                            </button>
                        </div>
                        
                        <!-- Group Members -->
                        <p class="text-muted small mt-2 mb-1">
                            <strong>Leader:</strong> <?= htmlspecialchars($project['leader_name']) ?> 
                            <?php if (!empty($project['groupmate1'])): ?>
                                &nbsp;•&nbsp; <strong>Member 1:</strong> <?= htmlspecialchars($project['groupmate1']) ?>
                            <?php endif; ?>
                            <?php if (!empty($project['groupmate2'])): ?>
                                &nbsp;•&nbsp; <strong>Member 2:</strong> <?= htmlspecialchars($project['groupmate2']) ?>
                            <?php endif; ?>
                        </p>

                        <!-- Academic Staff: Adviser & Panelists -->
                        <p class="text-secondary small mb-0">
                            <strong>Adviser:</strong> <?= htmlspecialchars($project['adviser_name'] ?? 'Not assigned') ?>
                            &nbsp;•&nbsp; 
                            <strong>Panelist 1:</strong> <?= htmlspecialchars($project['panelist1_name'] ?? 'Not assigned') ?>
                            &nbsp;•&nbsp; 
                            <strong>Panelist 2:</strong> <?= htmlspecialchars($project['panelist2_name'] ?? 'Not assigned') ?>
                        </p>
                    </div>

                    <!-- Document Quick Action Button -->
                    <div class="text-md-end flex-shrink-0">
                        <?php if (!empty($project['project_file'])): ?>
                            <a href="../Documents/<?= htmlspecialchars($project['project_file']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                View Current PDF
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary btn-sm" disabled>
                                No PDF Uploaded
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row: Checklist Progress -->
        <div class="row g-3 align-items-center mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 p-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-semibold text-secondary">Checklist Completion Progress</span>
                        <span class="fw-bold text-primary"><?= $progress_percentage ?>% (<?= $approved_count ?> of <?= $total_items ?> Approved)</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progress_percentage ?>%;"
                            aria-valuenow="<?= $progress_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Milestone Checklist Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="h5 fw-bold mb-0">Project Checklist</h2>
                <small class="text-muted">Approval status tracked by your adviser</small>
            </div>
            <div class="list-group list-group-flush">
                
                <!-- Title Item (chapter_number = 0) -->
                <?php 
                    $isTitleApproved = isset($approvals[0]) && $approvals[0] == 1;
                ?>
                <div class="list-group-item d-flex justify-content-between align-items-center py-3 <?= $isTitleApproved ? 'bg-success-subtle' : '' ?>">
                    <span class="<?= $isTitleApproved ? 'fw-semibold text-dark' : 'text-secondary' ?>">Title</span>
                    <?php if ($isTitleApproved): ?>
                        <span class="badge bg-success">Approved</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border">Pending</span>
                    <?php endif; ?>
                </div>

                <!-- Chapters 1 to 5 Items -->
                <?php for ($ch = 1; $ch <= 5; $ch++): 
                    $isApproved = isset($approvals[$ch]) && $approvals[$ch] == 1;
                ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 <?= $isApproved ? 'bg-success-subtle' : '' ?>">
                        <span class="<?= $isApproved ? 'fw-semibold text-dark' : 'text-secondary' ?>">Chapter <?= $ch ?></span>
                        <?php if ($isApproved): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border">Pending</span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>

            </div>
        </div>

        <!-- TWO COLUMN LAYOUT: Upload (Left) & Feedback (Right) -->
        <div class="row g-4">
            
            <!-- LEFT COLUMN: Document Upload / Revision Card -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h2 class="h5 fw-bold mb-0">Submit / Update Document</h2>
                        <small class="text-muted">Upload your latest manuscript in PDF format</small>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- Display current uploaded file name if it exists -->
                        <?php if (!empty($project['project_file'])): ?>
                            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <small class="text-muted d-block">Current File on Record:</small>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($project['project_file']) ?></span>
                                </div>
                                <a href="../Documents/<?= htmlspecialchars($project['project_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View File
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-3">No document uploaded yet.</p>
                        <?php endif; ?>

                        <!-- Upload Form pointing back to dashboard.php -->
                        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="projectPdf" class="form-label small fw-semibold">Select New PDF File</label>
                                <input class="form-control" type="file" id="projectPdf" name="project_file" accept=".pdf" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Revision</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Adviser & Panelist Feedback -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h2 class="h5 fw-bold mb-0">Adviser & Panelist Feedback</h2>
                        <small class="text-muted">Revision notes and evaluation comments</small>
                    </div>
                    <div class="card-body p-3" style="max-height: 380px; overflow-y: auto;">
                        <div class="vstack gap-3">

                            <?php if (empty($comments)): ?>
                                <div class="text-center text-muted small py-4">
                                    No feedback or comments posted yet.
                                </div>
                            <?php else: ?>
                                <?php foreach ($comments as $comm): 
                                    $formatted_date = !empty($comm['created_at']) ? date('M j, Y', strtotime($comm['created_at'])) : '';
                                    $badgeClass = 'bg-primary';
                                    $roleLabel = 'Adviser';
                                ?>
                                    <!-- Feedback Item -->
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge <?= $badgeClass ?>"><?= $roleLabel ?></span>
                                            <small class="text-muted"><?= htmlspecialchars($formatted_date) ?></small>
                                        </div>
                                        <p class="small text-dark mb-0">
                                            <?= nl2br(htmlspecialchars($comm['comment'])) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- EDIT TITLE MODAL -->
    <div class="modal fade" id="editTitleModal" tabindex="-1" aria-labelledby="editTitleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="dashboard.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTitleModalLabel">Update Project Title</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="studentProjectTitle" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="studentProjectTitle" name="title"
                                value="<?= htmlspecialchars($project['title']) ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_title" class="btn btn-primary">Save Title</button>
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