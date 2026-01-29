# StudyBuddy

## Omschrijving

StudyBuddy is een webapplicatie gebouwd voor studenten van ROC Nova om hun studie beter te plannen en samen te werken met klasgenoten. De applicatie biedt functionaliteiten voor takenbeheer, studiegroepen en het plannen van afspraken.

## Technische Details

- **Backend**: PHP 8+ met Object Oriented Programming (OOP)
- **Database**: MySQL
- **Security**: PDO met prepared statements, wachtwoorden worden gehashed met `password_hash()`
- **Architectuur**: MVC-gebaseerd met gescheiden classes en views

## Functionaliteiten

### Accounts en Beveiliging
- Registratie en login systeem
- Wachtwoorden worden veilig gehashed opgeslagen
- Sessie-gebaseerde authenticatie
- Admin en student rollen

### Takenbeheer
- Persoonlijke taken aanmaken, bewerken en verwijderen
- Elke taak bevat: titel, beschrijving, deadline, prioriteit, status
- Filteren op status (te doen, bezig, afgerond)
- Statistieken over voortgang

### Studiegroepen
- Groepen aanmaken met unieke uitnodigingscode
- Lid worden via uitnodigingscode
- Groepseigenaar kan groep beheren
- Leden kunnen groepen verlaten

### Afspraken
- Afspraken plannen binnen groepen
- Datum, tijd, locatie en onderwerp vastleggen
- Groepsleden kunnen reageren: erbij, misschien, niet
- Overzicht van reacties per afspraak

### Dashboard
- Overzicht van openstaande taken
- Percentage afgeronde taken
- Eerstvolgende afspraak
- Groepsstatistieken

### Admin Functies
- Gebruikersbeheer (blokkeren/deblokkeren)
- Rollen wijzigen
- Accounts verwijderen

## Installatie

### Vereisten
- PHP 8.0 of hoger
- MySQL 5.7 of hoger
- Webserver (Apache/Nginx) of PHP's built-in server
- Composer (optioneel, voor dependencies)

### Stap 1: Database Opzetten

1. Maak een nieuwe MySQL database aan:
```sql
CREATE DATABASE studybuddy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importeer het database schema:
```bash
mysql -u root -p studybuddy < studybuddy.sql
```

Of kopieer en plak de inhoud van `studybuddy.sql` in je MySQL client (phpMyAdmin, MySQL Workbench, etc.)

### Stap 2: Database Configuratie

1. Open het bestand `shitv2/config/config.php`
2. Pas de database credentials aan naar jouw lokale setup:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'studybuddy');
define('DB_USER', 'root');          // Pas aan naar jouw MySQL gebruiker
define('DB_PASS', '');              // Pas aan naar jouw MySQL wachtwoord
```

### Stap 3: Webserver Configureren

#### Optie A: PHP Built-in Server (Voor Development)

1. Navigeer naar de public directory:
```bash
cd shitv2/public
```

2. Start de PHP development server:
```bash
php -S localhost:8000
```

3. Open in je browser: `http://localhost:8000`

#### Optie B: Apache/Nginx

1. Configureer je webserver om naar de `shitv2/public` directory te wijzen
2. Zorg dat de `.htaccess` bestanden (indien aanwezig) gelezen worden
3. Start je webserver en navigeer naar de juiste URL

### Stap 4: Testen

Navigeer naar de applicatie in je browser en test de registratie- en login functionaliteit.

## Test Accounts

Na het importeren van `database.sql` zijn er twee testaccounts beschikbaar:

### Admin Account
- **Email**: `admin@studybuddy.nl`
- **Wachtwoord**: `admin123`
- **Rol**: Admin
- **Rechten**: Toegang tot admin panel, gebruikersbeheer

### Student Account
- **Email**: `student@studybuddy.nl`
- **Wachtwoord**: `student123`
- **Rol**: Student
- **Rechten**: Reguliere student functionaliteiten

**Belangrijk**: Wijzig deze wachtwoorden na eerste login in productie!

## Gebruik

### Voor Studenten

1. **Registreren**: Maak een account aan via de registratiepagina
2. **Inloggen**: Log in met je email en wachtwoord
3. **Dashboard**: Bekijk je overzicht na inloggen
4. **Taken**: 
   - Ga naar "Taken" in de navigatie
   - Maak nieuwe taken aan met titel, beschrijving, deadline, prioriteit en status
   - Bewerk of verwijder bestaande taken
   - Filter taken op status
