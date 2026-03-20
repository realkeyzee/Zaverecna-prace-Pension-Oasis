<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject_user = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));

    if (empty($name) || empty($email) || empty($message)) {
        header("Location: kontakt.html?status=error");
        exit();
    }

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        // ========= NASTAVENÍ SMTP (ANONYMIZOVÁNO) =========
        $mail->Host       = 'smtp.vasedomena.cz';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@vasedomena.cz'; 
        $mail->Password   = 'smtp_password_here'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('info@vasedomena.cz', 'Webový formulář Oasis');
        $mail->addAddress('info@vasedomena.cz'); 
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Nový dotaz z webu: " . $subject_user;
        
        $email_body = "
        <html>
        <head>
            <style>body { font-family: Arial, sans-serif; }</style>
        </head>
        <body>
            <h3>Nový dotaz z kontaktního formuláře</h3>
            <p><strong>Jméno:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Předmět:</strong> $subject_user</p>
            <hr>
            <p><strong>Zpráva:</strong></p>
            <p style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #3A5A40;'>
                " . nl2br($message) . "
            </p>
        </body>
        </html>";

        $mail->Body = $email_body;
        $mail->AltBody = "Jméno: $name\nEmail: $email\nZpráva:\n$message";

        $mail->send();
        
        header("Location: kontakt.html?status=success");
        exit();

    } catch (Exception $e) {
        error_log("Chyba odeslání kontaktu: {$mail->ErrorInfo}");
        header("Location: kontakt.html?status=error");
        exit();
    }
} else {
    header("Location: kontakt.html");
    exit();
}
?>