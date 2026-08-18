<?php
// config/mail_config.php

return [
    'host' => 'smtp.gmail.com', // Serveur SMTP (ex: smtp.gmail.com)
    'username' => 'votre-email@gmail.com', // Votre adresse email
    'password' => 'votre-mot-de-passe-d-application', // Votre mot de passe d'application
    'port' => 587, // Port SMTP (587 pour TLS, 465 pour SSL)
    'encryption' => 'tls', // 'tls' ou 'ssl'
    'from_email' => 'noreply@admissio.ma',
    'from_name' => 'Admissio Platform'
];
