<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Fleet Management' ?></title>
    <meta name="description" content="<?= $meta_description ?? '' ?>">
    <meta name="keywords" content="fleet management, gestiune flota, vehicule, soferi, rapoarte, mentenanta">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="Fleet Management - Sistem Profesional de Gestiune Flote Auto">
    <meta property="og:description" content="Solutie completa pentru managementul flotelor de vehicule">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= BASE_URL ?>">
    
    <link href="<?= BASE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --dark-color: #1a202c;
            --light-color: #f7fafc;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            overflow-x: hidden;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,165.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
        }
        
        .btn-hero-primary {
            background: white;
            color: var(--primary-color);
            border: none;
        }
        
        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            color: var(--secondary-color);
        }
        
        .btn-hero-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-hero-outline:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: var(--light-color);
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2rem;
            color: white;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .feature-description {
            color: #718096;
            line-height: 1.7;
        }
        
        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 60px 0;
        }
        
        .stat-box {
            text-align: center;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            display: block;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Pricing Section */
        .pricing-section {
            padding: 80px 0;
        }
        
        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .pricing-card.featured {
            border: 3px solid var(--primary-color);
            transform: scale(1.05);
        }
        
        .pricing-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.3);
        }
        
        .pricing-badge {
            position: absolute;
            top: 20px;
            right: -35px;
            background: var(--accent-color);
            color: white;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-size: 0.8rem;
            font-weight: 700;
        }
        
        .pricing-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .pricing-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-color);
            margin: 20px 0;
        }
        
        .pricing-price small {
            font-size: 1.2rem;
            font-weight: 400;
            color: #718096;
        }
        
        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }
        
        .pricing-features li {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .pricing-features li:last-child {
            border-bottom: none;
        }
        
        .pricing-features i {
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--dark-color) 0%, #2d3748 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 40px;
            padding-top: 30px;
            text-align: center;
            color: rgba(255,255,255,0.6);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .pricing-card.featured {
                transform: scale(1);
            }
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
                        <a class="nav-link" href="#features">Caracteristici</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= ROUTE_BASE ?>contact">Contact</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content animate-fade-in-up">
                    <h1 class="hero-title">Gestioneaza Flota Ta cu Usurinta</h1>
                    <p class="hero-subtitle">
                        Solutie completa pentru managementul flotelor de vehicule. 
                        Rapoarte avansate, notificari automate, multi-tenant.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="<?= ROUTE_BASE ?>contact" class="btn btn-hero btn-hero-primary">
                            <i class="fas fa-rocket"></i> Incepe Gratuit
                        </a>
                        <a href="#features" class="btn btn-hero btn-hero-outline">
                            <i class="fas fa-info-circle"></i> Afla Mai Mult
                        </a>
                    </div>
                    <div class="mt-4">
                        <small class="opacity-75">
                            <i class="fas fa-check-circle"></i> 100% Gratuit
                            <i class="fas fa-check-circle ms-3"></i> Setup in 5 minute
                            <i class="fas fa-check-circle ms-3"></i> Suport dedicat
                        </small>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <!-- Incearca sa incarce PNG real, apoi SVG placeholder, apoi fallback inline -->
                    <img src="<?= BASE_URL ?>assets/images/marketing/dashboard-preview.png" 
                         alt="Fleet Management Dashboard" 
                         class="img-fluid rounded shadow-lg"
                         style="max-width: 100%; height: auto;"
                         onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/marketing/dashboard-preview.svg';">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6 mb-4 mb-md-0">
                    <div class="stat-box">
                        <span class="stat-number"><?= PublicStats::formatNumber($stats['companies']) ?></span>
                        <span class="stat-label">Companii Active</span>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 mb-md-0">
                    <div class="stat-box">
                        <span class="stat-number"><?= PublicStats::formatNumber($stats['vehicles']) ?></span>
                        <span class="stat-label">Vehicule Gestionate</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <span class="stat-number"><?= $stats['uptime'] ?>%</span>
                        <span class="stat-label">Uptime Garantat</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <span class="stat-number"><?= $stats['support'] ?></span>
                        <span class="stat-label">Suport Tehnic</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Caracteristici Principale</h2>
                <p class="lead text-muted">Tot ce ai nevoie pentru gestionarea eficienta a flotei</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <h3 class="feature-title">Management Vehicule</h3>
                        <p class="feature-description">
                            Evidenta completa: marci, modele, an fabricatie, kilometraj, status operare. 
                            Export CSV/PDF fara diacritice.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Gestiune Soferi</h3>
                        <p class="feature-description">
                            Licente conducere, expirari, istoric alocare vehicule, 
                            telefon SMS pentru notificari.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h3 class="feature-title">Documente & Asigurari</h3>
                        <p class="feature-description">
                            ITP, RCA, autorizatii. Notificari automate expirare 
                            (30/15/7/1 zi inainte). Upload securizat.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3 class="feature-title">Mentenanta</h3>
                        <p class="feature-description">
                            Programare service periodic, istoric reparatii, costuri, 
                            alerte km scadente. Rapoarte centralizate.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-gas-pump"></i>
                        </div>
                        <h3 class="feature-title">Combustibil</h3>
                        <p class="feature-description">
                            Inregistrare alimentari, statistici consum mediu/100km, 
                            rapoarte costuri lunare, grafice per vehicul.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Rapoarte Avansate</h3>
                        <p class="feature-description">
                            Dashboard statistici live, grafice Chart.js interactive, 
                            export CSV/PDF. Fleet Overview, costuri, mentenanta.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section - COMENTAT (momentan gratuit)
    <section id="pricing" class="pricing-section bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Planuri & Preturi</h2>
                <p class="lead text-muted">Alege planul potrivit pentru flota ta</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="pricing-card">
                        <h3 class="pricing-name">Starter</h3>
                        <p class="text-muted">Perfect pentru flote mici</p>
                        <div class="pricing-price">
                            49€
                            <small>/luna</small>
                        </div>
                        <ul class="pricing-features">
                            <li><i class="fas fa-check"></i> Pana la 5 utilizatori</li>
                            <li><i class="fas fa-check"></i> Pana la 20 vehicule</li>
                            <li><i class="fas fa-check"></i> Rapoarte basic</li>
                            <li><i class="fas fa-check"></i> Notificari email</li>
                            <li><i class="fas fa-check"></i> Suport email</li>
                            <li><i class="fas fa-times text-muted"></i> Export PDF/CSV</li>
                            <li><i class="fas fa-times text-muted"></i> API acces</li>
                        </ul>
                        <a href="<?= ROUTE_BASE ?>contact?plan=starter" class="btn btn-outline-primary w-100 mt-3">
                            Selecteaza Starter
                        </a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="pricing-card featured">
                        <div class="pricing-badge">POPULAR</div>
                        <h3 class="pricing-name">Professional</h3>
                        <p class="text-muted">Pentru companii medii</p>
                        <div class="pricing-price">
                            149€
                            <small>/luna</small>
                        </div>
                        <ul class="pricing-features">
                            <li><i class="fas fa-check"></i> Pana la 15 utilizatori</li>
                            <li><i class="fas fa-check"></i> Pana la 100 vehicule</li>
                            <li><i class="fas fa-check"></i> Rapoarte avansate</li>
                            <li><i class="fas fa-check"></i> Notificari email + SMS</li>
                            <li><i class="fas fa-check"></i> Suport prioritar</li>
                            <li><i class="fas fa-check"></i> Export PDF/CSV</li>
                            <li><i class="fas fa-check"></i> API acces</li>
                        </ul>
                        <a href="<?= ROUTE_BASE ?>contact?plan=professional" class="btn btn-primary w-100 mt-3">
                            Selecteaza Professional
                        </a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="pricing-card">
                        <h3 class="pricing-name">Enterprise</h3>
                        <p class="text-muted">Flote mari, nevoi custom</p>
                        <div class="pricing-price">
                            Custom
                        </div>
                        <ul class="pricing-features">
                            <li><i class="fas fa-check"></i> Utilizatori nelimitati</li>
                            <li><i class="fas fa-check"></i> Vehicule nelimitate</li>
                            <li><i class="fas fa-check"></i> Rapoarte premium + BI</li>
                            <li><i class="fas fa-check"></i> Toate canalele notificari</li>
                            <li><i class="fas fa-check"></i> Suport dedicat 24/7</li>
                            <li><i class="fas fa-check"></i> White-label disponibil</li>
                            <li><i class="fas fa-check"></i> Integrari custom</li>
                        </ul>
                        <a href="<?= ROUTE_BASE ?>contact?plan=enterprise" class="btn btn-outline-primary w-100 mt-3">
                            Contacteaza-ne
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    -->

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Gata sa revolutionezi managementul flotei?</h2>
            <p class="lead mb-4">Incepe gratuit astazi! Solutie 100% gratuita pentru managementul flotei tale.</p>
            <a href="<?= ROUTE_BASE ?>contact" class="btn btn-hero btn-hero-primary btn-lg">
                <i class="fas fa-rocket"></i> Incepe Gratuit Acum
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h3 class="footer-title">
                        <i class="fas fa-truck"></i> Fleet Management
                    </h3>
                    <p class="text-white-50">
                        Solutie completa pentru managementul flotelor de vehicule. 
                        Multi-tenant, securizat, scalabil.
                    </p>
                    <div class="mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-2x"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin fa-2x"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube fa-2x"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h4 class="footer-title">Produs</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Caracteristici</a></li>
                        <li><a href="<?= ROUTE_BASE ?>contact">Contact</a></li>
                        <li><a href="#">Documentatie</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h4 class="footer-title">Companie</h4>
                    <ul class="footer-links">
                        <li><a href="#">Despre noi</a></li>
                        <li><a href="<?= ROUTE_BASE ?>contact">Contact</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Cariere</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h4 class="footer-title">Contact</h4>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope me-2"></i> office@fleetly.ro</li>
                        <li><i class="fas fa-phone me-2"></i> 0740 173 581</li>
                        <li><i class="fas fa-user me-2"></i> Noldi Nyikora</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="mb-0">
                    &copy; <?= date('Y') ?> Fleet Management. Toate drepturile rezervate. 
                    Created by <a href="https://conectica-it.ro" target="_blank" class="text-white">conectica-it.ro</a>
                </p>
            </div>
        </div>
    </footer>

    <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll pentru link-uri anchor
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
