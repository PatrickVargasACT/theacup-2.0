<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <title>User Selection|THEACUP</title>
</head>

<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

    <div class="container" style="max-width: 900px;">
        <!-- Branding Header -->
        <div class="text-center mb-5">
            <h1 class="fw-bold text-primary display-5 mb-2">THEACUP</h1>
            <p class="text-muted">Thesis and Capstone Unified Platform</p>
            <h2 class="h4 fw-semibold text-dark mt-4">Select your role to continue</h2>
        </div>

        <!-- Role Selection Cards Grid -->
        <div class="row g-4 justify-content-center">

            <!-- Student Role Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-4 transition-hover">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-3 text-primary">
                                <!-- User / Student Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor"
                                    class="bi bi-mortarboard" viewBox="0 0 16 16">
                                    <path
                                        d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14.5 6.473v3.308c0 .585-.5 1.01-1.09 1.09a7.512 7.512 0 0 1-5.41 1.745 7.512 7.512 0 0 1-5.41-1.745c-.59-.08-1.09-.505-1.09-1.09V6.473l-.5-.225V11.5a.5.5 0 0 0 1 0V6.98l7 3.111 7-3.111V11.5a.5.5 0 0 0 1 0V6.473l-7.5-3.5z" />
                                    <path d="M8 10.095l-6.5-2.889V11.5a6.5 6.5 0 0 0 13 0V7.206L8 10.095z" />
                                </svg>
                            </div>
                            <h3 class="h5 fw-bold text-dark">Student</h3>
                            <p class="text-muted small mb-4">
                                Track your checklist progress, view grades, upload document revisions, and check adviser
                                feedback.
                            </p>
                        </div>
                        <a href="Student/" class="btn btn-primary w-150 py-2 fw-semibold">Login as Student</a>
                    </div>
                </div>
            </div>

            <!-- Adviser Role Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-4 transition-hover">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-3 text-success">
                                <!-- Adviser / Shield Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor"
                                    class="bi bi-person-badge" viewBox="0 0 16 16">
                                    <path
                                        d="M6.5 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zM11 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                                    <path
                                        d="M4.5 0A2.5 2.5 0 0 0 2 2.5V14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2.5A2.5 2.5 0 0 0 11.5 0h-7zM3 2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5v10.795c-.423-.275-.918-.435-1.442-.435H4.442c-.524 0-1.019.16-1.442.435V2.5z" />
                                </svg>
                            </div>
                            <h3 class="h5 fw-bold text-dark">Adviser</h3>
                            <p class="text-muted small mb-4">
                                Manage student projects, review documentation files, update milestone checklists, and
                                post announcements.
                            </p>
                        </div>
                        <a href="Adviser/" class="btn btn-success w-150 py-2 fw-semibold">Login as Adviser</a>
                    </div>
                </div>
            </div>

            <!-- Panelist Role Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-4 transition-hover">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-3 text-warning">
                                <!-- Panelist / People Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor"
                                    class="bi bi-people" viewBox="0 0 16 16">
                                    <path
                                        d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.02H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.237-.247A7.016 7.016 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.72.308-.293.668-.54 1.046-.72zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" />
                                </svg>
                            </div>
                            <h3 class="h5 fw-bold text-dark">Panelist</h3>
                            <p class="text-muted small mb-4">
                                Evaluate defense presentations, assign grades, review submissions, and provide project
                                feedback.
                            </p>
                        </div>
                        <a href="Panelist/" class="btn btn-warning text-white w-150 py-2 fw-semibold">Login as
                            Panelist</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>

</html>