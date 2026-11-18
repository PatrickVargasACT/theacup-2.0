<?php
session_start();
require '../database.php'; 
// Note: Ensure your '../database.php' establishes a MySQLi connection 
// and stores the connection variable in $conn (e.g., $conn = mysqli_connect(...));

// Redirect if already logged in
if (isset($_SESSION['project_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Prepare statement using MySQLi
        $stmt = mysqli_prepare($conn, "SELECT project_id, title, leader_name, section, password FROM projects WHERE email = ? LIMIT 1");
        
        if ($stmt) {
            // Bind the email parameter ('s' stands for string)
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            
            // Get the result set
            $result = mysqli_stmt_get_result($stmt);
            $project = mysqli_fetch_assoc($result);

            // Close the statement
            mysqli_stmt_close($stmt);

            // Verify password (assumes passwords are stored using password_hash())
            if ($project && password_verify($password, $project['password'])) {
                // Prevent session fixation
                session_regenerate_id(true);

                // Store project details in session
                $_SESSION['project_id']  = $project['project_id'];
                $_SESSION['title']       = $project['title'];
                $_SESSION['leader_name'] = $project['leader_name'];
                $_SESSION['section']     = $project['section'];

                // Redirect to student/project dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid email address or password.";
            }
        } else {
            $error = "Database error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | THEACUP</title>
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body class="bg-light d-flex align-items-center py-4" style="height: 100vh;">

    <main class="form-signin w-100 m-auto" style="max-width: 400px;">
        <div class="card shadow-sm border-0 rounded-4 p-4">
            <form action="login.php" method="POST">
                <div class="text-center mb-4">
                    <h1 class="h3 mb-3 fw-normal">Student Login</h1>
                    <p class="text-muted">Please sign in to your account</p>
                </div>

                <!-- Dynamic Error Alert -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 text-center" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Email Field -->
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="floatingEmail" name="email" 
                           placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>" required>
                    <label for="floatingEmail">Email address</label>
                </div>

                <!-- Password Field -->
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="floatingPassword" name="password" 
                           placeholder="Password" required>
                    <label for="floatingPassword">Password</label>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="form-check text-start mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" value="remember-me" id="flexCheckDefault">
                    <label class="form-check-label" for="flexCheckDefault">
                        Remember me
                    </label>
                </div>

                <!-- Submit Button -->
                <button class="btn btn-primary w-100 py-2 rounded-3" type="submit">Sign in</button>
            </form>
        </div>
    </main>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>