<?php

class LandingController extends Controller
{
    public function index()
    {
        // Obtine statistici reale din baza de date
        $stats = PublicStats::getInstance()->getGlobalStats();
        
        // Landing page nu are header/footer standard, afisam direct view-ul
        $data = [
            'title' => 'Fleet Management - Sistem Profesional de Gestiune Flote Auto',
            'meta_description' => 'Solutie completa pentru managementul flotelor de vehicule. Multi-tenant, rapoarte avansate, notificari automate. Created by conectica-it.ro',
            'stats' => $stats
        ];
        
        extract($data);
        $viewFile = "modules/public/views/landing.php";
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "View not found: $viewFile";
        }
    }

    public function features()
    {
        $data = ['title' => 'Caracteristici - Fleet Management'];
        extract($data);
        include 'modules/public/views/features.php';
    }

    public function pricing()
    {
        $data = ['title' => 'Planuri si Preturi - Fleet Management'];
        extract($data);
        include 'modules/public/views/pricing.php';
    }

    public function contact()
    {
        $data = ['title' => 'Contact - Fleet Management'];
        extract($data);
        $viewFile = "modules/public/views/contact.php";
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "View not found: $viewFile";
        }
    }

    public function submitContact()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_BASE . 'contact');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $company = $_POST['company'] ?? '';
        $message = $_POST['message'] ?? '';
        $interest = $_POST['interest'] ?? '';

        // Validare
        if (empty($name) || empty($email) || empty($message)) {
            $_SESSION['error'] = 'Va rugam completati toate campurile obligatorii.';
            header('Location: ' . ROUTE_BASE . 'contact');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email invalid.';
            header('Location: ' . ROUTE_BASE . 'contact');
            exit;
        }

        // Trimite email catre admin (daca mail este configurat)
        try {
            require_once 'core/Mailer.php';
            $mailer = new Mailer();
            $emailContent = "
                <h2>Mesaj nou de pe site-ul Fleet Management</h2>
                <p><strong>Nume:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Telefon:</strong> {$phone}</p>
                <p><strong>Companie:</strong> {$company}</p>
                <p><strong>Interes:</strong> {$interest}</p>
                <p><strong>Mesaj:</strong></p>
                <p>{$message}</p>
            ";

            $sent = $mailer->send(
                'office@fleetly.ro',
                'Mesaj nou de contact - Fleet Management',
                $emailContent
            );

            if ($sent) {
                $_SESSION['success'] = 'Multumim! Mesajul dvs. a fost trimis. Va vom contacta in cel mai scurt timp.';
            } else {
                $_SESSION['error'] = 'A aparut o eroare la trimiterea mesajului. Va rugam incercati din nou sau contactati-ne telefonic.';
            }
        } catch (Exception $e) {
            // Daca mailer nu e configurat, salvam in log
            error_log("Contact form submission: $name ($email) - $message");
            $_SESSION['success'] = 'Multumim! Mesajul dvs. a fost inregistrat. Va vom contacta in cel mai scurt timp.';
        }

        header('Location: ' . ROUTE_BASE . 'contact');
        exit;
    }
}
