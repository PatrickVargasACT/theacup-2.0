<?php
session_start();

if (
    isset($_SESSION['user_id'])
) {
    // Session is set -> redirect to dashboard.php
    header("Location: dashboard.php");
    exit();
} else {
    // Session is not set -> redirect to login.php
    header("Location: login.php");
    exit();
}
?>