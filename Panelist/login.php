<?php
session_start();
include __DIR__ . '/../database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = mysqli_prepare($conn, "SELECT panelist_id, name, email, password FROM panelist WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Verify the password against the stored hash
            if (password_verify($password, $row['password'])) {
                // Set session variables for panelist
                $_SESSION['panelist_id'] = $row['panelist_id'];
                $_SESSION['panelist_name'] = $row['name'];
                $_SESSION['panelist_email'] = $row['email'];

                // Redirect to projects.php upon successful login
                header("Location: projects.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panelist Login | THEACUP</title>
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body class="bg-light d-flex align-items-center py-4" style="height: 100vh;">

    <main class="form-signin w-100 m-auto" style="max-width: 400px;">
        <div class="card shadow-sm border-0 rounded-4 p-4">
            <form action="login.php" method="POST">
                <div class="text-center mb-4">
                    <h1 class="h3 mb-3 fw-normal">Panelist Login</h1>
                    <p class="text-muted">Please sign in to your account</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Email Field -->
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="floatingEmail" name="email" placeholder="name@example.com" required>
                    <label for="floatingEmail">Email address</label>
                </div>

                <!-- Password Field -->
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                    <label for="floatingPassword">Password</label>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="form-check text-start mb-3">
                    <input class="form-check-input" type="checkbox" value="remember-me" id="flexCheckDefault">
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