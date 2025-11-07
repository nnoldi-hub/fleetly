<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Fleet Management</title>
    <link href="<?= BASE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding-top: 80px;
        }
        
        .contact-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .contact-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .contact-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .contact-body {
            padding: 40px;
        }
        
        .info-box {
            background: #f7fafc;
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .info-box i {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-right: 15px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 40px;
            color: white;
            font-weight: 600;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); position: fixed; top: 0; width: 100%; z-index: 1000;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>">
                <i class="fas fa-truck"></i> Fleet Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>#features">Caracteristici</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>#pricing">Preturi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= ROUTE_BASE ?>contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary px-4 ms-2" href="<?= ROUTE_BASE ?>login">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="contact-container">
        <div class="contact-card">
            <div class="contact-header">
                <h1><i class="fas fa-envelope"></i> Contacteaza-ne</h1>
                <p class="mb-0">Suntem aici sa te ajutam! Completeaza formularul si iti vom raspunde in cel mai scurt timp.</p>
            </div>
            
            <div class="contact-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="info-box">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email</strong>
                                <p class="mb-0 text-muted">office@fleetly.ro</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="info-box">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Telefon</strong>
                                <p class="mb-0 text-muted">0740 173 581</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="info-box">
                            <i class="fas fa-user"></i>
                            <div>
                                <strong>Contact</strong>
                                <p class="mb-0 text-muted">Noldi Nyikora</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="<?= ROUTE_BASE ?>contact/submit" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nume complet *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Companie</label>
                            <input type="text" class="form-control" name="company">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sunt interesat de:</label>
                        <select class="form-select" name="interest">
                            <option value="">Selecteaza...</option>
                            <option value="demo">Demonstratie sistem</option>
                            <option value="info">Informatii generale</option>
                            <option value="support">Suport tehnic</option>
                            <option value="custom">Solutie personalizata</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mesaj *</label>
                        <textarea class="form-control" name="message" rows="5" required 
                                  placeholder="Descrie-ne nevoile tale..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-submit btn-lg">
                            <i class="fas fa-paper-plane"></i> Trimite Mesaj
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">
                        Created by <a href="https://conectica-it.ro" target="_blank" class="text-decoration-none">conectica-it.ro</a>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
