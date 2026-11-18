<?php
session_start();
include __DIR__ . '/../database.php';

// Handle Form Submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name     = trim($_POST['name']);
        $email    = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        if (!empty($name) && !empty($email) && !empty($_POST['password'])) {
            $stmt = mysqli_prepare($conn, "INSERT INTO panelist (name, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $password);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: panelist_control.php");
        exit();
    } 
    elseif ($action === 'edit') {
        $panelist_id = intval($_POST['panelist_id']);
        $name        = trim($_POST['name']);
        $email       = trim($_POST['email']);
        $password    = $_POST['password'];

        if ($panelist_id > 0 && !empty($name) && !empty($email)) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE panelist SET name = ?, email = ?, password = ? WHERE panelist_id = ?");
                mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $hashed_password, $panelist_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE panelist SET name = ?, email = ? WHERE panelist_id = ?");
                mysqli_stmt_bind_param($stmt, "ssi", $name, $email, $panelist_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: panelist_control.php");
        exit();
    } 
    elseif ($action === 'delete') {
        $panelist_id = intval($_POST['panelist_id']);

        if ($panelist_id > 0) {
            $stmt = mysqli_prepare($conn, "DELETE FROM panelist WHERE panelist_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $panelist_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: panelist_control.php");
        exit();
    }
}

// Fetch all panelists from the database
$query = "SELECT * FROM panelist ORDER BY panelist_id ASC";
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
    <title>Panelist Control | Theacup</title>
</head>

<body>

    <!-- Modal for Adding Panelist -->
    <div class="modal fade" id="addPanelistModal" tabindex="-1" aria-labelledby="addPanelistModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="panelist_control.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPanelistModalLabel">Add Panelist</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="panelistName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="panelistName" name="name"
                                placeholder="Enter full name" required>
                        </div>

                        <div class="mb-3">
                            <label for="panelistEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="panelistEmail" name="email"
                                placeholder="name@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label for="panelistPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="panelistPassword" name="password"
                                placeholder="Enter password" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Panelist</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editPanelistModal" tabindex="-1" aria-labelledby="editPanelistModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="panelist_control.php" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="panelist_id" id="editPanelistId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPanelistModalLabel">Edit Panelist</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editPanelistName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editPanelistName" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="editPanelistEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="editPanelistEmail" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="editPanelistPassword" class="form-label">New Password <small
                                    class="text-muted">(Leave blank to keep current)</small></label>
                            <input type="password" class="form-control" id="editPanelistPassword" name="password"
                                placeholder="Enter new password">
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
    <div class="modal fade" id="deletePanelistModal" tabindex="-1" aria-labelledby="deletePanelistModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="panelist_control.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="panelist_id" id="deletePanelistId">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deletePanelistModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-0">Are you sure you want to delete the panelist <strong id="deletePanelistNameDisplay"></strong> (<span
                                class="text-muted" id="deletePanelistEmailDisplay"></span>)? This action cannot be undone.</p>
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
                        <a class="nav-link active" aria-current="page" href="panelist_control.php">Panelists</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="project_control.php">Projects</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-end align-items-center mt-3">
            <button type="button" class="btn btn-primary mt-3 mb-3" data-bs-toggle="modal"
                data-bs-target="#addPanelistModal">
                Add Panelist
            </button>
        </div>
        <div class="mt-3 border rounded-3 table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Email</th>
                        <th scope="col">Name</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <th scope="row"><?php echo htmlspecialchars($row['panelist_id']); ?></th>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editPanelistModal"
                                        data-id="<?php echo $row['panelist_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deletePanelistModal"
                                        data-id="<?php echo $row['panelist_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No panelists found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript to populate Edit and Delete Modals dynamically -->
    <script>
        const editModal = document.getElementById('editPanelistModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editPanelistId').value = button.getAttribute('data-id');
            document.getElementById('editPanelistName').value = button.getAttribute('data-name');
            document.getElementById('editPanelistEmail').value = button.getAttribute('data-email');
            document.getElementById('editPanelistPassword').value = ''; 
        });

        const deleteModal = document.getElementById('deletePanelistModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('deletePanelistId').value = button.getAttribute('data-id');
            document.getElementById('deletePanelistNameDisplay').textContent = button.getAttribute('data-name');
            document.getElementById('deletePanelistEmailDisplay').textContent = button.getAttribute('data-email');
        });
    </script>
</body>

</html>