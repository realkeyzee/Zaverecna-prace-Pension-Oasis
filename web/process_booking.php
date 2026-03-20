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

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $room_id = (int)$_POST['room_id'];
    $first_name = htmlspecialchars(trim($_POST['name']));
    $last_name = htmlspecialchars(trim($_POST['surname']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $street = htmlspecialchars(trim($_POST['street']));
    $city = htmlspecialchars(trim($_POST['city']));
    $zip = htmlspecialchars(trim($_POST['zip']));
    $country = htmlspecialchars(trim($_POST['country']));
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $note = htmlspecialchars(trim($_POST['message']));
    
    $payment_method = htmlspecialchars(trim($_POST['payment_method'])); 
    $add_ons_array = isset($_POST['add_ons']) ? $_POST['add_ons'] : [];
    $add_ons_text = implode(", ", $add_ons_array); 
    if (empty($add_ons_text)) $add_ons_text = "Žádné";

    if (empty($first_name) || empty($last_name) || empty($email) || empty($check_in) || empty($check_out) || empty($street) || empty($city)) {
        die("Chyba: Nevyplnili jste všechna povinná pole.");
    }

    if (!isset($_POST['gdpr_consent'])) {
        die("Chyba: Musíte souhlasit se zpracováním osobních údajů.");
    }

    if (!isset($_POST['terms_consent'])) {
        die("Chyba: Pro dokončení rezervace musíte souhlasit s obchodními podmínkami.");
    }

    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE room_id = ? AND (check_in < ? AND check_out > ?) AND status != 'cancelled'");
    $stmt_check->execute([$room_id, $check_out, $check_in]);
    if ($stmt_check->fetchColumn() > 0) {
        echo "<script>alert('Termín je obsazen.'); window.history.back();</script>";
        exit();
    }

    $stmt_price = $pdo->prepare("SELECT base_price_per_night, name FROM rooms WHERE id = ?");
    $stmt_price->execute([$room_id]);
    $room = $stmt_price->fetch();
    
    $base_price = $room['base_price_per_night'];
    $room_name = isset($room['name']) ? $room['name'] : "Pokoj č. " . $room_id; 

    $nights = (new DateTime($check_in))->diff(new DateTime($check_out))->days;
    $total_price = $nights * $base_price;

    $stmt_pay = $pdo->prepare("SELECT id, name, method_value FROM payment_methods WHERE method_value = ? LIMIT 1");
    $stmt_pay->execute([$payment_method]);
    $payment_method_data = $stmt_pay->fetch();
    
    if (!$payment_method_data) {
        die("Chyba: Neplatná platební metoda.");
    }
    $payment_method_id = $payment_method_data['id'];
    $payment_method_name = $payment_method_data['name']; 
    $add_ons_to_save = [];
    $add_ons_names = []; 
    
    if (!empty($add_ons_array)) {

        $inQuery = implode(',', array_fill(0, count($add_ons_array), '?'));
        
        $stmt_addons = $pdo->prepare("SELECT id, name, price, per_night FROM addons WHERE name IN ($inQuery)");
        $stmt_addons->execute($add_ons_array);
        $fetched_addons = $stmt_addons->fetchAll();

        foreach ($fetched_addons as $addon) {
            $addon_price = $addon['price'];
            $is_per_night = $addon['per_night'];

            if ($is_per_night) {
                $total_price += ($addon_price * $nights); 
            } else {
                $total_price += $addon_price;
            }

            $add_ons_to_save[] = [
                'addon_id' => $addon['id'],
                'price_snapshot' => $addon_price,
                'per_night_snapshot' => $is_per_night
            ];
            $add_ons_names[] = $addon['name'];
        }
    }

    $add_ons_text = empty($add_ons_names) ? "Žádné" : implode(", ", $add_ons_names);

    $stmt_guest = $pdo->prepare("SELECT id FROM guests WHERE email = ?");
    $stmt_guest->execute([$email]);
    $existing_guest = $stmt_guest->fetch();

    if ($existing_guest) {
        $guest_id = $existing_guest['id'];
        $pdo->prepare("UPDATE guests SET first_name=?, last_name=?, email=?, phone=?, street=?, city=?, zip=?, country=? WHERE id=?")
            ->execute([$first_name, $last_name, $email, $phone, $street, $city, $zip, $country, $guest_id]);
    } else {
        $pdo->prepare("INSERT INTO guests (first_name, last_name, email, phone, street, city, zip, country, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())")
            ->execute([$first_name, $last_name, $email, $phone, $street, $city, $zip, $country]);
        $guest_id = $pdo->lastInsertId();
    }

    $date_part = date('dmY', strtotime($check_in)); 
    $order_number = $guest_id . $date_part . $room_id; 

    $sql_insert = "INSERT INTO reservations (order_number, room_id, guest_id, payment_method_id, check_in, check_out, status, total_price, note, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())";
    
    $pdo->prepare($sql_insert)->execute([
        $order_number,
        $room_id, 
        $guest_id, 
        $payment_method_id,
        $check_in, 
        $check_out, 
        $total_price, 
        $note
    ]);
    
    $reservation_id = $pdo->lastInsertId();

    if (!empty($add_ons_to_save)) {

        $stmt_pivot = $pdo->prepare("INSERT INTO reservation_addons (reservation_id, addon_id, quantity, price_snapshot, per_night_snapshot) VALUES (?, ?, 1, ?, ?)");
        
        foreach ($add_ons_to_save as $addon) {
            $stmt_pivot->execute([
                $reservation_id, 
                $addon['addon_id'], 
                $addon['price_snapshot'], 
                $addon['per_night_snapshot']
            ]);
        }
    }
    
    function sendSmtpEmail($to, $subject, $bodyHtml) {
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

        $web_url = "https://www.vasedomena.cz"; 
        $logo_url = $web_url . "/miniature.PNG"; 

        $client_subject = "Potvrzení přijetí rezervace - Penzion Oasis";
    
    $date_numbers = date('dmY', strtotime($check_in)); 
    $variable_symbol = $order_number;

    $client_msg = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
            /* ZVĚTŠENÁ ŠÍŘKA NA 700px */
            .email-container { max-width: 1000px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eaeaea; }
            .header { background-color: #3A5A40; padding: 25px; text-align: center; color: white; }
            .header h1 { margin: 0; font-size: 26px; font-weight: 600; letter-spacing: 0.5px; }
            .content { padding: 40px; color: #333333; line-height: 1.6; }
            
            /* TABULKA PŘES CELOU ŠÍŘI */
            .booking-details { width: 100%; border-collapse: collapse; margin: 25px 0; font-size: 15px; }
            .booking-details td { padding: 15px; border-bottom: 1px solid #eee; }
            .booking-details td:first-child { font-weight: bold; color: #555; width: 40%; white-space: nowrap; }
            .booking-details td:last-child { color: #000; font-weight: 600; text-align: right; }
            
            .payment-box { background-color: #f1f8f1; border-left: 5px solid #3A5A40; padding: 20px; margin-top: 30px; border-radius: 4px; }
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
                <h1>Potvrzení rezervace</h1>
            </div>

            <div class='content'>
                <p style='font-size: 16px;'>Vážený/á <strong>$first_name $last_name</strong>,</p>
                <br>
                <p>děkujeme, že jste si vybrali <strong>Penzion Oasis</strong>. Úspěšně jsme přijali vaši rezervaci a blokujeme pro vás termín.</p>

                <table class='booking-details'>
                <tr>
                            <td>Číslo rezervace:</td>
                            <td><strong>$order_number</strong></td>
                        </tr>
                    <tr>
                        <td>Termín pobytu:</td>
                        <td>" . date('d.m.Y', strtotime($check_in)) . " – " . date('d.m.Y', strtotime($check_out)) . "</td>
                    </tr>
                    <tr>
                        <td>Vybraný pokoj:</td>
                        <td>$room_name</td>
                    </tr>
                    <tr>
                        <td>Počet nocí:</td>
                        <td>$nights</td>
                    </tr>
                    <tr>
                        <td>Služby navíc:</td>
                        <td>$add_ons_text</td>
                    </tr>
                    <tr>
                        <td>Způsob platby:</td>
                        <td>$payment_method_name</td>
                    </tr>
                    <tr style='background-color: #fafafa;'>
                        <td style='font-size:18px; color:#3A5A40; border-bottom: none;'>Cena celkem:</td>
                        <td style='font-size:18px; color:#3A5A40; border-bottom: none;'>$total_price Kč</td>
                    </tr>
                </table>
    ";

    if ($payment_method == 'transfer') {
        $client_msg .= "
        <div class='payment-box'>
            <h3 style='margin-top: 0; color: #2e7d32; font-size: 18px; margin-bottom: 15px;'>Údaje k platbě převodem</h3>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 5px 0;'>Číslo účtu:</td>
                    <td style='font-weight: bold;'>123456789/1234</td>
                </tr>
                <tr>
                    <td style='padding: 5px 0;'>Variabilní symbol:</td>
                    <td style='font-weight: bold; color: #3A5A40;'>$variable_symbol</td>
                </tr>
                <tr>
                    <td style='padding: 5px 0;'>Částka:</td>
                    <td style='font-weight: bold;'>$total_price Kč</td>
                </tr>
            </table>
            <p style='font-size: 13px; margin-top: 15px; color: #555; margin-bottom: 0;'>
                <em>Jakmile platbu obdržíme, zašleme vám finální potvrzení rezervace.</em>
            </p>
        </div>";
    } else {

        $client_msg .= "
        <div class='payment-box'>
            <h3 style='margin-top: 0; color: #2e7d32; font-size: 18px;'>Platba na místě</h3>
            <p style='margin-bottom: 0;'>Platbu provedete hotově při příjezdu na recepci. Vaši rezervaci vám finálně potvrdíme e-mailem během několika dní.</p>
        </div>";
    }


    $client_msg .= "
                <div style='margin-top: 30px; padding: 15px; border-top: 1px solid #eee; text-align: center;'>
                    <p style='font-size: 13px; color: #666; margin-bottom: 10px;'>
                        Pokud potřebujete rezervaci zrušit (lze maximálně 48 hodin před příjezdem), můžete tak učinit kliknutím na odkaz níže:
                    </p>
                    <a href='$web_url/zrusit-rezervaci.php?order=$order_number' style='display: inline-block; padding: 5px 10px; background-color: #f8d7da; color: #721c24; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 11px;'>Zrušit rezervaci</a>
                </div>

    ";
    

    $client_msg .= "
                <p style='margin-top: 40px; margin-bottom: 0;'>Těšíme se na vaši návštěvu!</p>
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

    $admin_msg = "
    <html><body style='font-family: Arial;'>
      <h2 style='color: #d32f2f;'>Nová rezervace ke schválení</h2>
      <p>Host: $first_name $last_name ($email, $phone)</p>
      <p>Termín: $check_in - $check_out</p>
      <p>Pokoj: $room_name</p>
      <p>Cena: $total_price Kč</p>
      <p>Poznámka: $note</p>
      <p><a href='https://www.vasedomena.cz/admin.php'>Přejít do administrace</a></p>
    </body></html>";

    sendSmtpEmail($email, "Potvrzení rezervace", $client_msg);
    sendSmtpEmail("info@vasedomena.cz", "Nová rezervace: $check_in", $admin_msg);

    echo "<script>window.location.href = 'dekujeme.html?order=$order_number';</script>";

} else {
    header("Location: rezervace.php");
}
?>