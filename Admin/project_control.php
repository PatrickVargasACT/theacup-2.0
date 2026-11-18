<?php
session_start();
include __DIR__ . '/../database.php';

// Handle Form Submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title        = trim($_POST['title']);
        $isCapstone   = intval($_POST['isCapstone'] ?? 1);
        $section      = trim($_POST['section']);
        $leader_name  = trim($_POST['leader_name']);
        $groupmate1   = !empty(trim($_POST['groupmate1'])) ? trim($_POST['groupmate1']) : null;
        $groupmate2   = !empty(trim($_POST['groupmate2'])) ? trim($_POST['groupmate2']) : null;
        
        // Foreign keys (NULL if unselected)
        $adviser_id   = !empty($_POST['adviser_id']) ? intval($_POST['adviser_id']) : null;
        $panelist1_id = !empty($_POST['panelist1_id']) ? intval($_POST['panelist1_id']) : null;
        $panelist2_id = !empty($_POST['panelist2_id']) ? intval($_POST['panelist2_id']) : null;

        $project_desc = !empty(trim($_POST['project'])) ? trim($_POST['project']) : null;
        $email        = trim($_POST['email']);
        $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);

        if (!empty($title) && !empty($section) && !empty($leader_name) && !empty($email) && !empty($_POST['password'])) {
            $stmt = mysqli_prepare($conn, "INSERT INTO projects (title, isCapstone, section, leader_name, groupmate1, groupmate2, adviser_id, panelist1_id, panelist2_id, project, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sissssiiisss", $title, $isCapstone, $section, $leader_name, $groupmate1, $groupmate2, $adviser_id, $panelist1_id, $panelist2_id, $project_desc, $email, $password);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: project_control.php");
        exit();
    } 
    elseif ($action === 'edit') {
        $project_id   = intval($_POST['project_id']);
        $title        = trim($_POST['title']);
        $isCapstone   = intval($_POST['isCapstone'] ?? 1);
        $section      = trim($_POST['section']);
        $leader_name  = trim($_POST['leader_name']);
        $groupmate1   = !empty(trim($_POST['groupmate1'])) ? trim($_POST['groupmate1']) : null;
        $groupmate2   = !empty(trim($_POST['groupmate2'])) ? trim($_POST['groupmate2']) : null;
        
        // Foreign keys (NULL if unselected)
        $adviser_id   = !empty($_POST['adviser_id']) ? intval($_POST['adviser_id']) : null;
        $panelist1_id = !empty($_POST['panelist1_id']) ? intval($_POST['panelist1_id']) : null;
        $panelist2_id = !empty($_POST['panelist2_id']) ? intval($_POST['panelist2_id']) : null;

        $project_desc = !empty(trim($_POST['project'])) ? trim($_POST['project']) : null;
        $email        = trim($_POST['email']);
        $password     = $_POST['password'];
        $grade        = $_POST['grade'] !== '' ? floatval($_POST['grade']) : null;

        if ($project_id > 0 && !empty($title) && !empty($section) && !empty($leader_name) && !empty($email)) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE projects SET title = ?, isCapstone = ?, section = ?, leader_name = ?, groupmate1 = ?, groupmate2 = ?, adviser_id = ?, panelist1_id = ?, panelist2_id = ?, project = ?, email = ?, password = ?, grade = ? WHERE project_id = ?");
                mysqli_stmt_bind_param($stmt, "sissssiiisssdi", $title, $isCapstone, $section, $leader_name, $groupmate1, $groupmate2, $adviser_id, $panelist1_id, $panelist2_id, $project_desc, $email, $hashed_password, $grade, $project_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE projects SET title = ?, isCapstone = ?, section = ?, leader_name = ?, groupmate1 = ?, groupmate2 = ?, adviser_id = ?, panelist1_id = ?, panelist2_id = ?, project = ?, email = ?, grade = ? WHERE project_id = ?");
                mysqli_stmt_bind_param($stmt, "sissssiiisssd", $title, $isCapstone, $section, $leader_name, $groupmate1, $groupmate2, $adviser_id, $panelist1_id, $panelist2_id, $project_desc, $email, $grade, $project_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

            // Insert notification for the student project
            $notif_content = "Your project details and information have been updated by the administrator/adviser.";
            $notif_stmt = mysqli_prepare($conn, "INSERT INTO notification (project_id, content, isRead) VALUES (?, ?, 0)");
            mysqli_stmt_bind_param($notif_stmt, "is", $project_id, $notif_content);
            mysqli_stmt_execute($notif_stmt);
            mysqli_stmt_close($notif_stmt);

    } 
    elseif ($action === 'delete') {
        $project_id = intval($_POST['project_id']);

        if ($project_id > 0) {
            $stmt = mysqli_prepare($conn, "DELETE FROM projects WHERE project_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $project_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: project_control.php");
        exit();
    }
}

// Fetch lists for dropdowns
$advisers = [];
$adv_res = mysqli_query($conn, "SELECT adviser_id, name FROM advisers ORDER BY name ASC");
if ($adv_res) {
    while ($row = mysqli_fetch_assoc($adv_res)) {
        $advisers[] = $row;
    }
}

$panelists = [];
$pan_res = mysqli_query($conn, "SELECT panelist_id, name FROM panelist ORDER BY name ASC");
if ($pan_res) {
    while ($row = mysqli_fetch_assoc($pan_res)) {
        $panelists[] = $row;
    }
}

// Fetch all projects with Adviser and Panelist names using LEFT JOIN
$query = "SELECT p.*, 
                 a.name AS adviser_name, 
                 p1.name AS panelist1_name, 
                 p2.name AS panelist2_name
          FROM projects p
          LEFT JOIN advisers a ON p.adviser_id = a.adviser_id
          LEFT JOIN panelist p1 ON p.panelist1_id = p1.panelist_id
          LEFT JOIN panelist p2 ON p.panelist2_id = p2.panelist_id
          ORDER BY p.project_id ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Project Control | Theacup</title>
</head>

<body>

    <!-- Modal for Adding Project -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="project_control.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProjectModalLabel">Add Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="projectTitle" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="projectTitle" name="title"
                                placeholder="Enter project title" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="projectType" class="form-label">Project Type</label>
                                <select class="form-select" id="projectType" name="isCapstone" required>
                                    <option value="1" selected>Capstone</option>
                                    <option value="0">Thesis</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="projectSection" class="form-label">Section</label>
                                <input type="text" class="form-control" id="projectSection" name="section"
                                    placeholder="e.g., BSIT4W" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="leaderName" class="form-label">Leader Name</label>
                                <input type="text" class="form-control" id="leaderName" name="leader_name"
                                    placeholder="Group leader" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="groupmate1" class="form-label">Groupmate 1</label>
                                <input type="text" class="form-control" id="groupmate1" name="groupmate1"
                                    placeholder="Optional">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="groupmate2" class="form-label">Groupmate 2</label>
                                <input type="text" class="form-control" id="groupmate2" name="groupmate2"
                                    placeholder="Optional">
                            </div>
                        </div>

                        <!-- Adviser and Panelists Selection -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="adviserSelect" class="form-label">Assigned Adviser</label>
                                <select class="form-select" id="adviserSelect" name="adviser_id">
                                    <option value="">None</option>
                                    <?php foreach ($advisers as $adv): ?>
                                        <option value="<?= $adv['adviser_id'] ?>">
                                            <?= htmlspecialchars($adv['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="panelist1Select" class="form-label">Panelist 1</label>
                                <select class="form-select" id="panelist1Select" name="panelist1_id">
                                    <option value="">None</option>
                                    <?php foreach ($panelists as $pan): ?>
                                        <option value="<?= $pan['panelist_id'] ?>">
                                            <?= htmlspecialchars($pan['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="panelist2Select" class="form-label">Panelist 2</label>
                                <select class="form-select" id="panelist2Select" name="panelist2_id">
                                    <option value="">None</option>
                                    <?php foreach ($panelists as $pan): ?>
                                        <option value="<?= $pan['panelist_id'] ?>">
                                            <?= htmlspecialchars($pan['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="projectDescription" class="form-label">Project Description / Details</label>
                            <textarea class="form-control" id="projectDescription" name="project" rows="3"
                                placeholder="Enter project abstract or details"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="projectEmail" class="form-label">Account Email</label>
                                <input type="email" class="form-control" id="projectEmail" name="email"
                                    placeholder="leader@example.com" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="projectPassword" class="form-label">Password</label>
                                <input type="password" class="form-control" id="projectPassword" name="password"
                                    placeholder="Enter password" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="project_control.php" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="project_id" id="editProjectId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editProjectTitle" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="editProjectTitle" name="title" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editProjectType" class="form-label">Project Type</label>
                                <select class="form-select" id="editProjectType" name="isCapstone" required>
                                    <option value="1">Capstone</option>
                                    <option value="0">Thesis</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editProjectSection" class="form-label">Section</label>
                                <input type="text" class="form-control" id="editProjectSection" name="section" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="editLeaderName" class="form-label">Leader Name</label>
                                <input type="text" class="form-control" id="editLeaderName" name="leader_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editGroupmate1" class="form-label">Groupmate 1</label>
                                <input type="text" class="form-control" id="editGroupmate1" name="groupmate1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editGroupmate2" class="form-label">Groupmate 2</label>
                                <input type="text" class="form-control" id="editGroupmate2" name="groupmate2">
                            </div>
                        </div>

                        <!-- Adviser and Panelists Selection -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="editAdviserSelect" class="form-label">Assigned Adviser</label>
                                <select class="form-select" id="editAdviserSelect" name="adviser_id">
                                    <option value="">None</option>
                                    <?php foreach ($advisers as $adv): ?>
                                        <option value="<?= $adv['adviser_id'] ?>">
                                            <?= htmlspecialchars($adv['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editPanelist1Select" class="form-label">Panelist 1</label>
                                <select class="form-select" id="editPanelist1Select" name="panelist1_id">
                                    <option value="">None</option>
                                    <?php foreach ($panelists as $pan): ?>
                                        <option value="<?= $pan['panelist_id'] ?>">
                                            <?= htmlspecialchars($pan['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editPanelist2Select" class="form-label">Panelist 2</label>
                                <select class="form-select" id="editPanelist2Select" name="panelist2_id">
                                    <option value="">None</option>
                                    <?php foreach ($panelists as $pan): ?>
                                        <option value="<?= $pan['panelist_id'] ?>">
                                            <?= htmlspecialchars($pan['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editProjectDescription" class="form-label">Project Description / Details</label>
                            <textarea class="form-control" id="editProjectDescription" name="project" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editProjectEmail" class="form-label">Account Email</label>
                                <input type="email" class="form-control" id="editProjectEmail" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editProjectPassword" class="form-label">New Password <small
                                        class="text-muted">(Leave blank to keep current)</small></label>
                                <input type="password" class="form-control" id="editProjectPassword" name="password"
                                    placeholder="Enter new password">
                            </div>
                        </div>

                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3">
                                <label for="editProjectGrade" class="form-label">Grade</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control"
                                    id="editProjectGrade" name="grade">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="project_control.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="project_id" id="deleteProjectId">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteProjectModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-0">Are you sure you want to delete the project <strong id="deleteProjectTitleDisplay"></strong> led by <span id="deleteProjectLeaderDisplay"></span>? This action cannot be undone.</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Theacup</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="adviser_control.php">Advisers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="panelist_control.php">Panelists</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="project_control.php">Projects</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-end align-items-center mt-3">
            <button type="button" class="btn btn-primary mt-3 mb-3" data-bs-toggle="modal"
                data-bs-target="#addProjectModal">
                Add Project
            </button>
        </div>
        <div class="mt-3 border rounded-3 table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Project Details</th>
                        <th scope="col">Type / Section</th>
                        <th scope="col">Team & Committee</th>
                        <th scope="col">Email</th>
                        <th scope="col">Grade</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <th scope="row"><?php echo htmlspecialchars($row['project_id']); ?></th>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($row['project'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($row['section'])): ?>
                                        <span class="badge bg-dark-subtle text-dark border border-dark-subtle"><?php echo htmlspecialchars($row['section']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($row['isCapstone'] == 1): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1">Capstone</span>
                                    <?php else: ?>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle mt-1">Thesis</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><strong>Leader:</strong> <?php echo htmlspecialchars($row['leader_name']); ?></div>
                                    <?php 
                                        $members = [];
                                        if (!empty($row['groupmate1'])) $members[] = $row['groupmate1'];
                                        if (!empty($row['groupmate2'])) $members[] = $row['groupmate2'];
                                        if (!empty($members)):
                                    ?>
                                        <div class="small text-muted">Members: <?php echo htmlspecialchars(implode(', ', $members)); ?></div>
                                    <?php endif; ?>

                                    <!-- Committee Assignments Display -->
                                    <div class="mt-2 pt-2 border-top small text-secondary">
                                        <div><strong>Adviser:</strong> <?= htmlspecialchars($row['adviser_name'] ?? 'None') ?></div>
                                        <div><strong>Panelist 1:</strong> <?= htmlspecialchars($row['panelist1_name'] ?? 'None') ?></div>
                                        <div><strong>Panelist 2:</strong> <?= htmlspecialchars($row['panelist2_name'] ?? 'None') ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['grade'] !== null ? htmlspecialchars($row['grade']) : '<span class="text-muted">N/A</span>'; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-btn mb-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editProjectModal"
                                        data-project_id="<?php echo $row['project_id']; ?>"
                                        data-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>"
                                        data-iscapstone="<?php echo $row['isCapstone']; ?>"
                                        data-section="<?php echo htmlspecialchars($row['section'], ENT_QUOTES); ?>"
                                        data-leader_name="<?php echo htmlspecialchars($row['leader_name'], ENT_QUOTES); ?>"
                                        data-groupmate1="<?php echo htmlspecialchars($row['groupmate1'] ?? '', ENT_QUOTES); ?>"
                                        data-groupmate2="<?php echo htmlspecialchars($row['groupmate2'] ?? '', ENT_QUOTES); ?>"
                                        data-adviser_id="<?php echo $row['adviser_id'] ?? ''; ?>"
                                        data-panelist1_id="<?php echo $row['panelist1_id'] ?? ''; ?>"
                                        data-panelist2_id="<?php echo $row['panelist2_id'] ?? ''; ?>"
                                        data-project="<?php echo htmlspecialchars($row['project'] ?? '', ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>"
                                        data-grade="<?php echo $row['grade'] ?? ''; ?>">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteProjectModal"
                                        data-id="<?php echo $row['project_id']; ?>"
                                        data-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>"
                                        data-leader="<?php echo htmlspecialchars($row['leader_name'], ENT_QUOTES); ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No projects found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript to populate Edit and Delete Modals dynamically -->
    <script>
        const editModal = document.getElementById('editProjectModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editProjectId').value = button.getAttribute('data-project_id');
            document.getElementById('editProjectTitle').value = button.getAttribute('data-title');
            document.getElementById('editProjectType').value = button.getAttribute('data-iscapstone') || '1';
            document.getElementById('editProjectSection').value = button.getAttribute('data-section');
            document.getElementById('editLeaderName').value = button.getAttribute('data-leader_name');
            document.getElementById('editGroupmate1').value = button.getAttribute('data-groupmate1');
            document.getElementById('editGroupmate2').value = button.getAttribute('data-groupmate2');
            
            // Populate Dropdowns with assigned IDs (defaults to "" if empty / "None")
            document.getElementById('editAdviserSelect').value = button.getAttribute('data-adviser_id') || '';
            document.getElementById('editPanelist1Select').value = button.getAttribute('data-panelist1_id') || '';
            document.getElementById('editPanelist2Select').value = button.getAttribute('data-panelist2_id') || '';

            document.getElementById('editProjectDescription').value = button.getAttribute('data-project');
            document.getElementById('editProjectEmail').value = button.getAttribute('data-email');
            document.getElementById('editProjectPassword').value = ''; 
            document.getElementById('editProjectGrade').value = button.getAttribute('data-grade');
        });

        const deleteModal = document.getElementById('deleteProjectModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('deleteProjectId').value = button.getAttribute('data-id');
            document.getElementById('deleteProjectTitleDisplay').textContent = button.getAttribute('data-title');
            document.getElementById('deleteProjectLeaderDisplay').textContent = button.getAttribute('data-leader');
        });
    </script>
</body>

</html>