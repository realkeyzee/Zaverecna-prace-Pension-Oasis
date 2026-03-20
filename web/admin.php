<?php

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

$login_error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username']; 
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "Nesprávné jméno nebo heslo.";
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="cs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Přihlášení do administrace</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
        <style>
            body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f5f5ed; margin: 0; font-family: 'Poppins', sans-serif;}
            .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
            .login-box input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Poppins', sans-serif; box-sizing: border-box; }
            .login-box button { width: 100%; padding: 12px; background-color: #3A5A40; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1.1em; transition: 0.2s;}
            .login-box button:hover { background-color: #2b452f; }
            .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9em; }
        </style>
    </head>
    
    <body>
        <div class="login-box">
            <img src="logo.PNG" alt="Logo" style="height: 50px; margin-bottom: 20px;">
            <h2 style="color: #3A5A40; margin-top: 0;">Administrace</h2>
            <?php if ($login_error): ?><div class="error"><?= $login_error ?></div><?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Přihlašovací jméno" required>
                <input type="password" name="password" placeholder="Heslo" required>
                <button type="submit" name="login_submit">Přihlásit se</button>
            </form>
            <a href="index.php" style="display: block; margin-top: 20px; color: #666; text-decoration: none; font-size: 0.9em;">&larr; Zpět na web</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function sendAdminSmtpEmail($to, $subject, $bodyHtml) {
    require_once 'Exception.php';
    require_once 'PHPMailer.php';
    require_once 'SMTP.php';

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


$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['res_id'])) {
        $res_id = (int)$_POST['res_id'];
        
        if ($_POST['action'] === 'confirm') {
            $current_admin_id = $_SESSION['admin_id'];
            $stmt_admin = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
            $stmt_admin->execute([$current_admin_id]);
            $admin_row = $stmt_admin->fetch();
            $admin_name = $admin_row ? $admin_row['username'] : 'Neznámý admin';
            
            $pdo->prepare("UPDATE reservations SET status = 'confirmed', confirmed_at = NOW(), confirmed_by = ? WHERE id = ?")->execute([$current_admin_id, $res_id]);

            $stmt_info = $pdo->prepare("
                SELECT r.*, g.first_name, g.last_name, g.email, g.phone 
                FROM reservations r JOIN guests g ON r.guest_id = g.id 
                WHERE r.id = ?
            ");
            $stmt_info->execute([$res_id]);
            $res_info = $stmt_info->fetch();

            if ($res_info) {
                $guest_email = $res_info['email'];
                $guest_name = $res_info['first_name'] . ' ' . $res_info['last_name'];
                $check_in_str = date('d.m.Y', strtotime($res_info['check_in']));
                $check_out_str = date('d.m.Y', strtotime($res_info['check_out']));
                $order_num = $res_info['order_number'];
                $web_url = "https://www.vasedomena.cz"; 
                $logo_url = $web_url . "/miniature.PNG";

                $client_subject = "Závazné potvrzení rezervace a platby";
                $client_msg = "
                <html>
                <head>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                        .email-container { max-width: 1000px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eaeaea; }
                        .header { background-color: #3A5A40; padding: 25px; text-align: center; color: white; }
                        .header h1 { margin: 0; font-size: 26px; font-weight: 600; }
                        .content { padding: 40px; color: #333333; line-height: 1.6; }
                        .booking-details { width: 100%; border-collapse: collapse; margin: 25px 0; font-size: 15px; }
                        .booking-details td { padding: 15px; border-bottom: 1px solid #eee; }
                        .booking-details td:first-child { font-weight: bold; color: #555; width: 40%; }
                        .booking-details td:last-child { color: #000; font-weight: 600; text-align: right; }
                        .footer { background-color: #f9f9f9; padding: 25px 40px; border-top: 1px solid #eeeeee; font-size: 13px; color: #666; }
                        .signature-table td { vertical-align: middle; }
                        .signature-border { border-left: 2px solid #3A5A40; padding-left: 20px; margin-left: 20px; }
                        a { color: #3A5A40; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <div class='header'>
                            <h1>Platba přijata – rezervace potvrzena</h1>
                        </div>
                        <div class='content'>
                            <p style='font-size: 16px;'>Vážený/á <strong>$guest_name</strong>,</p>
                            <br>
                            <p>s radostí Vám oznamujeme, že jsme v pořádku obdrželi Vaši platbu.</p>
                            <p><strong>Vaše rezervace v Penzion Oasis je tímto finálně a závazně potvrzena.</strong></p>

                            <table class='booking-details'>
                                <tr><td>Číslo objednávky:</td><td><strong>$order_num</strong></td></tr>
                                <tr><td>Termín pobytu:</td><td>$check_in_str – $check_out_str</td></tr>
                                <tr style='background-color: #f4fbf5;'>
                                    <td style='font-size:16px; color:#28a745; border-bottom: none;'>Stav:</td>
                                    <td style='font-size:16px; color:#28a745; border-bottom: none;'>ZAPLACENO A POTVRZENO</td>
                                </tr>
                            </table>
                            <p style='margin-top: 40px;'>Těšíme se na Vás!</p>
                        </div>
                        <div class='footer'>
                            <table class='signature-table' border='0' cellspacing='0' cellpadding='0'>
                                <tr>
                                    <td width='70'><img src='$logo_url' alt='Penzion Oasis' width='70' style='display: block; border-radius: 50%;margin-right: 15px;'></td>
                                    <td class='signature-border'>
                                        <strong style='font-size: 15px; color: #333; display: block; margin-bottom: 4px;'>Tým Penzion Oasis</strong>
                                        <a href='tel:+420123456789' style='color: #666; text-decoration: none; display: block; margin-bottom: 2px;'>+420 123 456 789</a>
                                        <span style='color: #666; display: block; margin-bottom: 2px;'>Kaštanová 1161, 289 24 Milovice</span>
                                        <a href='$web_url' style='color: #3A5A40; font-weight: 600;'>www.vasedomena.cz</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </body>
                </html>";

                $admin_subject = "POTVRZENO: Rezervace $order_num";
                $admin_msg = "
                <html><body style='font-family: Arial;'>
                  <h2 style='color: #28a745;'>Rezervace byla úspěšně potvrzena</h2>
                  <p><strong>Host:</strong> $guest_name ($guest_email)</p>
                  <p><strong>Číslo objednávky:</strong> $order_num</p>
                  <p><strong>Potvrdil:</strong> $admin_name</p>
                  <hr>
                  <p>E-mail s potvrzením o přijetí platby byl odeslán hostovi.</p>
                </body></html>";

                sendAdminSmtpEmail($guest_email, $client_subject, $client_msg);
                sendAdminSmtpEmail("info@vasedomena.cz", $admin_subject, $admin_msg);
            }
            
            $message = "<div class='alert alert-success'>Rezervace č. $order_num byla úspěšně potvrzena a e-maily odeslány.</div>";
        } 
        
        elseif ($_POST['action'] === 'cancel') {
            $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : 'Zrušeno ze strany ubytování.';

            $stmt_note = $pdo->prepare("SELECT admin_note FROM reservations WHERE id = ?");
            $stmt_note->execute([$res_id]);
            $current_note = $stmt_note->fetchColumn();
            
            $added_text = "DŮVOD ZRUŠENÍ: " . $cancel_reason;
            $new_note = !empty($current_note) ? $current_note . "\n\n" . $added_text : $added_text;

            $pdo->prepare("UPDATE reservations SET status = 'cancelled', admin_note = ? WHERE id = ?")->execute([$new_note, $res_id]);
            
            $stmt_info = $pdo->prepare("
                SELECT r.*, g.first_name, g.last_name, g.email, g.phone 
                FROM reservations r JOIN guests g ON r.guest_id = g.id 
                WHERE r.id = ?
            ");
            $stmt_info->execute([$res_id]);
            $res_info = $stmt_info->fetch();

            if ($res_info) {
                $guest_email = $res_info['email'];
                $guest_name = $res_info['first_name'] . ' ' . $res_info['last_name'];
                $check_in_str = date('d.m.Y', strtotime($res_info['check_in']));
                $check_out_str = date('d.m.Y', strtotime($res_info['check_out']));
                $order_num = $res_info['order_number'];
                $web_url = "https://www.vasedomena.cz"; 
                $logo_url = $web_url . "/miniature.PNG";

                $cancel_reason_safe = htmlspecialchars($cancel_reason);

                $client_subject = "Zrušení rezervace";
                $client_msg = "
                    <html>
                    <head>
                        <style>
                            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                            .email-container { max-width: 1000px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eaeaea; }
                            .header { background-color: #3A5A40; padding: 25px; text-align: center; color: white; } 
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
                            .cancel-reason { background-color: #fdf2f2; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #333; }
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
                                <p>oznamujeme, že vaše rezervace v <strong>Penzion Oasis</strong> byla zrušena.</p>
                                
                                <div class='cancel-reason'>
                                    <strong style='color: #dc3545; font-size: 15px;'>Důvod zrušení:</strong><br><br>
                                    " . nl2br($cancel_reason_safe) . "
                                </div>

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
                                <p>V případě, že jste svůj pobyt uhradili, vrátíme Vám peníze na účet do 5 pracovních dní.</p>

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
                                            <span style='color: #666; display: block; margin-bottom: 2px;'>Kaštanová 1161, 289 24 Milovice</span>
                                            <a href='$web_url' style='color: #3A5A40; font-weight: 600;'>www.vasedomena.cz</a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </body>
                    </html>";

                sendAdminSmtpEmail($guest_email, $client_subject, $client_msg);
            }
            
            $message = "<div class='alert alert-danger'>Rezervace byla zrušena a důvod zapsán do interní poznámky. E-mail byl úspěšně odeslán hostovi.</div>";
        } 
        
        elseif ($_POST['action'] === 'save_note') {
            $note_text = trim($_POST['admin_note']);
            $pdo->prepare("UPDATE reservations SET admin_note = ? WHERE id = ?")->execute([$note_text, $res_id]);
            $message = "<div class='alert alert-success'>Interní poznámka byla úspěšně uložena.</div>";
        }

        elseif ($_POST['action'] === 'update_reservation') {
            $guest_id = (int)$_POST['guest_id'];
            
            $stmt_g = $pdo->prepare("UPDATE guests SET first_name=?, last_name=?, email=?, phone=?, street=?, city=?, zip=?, country=? WHERE id=?");
            $stmt_g->execute([
                trim($_POST['first_name']), trim($_POST['last_name']), trim($_POST['email']), trim($_POST['phone']),
                trim($_POST['street']), trim($_POST['city']), trim($_POST['zip']), trim($_POST['country']),
                $guest_id
            ]);

            $stmt_r = $pdo->prepare("UPDATE reservations SET room_id=?, check_in=?, check_out=?, total_price=?, payment_method_id=?, note=? WHERE id=?");
            $stmt_r->execute([
                (int)$_POST['room_id'], $_POST['check_in'], $_POST['check_out'], (int)$_POST['total_price'],
                (int)$_POST['payment_method_id'], trim($_POST['note']),
                $res_id
            ]);

            $pdo->prepare("DELETE FROM reservation_addons WHERE reservation_id = ?")->execute([$res_id]);
            
            if (!empty($_POST['add_ons']) && is_array($_POST['add_ons'])) {
                $stmt_addon_info = $pdo->prepare("SELECT id, price, per_night FROM addons WHERE id = ?");
                $stmt_insert_ra = $pdo->prepare("INSERT INTO reservation_addons (reservation_id, addon_id, quantity, price_snapshot, per_night_snapshot) VALUES (?, ?, 1, ?, ?)");
                
                foreach ($_POST['add_ons'] as $addon_id) {
                    $stmt_addon_info->execute([$addon_id]);
                    $a_info = $stmt_addon_info->fetch();
                    if ($a_info) {
                        $stmt_insert_ra->execute([$res_id, $a_info['id'], $a_info['price'], $a_info['per_night']]);
                    }
                }
            }

            header("Location: admin.php?id=" . $res_id . "&updated=1");
            exit;
        }
    }
}

if (isset($_GET['updated'])) {
    $message = "<div class='alert alert-success'>Údaje o rezervaci byly úspěšně upraveny.</div>";
}

function getStatusLabel($status) {
    switch ($status) {
        case 'pending': return '<span class="badge badge-warning">Čeká na vyřízení</span>';
        case 'confirmed': return '<span class="badge badge-success">Potvrzeno</span>';
        case 'cancelled': return '<span class="badge badge-danger">Zrušeno</span>';
        default: return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrace rezervací | Penzion Oasis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/jpeg" href="miniature.PNG">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
        .admin-table th, .admin-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .admin-table th { background-color: #3A5A40; color: white; font-weight: 600; }
        .admin-table tr:hover { background-color: #f9f9f9; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .badge-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .btn-danger { background-color: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-family: 'Poppins';}
        .btn-danger:hover { background-color: #87131e; }
        .btn-success { background-color: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-family: 'Poppins';}
        .btn-success:hover { background-color: #218838; }
        .btn-search { background-color: #3A5A40; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-family: 'Poppins';}
        .btn-search:hover { background-color: #27422c; }
        
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; text-align: left; }
        .detail-box { background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #3A5A40; margin-bottom: 20px; }
        .detail-box p { margin: 5px 0; }
        
        .edit-form-group { margin-bottom: 12px; }
        .edit-form-group label { display: block; font-size: 0.85em; color: #555; margin-bottom: 4px; font-weight: 600; }
        .edit-form-group input[type="text"], .edit-form-group input[type="email"], .edit-form-group input[type="number"], .edit-form-group input[type="date"], .edit-form-group select, .edit-form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Poppins', sans-serif; box-sizing: border-box; }
        
        @media (max-width: 768px) {
            .detail-grid { grid-template-columns: 1fr; }
            .admin-table { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>
    <header>
    <div class="header-content">
        <div class="logo">
            <a href="index.php">
                <img src="logo.PNG" alt="Penzion Oasis Logo" class="desktop-logo">
            </a>
        </div>
        
        <nav id="nav-wrapper">
            <ul class="nav-menu">
                  <li><a href="admin_pokoje.php">Pokoje a Ceny</a></li>
                    <li><a href="admin_addons.php">Doplňky</a></li>
                    <li><a href="admin_payments.php" class="active">Platby</a></li>
                    <li><a href="admin.php">Rezervace</a></li>
                    <li><a href="admin.php?logout=1" style="color: #dc3545; font-weight: bold;">Odhlásit se</a></li>
            </ul>
        </nav>

        <button class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

    <main class="content-section" style="min-height: 70vh;">
        <div class="container">
            
            <?php echo $message; ?>

            <?php
            if (isset($_GET['id'])): 
                $id = (int)$_GET['id'];
                $is_edit_mode = isset($_GET['edit']) && $_GET['edit'] == 1;
                
                $stmt = $pdo->prepare("
                    SELECT r.*, g.first_name, g.last_name, g.email, g.phone, g.street, g.city, g.zip, g.country,
                           pm.name AS payment_name, a.username AS admin_username
                    FROM reservations r 
                    JOIN guests g ON r.guest_id = g.id 
                    LEFT JOIN payment_methods pm ON r.payment_method_id = pm.id
                    LEFT JOIN admins a ON r.confirmed_by = a.id
                    WHERE r.id = ?
                ");
                $stmt->execute([$id]);
                $res = $stmt->fetch();

                $saved_addon_ids = [];
                $saved_addon_names = [];
                if ($res) {
                    $stmt_ra = $pdo->prepare("SELECT a.id, a.name FROM reservation_addons ra JOIN addons a ON ra.addon_id = a.id WHERE ra.reservation_id = ?");
                    $stmt_ra->execute([$id]);
                    $fetched_addons = $stmt_ra->fetchAll();
                    $saved_addon_ids = array_column($fetched_addons, 'id'); 
                    $saved_addon_names = array_column($fetched_addons, 'name');
                }

                if ($res):
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <a href="admin.php" class="btn btn-secondary" style="display:inline-block; text-decoration: none;">&larr; Zpět na výpis</a>
                    
                    <?php if (!$is_edit_mode && $res['status'] !== 'cancelled'): ?>
                        <a href="admin.php?id=<?= $id ?>&edit=1" class="btn btn-primary" style="background-color: #007bff; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; font-weight: bold; font-family: 'Poppins';">Upravit rezervaci</a>
                    <?php endif; ?>
                </div>

                <div class="res-card">
                    <h2 class="res-card-title">
                        <?= $is_edit_mode ? 'Úprava rezervace' : 'Detail rezervace' ?> #<?= htmlspecialchars($res['order_number']) ?> 
                        <?php if (!$is_edit_mode) echo getStatusLabel($res['status']); ?>
                    </h2>
                    
                    <?php if ($is_edit_mode): ?>
                    <form method="POST" action="admin.php?id=<?= $id ?>">
                        <input type="hidden" name="action" value="update_reservation">
                        <input type="hidden" name="res_id" value="<?= $res['id'] ?>">
                        <input type="hidden" name="guest_id" value="<?= $res['guest_id'] ?>">

                        <div class="detail-grid">
                            <div>
                                <h3 style="color: #3A5A40; border-bottom: 2px solid #eee; padding-bottom: 10px;">Údaje o hostovi (Úprava)</h3>
                                <div class="detail-box">
                                    <div style="display:flex; gap:10px;">
                                        <div class="edit-form-group" style="flex:1;"><label>Jméno</label><input type="text" name="first_name" value="<?= htmlspecialchars($res['first_name']) ?>" required></div>
                                        <div class="edit-form-group" style="flex:1;"><label>Příjmení</label><input type="text" name="last_name" value="<?= htmlspecialchars($res['last_name']) ?>" required></div>
                                    </div>
                                    <div class="edit-form-group"><label>E-mail</label><input type="email" name="email" value="<?= htmlspecialchars($res['email']) ?>" required></div>
                                    <div class="edit-form-group"><label>Telefon</label><input type="text" name="phone" value="<?= htmlspecialchars($res['phone']) ?>" required></div>
                                    <hr style="border-top: 1px solid #ddd; margin: 15px 0;">
                                    <div class="edit-form-group"><label>Ulice a č.p.</label><input type="text" name="street" value="<?= htmlspecialchars($res['street']) ?>" required></div>
                                    <div class="edit-form-group"><label>Město</label><input type="text" name="city" value="<?= htmlspecialchars($res['city']) ?>" required></div>
                                    <div style="display:flex; gap:10px;">
                                        <div class="edit-form-group" style="flex:1;"><label>PSČ</label><input type="text" name="zip" value="<?= htmlspecialchars($res['zip']) ?>" required></div>
                                        <div class="edit-form-group" style="flex:1;"><label>Stát</label><input type="text" name="country" value="<?= htmlspecialchars($res['country']) ?>" required></div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 style="color: #3A5A40; border-bottom: 2px solid #eee; padding-bottom: 10px;">Podrobnosti pobytu (Úprava)</h3>
                                <div class="detail-box">
                                    <div class="edit-form-group">
                                        <label>Pokoj</label>
                                        <select name="room_id" required>
                                            <?php 
                                            $rooms_db = $pdo->query("SELECT id, name, base_price_per_night FROM rooms ORDER BY id")->fetchAll();
                                            foreach($rooms_db as $rm): 
                                            ?>
                                                <option value="<?= $rm['id'] ?>" data-price="<?= $rm['base_price_per_night'] ?>" <?= $rm['id'] == $res['room_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($rm['name']) ?> (ID: <?= $rm['id'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div style="display:flex; gap:10px;">
                                        <div class="edit-form-group" style="flex:1;"><label>Příjezd</label><input type="date" name="check_in" value="<?= $res['check_in'] ?>" required></div>
                                        <div class="edit-form-group" style="flex:1;"><label>Odjezd</label><input type="date" name="check_out" value="<?= $res['check_out'] ?>" required></div>
                                    </div>
                                    
                                    <div class="edit-form-group">
                                        <label>Doplňkové služby</label>
                                        <div style="background: white; border: 1px solid #ddd; padding: 12px; border-radius: 5px;">
                                            <?php
                                            $addons_db = $pdo->query("SELECT * FROM addons ORDER BY id ASC")->fetchAll();

                                            if (empty($addons_db)) {
                                                echo "<span style='color: #888; font-size: 0.9em;'>Žádné doplňky nejsou k dispozici.</span>";
                                            } else {
                                                foreach ($addons_db as $addon) {
                                                    $is_checked = in_array($addon['id'], $saved_addon_ids) ? 'checked' : '';
                                                    echo '<label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 8px; font-weight: 400; color: #333; font-size: 0.95em;">';
                                                    echo '<input type="checkbox" name="add_ons[]" class="addon-checkbox" value="' . $addon['id'] . '" data-price="' . $addon['price'] . '" data-pernight="' . $addon['per_night'] . '" ' . $is_checked . ' style="width: auto; margin: 0 10px 0 0;">';
                                                    echo htmlspecialchars($addon['name']) . ' (' . $addon['price'] . ' Kč' . ($addon['per_night'] ? ' / noc' : '') . ')';
                                                    echo '</label>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>

                                    <div class="edit-form-group"><label>Poznámka od hosta</label><textarea name="note" rows="2"><?= htmlspecialchars($res['note']) ?></textarea></div>
                                    <hr style="border-top: 1px solid #ddd; margin: 15px 0;">
                                    <div class="edit-form-group">
                                        <label>Způsob platby</label>
                                        <select name="payment_method_id" required>
                                            <?php
                                            $payments_db = $pdo->query("SELECT id, name FROM payment_methods ORDER BY id ASC")->fetchAll();
                                            foreach ($payments_db as $pm) {
                                                $is_selected = ($pm['id'] == $res['payment_method_id']) ? 'selected' : '';
                                                echo '<option value="' . $pm['id'] . '" ' . $is_selected . '>' . htmlspecialchars($pm['name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="edit-form-group">
                                        <label>Celková cena (Kč) <span style="font-size: 0.85em; color: #666; font-weight: normal;">(Automaticky se přepočítává, lze upravit ručně)</span></label>
                                        <input type="number" id="edit-total-price" name="total_price" value="<?= $res['total_price'] ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: right; border-top: 1px solid #eee; padding-top: 20px;">
                            <a href="admin.php?id=<?= $id ?>" class="btn btn-secondary" style="margin-right: 15px; text-decoration: none; padding: 10px 15px; font-size: 0.8em; text-transform: none; font-weight: normal;">Zrušit úpravy</a>
                            <button type="submit" class="btn-success"">Uložit všechny změny</button>
                        </div>
                    </form>
                    
                    <?php else: ?>
                    <div class="detail-grid">
                        <div>
                            <h3 style="color: #3A5A40; border-bottom: 2px solid #eee; padding-bottom: 10px;">Údaje o hostovi</h3>
                            <div class="detail-box">
                                <p><strong>Jméno:</strong> <?= htmlspecialchars($res['first_name'] . ' ' . $res['last_name']) ?></p>
                                <p><strong>E-mail:</strong> <a href="mailto:<?= htmlspecialchars($res['email']) ?>"><?= htmlspecialchars($res['email']) ?></a></p>
                                <p><strong>Telefon:</strong> <a href="tel:<?= htmlspecialchars($res['phone']) ?>"><?= htmlspecialchars($res['phone']) ?></a></p>
                                <hr style="border-top: 1px solid #ddd; margin: 15px 0;">
                                <p><strong>Adresa:</strong><br>
                                    <?= htmlspecialchars($res['street']) ?><br>
                                    <?= htmlspecialchars($res['zip']) ?> <?= htmlspecialchars($res['city']) ?><br>
                                    <?= htmlspecialchars($res['country']) ?>
                                </p>
                            </div>
                        </div>

                        <div>
                            <h3 style="color: #3A5A40; border-bottom: 2px solid #eee; padding-bottom: 10px;">Podrobnosti pobytu</h3>
                            <div class="detail-box">
                                <p><strong>Vytvořeno:</strong> <?= date('d.m.Y v H:i', strtotime($res['created_at'])) ?></p>
                                
                                <?php if ($res['status'] === 'confirmed' && !empty($res['confirmed_at'])): ?>
                                    <p style="color: #28a745; font-weight: 500;">
                                        <strong>Potvrzeno:</strong> <?= date('d.m.Y v H:i', strtotime($res['confirmed_at'])) ?> (Uživatel: <?= htmlspecialchars($res['admin_username'] ?? 'Neznámý') ?>)
                                    </p>
                                <?php endif; ?>
                                
                                <hr style="border-top: 1px dashed #ddd; margin: 10px 0;">
                                <p><strong>ID Pokoje:</strong> Pokoj č. <?= htmlspecialchars($res['room_id']) ?></p>
                                <p><strong>Termín:</strong> <?= date('d.m.Y', strtotime($res['check_in'])) ?> – <?= date('d.m.Y', strtotime($res['check_out'])) ?></p>
                                <p><strong>Doplňky:</strong> <?= !empty($saved_addon_names) ? htmlspecialchars(implode(', ', $saved_addon_names)) : 'Žádné' ?></p>
                                <p><strong>Poznámka od hosta:</strong> <?= nl2br(htmlspecialchars($res['note'] ?: 'Bez poznámky')) ?></p>
                                <hr style="border-top: 1px solid #ddd; margin: 15px 0;">
                                <p><strong>Způsob platby:</strong> <?= htmlspecialchars($res['payment_name'] ?? 'Neznámá metoda') ?></p>
                                <p style="font-size: 1.2em; color: #3A5A40;"><strong>Celková cena:</strong> <?= number_format($res['total_price'], 0, ',', ' ') ?> Kč</p>
                            </div>
                        </div>
                    </div>

                    <div class="detail-grid" style="margin-top: 30px;">
                        <div>
                            <h3 style="color: #3A5A40; border-bottom: 2px solid #eee; padding-bottom: 10px; font-size: 1.2em; margin-top: 0;">Kalendář obsazenosti</h3>
                            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid #3A5A40; max-width: 350px;">
                                <iframe src="calendar-detail.php?room_id=<?= $res['room_id'] ?>" style="width: 100%; height: 310px; border: none; overflow: hidden;" scrolling="no"></iframe>
                            </div>
                        </div>

                        <div>
                            <h3 style="color: #3A5A40; border-bottom: 2px solid #eee; padding-bottom: 10px; font-size: 1.2em; margin-top: 0;">Interní poznámka</h3>
                            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid #3A5A40;">
                                <form method="POST">
                                    <input type="hidden" name="res_id" value="<?= $res['id'] ?>">
                                    <input type="hidden" name="action" value="save_note">
                                    <textarea name="admin_note" rows="9" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Poppins', sans-serif; resize: vertical; box-sizing: border-box; background: white;" placeholder="Zde si můžete napsat interní poznámku (např. host zaplatil zálohu, atd.)..."><?= htmlspecialchars($res['admin_note'] ?? '') ?></textarea>
                                    <button type="submit" style="margin-top: 10px; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; background-color: #3A5A40; color: white; font-weight: bold; width: 100%; font-family: 'Poppins', sans-serif; transition: 0.2s;">Uložit poznámku</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if ($res['status'] !== 'cancelled'): ?>
                        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; display: flex; gap: 15px; justify-content: flex-end;">
                            <form method="POST" onsubmit="return confirmCancel(this);">
                                <input type="hidden" name="res_id" value="<?= $res['id'] ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn-danger">Zrušit rezervaci</button>
                            </form>

                            <?php if ($res['status'] === 'pending'): ?>
                            <form method="POST" onsubmit="return confirm('Potvrdit platbu a rezervaci? Hostovi a vám se odešle potvrzovací e-mail.');">
                                <input type="hidden" name="res_id" value="<?= $res['id'] ?>">
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="btn-success">Potvrdit rezervaci (a poslat e-mail)</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php endif;  ?>
                </div>

            <?php
                else:
                    echo "<div class='alert alert-danger'>Rezervace nebyla nalezena.</div>";
                endif;

            else: 
                $search_query = trim($_GET['search'] ?? '');
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                    <h1 style="color: #3A5A40; margin: 0;">Přehled rezervací</h1>
                    
                    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Hledat jméno, e-mail, VS..." style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Poppins', sans-serif; width: 250px;">
                        <button type="submit" class="btn-search" style="padding: 10px 15px;">Hledat</button>
                        
                        <?php if ($search_query !== ''): ?>
                            <a href="admin.php" class="btn-danger" style="text-decoration: none; padding: 10px 15px; display: inline-block;">Zrušit filtr</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>VS (Objednávka)</th>
                            <th>Host</th>
                            <th>Termín</th>
                            <th>Pokoj</th>
                            <th>Cena</th>
                            <th>Stav</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "
                            SELECT r.id, r.order_number, r.check_in, r.check_out, r.status, r.total_price, r.room_id,
                                   g.first_name, g.last_name, g.email, g.phone
                            FROM reservations r
                            JOIN guests g ON r.guest_id = g.id
                        ";
                        
                        $params = [];

                        if ($search_query !== '') {
                            $sql .= " WHERE r.order_number LIKE ? 
                                      OR g.first_name LIKE ? 
                                      OR g.last_name LIKE ? 
                                      OR g.email LIKE ? 
                                      OR g.phone LIKE ?
                                      OR CONCAT(g.first_name, ' ', g.last_name) LIKE ?";
                                      
                            $search_param = "%$search_query%";
                            $params = array_fill(0, 6, $search_param);
                        }

                        $sql .= " ORDER BY r.created_at DESC";

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);

                        $count = 0;
                        while ($row = $stmt->fetch()):
                            $count++;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['order_number']) ?></strong></td>
                            <td>
                                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?><br>
                                <span style="font-size: 0.85em; color: #777;"><?= htmlspecialchars($row['email']) ?></span>
                            </td>
                            <td><?= date('d.m.Y', strtotime($row['check_in'])) ?> - <?= date('d.m.Y', strtotime($row['check_out'])) ?></td>
                            <td>Pokoj <?= htmlspecialchars($row['room_id']) ?></td>
                            <td><?= number_format($row['total_price'], 0, ',', ' ') ?> Kč</td>
                            <td><?= getStatusLabel($row['status']) ?></td>
                            <td>
                                <a href="admin.php?id=<?= $row['id'] ?>" class="btn btn-secondary" style="padding: 8px 15px; font-size: 0.85em; text-decoration: none;">Detail</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($count === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #666;">
                                Žádné rezervace nebyly nalezeny.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
    </main>
</body>
<script>
function recalculatePrice() {
    const roomSelect = document.querySelector('select[name="room_id"]');
    const checkInInput = document.querySelector('input[name="check_in"]');
    const checkOutInput = document.querySelector('input[name="check_out"]');
    const priceInput = document.getElementById('edit-total-price');

    if (!roomSelect || !checkInInput || !checkOutInput || !priceInput) return;

    const checkIn = checkInInput.value;
    const checkOut = checkOutInput.value;
    
    if (!checkIn || !checkOut) return;

    const start = new Date(checkIn);
    const end = new Date(checkOut);
    
    if (start >= end) return; 
    const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
    
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const roomPrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;

    let total = diffDays * roomPrice;

    document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
        let price = parseFloat(cb.getAttribute('data-price')) || 0;
        let perNight = parseInt(cb.getAttribute('data-pernight')) || 0;
        
        if (perNight === 1) {
            total += (price * diffDays);
        } else {
            total += price; 
        }
    });

    priceInput.value = total;
}

function confirmCancel(form) {
    let reason = prompt("Zadejte důvod zrušení (tento text se odešle hostovi v e-mailu):");
    if (reason === null || reason.trim() === "") {
        alert("Zrušení přerušeno. Důvod zrušení je povinný.");
        return false;
    }
    let reasonInput = document.createElement("input");
    reasonInput.type = "hidden";
    reasonInput.name = "cancel_reason";
    reasonInput.value = reason;
    form.appendChild(reasonInput);
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.querySelector('input[name="action"][value="update_reservation"]');
    if (editForm) {
        const formGroup = editForm.closest('form');
        formGroup.querySelector('select[name="room_id"]').addEventListener('change', recalculatePrice);
        formGroup.querySelector('input[name="check_in"]').addEventListener('change', recalculatePrice);
        formGroup.querySelector('input[name="check_out"]').addEventListener('change', recalculatePrice);
        formGroup.querySelectorAll('.addon-checkbox').forEach(cb => cb.addEventListener('change', recalculatePrice));
    }

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