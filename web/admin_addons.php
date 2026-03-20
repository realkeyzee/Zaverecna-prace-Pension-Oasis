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

    if ($_POST['action'] === 'add_addon') {
        $stmt = $pdo->prepare("INSERT INTO addons (name, price, per_night) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['name']), (int)$_POST['price'], (int)$_POST['per_night']]);
        $message = "<div class='alert alert-success'>Nový doplněk byl úspěšně přidán.</div>";
    } 
    
    elseif ($_POST['action'] === 'update_addon') {
        $stmt = $pdo->prepare("UPDATE addons SET name = ?, price = ?, per_night = ? WHERE id = ?");
        $stmt->execute([trim($_POST['name']), (int)$_POST['price'], (int)$_POST['per_night'], (int)$_POST['addon_id']]);
        $message = "<div class='alert alert-success'>Doplněk byl úspěšně upraven.</div>";
    }

    elseif ($_POST['action'] === 'delete_addon') {
        $stmt = $pdo->prepare("DELETE FROM addons WHERE id = ?");
        $stmt->execute([(int)$_POST['addon_id']]);
        header("Location: admin_addons.php?deleted=1");
        exit;
    }
}

if (isset($_GET['deleted'])) $message = "<div class='alert alert-success'>Doplněk byl úspěšně smazán.</div>";
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrace doplňků | Penzion Oasis</title>
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
                $r = ['id'=>'', 'name'=>'', 'price'=>0, 'per_night'=>1];
                if ($isEdit) {
                    $stmt = $pdo->prepare("SELECT * FROM addons WHERE id = ?");
                    $stmt->execute([(int)$_GET['id']]);
                    if($fetched = $stmt->fetch()) {
                        $r = $fetched;
                    }
                }
            ?>
                <div style="text-align: left; margin-bottom: 20px;">
                    <a href="admin_addons.php" class="btn btn-secondary" style="display:inline-block;">&larr; Zpět na seznam</a>
                </div>

                <div class="res-card">
                    <h2 style="margin-top:0; color: #3A5A40; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 25px;">
                        <?= $isEdit ? "Úprava doplňku" : "Nový doplněk" ?>
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $isEdit ? 'update_addon' : 'add_addon' ?>">
                        <?php if($isEdit): ?><input type="hidden" name="addon_id" value="<?= $r['id'] ?>"><?php endif; ?>
                        
                        <div class="detail-box">
                            <label>Název služby / doplňku (např. Dětská postýlka, Pes)</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($r['name']) ?>" required>

                            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label>Cena (Kč)</label>
                                    <input type="number" name="price" value="<?= $r['price'] ?>" required>
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <label>Typ účtování</label>
                                    <select name="per_night" required>
                                        <option value="1" <?= $r['per_night'] == 1 ? 'selected' : '' ?>>Za každou noc pobytu</option>
                                        <option value="0" <?= $r['per_night'] == 0 ? 'selected' : '' ?>>Jednorázově za celý pobyt (ZDARMA zadejte cenu 0)</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                                <div>
                                    <?php if ($isEdit): ?>
                                    <button type="button" class="btn btn-danger" onclick="if(confirm('Opravdu chcete tento doplněk smazat? Zmizí i z formuláře pro nové rezervace.')) { document.getElementById('deleteForm').submit(); }">Smazat doplněk</button>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn btn-success" style="padding: 12px 40px; font-weight: bold;">Uložit doplněk</button>
                            </div>
                        </div>
                    </form>

                    <?php if ($isEdit): ?>
                    <form method="POST" id="deleteForm" style="display: none;">
                        <input type="hidden" name="action" value="delete_addon">
                        <input type="hidden" name="addon_id" value="<?= $r['id'] ?>">
                    </form>
                    <?php endif; ?>

                </div>

            <?php else: ?>
                <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h1 style="color: #3A5A40; margin: 0;">Přehled doplňkových služeb</h1>
                    <a href="admin_addons.php?add=1" class="btn btn-success" style="text-decoration: none; background-color: #3A5A40;">+ Přidat doplněk</a>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Název doplňku</th>
                            <th>Cena</th>
                            <th>Účtování</th>
                            <th style="text-align: right; padding-right: 35px;">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->query("SELECT * FROM addons ORDER BY id ASC");
                        $addons = $stmt->fetchAll();
                        
                        if (count($addons) > 0):
                            foreach ($addons as $row):
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td><?= $row['price'] == 0 ? '<span style="color: #28a745; font-weight: bold;">ZDARMA</span>' : number_format($row['price'], 0, ',', ' ') . ' Kč' ?></td>
                            <td><?= $row['per_night'] == 1 ? 'Za noc' : 'Za pobyt (jednorázově)' ?></td>
                            <td style="text-align: right;">
                                <a href="admin_addons.php?id=<?= $row['id'] ?>" class="btn btn-secondary" style="padding: 8px 15px; font-size: 0.85em; text-decoration: none;">Upravit</a>
                            </td>
                        </tr>
                        <?php 
                            endforeach; 
                        else:
                        ?>
                            <tr><td colspan="4" style="text-align: center; color: #666; padding: 20px;">Zatím nemáte vytvořené žádné doplňky.</td></tr>
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