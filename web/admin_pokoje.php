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
    
    if ($_POST['action'] === 'update_room') {
        $stmt = $pdo->prepare("UPDATE rooms SET name = ?, badge_text = ?, long_description = ?, equipment = ?, max_beds = ?, capacity = ?, base_price_per_night = ?, is_active = ? WHERE id = ?");
        $name = trim($_POST['name']);
        $badge = trim($_POST['badge_text']);
        $beds = (int)$_POST['max_beds'];
        $is_active = isset($_POST['is_active']) ? 1 : 0; 
        
        $stmt->execute([$name, $badge, trim($_POST['long_description']), trim($_POST['equipment']), $beds, $beds, (int)$_POST['price'], $is_active, (int)$_POST['room_id']]);
        $message = "<div class='alert alert-success'>Údaje pro Pokoj č. ".$_POST['room_id']." byly úspěšně uloženy.</div>";
    }
    
    elseif ($_POST['action'] === 'upload_photos') {
        $room_id = (int)$_POST['room_id'];
        $target_dir = "Fotky pokoj $room_id/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $uploaded_count = 0;
        if (!empty($_FILES['photos']['name'][0])) {
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                $file_name = basename($_FILES['photos']['name'][$key]);
                $target_file = $target_dir . $file_name;
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $uploaded_count++;
                }
            }
            $message = "<div class='alert alert-success'>Bylo nahráno $uploaded_count fotek.</div>";
        }
    }

    elseif ($_POST['action'] === 'delete_photo') {
        $photo_path = $_POST['photo_path'];
        if (file_exists($photo_path)) {
            unlink($photo_path);
            $message = "<div class='alert alert-success'>Fotografie byla smazána.</div>";
        }
    }

    elseif ($_POST['action'] === 'add_room') {
        $stmt = $pdo->prepare("INSERT INTO rooms (name, badge_text, long_description, equipment, max_beds, capacity, base_price_per_night, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $name = trim($_POST['name']);
        $badge = trim($_POST['badge_text']);
        $beds = (int)$_POST['max_beds'];
        $stmt->execute([$name, $badge, trim($_POST['long_description']), trim($_POST['equipment']), $beds, $beds, (int)$_POST['price']]);
        
        $new_room_id = $pdo->lastInsertId(); 
        $target_dir = "Fotky pokoj $new_room_id/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $default_source = "default.jpg"; 
        if (file_exists($default_source)) {
            copy($default_source, $target_dir . "pokoj" . $new_room_id . ".jpg");
        }

        $message = "<div class='alert alert-success'>Nový pokoj byl úspěšně přidán.</div>";
    }
    
    elseif ($_POST['action'] === 'delete_room') {
        try {
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([(int)$_POST['room_id']]);
            header("Location: admin_pokoje.php?deleted=1");
            exit;
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Pokoj nelze smazat (existují na něj rezervace).</div>";
        }
    }
}

