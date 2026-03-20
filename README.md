# Rezervační systém a webová prezentace penzionu

Tento repozitář obsahuje zdrojové kódy komplexního rezervačního systému a webové prezentace, které byly vytvořeny v rámci mé diplomové práce. 

## Důležité upozornění k obsahu repozitáře

Kód v tomto repozitáři vychází z reálného nasazení pro konkrétního klienta. Pro účely veřejného publikování na platformě GitHub však musel být projekt částečně upraven:

* **Anonymizace citlivých dat:** Veškeré přístupové údaje do databáze, kryptografické klíče, hesla a konfigurace SMTP serveru pro odesílání e-mailů byly z bezpečnostních důvodů odstraněny nebo nahrazeny fiktivními hodnotami.
* **Absence grafických materiálů a fotografií:** Na výslovné přání zadavatele (majitelů penzionu) neobsahuje tento repozitář žádné obrazové přílohy (fotografie pokojů, exteriéru, loga podniku). Důvodem je ochrana autorských práv k fotografiím, zachování vizuální identity podniku a ochrana soukromí majitelů.

## Použité technologie
* **Backend:** PHP 8+ (PDO pro komunikaci s databází, objektově orientovaný přístup pro mailer)
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (DOM manipulace, asynchronní validace termínů přes Fetch API)
* **Databáze:** MySQL / MariaDB (Znormalizovaná relační struktura s využitím cizích klíčů a spojovacích tabulek)
* **Externí knihovny:** PHPMailer (pro bezpečné odesílání potvrzovacích e-mailů s podporou SMTP a SSL/TLS)

