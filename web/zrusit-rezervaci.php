<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

// ========= NASTAVENÍ DB (ANONYMIZOVÁNO) =========
$db_host = 'localhost';
$db_name = 'database_name';
$db_user = 'database_user'; 
$db_pass = 'database_password';    
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Chyba připojení k databázi.");
}

function sendCancelEmail($to, $subject, $bodyHtml) {
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0; 
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

        $mail->setFrom('info@vasedomena.cz', 'Penzion Oasis');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

$order_number = $_GET['order'] ?? ($_POST['order'] ?? '');
$status_message = "";
$can_cancel = false;
$reservation = null;

if (!empty($order_number)) {

    $stmt = $pdo->prepare("
        SELECT r.id, r.check_in, r.check_out, r.status, r.room_id, r.order_number,
               g.first_name, g.last_name, g.email, g.phone
        FROM reservations r
        JOIN guests g ON r.guest_id = g.id
        WHERE r.order_number = ?
    ");
    $stmt->execute([$order_number]);
    $reservation = $stmt->fetch();

    if ($reservation) {
        if ($reservation['status'] === 'cancelled') {
            $status_message = "<span style='color: black;'>Tato rezervace již byla zrušena dříve.</span>";
        } else {

            $check_in_datetime = new DateTime($reservation['check_in'] . ' 14:00:00');
            $now = new DateTime();
            
            $interval = $now->diff($check_in_datetime);
            $hours_remaining = ($interval->days * 24) + $interval->h;
            
            if ($interval->invert === 1 || $hours_remaining < 48) {
                $status_message = "<span style='color: #69040e;'>Rezervaci již nelze zrušit online, protože do příjezdu zbývá méně než 48 hodin. Prosím, kontaktujte nás telefonicky.</span>";
            } else {
                $can_cancel = true;
                
                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_cancel'])) {
           
                    $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE order_number = ?")->execute([$order_number]);
                    
                    $can_cancel = false;
                    $status_message = "<span style='color: black;'>Vaše rezervace byla úspěšně zrušena. Termín byl uvolněn. Potvrzení jsme zaslali na váš e-mail.</span>";

                    $guest_email = $reservation['email'];
                    $guest_name = $reservation['first_name'] . ' ' . $reservation['last_name'];
                    $check_in_str = date('d.m.Y', strtotime($reservation['check_in']));
                    $check_out_str = date('d.m.Y', strtotime($reservation['check_out']));
                    $order_num = $reservation['order_number'];
                    
                    $web_url = "https://www.vasedomena.cz"; 
                    $logo_url = $web_url . "/miniature.PNG";

                    $client_subject = "Zrušení rezervace";
                    
                    $client_msg = "
                    <html>
                    <head>
                        <style>
                            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                            .email-container { max-width: 1000px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eaeaea; }
                            .header { background-color: #3A5A40; padding: 25px; text-align: center; color: white; } /* Změněno na červenou pro zrušení */
                            .header h1 { margin: 0; font-size: 26px; font-weight: 600; letter-spacing: 0.5px; }
                            .content { padding: 40px; color: #333333; line-height: 1.6; }
                            .booking-details { width: 100%; border-collapse: collapse; margin: 25px 0; font-size: 15px; }
                            .booking-details td { padding: 15px; border-bottom: 1px solid #eee; }
                            .booking-details td:first-child { font-weight: bold; color: #555; width: 40%; white-space: nowrap; }
                            .booking-details td:last-child { color: #000; font-weight: 600; text-align: right; }
                            .footer { background-color: #f9f9f9; padding: 25px 40px; border-top: 1px solid #eeeeee; font-size: 13px; color: #666; }
                            .signature-table { width: 100%; }
                            .signature-table td { vertical-align: middle; }
                            .signature-border { border-left: 2px solid #3A5A40; padding-left: 20px; margin-left: 20px; }
                            a { color: #3A5A40; text-decoration: none; }
                        </style>
                    </head>
                    <body>
                        <div class='email-container'>
                            <div class='header'>
                                <h1>Zrušení rezervace</h1>
                            </div>

                            <div class='content'>
                                <p style='font-size: 16px;'>Vážený/á <strong>$guest_name</strong>,</p>
                                <br>
                                <p>potvrzujeme, že vaše rezervace v <strong>Penzion Oasis</strong> byla úspěšně zrušena.</p>

                                <table class='booking-details'>
                                    <tr>
                                        <td>Číslo rezervace:</td>
                                        <td><strong>$order_num</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Zrušený termín:</td>
                                        <td>$check_in_str – $check_out_str</td>
                                    </tr>
                                    <tr style='background-color: #fdf2f2;'>
                                        <td style='font-size:16px; color:#dc3545; border-bottom: none;'>Stav rezervace:</td>
                                        <td style='font-size:16px; color:#dc3545; border-bottom: none;'>ZRUŠENO</td>
                                    </tr>
                                </table>

                                <p style='margin-top: 40px; margin-bottom: 0;'>Těšíme se, že Vás přivítáme někdy příště.</p>
                            </div>

                            <div class='footer'>
                                <table class='signature-table' border='0' cellspacing='0' cellpadding='0'>
                                    <tr>
                                        <td width='70'>
                                            <img src='$logo_url' alt='Penzion Oasis' width='70' style='display: block; border-radius: 50%;margin-right: 15px;'>
                                        </td>
                                        <td class='signature-border'>
                                            <strong style='font-size: 15px; color: #333; display: block; margin-bottom: 4px;'>Tým Penzion Oasis</strong>
                                            <a href='tel:+420123456789' style='color: #666; text-decoration: none; display: block; margin-bottom: 2px;'>+420 123 456 789</a>
                                            <span style='color: #666; display: block; margin-bottom: 2px;'>Ulice 123, 123 45 Město</span>
                                            <a href='$web_url' style='color: #3A5A40; font-weight: 600;'>www.vasedomena.cz</a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </body>
                    </html>";

                    $admin_subject = "ZRUŠENÁ REZERVACE: $order_num";
                    $admin_msg = "
                    <html><body style='font-family: Arial;'>
                      <h2 style='color: #dc3545;'>Rezervace byla zrušena hostem online</h2>
                      <p><strong>Host:</strong> $guest_name ($guest_email, {$reservation['phone']})</p>
                      <p><strong>Číslo rezervace:</strong> $order_num</p>
                      <p><strong>Termín:</strong> $check_in_str - $check_out_str</p>
                      <p><strong>Pokoj ID:</strong> {$reservation['room_id']}</p>
                      <hr>
                      <p>Tento termín byl v systému automaticky uvolněn k dalšímu prodeji.</p>
                    </body></html>";

                    sendCancelEmail($guest_email, $client_subject, $client_msg);
                    sendCancelEmail("info@vasedomena.cz", $admin_subject, $admin_msg);
                }
            }
        }
    } else {
        $status_message = "<span style='color: #dc3545;'>Rezervace s tímto číslem nebyla nalezena.</span>";
    }
} else {
    $status_message = "<span style='color: #dc3545;'>Neplatný odkaz. Chybí číslo rezervace.</span>";
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zrušení rezervace | Penzion Oasis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/jpeg" href="miniature.PNG">
</head>

<body>

     <header>
    <div class="header-content">
        <div class="logo">
            <a href="index.php">
                <img src="logo.PNG" alt="Penzion Oasis Logo" class="desktop-logo">
                <img src="miniature.PNG" alt="Mini Logo" class="mobile-logo">
            </a>
        </div>
        
        <nav id="nav-wrapper">
            <ul class="nav-menu">
                <li><a href="index.php">Úvod</a></li>
                <li><a href="onas.html">O nás</a></li>
                <li><a href="pokoje.php">Pokoje a Ceny</a></li>
                <li><a href="kontakt.html">Kontakt</a></li>
            </ul>
        </nav>

        <button class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

    <main class="content-section">
        <div class="container">
            <div class="cancel-box">
                <h1>Zrušení rezervace</h1>
                
                <?php if ($status_message): ?>
                    <div class="status-msg">
                        <?= $status_message ?>
                    </div>
                <?php endif; ?>

                <?php if ($can_cancel): ?>
                    <p style="font-size: 1.1em; color: black;">
                        Opravdu si přejete zrušit rezervaci č. <strong><?= htmlspecialchars($order_number) ?></strong><br>
                        v termínu od <strong><?= date('d.m.Y', strtotime($reservation['check_in'])) ?></strong>?
                    </p>
                    <p style="color: #dc3545; font-size: 0.9em;">Tato akce je nevratná.</p>

                    <form method="POST">
                        <input type="hidden" name="order" value="<?= htmlspecialchars($order_number) ?>">
                        <button type="submit" name="confirm_cancel" class="btn-danger">Ano, zrušit rezervaci</button>
                        <a href="index.php" class="btn-keep">Ne, ponechat</a>
                    </form>
                <?php else: ?>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Zpět na hlavní stránku</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
            document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : 'auto';
        });
    }
});
</script>
</html>