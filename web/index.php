<?php
// ========= NASTAVENÍ DB (ANONYMIZOVÁNO) =========
$db_host = 'localhost';
$db_name = 'database_name';
$db_user = 'database_user'; 
$db_pass = 'database_password';    
$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penzion Oasis | Klidný odpočinek v přírodě</title>
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


    <section id="hero" class="hero-section">
        <div class="hero-content">
            <h1>Vítejte v Penzionu Oasis</h1>
            <p>Střednědobé ubytování v klidném prostředí.</p>
            <a href="pokoje.php" class="btn btn-primary">Rezervovat pobyt</a>
        </div>
    </section>
    
    <section id="pokoje-prehled" class="content-section">
        <div class="container">
            <h2>Náhledy na naše pokoje</h2>
            <div class="room-preview-grid">
                
                <?php
     
                $stmt = $pdo->query("SELECT * FROM rooms ORDER BY id ASC LIMIT 3");
                while ($room = $stmt->fetch()):
                    $id = $room['id'];
        
                    $short_desc = mb_strimwidth($room['long_description'] ?? 'Příjemné ubytování v našem penzionu.', 0, 75, "...");
                ?>
                <div class="room-preview-card">
                    <img src="Fotky pokoj <?= $id ?>/pokoj<?= $id ?>.jpg" alt="Pokoj <?= $id ?>" onerror="this.src='Fotky pokoj <?= $id ?>/11.jpeg';">
                    <div class="room-preview-card-content">
                        <h3><?= htmlspecialchars($room['name']) ?></h3>
                        <p><?= htmlspecialchars($short_desc) ?></p>
                        <a href="pokoj.php?id=<?= $id ?>" class="btn btn-secondary">Detail pokoje</a>
                    </div>
                </div>
                <?php endwhile; ?>

            </div>
        </div>
    </section>

    <section id="proc-nas-vybrat" class="content-section" style="background-color: #f0f0e8;">
        <div class="container">
            <h2>Proč si vybrat právě nás?</h2>
            <div class="icon-text-grid">
                <div class="icon-text-block">
                    <h3>Na dosah divoké přírody</h3>
                    <p>Náš penzion je kousek od unikátní rezervace divokých koní, praturů a zubrů. Zapomeňte na klasické zoo – u nás zažijete atmosféru evropské savany na vlastní oči. Je to ideální místo pro milovníky přírody a fotografy, kteří hledají klid a neokoukané výhledy do krajiny, která se vrací ke svým kořenům.</p>
                </div>
                <div class="icon-text-block">
                    <h3>Ráj pro aktivní rodiny a dobrodruhy</h3>
                    <p>Poloha na kraji Milovic nabízí nevyčerpatelné možnosti. Během pár minut jste v rodinném parku Mirakulum, na tankodromu nebo na cyklostezkách, které vedou rovinatou krajinou vhodnou i pro nejmenší cyklisty. Jsme ideální základnou pro ty, kteří chtějí každý den zažít nové dobrodružství, ale večer se rádi vrátí do klidu a pohodlí.</p>
                </div>
                <div class="icon-text-block">
                    <h3>Místo s fascinující historií</h3>
                    <p>Milovice mají nesmírně zajímavou historii, která se dříve psala především v uniformách. Město prošlo úžasnou proměnou a náš penzion je její součástí. Pokud vás zajímá, jak moderní město vypadalo na sklonku 20. století, doporučujeme navštívit místní městské muzeum, které vám odhalí tajemství této unikátní lokality.</p>
                </div>
                <div class="icon-text-block">
                    <h3>Skvělá dostupnost a spojení</h3>
                    <p>Díky výbornému a přímému vlakovému spojení můžete auto nechat v klidu u nás. Do centra Prahy se dostanete pohodlně a bez kolon za necelou hodinu. Jsme perfektní volbou pro ty, kteří chtějí kombinovat klidné ubytování u přírody s výlety do metropole nebo za památkami v okolí středních Čech.</p>
                </div>
            </div>
        </div>
    </section>

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
                    <li><a href="onas.html">O nás</a></li>
                    <li><a href="pokoje.php">Pokoje a Ceny</a></li>
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