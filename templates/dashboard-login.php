<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Submissions - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>Form Submissions</h1>
            <p>Enter your Tenant ID to view submissions</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="dashboard.php">
                <div class="form-group">
                    <label for="tenant_id">Tenant ID</label>
                    <input
                        type="text"
                        id="tenant_id"
                        name="tenant_id"
                        placeholder="Enter your tenant ID"
                        required
                        autofocus
                    >
                </div>
                <button type="submit" class="btn btn-primary">View Submissions</button>
            </form>
        </div>
    </div>
</body>
</html>
