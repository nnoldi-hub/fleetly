<?php

class LandingController extends Controller
{
    public function index()
    {
        $this->view('public/landing', [
            'title' => 'Fleet Management - Sistem Profesional de Gestiune Flote Auto',
            'meta_description' => 'Solutie completa pentru managementul flotelor de vehicule. Multi-tenant, rapoarte avansate, notificari automate. Created by conectica-it.ro'
        ]);
    }

    public function features()
    {
        $this->view('public/features', [
            'title' => 'Caracteristici - Fleet Management'
        ]);
    }

    public function pricing()
    {
        $this->view('public/pricing', [
            'title' => 'Planuri si Preturi - Fleet Management'
        ]);
    }

    public function contact()
    {
        $this->view('public/contact', [
            'title' => 'Contact - Fleet Management'
        ]);
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

        // Trimite email catre admin
        $mailer = new Mailer();
        $emailContent = "
            <h2>Mesaj nou de pe site-ul Fleet Management</h2>
            <p><strong>Nume:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Telefon:</strong> {$phone}</p>
            <p><strong>Companie:</strong> {$company}</p>
            <p><strong>Mesaj:</strong></p>
            <p>{$message}</p>
        ";

        $sent = $mailer->send(
            'support@conectica-it.ro', // sau email-ul tau
            'Mesaj nou de contact - Fleet Management',
            $emailContent
        );

        if ($sent) {
            $_SESSION['success'] = 'Multumim! Mesajul dvs. a fost trimis. Va vom contacta in cel mai scurt timp.';
        } else {
            $_SESSION['error'] = 'A aparut o eroare. Va rugam incercati din nou sau contactati-ne telefonic.';
        }

        header('Location: ' . ROUTE_BASE . 'contact');
        exit;
    }
}
