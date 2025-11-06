<?php
// config/mail.example.php
// Copiaza acest fisier in mail.php si configureaza SMTP sau foloseste mail()/log.

return [
    'enabled' => false,
    'driver' => 'smtp',
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'no-reply@example.com',
        'password' => 'changeme',
        'encryption' => 'tls',
        'timeout' => 10,
    ],
    'from' => [
        'email' => 'no-reply@example.com',
        'name' => 'Fleet Management'
    ],
];
