<?php
// Připojení k databázi (ANONYMIZOVÁNO)
$db_host = 'localhost';
$db_name = 'database_name';
$db_user = 'database_user'; 
$db_pass = 'database_password';    
$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch();

if (!$room) {
    die("Pokoj nenalezen.");
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail <?= htmlspecialchars($room['name']) ?> | Penzion Oasis</title>
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

    <main class="content-section" style="padding-top: 40px;">
        <div class="container">
            <h1 style="font-size: 2em; color: #3A5A40; text-align: left; margin-bottom: 30px;">
                <?= htmlspecialchars($room['name']) ?>
            </h1>

          <div class="room-detail-layout">
    
    <div class="gallery-section">
        
        <div class="main-image-container">
            <img id="main-room-image" src="Fotky pokoj <?= $id ?>/pokoj<?= $id ?>.jpg" alt="Hlavní obrázek pokoje <?= $id ?>">

        <button id="prev-button" class="nav-arrow left-arrow">
                &lt; </button>
             <button id="next-button" class="nav-arrow right-arrow">
                &gt; </button>
        </div>
        
        <div class="thumbnail-gallery">
            <?php
            $folder = "Fotky pokoj $id/";
            if (is_dir($folder)) {
                $images = glob($folder . "*.{jpg,jpeg,png,JPG,JPEG,PNG}", GLOB_BRACE);
                foreach ($images as $index => $img) {
                    $active = ($index === 0) ? 'active' : '';
                    echo '<img class="thumbnail '.$active.'" src="'.$img.'" data-full-image="'.$img.'" alt="Náhled">';
                }
            } else {
                echo '<img class="thumbnail active" src="Fotky pokoj '.$id.'/pokoj'.$id.'.jpg" data-full-image="Fotky pokoj '.$id.'/pokoj'.$id.'.jpg" alt="Náhled 1">';
            }
            ?>
        </div>

                    <div style="margin-top: 40px;">
                        <h3>Detailní popis pokoje</h3>
                        <p><?= nl2br(htmlspecialchars($room['long_description'] ?? '')) ?></p>

                        <h3 style="margin-top: 30px;">Vybavení</h3>
                        <ul style="list-style: none; padding-left: 0; display: flex; flex-wrap: wrap; gap: 15px 30px;">
                            <?php 
                            $vybaveni = explode("\n", trim($room['equipment'] ?? ''));
                            foreach ($vybaveni as $item): 
                                if (trim($item)): 
                            ?>
                                <li style="flex-basis: 45%;">• <?= htmlspecialchars(trim($item)) ?></li>
                            <?php 
                                endif; 
                            endforeach; 
                            ?>
                        </ul>
                    </div>

                </div>

                <div class="room-booking-aside">
                
                <div class="price-summary-card">
                    <h4>Cena a Dostupnost</h4>
                    <div class="summary-line" style="font-size: 1.3em;">
                        <span class="label">Základní cena:</span>
                        <span class="value" style="font-weight: bold;"><?= number_format($room['base_price_per_night'], 0, ',', ' ') ?> Kč / noc</span>
                    </div>
                    <div class="summary-line">
                        <span class="label">Max. počet lůžek:</span>
                        <span class="value"><?= $room['max_beds'] ?? 4 ?> osoby</span>
                    </div>
                    
                    <hr>

<div class="calendar-display">
    <iframe src="calendar-detail.php?room_id=<?= $id ?>"
            style="border:none;width:100%;height:310px;"></iframe>
</div>

                    
                    <div class="calendar-legend">
                        <div class="legend-item"><span class="color-box available"></span> Volný</div>
                        <div class="legend-item"><span class="color-box booked"></span> Obsazeno</div>
                        <div class="legend-item"><span class="color-box pending"></span> Blokace/Předrezervace</div>
                    </div>

                    <hr>

                    <a href="rezervace.php?room_id=<?= $id ?>" class="btn btn-primary" style="width: 86%; margin-top: 15px;">
                        Rezervovat tento pokoj
                    </a>
                    
                    <p class="small-info" style="text-align: center; margin-top: 10px; color: #777;">Indiviuální rezervaci lze řešit telefonickou domlouvou.</p>
                </div>
                
                <div class="small-info-box">
    <h4>Proč rezervovat u nás?</h4>
    <p><strong>Zrušení zdarma</strong> až 48 hodin před příjezdem.</p>
    <p><strong>Nejlepší cena</strong> garantována při rezervaci přímo.</p>
    <p><strong>Wi-Fi a parkování</strong> pro hosty zdarma.</p>
</div>
            </div>
            
        </div> </div> </main>
            </div>
        </div>
    </main>
    <footer>
        <div class="container footer-grid">
            <div class="footer-column">
                <h4>Penzion Oasis</h4>
                <p>Klidný odpočinek v srdci přírody.</p>
                <p>Adresa: Ulice 123, 123 45 Město</p>
                <br>
                <a href="#" class="iubenda-white iubenda-noiframe iubenda-embed iubenda-noiframe " title="Privacy Policy ">Privacy Policy</a>
                <a href="#" class="iubenda-white iubenda-noiframe iubenda-embed iubenda-noiframe " title="Cookie Policy ">Cookie Policy</a>
            </div>
            <div class="footer-column">
                <h4>Rychlé odkazy</h4>
                <ul>
                    <li><a href="o-nas.html">O nás</a></li>
                    <li><a href="pokoje.html">Pokoje a Ceny</a></li>
                    <li><a href="kontakt.html">Kontakt</a></li>

                   
                </ul>
            </div>
            <div class="footer-column">
                <h4>Kontaktujte nás</h4>
                <p>Email: info@vasedomena.cz</p>
                <p>Telefon: +420 123 456 789</p>
                <p>Provozní doba: Po-Ne 8:00 - 20:00</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Penzion Oasis. Všechna práva vyhrazena.</p>
        </div>
    </footer>

    <script src="detail-pokoj.js">
    
    </script> 
    </body>
</html>