5. **Groepen**:
   - Ga naar "Groepen" in de navigatie
   - Maak een nieuwe groep aan of join een bestaande groep met een code
   - Klik op "Bekijken" om groepsdetails te zien
6. **Afspraken**:
   - Open een groep
   - Maak een nieuwe afspraak aan
   - Reageer op afspraken: erbij, misschien, of niet

### Voor Admins

1. Log in met een admin account
2. Klik op "Admin Panel" in de navigatie
3. Beheer gebruikers:
   - Bekijk alle geregistreerde gebruikers
   - Blokkeer of deblokkeer accounts
   - Wijzig gebruikersrollen
   - Verwijder accounts (met bevestiging)

## Projectstructuur

```
shitzooi/
├── studybuddy.sql               # Database schema en test data
├── README.md                    # Deze file
├── opdracht/                    # Projectdocumentatie
│   ├── erd.png                 # Entity Relationship Diagram
│   ├── classdiagram_shitzooi.png  # Klassendiagram (OOP)
│   ├── user_stories.md         # User Stories met acceptatiecriteria
│   └── opdracht.txt            # Originele opdracht
└── shitv2/                     # Hoofdapplicatie
    ├── classes/                # PHP Classes (OOP)
    │   ├── Database.php       # Database connectie (Singleton)
    │   ├── User.php           # Gebruikersbeheer
    │   ├── Task.php           # Takenbeheer
    │   ├── Group.php          # Groepenbeheer
    │   ├── GroupMember.php    # Groepslidmaatschap
    │   ├── Appointment.php    # Afspraken
    │   ├── AppointmentResponse.php  # Reacties op afspraken
    │   └── DashboardHelper.php      # Dashboard statistieken
    ├── config/                 # Configuratie
    │   └── config.php         # Database config en settings
    └── public/                # Publieke bestanden
        ├── dashboard.php      # Dashboard pagina
        ├── tasks.php          # Takenbeheer pagina
        ├── groups.php         # Groepenoverzicht
        ├── group_detail.php   # Groepsdetails en afspraken
        ├── login.php          # Login pagina
        ├── register.php       # Registratie pagina
        ├── logout.php         # Logout handler
        └── style.css          # Styling
```

## Ontwerp Documenten

Het project bevat uitgebreide ontwerpdocumenten in de `opdracht/` directory:

- **erd.png**: Entity Relationship Diagram met 6 tabellen en hun relaties
- **classdiagram_shitzooi.png**: Klassendiagram met 8 PHP classes inclusief properties en methodes
- **user_stories.md**: 11 user stories met acceptatiecriteria
- **opdracht.txt**: Originele opdracht en requirements

## Database Schema

Het project gebruikt 6 hoofdtabellen:

1. **users**: Gebruikersaccounts (studenten en admins)
2. **tasks**: Persoonlijke taken van studenten
3. **groups**: Studiegroepen
4. **group_members**: Koppeltabel voor groepslidmaatschap
5. **appointments**: Afspraken binnen groepen
6. **appointment_responses**: Reacties op afspraken

Zie `opdracht/erd.png` voor een visuele weergave van de tabellen en hun relaties.

## Security Features

- Wachtwoorden worden gehashed met `password_hash()` (bcrypt)
- PDO prepared statements tegen SQL injection
- Sessie-based authenticatie
- Input validatie en sanitization
- Role-based access control (Student/Admin)
- XSS bescherming via `htmlspecialchars()`

## Veelvoorkomende Problemen

### Database connectie mislukt
- Controleer of MySQL draait
- Verifieer de credentials in `config/config.php`
- Zorg dat de database `studybuddy` bestaat

### Session errors
- Zorg dat PHP sessies enabled zijn
- Check schrijfrechten op de session directory

### 404 errors
- Controleer of je in de juiste directory bent (`shitv2/public`)
- Verifieer dat de webserver correct geconfigureerd is

## Toekomstige Verbeteringen

Mogelijke uitbreidingen voor versie 2.0:
- Email notificaties voor komende afspraken
- Bestandsupload voor taken
- Chat functionaliteit binnen groepen
- Kalenderweergave voor afspraken
- Dark mode
- Mobile app

## Auteur

Ontwikkeld voor ROC Nova Studentenraad & Studiecoach-team

## Licentie

Dit project is ontwikkeld voor educatieve doeleinden.

## Support

Voor vragen of problemen, neem contact op met het ontwikkelteam.

---

**Versie**: 1.0  
**Laatste Update**: Januari 2026
