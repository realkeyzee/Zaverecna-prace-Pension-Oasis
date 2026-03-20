<?php
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

if (!isset($room_id)) {
    $room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 1;
}

$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$months = [
    1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
    5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
    9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
];
$month_name = $months[$month];

$first_day_ts   = mktime(0, 0, 0, $month, 1, $year);
$days_in_month  = (int)date('t', $first_day_ts);
$first_weekday  = (int)date('N', $first_day_ts); // 1=Po ... 7=Ne

$month_start = date('Y-m-d', $first_day_ts);
$month_end   = date('Y-m-d', mktime(0, 0, 0, $month, $days_in_month + 1, $year));

$sql = "
    SELECT check_in, check_out, status
    FROM reservations
    WHERE room_id = :room_id
      AND status IN ('pending','confirmed')
      AND (check_in < :month_end AND check_out > :month_start)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':room_id'     => $room_id,
    ':month_start' => $month_start,
    ':month_end'   => $month_end,
]);
$reservations = $stmt->fetchAll();

$day_status = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $day_status[$d] = 'free';
}

foreach ($reservations as $res) {
    $ci = new DateTime($res['check_in']);
    $co = new DateTime($res['check_out']);

    for ($d = 1; $d <= $days_in_month; $d++) {
        $current = new DateTime("$year-$month-$d");

        if ($current >= $ci && $current < $co) {
            if ($res['status'] === 'confirmed') {
                $day_status[$d] = 'confirmed';
            } elseif ($res['status'] === 'pending' && $day_status[$d] !== 'confirmed') {
                $day_status[$d] = 'pending';
            }
        }
    }
}

$prev_month = $month - 1;
$prev_year  = $year;
$next_month = $month + 1;
$next_year  = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
if ($next_month > 12) { $next_month = 1; $next_year++; }

$currentScript = htmlspecialchars($_SERVER['PHP_SELF']);
$prev_url = $currentScript . '?room_id=' . $room_id . '&year=' . $prev_year . '&month=' . $prev_month;
$next_url = $currentScript . '?room_id=' . $room_id . '&year=' . $next_year . '&month=' . $next_month;
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="calendar-display">
    <div class="calendar-header">
        <button class="nav-month prev-month"
                onclick="window.location='<?php echo $prev_url; ?>'">&lt;</button>

        <span><?php echo $month_name . ' ' . $year; ?></span>

        <button class="nav-month next-month"
                onclick="window.location='<?php echo $next_url; ?>'">&gt;</button>
    </div>

    <div class="calendar-grid">
        <div class="day header">Po</div>
        <div class="day header">Út</div>
        <div class="day header">St</div>
        <div class="day header">Čt</div>
        <div class="day header">Pá</div>
        <div class="day header">So</div>
        <div class="day header">Ne</div>

        <?php
        for ($i = 1; $i < $first_weekday; $i++) {
            echo '<div class="day blank"></div>';
        }
        for ($d = 1; $d <= $days_in_month; $d++) {
            $status = $day_status[$d];
            if ($status === 'confirmed') {
                $class = 'day booked';     
            } elseif ($status === 'pending') {
                $class = 'day pending';   
            } else {
                $class = 'day available'; 
            }

            echo '<div class="' . $class . '">' . $d . '</div>';
        }
        ?>
    </div>
</div>

</body>
</html>