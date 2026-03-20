document.addEventListener('DOMContentLoaded', function() {
    
    // Načtení prvků z DOMu
    const filterBtn = document.getElementById('filter-submit');
    const priceSlider = document.getElementById('filter-price');
    const priceDisplay = document.getElementById('price-val');
    const personsInput = document.getElementById('filter-persons');
    const dateStartInput = document.getElementById('filter-date-start');
    const dateEndInput = document.getElementById('filter-date-end');
    
    // Všechny karty pokojů
    const roomCards = document.querySelectorAll('.room-card-vertical');

    // 1. Změna čísla u posuvníku ceny v reálném čase
    if (priceSlider && priceDisplay) {
        priceSlider.addEventListener('input', function() {
            priceDisplay.textContent = this.value + ' Kč';
        });
    }

    // 2. Funkce filtrování
    function filterRooms() {
        const maxPrice = parseInt(priceSlider.value) || 10000;
        const requiredPersons = parseInt(personsInput.value) || 1;

        let visibleCount = 0;

        roomCards.forEach(card => {
            // Získáme data z atributů data-price a data-beds
            const cardPrice = parseInt(card.getAttribute('data-price'));
            const cardBeds = parseInt(card.getAttribute('data-beds'));

            let isVisible = true;

            // Podmínka Ceny: Pokud je cena pokoje vyšší než limit, skryjeme ho
            if (cardPrice > maxPrice) {
                isVisible = false;
            }

            // Podmínka Kapacity: Pokud je kapacita pokoje menší než počet osob, skryjeme ho
            // (Hledám pro 3 lidi -> Dvoulůžák (2) se skryje)
            if (cardBeds < requiredPersons) {
                isVisible = false;
            }

            // Aplikace viditelnosti
            if (isVisible) {
                card.style.display = 'flex'; // Zobrazit
                visibleCount++;
            } else {
                card.style.display = 'none'; // Skrýt
            }
        });

        // 3. Bonus: Přenos data do odkazů
        updateLinksWithDate();

        if (visibleCount === 0) {
            alert("Vašim filtrům neodpovídá žádný pokoj. Zkuste zvýšit cenu nebo snížit nároky.");
        }
    }

    // Funkce pro aktualizaci odkazů (přidá ?check_in=... do URL)
    function updateLinksWithDate() {
        const checkIn = dateStartInput.value;
        const checkOut = dateEndInput.value;

        if (checkIn && checkOut) {
            document.querySelectorAll('.btn-primary').forEach(btn => {
                // Pokud je to odkaz na detail (ne tlačítko Filtrovat)
                if (btn.tagName === 'A' && btn.href) {
                    const url = new URL(btn.href);
                    url.searchParams.set('check_in', checkIn);
                    url.searchParams.set('check_out', checkOut);
                    btn.href = url.toString();
                }
            });
        }
    }

    // Spuštění filtru po kliknutí
    if (filterBtn) {
        filterBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Zabrání obnovení stránky
            filterRooms();
        });
    }
});