if (isset($_GET['deleted'])) $message = "<div class='alert alert-success'>Pokoj byl úspěšně smazán.</div>";
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrace pokojů | Penzion Oasis</title>
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
                $r = ['id'=>'','name'=>'','badge_text'=>'Pokoj','long_description'=>'','equipment'=>'','max_beds'=>2,'base_price_per_night'=>1200];
                if ($isEdit) {
                    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
                    $stmt->execute([(int)$_GET['id']]);
                    $r = $stmt->fetch();
                }
            ?>
                <div style="text-align: left; margin-bottom: 20px;">
                    <a href="admin_pokoje.php" class="btn btn-secondary" style="display:inline-block; text-decoration: none;">&larr; Zpět na seznam</a>
                </div>

                <div class="res-card">
                    <h2 style="margin-top:0; color: #3A5A40; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 25px;">
                        <?= $isEdit ? "Detail pokoje č. ".$r['id'] : "Nový pokoj" ?>
                    </h2>


                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?= $isEdit ? 'update_room' : 'add_room' ?>">
                        <?php if($isEdit): ?><input type="hidden" name="room_id" value="<?= $r['id'] ?>"><?php endif; ?>
                        <div style="margin-top: 15px; background: #f1f8f1; padding: 15px; border-left: 3px solid #3A5A40; border-radius: 6px;">
                                <label style="display: flex; align-items: center; cursor: pointer; margin: 0; color: #3A5A40;">
                                    <input type="checkbox" name="is_active" value="1" <?= (!isset($r['is_active']) || $r['is_active'] == 1) ? 'checked' : '' ?> style="width: 20px; height: 20px; margin: 0 10px 0 0;">
                                    Pokoj je aktivní (lze ho na webu rezervovat)
                                </label>
                            </div>
                        <div class="detail-box">
                        
                            <label>Název pokoje</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($r['name']) ?>" required>

                            <label>Štítek na fotce</label>
                            <input type="text" name="badge_text" value="<?= htmlspecialchars($r['badge_text'] ?? 'Pokoj') ?>" required>

                            <div style="display: flex; gap: 20px;">
                                <div style="flex: 1;">
                                    <label>Cena za noc (Kč)</label>
                                    <input type="number" name="price" value="<?= $r['base_price_per_night'] ?>" required>
                                </div>
                                <div style="flex: 1;">
                                    <label>Maximální kapacita (osob)</label>
                                    <input type="number" name="max_beds" value="<?= $r['max_beds'] ?>" required>
                                </div>
                                
                            </div>
                            

                            <label>Dlouhý text (popis v detailu na webu)</label>
                            <textarea name="long_description" rows="6"><?= htmlspecialchars($r['long_description']) ?></textarea>

                            <label>Vybavení (každý řádek jedna položka)</label>
                            <textarea name="equipment" rows="4"><?= htmlspecialchars($r['equipment']) ?></textarea>
                            
                            <?php if ($isEdit): ?>
                            <div  style="border-left-color: #28a745; margin-top: 30px; padding-top: 20px;">
                                <label style="margin-top: 0;">Fotogalerie pokoje</label>
                                
                                
                                <div style="background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 20px;">
                                    <input type="file" name="photos[]" multiple accept="image/*" style="margin-bottom: 10px; width: auto;" class="file-input">
                                    <button type="submit" name="action" value="upload_photos" class="btn btn-success" style="padding: 8px 20px;">Nahrát vybrané fotky</button>
                                </div>

                                <div class="admin-gallery">
                                    <?php
                                    $folder = "Fotky pokoj " . $r['id'] . "/";
                                    if (file_exists($folder)) {
                                        $images = glob($folder . "*.{jpg,jpeg,png,JPG,JPEG,PNG}", GLOB_BRACE);
                                        foreach ($images as $img) {
                                            ?>
                                            <div class="admin-photo-item">
                                                <img src="<?= $img ?>">
                                                <button type="submit" name="action" value="delete_photo" class="del-photo-btn" onclick="document.getElementById('photo_to_delete').value='<?= $img ?>'; return confirm('Smazat tuto fotku?');" title="Smazat fotku">X</button>
                                            </div>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <input type="hidden" name="photo_path" id="photo_to_delete" value="">
                            </div>
                            <?php endif; ?>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                                <div>
                                    <?php if ($isEdit): ?>
                                    <button type="submit" name="action" value="delete_room" class="btn btn-danger" onclick="return confirm('Opravdu chcete tento pokoj zcela smazat z databáze?');">Smazat tento pokoj</button>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" name="action" value="<?= $isEdit ? 'update_room' : 'add_room' ?>" class="btn btn-success" style="padding: 12px 40px; font-weight: bold;">Uložit změny údajů</button>
                            </div>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h1 style="color: #3A5A40; margin: 0;">Přehled pokojů</h1>
                    <a href="admin_pokoje.php?add=1" class="btn btn-success" style="text-decoration: none; background-color: #3A5A40;">+ Přidat nový pokoj</a>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Název / Typ</th>
                            <th>Lůžka</th>
                            <th>Cena / Noc</th>
                            <th>Stav</th>
                            <th style="text-align: right; padding-right: 35px;">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->query("SELECT * FROM rooms ORDER BY id ASC");
                        while ($row = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><strong><?= $row['id'] ?></strong></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= $row['max_beds'] ?> os.</td>
                            <td><?= number_format($row['base_price_per_night'], 0, ',', ' ') ?> Kč</td>
                            <td>
                                <?php if (!isset($row['is_active']) || $row['is_active'] == 1): ?>
                                    <span style="background-color: #d4edda; color: #155724; padding: 4px 10px; border-radius: 20px; font-size: 0.85em; font-weight: 600; border: 1px solid #c3e6cb;">Aktivní</span>
                                <?php else: ?>
                                    <span style="background-color: #f8d7da; color: #721c24; padding: 4px 10px; border-radius: 20px; font-size: 0.85em; font-weight: 600; border: 1px solid #f5c6cb;">Skrytý</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="admin_pokoje.php?id=<?= $row['id'] ?>" class="btn btn-secondary" style="padding: 8px 15px; font-size: 0.85em; text-decoration: none;">Detail</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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