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

$stmtRooms = $pdo->query("SELECT id, name, base_price_per_night FROM rooms WHERE is_active = 1 ORDER BY id ASC");
$roomsDB = $stmtRooms->fetchAll();

$addonsDB = [];
try {
    $stmtAddons = $pdo->query("SELECT * FROM addons ORDER BY id ASC");
    $addonsDB = $stmtAddons->fetchAll();
} catch (PDOException $e) {

    $addonsDB = [
        ['name' => 'Pobyt se psem / kočkou', 'price' => 200, 'per_night' => 1],
        ['name' => 'Dětská postýlka (na vyžádání)', 'price' => 0, 'per_night' => 0]
    ];
}


$paymentsDB = [];
try {
    $stmtPayments = $pdo->query("SELECT * FROM payment_methods ORDER BY id ASC");
    $paymentsDB = $stmtPayments->fetchAll();
} catch (PDOException $e) {

    $paymentsDB = [
        ['method_value' => 'transfer', 'name' => 'Bankovní převod (předem)']
    ];
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervace | Penzion Oasis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/jpeg" href="miniature.PNG">
</head>
<script type="text/javascript">
var _iub = _iub || [];
_iub.csConfiguration = {"siteId":1234567,"cookiePolicyId":12345678,"storage":{"useSiteId":true}};
_iub.csLangConfiguration = {"cs":{"cookiePolicyId":12345678}};
</script>
<script type="text/javascript" src="https://cs.iubenda.com/autoblocking/1234567.js"></script>
<script type="text/javascript" src="//cdn.iubenda.com/cs/iubenda_cs.js" charset="UTF-8" async></script>
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

    <div class="steps-container">
        <div class="container">
            <ul class="steps-list">
                <li class="step active" id="progress-1"><span class="step-number">1</span><span class="step-title">Termín</span></li>
                <li class="step" id="progress-2"><span class="step-number">2</span><span class="step-title">Údaje</span></li>
                <li class="step" id="progress-3"><span class="step-number">3</span><span class="step-title">Doplňky</span></li>
                <li class="step" id="progress-4"><span class="step-number">4</span><span class="step-title">Platba</span></li>
                <li class="step" id="progress-5"><span class="step-number">5</span><span class="step-title">Souhrn</span></li>
            </ul>
        </div>
    </div>

    <main class="content-section" style=" min-height: 80vh;">
        <div class="container">
            
            <form action="process_booking.php" method="POST" id="mainForm">
                <input type="hidden" id="hidden-room-id" name="room_id" value="">
                
                <div class="reservation-layout-grid">
                    
                    <div class="res-main-content">
                        
                        <div class="step-content active" id="step-1">
                            <div class="res-card">
                                <h2 class="res-card-title">1. Výběr pokoje a termínu</h2>
                                
                                <label class="form-label">Vyberte pokoj</label>
                                <select id="room-selector" class="modern-select">
                                    <?php foreach ($roomsDB as $room): ?>
                                        <option value="<?= $room['id'] ?>">
                                            <?= htmlspecialchars($room['name']) ?> (<?= (int)$room['base_price_per_night'] ?> Kč/noc)
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="calendar-wrapper-modern" style="margin-bottom: 20px;">
                                    <iframe id="calendar-iframe" src="" width="100%" height="300px" frameborder="0"></iframe>
                                </div>
<p style="font-size: 0.8em;">*Rezervaci lze provést nejpozději dva dny dopředu a dva dny po skončení jiného pobytu, zároveň délka pobytu může být 7 - 30 nocí.</p>
                                <div class="dates-row">
                                
                                    <div class="form-group-half">
                                        <label class="form-label">Příjezd</label>
                                        <input type="date" id="check_in" name="check_in" required class="modern-input">
                                    </div>
                                    <div class="form-group-half">
                                        <label class="form-label">Odjezd</label>
                                        <input type="date" id="check_out" name="check_out" required class="modern-input">
                                    </div>
                                </div>
                                <div id="date-error-msg" style="display:none; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px;"></div>
                                <button type="button" class="btn btn-primary btn-block" onclick="nextStep(2)">Pokračovat na údaje &rarr;</button>
                            </div>
                        </div>

                        <div class="step-content" id="step-2">
                            <div class="res-card">
                                <h2 class="res-card-title">2. Osobní údaje</h2>
                                
                                <div class="form-row">
                                    <div class="form-group-half">
                                        <label class="form-label">Jméno</label>
                                        <input type="text" id="name" name="name" placeholder="Jan" required class="modern-input" pattern="[a-zA-ZáčďéěíňóřšťúůýžÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ\s\-]+" title="Jméno musí obsahovat pouze písmena">
                                    </div>
                                    <div class="form-group-half">
                                        <label class="form-label">Příjmení</label>
                                        <input type="text" id="surname" name="surname" placeholder="Novák" required class="modern-input" pattern="[a-zA-ZáčďéěíňóřšťúůýžÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ\s\-]+" title="Příjmení musí obsahovat pouze písmena">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group-half">
                                        <label class="form-label">Email</label>
                                        <input type="email" id="email" name="email" placeholder="jan@example.com" required class="modern-input">
                                    </div>
                                    <div class="form-group-half">
                                        <label class="form-label">Telefon</label>
                                        <input type="tel" id="phone" name="phone" placeholder="+420 123 456 789" required class="modern-input" pattern="[0-9+\s]{9,20}" title="Zadejte platné telefonní číslo">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group-half">
                                        <label class="form-label">Ulice a č.p.</label>
                                        <input type="text" id="street" name="street" placeholder="Lipová 15" required class="modern-input" pattern="^(?=.*\d)(?=.*[a-zA-ZáčďéěíňóřšťúůýžÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ]).+$" title="Musí obsahovat název ulice i číslo popisné">
                                    </div>
                                    <div class="form-group-half">
                                        <label class="form-label">Město</label>
                                        <input type="text" id="city" name="city" placeholder="Praha" required class="modern-input" pattern="[a-zA-ZáčďéěíňóřšťúůýžÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ\s\-]+" title="Město musí obsahovat pouze písmena">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group-half">
                                        <label class="form-label">PSČ</label>
                                        <input type="text" id="zip" name="zip" placeholder="110 00" required class="modern-input" pattern="\d{3}\s?\d{2}" title="PSČ musí obsahovat 5 číslic (např. 123 45)">
                                    </div>
                                    <div class="form-group-half">
                                        <label class="form-label">Stát</label>
                                        <input type="text" id="country" name="country" value="Česká republika" required class="modern-input" pattern="[a-zA-ZáčďéěíňóřšťúůýžÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ\s]+" title="Stát musí obsahovat pouze písmena">
                                    </div>
                                </div>

                                <label class="form-label">Poznámka</label>
                                <textarea name="message" rows="4" class="modern-input"></textarea>

                                <div style="margin-top: 15px; margin-bottom: 25px;">
                                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 0.9em; color: #555;">
                                        <input type="checkbox" name="gdpr_consent" id="gdpr_consent" required style="margin-top: 3px;">
                                        <span>Souhlasím se zpracováním osobních údajů pro účely vyřízení rezervace.</span>
                                    </label>
                                </div>

                                <div class="buttons-row">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(1)">&larr; Zpět</button>
                                    <button type="button" class="btn btn-primary" onclick="nextStep(3)">Pokračovat na doplňky &rarr;</button>
                                </div>
                            </div>
                        </div>

                        <div class="step-content" id="step-3">
                            <div class="res-card">
                                <h2 class="res-card-title">3. Doplňkové služby</h2>
                                
                                <?php foreach ($addonsDB as $addon): ?>
                                    <div class="addon-item">
                                        <label class="addon-label">
                                            <input type="checkbox" name="add_ons[]" class="addon-checkbox" 
                                                   value="<?= htmlspecialchars($addon['name']) ?>" 
                                                   data-price="<?= $addon['price'] ?>" 
                                                   data-pernight="<?= $addon['per_night'] ?>" 
                                                   onchange="updateSidebar()">
                                            <?= htmlspecialchars($addon['name']) ?>
                                        </label>
                                        <span class="addon-price">
                                            <?php 
                                            if ($addon['price'] > 0) {
                                                echo '+' . $addon['price'] . ' Kč' . ($addon['per_night'] ? ' / noc' : '');
                                            } else {
                                                echo 'ZDARMA';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>

                                <div class="buttons-row">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(2)">&larr; Zpět</button>
                                    <button type="button" class="btn btn-primary" onclick="nextStep(4)">Pokračovat na platbu &rarr;</button>
                                </div>
                            </div>
                        </div>

                        <div class="step-content" id="step-4">
                            <div class="res-card">
                                <h2 class="res-card-title">4. Způsob platby</h2>
                                
                                <?php $is_first_payment = true; foreach ($paymentsDB as $payment): ?>
                                    <div class="payment-item">
                                        <label class="addon-label">
                                            <input type="radio" name="payment_method" value="<?= htmlspecialchars($payment['method_value']) ?>" <?= $is_first_payment ? 'checked' : '' ?>>
                                            <span class="payment-name-text"><?= htmlspecialchars($payment['name']) ?></span>
                                        </label>
                                    </div>
                                <?php $is_first_payment = false; endforeach; ?>

                                <div class="buttons-row">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(3)">&larr; Zpět</button>
                                    <button type="button" class="btn btn-primary" onclick="nextStep(5)">Pokračovat na shrnutí &rarr;</button>
                                </div>
                            </div>
                        </div>

                        <div class="step-content" id="step-5">
                            <div class="res-card">
                                <h2 class="res-card-title">5. Kontrola a odeslání</h2>
                                
                                <div class="review-box">
                                    <strong>Host:</strong> <span id="review-name"></span><br>
                                    <strong>Adresa:</strong> <span id="review-address"></span><br>
                                    <strong>Email:</strong> <span id="review-email"></span><br>
                                    <strong>Telefon:</strong> <span id="review-phone"></span>
                                </div>

                                <div class="review-box">
                                    <strong>Pokoj:</strong> <span id="review-room"></span><br>
                                    <strong>Termín:</strong> <span id="review-dates"></span><br>
                                    <strong>Služby navíc:</strong> <span id="review-addons">Žádné</span><br>
                                    <strong>Platba:</strong> <span id="review-payment"></span>
                                </div>

                                <div style="margin-top: 20px; margin-bottom: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border: 1px solid #ffeeba;">
                                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 0.9em; color: #856404;">
                                        <input type="checkbox" name="terms_consent" id="terms_consent" required style="margin-top: 3px;">
                                        <span>
                                            Odesláním rezervace závazně souhlasím s 
                                            <a href="obchodni-podminky.html" target="_blank" style="text-decoration: underline; color: #856404; font-weight: bold;">obchodními podmínkami ubytování</a> 
                                            a storno podmínkami.
                                        </span>
                                    </label>
                                </div>

                                <div class="buttons-row">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(4)">&larr; Zpět</button>
                                    <button type="submit" class="btn btn-primary">ZÁVAZNĚ REZERVOVAT</button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="res-sidebar">
                        <div class="summary-card sticky-card">
                            <h3 class="summary-title">Vaše rezervace</h3>
                            <img id="main-preview-img" src="" style="width:100%; height:150px; object-fit:cover; border-radius:8px; margin-bottom:15px;">
                            
                            <div class="summary-details">
                                <div class="summary-row"><span>Pokoj:</span><strong id="sidebar-room-name">--</strong></div>
                                <div class="summary-row"><span>Termín:</span><strong id="sidebar-dates">--</strong></div>
                                <div class="summary-row"><span>Nocí:</span><strong id="sidebar-nights">0</strong></div>
                                
                                <div class="summary-row" id="sidebar-addons-row" style="display:none; color:#3A5A40;">
                                    <span>Příplatky:</span><strong id="sidebar-addons-price">+0 Kč</strong>
                                </div>
                                
                                <hr class="summary-divider">
                                <div class="summary-total">
                                    <span>Cena celkem:</span>
                                    <span class="price" id="sidebar-price">0 Kč</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
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
    const rooms = {
        <?php foreach ($roomsDB as $r): ?>
        "<?= $r['id'] ?>": { 
            name: "<?= addslashes($r['name']) ?>", 
            img: "Fotky pokoj <?= $r['id'] ?>/pokoj<?= $r['id'] ?>.jpg", 
            price: <?= (int)$r['base_price_per_night'] ?> 
        },
        <?php endforeach; ?>
    };

    const form = document.getElementById('mainForm');
    const selector = document.getElementById('room-selector');
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');

    window.handleCalendarClick = function(clickedDateStr) {
        const clickedDate = new Date(clickedDateStr);
        const today = new Date();
        today.setHours(0,0,0,0);
        
        const minArrivalDate = new Date(today);
        minArrivalDate.setDate(today.getDate() + 2);
        
        if (clickedDate < minArrivalDate) {
            alert("Rezervaci je nutné provést minimálně 2 dny předem.");
            return;
        }

        const currentCheckInStr = checkIn.value;
        const currentCheckOutStr = checkOut.value;

        if (!currentCheckInStr) {
            checkIn.value = clickedDateStr;
            checkIn.dispatchEvent(new Event('change')); 
        } else if (!currentCheckOutStr) {
            if (clickedDateStr <= currentCheckInStr) {
                checkIn.value = clickedDateStr;
                checkOut.value = "";
                checkIn.dispatchEvent(new Event('change'));
            } else {
                checkOut.value = clickedDateStr;
                const start = new Date(checkIn.value);
                const end = new Date(checkOut.value);
                const diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)); 
                
                if (diffDays < 7 || diffDays > 30) {
                     alert("Délka pobytu musí být mezi 7 a 30 nocemi. Datum odjezdu bylo zrušeno.");
                     checkOut.value = "";
                } else {
                     checkOut.dispatchEvent(new Event('change'));
                }
            }
        } else {
             checkIn.value = clickedDateStr;
             checkOut.value = "";
             checkIn.dispatchEvent(new Event('change'));
        }
    };

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const roomIdFromUrl = urlParams.get('room_id'); 

        if (roomIdFromUrl) {
            const optionExists = selector.querySelector(`option[value="${roomIdFromUrl}"]`);
            if (optionExists) {
                selector.value = roomIdFromUrl; 
            }
        }

        const today = new Date();
        const minArrivalDate = new Date(today);
        minArrivalDate.setDate(today.getDate() + 2); 
        const minArrivalString = minArrivalDate.toISOString().split('T')[0];
        checkIn.setAttribute('min', minArrivalString);
        checkOut.setAttribute('min', minArrivalString);

        checkIn.addEventListener('change', function() {
            if (!this.value) return;
            
            const startDate = new Date(this.value);
            const dznes = new Date();
            dznes.setHours(0,0,0,0);
            const minimalniPriezjd = new Date(dznes);
            minimalniPriezjd.setDate(dznes.getDate() + 2);

            if (startDate < minimalniPriezjd) {
                alert("Rezervaci je nutné provést minimálně 2 dny předem.");
                this.value = ""; 
                if (checkOut.value) checkOut.value = ""; 
                updateSidebar();
                return;
            }

            const minCheckoutDate = new Date(startDate);
            minCheckoutDate.setDate(startDate.getDate() + 7);
            const maxCheckoutDate = new Date(startDate);
            maxCheckoutDate.setDate(startDate.getDate() + 30);

            checkOut.setAttribute('min', minCheckoutDate.toISOString().split('T')[0]);
            checkOut.setAttribute('max', maxCheckoutDate.toISOString().split('T')[0]);
            
            if (checkOut.value) {
                const currentEndDate = new Date(checkOut.value);
                if (currentEndDate < minCheckoutDate || currentEndDate > maxCheckoutDate) {
                    checkOut.value = "";
                    alert("Datum odjezdu bylo upraveno dle podmínek.");
                }
            }
            updateSidebar();
            validateAvailability();
        });

        checkOut.addEventListener('change', function() { updateSidebar(); validateAvailability(); });
        selector.addEventListener('change', function() { updateSidebar(); validateAvailability(); });

        updateSidebar(); 

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

    async function validateAvailability() {
        const roomId = selector.value;
        const start = checkIn.value;
        const end = checkOut.value;
        const errorBox = document.getElementById('date-error-msg');
        
        if (!roomId || !start || !end) {
            if(errorBox) errorBox.style.display = 'none';
            return true; 
        }

        try {
            const response = await fetch(`check_availability.php?check_in=${start}&check_out=${end}`);
            const data = await response.json();
            let isOccupied = false;
            if (data.occupied_ids && Array.isArray(data.occupied_ids)) {
                isOccupied = data.occupied_ids.map(id => id.toString()).includes(roomId.toString());
            }

            if (isOccupied) {
                if(errorBox) {
                    errorBox.style.display = 'block';
                    errorBox.innerHTML = '<strong>Tento pokoj je v termínu obsazen.</strong><br>Změňte termín nebo vyberte jiný pokoj.';
                }
                return false;
            } else {
                if(errorBox) errorBox.style.display = 'none';
                return true; 
            }
        } catch (error) {
            console.error("Chyba při ověřování:", error);
            return true; 
        }
    }

    async function nextStep(stepNumber) {
        if (stepNumber === 2) {
            if (!checkIn.value || !checkOut.value) { alert("Vyberte prosím datum příjezdu a odjezdu."); return; }
            const start = new Date(checkIn.value);
            const end = new Date(checkOut.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (start >= end) { alert("Datum odjezdu musí být později než příjezd."); return; }

            const diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)); 
            if (diffDays < 7) { alert("Minimální délka pobytu je 7 nocí."); return; }
            if (diffDays > 30) { alert("Maximální délka pobytu je 30 nocí."); return; }

            const btn = document.querySelector('button[onclick="nextStep(2)"]');
            const originalText = btn ? btn.innerText : "Pokračovat";
            if(btn) { btn.innerText = "Ověřuji..."; btn.disabled = true; }

            const isAvailable = await validateAvailability();

            if(btn) { btn.innerText = originalText; btn.disabled = false; }
            if (!isAvailable) { alert("Vybraný pokoj je v tomto termínu obsazen. Prosím vyberte jiný."); return; }
        }

        if (stepNumber === 3) {
            const fields = ['name', 'surname', 'email', 'phone', 'street', 'city', 'zip', 'country', 'gdpr_consent'];
            let isValid = true;
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el && !el.checkValidity()) isValid = false;
            });
            if (!isValid) { form.reportValidity(); return; }
        }

        if (stepNumber === 5) {
            updateReviewStep();
        }

        document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
        document.getElementById('step-' + stepNumber).classList.add('active');

        document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
        document.getElementById('progress-' + stepNumber).classList.add('active');
        window.scrollTo(0, 0);
    }

    function prevStep(stepNumber) {
        document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
        document.getElementById('step-' + stepNumber).classList.add('active');
        document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
        document.getElementById('progress-' + stepNumber).classList.add('active');
    }

    function updateSidebar() {
        const roomId = selector.value;
        const room = rooms[roomId];
        
        const previewImg = document.getElementById('main-preview-img');
        if (previewImg && room) previewImg.src = room.img;
        
        document.getElementById('sidebar-room-name').textContent = room ? room.name : "";
        
        const hiddenRoomInput = document.getElementById('hidden-room-id');
        const oldRoomId = hiddenRoomInput.value;
        hiddenRoomInput.value = roomId;
        
        const calendarFrame = document.getElementById('calendar-iframe');
        if (calendarFrame) {
            if (oldRoomId !== roomId || !calendarFrame.src) {
                calendarFrame.src = `calendar-reservation.php?room_id=${roomId}&bg=white`;
            } else {
                if (calendarFrame.contentWindow && typeof calendarFrame.contentWindow.highlightDates === 'function') {
                    calendarFrame.contentWindow.highlightDates(checkIn.value, checkOut.value);
                }
            }
        }

        if (checkIn.value && checkOut.value) {
            const start = new Date(checkIn.value);
            const end = new Date(checkOut.value);
            const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)); 
            
            if (diffDays > 0) {
                let roomTotal = diffDays * (room ? room.price : 0);
                let addonsTotal = 0;

                document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
                    let price = parseInt(cb.getAttribute('data-price')) || 0;
                    let isPerNight = parseInt(cb.getAttribute('data-pernight')) || 0;
                    
                    if (isPerNight === 1) {
                        addonsTotal += (price * diffDays);
                    } else {
                        addonsTotal += price;
                    }
                });

                const addonsRow = document.getElementById('sidebar-addons-row');
                if (addonsTotal > 0) {
                    addonsRow.style.display = 'flex';
                    document.getElementById('sidebar-addons-price').textContent = `+${addonsTotal} Kč`;
                } else {
                    addonsRow.style.display = 'none';
                }

                document.getElementById('sidebar-dates').textContent = `${formatDate(checkIn.value)} - ${formatDate(checkOut.value)}`;
                document.getElementById('sidebar-nights').textContent = diffDays;
                document.getElementById('sidebar-price').textContent = (roomTotal + addonsTotal) + " Kč";
            }
        } else {
            document.getElementById('sidebar-dates').textContent = "--";
            document.getElementById('sidebar-nights').textContent = "0";
            document.getElementById('sidebar-price').textContent = "0 Kč";
        }
    }

    function updateReviewStep() {
        document.getElementById('review-name').textContent = document.getElementById('name').value + " " + document.getElementById('surname').value;
        document.getElementById('review-email').textContent = document.getElementById('email').value;
        document.getElementById('review-phone').textContent = document.getElementById('phone').value;
        
        const fullAddress = `${document.getElementById('street').value}, ${document.getElementById('zip').value} ${document.getElementById('city').value}, ${document.getElementById('country').value}`;
        const addressEl = document.getElementById('review-address');
        if (addressEl) addressEl.textContent = fullAddress;

        document.getElementById('review-room').textContent = document.getElementById('sidebar-room-name').textContent;
        
        if (checkIn.value && checkOut.value) {
            document.getElementById('review-dates').textContent = `${formatDate(checkIn.value)} - ${formatDate(checkOut.value)}`;
        } else {
            document.getElementById('review-dates').textContent = "--";
        }

        let services = [];
        document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
            services.push(cb.value);
        });
        document.getElementById('review-addons').textContent = services.length > 0 ? services.join(", ") : "Žádné";

        const selectedPaymentRadio = document.querySelector('input[name="payment_method"]:checked');
        if (selectedPaymentRadio) {
            const paymentName = selectedPaymentRadio.closest('label').querySelector('.payment-name-text').textContent;
            document.getElementById('review-payment').textContent = paymentName;
        }
    }

    function formatDate(isoDate) {
        if (!isoDate) return "";
        const parts = isoDate.split('-');
        return `${parts[2]}.${parts[1]}.${parts[0]}`;
    }
    </script>
</body>
</html>