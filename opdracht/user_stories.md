# User Stories - StudyBuddy

## US1: Account aanmaken
**Als** student  
**Wil ik** een account kunnen aanmaken  
**Zodat** ik de applicatie kan gebruiken en mijn gegevens veilig opgeslagen worden

**Acceptatiecriteria:**
- Student kan registreren met naam, email en wachtwoord
- Email moet uniek zijn (geen dubbele accounts)
- Wachtwoord wordt gehasht opgeslagen in de database (niet leesbaar)
- Na registratie wordt de student doorgestuurd naar login pagina
- Validatie: alle velden zijn verplicht

## US2: Inloggen
**Als** student  
**Wil ik** kunnen inloggen met mijn account  
**Zodat** ik toegang krijg tot mijn persoonlijke gegevens en taken

**Acceptatiecriteria:**
- Student kan inloggen met email en wachtwoord
- Bij correcte gegevens wordt student doorgestuurd naar dashboard
- Bij incorrecte gegevens verschijnt foutmelding
- Sessie blijft actief totdat student uitlogt
- Uitloggen is mogelijk via logout knop

## US3: Taak aanmaken
**Als** student  
**Wil ik** een nieuwe taak kunnen aanmaken  
**Zodat** ik mijn huiswerk en opdrachten kan bijhouden

**Acceptatiecriteria:**
- Student kan taak aanmaken met: titel, beschrijving, deadline, prioriteit, status
- Prioriteit opties: laag, normaal, hoog
- Status opties: te doen, bezig, afgerond
- Deadline moet in de toekomst liggen
- Taak wordt gekoppeld aan ingelogde student

## US4: Taken bekijken en beheren
**Als** student  
**Wil ik** mijn taken kunnen bekijken, wijzigen en verwijderen  
**Zodat** ik mijn takenoverzicht actueel kan houden

**Acceptatiecriteria:**
- Student ziet overzicht van al zijn/haar taken
- Taken kunnen gefilterd worden op status
- Taken kunnen worden bewerkt (alle velden aanpasbaar)
- Taken kunnen worden verwijderd
- Bevestiging bij verwijderen om ongelukken te voorkomen

## US5: Studiegroep aanmaken
**Als** student  
**Wil ik** een studiegroep kunnen aanmaken  
**Zodat** ik samen kan werken met klasgenoten

**Acceptatiecriteria:**
- Student kan groep aanmaken met naam en optionele beschrijving
- Student die groep aanmaakt wordt automatisch eigenaar
- Groep krijgt een unieke code voor uitnodigingen
- Eigenaar wordt automatisch lid van de groep

## US6: Lid worden van studiegroep
**Als** student  
**Wil ik** lid kunnen worden van een bestaande studiegroep  
**Zodat** ik kan deelnemen aan groepsactiviteiten

**Acceptatiecriteria:**
- Student kan groep joinen via groepscode
- Student ziet bevestiging na succesvol joinen
- Student kan niet dubbel lid worden van dezelfde groep
- Foutmelding als groepscode niet bestaat

## US7: Groepsleden bekijken
**Als** student  
**Wil ik** zien wie er lid zijn van mijn groepen  
**Zodat** ik weet met wie ik samenwerk

**Acceptatiecriteria:**
- Bij elke groep is ledenlijst zichtbaar
- Naam van elk lid wordt getoond
- Eigenaar is duidelijk gemarkeerd
- Alleen groepsleden kunnen ledenlijst zien

## US8: Afspraak maken binnen groep
**Als** groepslid  
**Wil ik** een afspraak kunnen plannen  
**Zodat** we kunnen afspreken om samen te studeren

**Acceptatiecriteria:**
- Groepslid kan afspraak aanmaken met: datum, tijd, locatie/link, onderwerp
- Afspraak is alleen zichtbaar voor groepsleden
- Datum moet in de toekomst liggen
- Alle groepsleden zien de nieuwe afspraak

## US9: Reageren op afspraak
**Als** groepslid  
**Wil ik** kunnen aangeven of ik bij een afspraak kan zijn  
**Zodat** anderen weten of ik kom

**Acceptatiecriteria:**
- Student kan kiezen uit: "erbij", "misschien", "niet"
- Status is zichtbaar voor alle groepsleden
- Student kan zijn/haar antwoord aanpassen
- Per afspraak wordt getoond wie wel/misschien/niet komt

## US10: Dashboard bekijken
**Als** student  
**Wil ik** een overzicht zien van mijn activiteiten  
**Zodat** ik snel kan zien wat er speelt

**Acceptatiecriteria:**
- Dashboard toont aantal openstaande taken
- Dashboard toont percentage afgeronde taken
- Dashboard toont eerstvolgende afspraak (indien aanwezig)
- Bij elke groep: aantal leden en aankomende afspraken
- Dashboard is eerste pagina na inloggen

## US11: Admin - Gebruikers beheren (bonus)
**Als** admin (studiecoach)  
**Wil ik** gebruikers kunnen beheren  
**Zodat** ik problematische accounts kan blokkeren of verwijderen

**Acceptatiecriteria:**
- Admin kan alle gebruikers zien
- Admin kan gebruiker blokkeren/deblokkeren
- Admin kan gebruiker verwijderen (met bevestiging)
- Admin kan rol van gebruiker aanpassen
- Geblokkeerde gebruikers kunnen niet inloggen
