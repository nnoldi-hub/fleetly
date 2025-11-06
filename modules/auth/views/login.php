<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare - Fleet Management</title>
    <link href="<?= BASE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .login-header i {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .login-body {
            padding: 40px 30px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-truck"></i>
                <h2>Fleet Management</h2>
            </div>
            <div class="login-body">
                <?php if (isset($_SESSION["error"])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION["error"]) ?></div>
                    <?php unset($_SESSION["error"]); ?>
                <?php endif; ?>
                <form action="<?= ROUTE_BASE ?>login" method="POST">
                    <div class="mb-3">
                        <input type="text" class="form-control" name="username" required placeholder="Username / Email">
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" name="password" required placeholder="Parolă">
                    </div>
                    <div class="mb-3">
                        <input type="checkbox" name="remember_me"> Ține-mă minte
                    </div>
                    <button type="submit" class="btn btn-login text-white">Autentificare</button>
                </form>
                <div class="text-center mt-4">
                    <small class="text-muted">
                        Created by <a href="https://conectica-it.ro" target="_blank" class="text-decoration-none">conectica-it.ro</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>