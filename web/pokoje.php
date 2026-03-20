<?php
// Připojení k databázi (ANONYMIZOVÁNO)
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
    <title>Pokoje a Ceny | Penzion Oasis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            <h2 style="margin-bottom: 10px;">Naše ubytování</h2>
            
            <div class="rooms-page-layout">
               <button id="mobile-filter-toggle" class="mobile-filter-btn">▼ Zobrazit filtry</button>
                <aside id="filters-sidebar" class="filters-sidebar res-card" style="padding: 25px;">
                     
                    <div style="margin-bottom: 20px;">
                        <label class="form-label">Termín pobytu</label>
                        <div class="date-filter-row">
                            <div>
                                <span class="date-label">Příjezd (Od)</span>
                                <input type="date" id="filter-date-start" class="modern-input" style="margin-bottom:0;">
                            </div>
                            <div>
                                <span class="date-label">Odjezd (Do)</span>
                                <input type="date" id="filter-date-end" class="modern-input" style="margin-bottom:0;">
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label class="form-label">Počet osob</label>
                        <input type="number" id="filter-persons" class="modern-input" placeholder="Např. 2" min="1" value="1">
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label class="form-label">Cena za noc (max)</label>
                        <input type="range" id="filter-price" min="1000" max="3000" value="3000" step="100">
                        <span id="price-val" style="font-weight:600; color:#3A5A40; display:block; margin-top:5px;">3000 Kč</span>
                    </div>

                    <button id="filter-submit" class="btn btn-primary" style="width:100%; justify-content:center;">Filtrovat</button>
                    
                    <button id="filter-reset" class="btn btn-reset" style="width:100%; justify-content:center; padding:10px; font-size:0.9em;">Zrušit filtry</button>
                </aside>


                <div class="rooms-grid">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM rooms WHERE is_active = 1 ORDER BY id ASC");

                    while ($room = $stmt->fetch()):
                        $short_desc = mb_strimwidth($room['long_description'] ?? 'Útulný pokoj s veškerým vybavením.', 0, 110, "...");
                        $beds = $room['max_beds'] ?? 2;
                        $price = $room['base_price_per_night'];
                        $id = $room['id'];
                    ?>
                    
                    <div class="room-card-vertical" data-beds="<?= $beds ?>" data-price="<?= $price ?>" data-room-id="<?= $id ?>">
                        <div class="room-card-img-wrapper">
                            <span class="room-badge"><?= htmlspecialchars($room['badge_text']) ?></span>
                            <img src="Fotky pokoj <?= $id ?>/pokoj<?= $id ?>.jpg" alt="Pokoj <?= $id ?>" onerror="this.src='Fotky pokoj <?= $id ?>/11.jpeg';">
                        </div>
                        <div class="room-card-body">
                            <h3><?= htmlspecialchars($room['name']) ?></h3>
                            <p style="font-size:0.9em; color:#666; margin-bottom:15px; flex-grow:1;">
                                <?= htmlspecialchars($short_desc) ?>
                            </p>
                            <div class="room-meta-icons">
                                <span>Max <?= $beds ?> osoby</span>
                                <span class="room-price-highlight">od <?= number_format($price, 0, ',', ' ') ?> Kč/noc</span>
                            </div>
                            <a href="pokoj.php?id=<?= $id ?>" class="btn btn-primary" style="text-align:center;">Detail a rezervace</a>
                        </div>
                    </div>

                    <?php endwhile; ?>
                </div>

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
    const btn = document.getElementById('mobile-filter-toggle');
    const sidebar = document.getElementById('filters-sidebar');

    if (btn && sidebar) {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); 
            if (sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                btn.innerHTML = '▼ Zobrazit filtry';
            } else {
                sidebar.classList.add('active');
                btn.innerHTML = '▲ Skrýt filtry';
            }
        });
    }

    const filterBtn = document.getElementById('filter-submit');
    const resetBtn = document.getElementById('filter-reset');
    const priceSlider = document.getElementById('filter-price');
    const priceDisplay = document.getElementById('price-val');
    const personsInput = document.getElementById('filter-persons');
    const dateStartInput = document.getElementById('filter-date-start');
    const dateEndInput = document.getElementById('filter-date-end');
    const roomCards = document.querySelectorAll('.room-card-vertical');

    if (priceSlider && priceDisplay) {
        priceSlider.addEventListener('input', function() {
            priceDisplay.textContent = this.value + ' Kč';
        });
    }

    async function filterRooms() {
        const maxPrice = parseInt(priceSlider.value) || 10000;
        const requiredPersons = parseInt(personsInput.value) || 1;
        const checkIn = dateStartInput.value;
        const checkOut = dateEndInput.value;

        const originalText = filterBtn.textContent;
        filterBtn.textContent = "Ověřuji...";
        filterBtn.disabled = true;

        let occupiedIds = [];

        if (checkIn && checkOut) {
            try {
                const response = await fetch(`check_availability.php?check_in=${checkIn}&check_out=${checkOut}`);
                const data = await response.json();
                if (data.occupied_ids) occupiedIds = data.occupied_ids.map(id => id.toString());
            } catch (error) { console.error(error); }
        }

        let visibleCount = 0;
        roomCards.forEach(card => {
            const cardPrice = parseInt(card.getAttribute('data-price'));
            const cardBeds = parseInt(card.getAttribute('data-beds'));
            const cardId = card.getAttribute('data-room-id');
            let isVisible = true;

            if (cardPrice > maxPrice) isVisible = false;
            if (cardBeds < requiredPersons) isVisible = false;
            if (occupiedIds.includes(cardId)) isVisible = false;

            if (isVisible) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        if (checkIn && checkOut) {
            document.querySelectorAll('.btn-primary').forEach(btn => {
                if (btn.tagName === 'A' && btn.href.includes('pokoj.php')) {
                    try {
                        const url = new URL(btn.href);
                        url.searchParams.set('check_in', checkIn);
                        url.searchParams.set('check_out', checkOut);
                        btn.href = url.toString();
                    } catch(e) {}
                }
            });
        }

        filterBtn.textContent = originalText;
        filterBtn.disabled = false;

        if (visibleCount === 0) alert("Žádný pokoj nevyhovuje filtrům.");
    }

    function resetFilters() {
        dateStartInput.value = '';
        dateEndInput.value = '';
        personsInput.value = 1;
        priceSlider.value = 3000;
        priceDisplay.textContent = '3000 Kč';
        roomCards.forEach(card => card.style.display = 'flex');
    }

    if (filterBtn) filterBtn.addEventListener('click', (e) => { e.preventDefault(); filterRooms(); });
    if (resetBtn) resetBtn.addEventListener('click', (e) => { e.preventDefault(); resetFilters(); });


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