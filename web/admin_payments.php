<?php
session_start();

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

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add_payment') {
        $stmt = $pdo->prepare("INSERT INTO payment_methods (method_value, name) VALUES (?, ?)");
        $stmt->execute([trim($_POST['method_value']), trim($_POST['name'])]);
        $message = "<div class='alert alert-success'>Nová platební metoda byla úspěšně přidána.</div>";
    } 
    
    elseif ($_POST['action'] === 'update_payment') {
        $stmt = $pdo->prepare("UPDATE payment_methods SET method_value = ?, name = ? WHERE id = ?");
        $stmt->execute([trim($_POST['method_value']), trim($_POST['name']), (int)$_POST['payment_id']]);
        $message = "<div class='alert alert-success'>Platební metoda byla úspěšně upravena.</div>";
    } 
    
    elseif ($_POST['action'] === 'delete_payment') {
        try {
            $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
            $stmt->execute([(int)$_POST['payment_id']]);
            header("Location: admin_payments.php?deleted=1");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $message = "<div class='alert alert-danger'><b>Nelze smazat:</b> Tuto platební metodu již používají existující rezervace. Pokud ji nechcete dále nabízet, doporučujeme jí pouze změnit 'Systémovou hodnotu' např. na 'zruseno_transfer'.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Chyba při mazání: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

if (isset($_GET['deleted'])) $message = "<div class='alert alert-success'>Způsob platby byl úspěšně smazán.</div>";
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrace plateb | Penzion Oasis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/jpeg" href="miniature.PNG">
    <link rel="stylesheet" href="style.css">
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

            <?php if (isset($_GET['id']) || isset($_GET['add'])): 
                $isEdit = isset($_GET['id']);
                $pm = ['id'=>'', 'method_value'=>'', 'name'=>''];
                if ($isEdit) {
                    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ?");
                    $stmt->execute([(int)$_GET['id']]);
                    $pm = $stmt->fetch();
                }
            ?>
                <div style="text-align: left; margin-bottom: 20px;">
                    <a href="admin_payments.php" class="btn btn-secondary" style="display:inline-block; text-decoration: none;">&larr; Zpět na seznam</a>
                </div>

                <div class="res-card">
                    <h2 style="margin-top:0; color: #3A5A40; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 25px;">
                        <?= $isEdit ? "Úprava platební metody" : "Nová platební metoda" ?>
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $isEdit ? 'update_payment' : 'add_payment' ?>">
                        <?php if($isEdit): ?><input type="hidden" name="payment_id" value="<?= $pm['id'] ?>"><?php endif; ?>
                        
                        <div class="detail-box">
                            <label>Zobrazovaný název (pro zákazníka)</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($pm['name']) ?>" required placeholder="např. Bankovní převod">

                            <label>Systémová hodnota (bez mezer a diakritiky)</label>
                            <input type="text" name="method_value" value="<?= htmlspecialchars($pm['method_value']) ?>" required placeholder="např. transfer">

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                                <div>
                                    <?php if ($isEdit): ?>
                                    <button type="submit" name="action" value="delete_payment" class="btn btn-danger" onclick="return confirm('Opravdu chcete tuto platbu zcela smazat z databáze? Může to ovlivnit staré rezervace.');">Smazat</button>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn btn-success" style="padding: 12px 40px; font-weight: bold;">Uložit</button>
                            </div>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h1 style="color: #3A5A40; margin: 0;">Způsoby platby</h1>
                    <a href="admin_payments.php?add=1" class="btn btn-success" style="text-decoration: none; background-color: #3A5A40;">+ Přidat platební metodu</a>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Zobrazovaný název</th>
                            <th>Systémová hodnota</th>
                            <th style="text-align: right; padding-right: 35px;">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY id ASC");
                        $payments = $stmt->fetchAll();
                        if (count($payments) > 0):
                            foreach ($payments as $row):
                        ?>
                        <tr>
                            <td><strong><?= $row['id'] ?></strong></td>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td style="color: #666;"><em><?= htmlspecialchars($row['method_value']) ?></em></td>
                            <td style="text-align: right;">
                                <a href="admin_payments.php?id=<?= $row['id'] ?>" class="btn btn-secondary" style="padding: 8px 15px; font-size: 0.85em; text-decoration: none;">Upravit</a>
                            </td>
                        </tr>
                        <?php 
                            endforeach; 
                        else:
                        ?>
                            <tr><td colspan="4" style="text-align: center; color: #666; padding: 20px;">Zatím nemáte vytvořené žádné způsoby platby.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

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
</body>
</html>