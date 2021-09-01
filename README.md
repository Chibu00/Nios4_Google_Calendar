# Nios4_Google_Calendar
Connect Nios4 with Google Calendar via PHP

ISTRUZIONI INTEGRAZIONE GOOGLE CALENDAR CON NIOS4
Guida per l'integrazione del calendario di Google con il sistema gestionale Nios4.
Attraverso questa guida, con semplici passaggi sarà in grado di far interagire il calendario di Google con Nios4 in maniera automatica.

PROCEDIMENTO CON GOOGLE
La prima cosa necessaria sarà quello di avere o creare un account Google in modo tale da accedere alla sezione Developer di Google (https://developers.google.com/). Dopo di che, click in "View all developer products" e facendo una ricerca su "Calendar API" troverà tutta la documentazione di Google attraverso le API.
Per gestire le integrazioni dovrà accedere alla Console Di Google Cloud (https://console.developers.google.com/) e creare un nuovo progetto.
Dopo aver creato il progetto, cliccare su "ABILITA API" e fare una ricerca per "Google Calendar API". Cliccare sulla sezione e ABILITA il progetto.
Dopo aver fatto l'abilitazione con Google Calendar API è necessario creare delle credenziali. Quindi cliccare su "CREA CREDENZIALI". Selezionare l'API "Google Calendar API" e scegliere "Dati utente" alla domanda "A quali dati accederai?". Andare avanti compilando il form sull'informazione dell'applicazione. Nella sezione 4 (ID client OAuth) scegliere come tipo di applicazione "Applicazione desktop" e poi impostare un nome a piacere.
A questo punto, dopo aver creato le credenziali, ciò che ci servirà sarà ID Client e Client Secret. Saranno necessarie per ricavarci il codice di autorizzazione per l'utilizzo di Nios4.
Prima di avviare la procedura per ricavare il codice di autorizzazione bisogna registrare il proprio dominio nella piattaforma di Google. Per fare questo, dalla Dashboard entrare nella sezione "Verifica del dominio" e aggiungere un nuovo dominio. Inserire il dominio per l'invio delle notifiche webhook considerando il fatto che questo URL deve essere registrato sulla Search Console di Google (così come viene avvisato da Google). Quindi entrare nella Search Console, aggiungere una nuova proprietà e verificare il dominio.
Dopo aver completato con successo la verifica del dominio, ora possiamo ricavarci il codice di autorizzazione, ma prima, siccome si è in uno stato di test, dobbiamo aggiungere l'indirizzo email con cui si vuole fare delle prove. Altrimenti si può impostare il tipo di utente esterno in modo tale che chiunque possa usufruire del servizio.
Dopo aver inserito anche l'indirizzo email per le prove, ci ricaviamo il link per il consenso e l'autorizzazione per Nios4:
Il link è il seguente:
	- https://accounts.google.com/o/oauth2/v2/auth?
e andranno aggiunti diversi parametri al link:
	- client_id --> si ricava questo paramentro dalle credenziali ottenute precedentemente
	- redirect_uri --> "urn:ietf:wg:oauth:2.0:oob"
	- response_type --> "code"
	- scope --> "https://www.googleapis.com/auth/calendar". Permette di leggere e scrivere da Google Calendar.
Ricordarsi che i parametri all'interno del link sono tutti separati da "&".
Una volta costruito il link basta eseguirlo in un browser qualsiasi, scegliere l'account con cui dare il consenso e l'autorizzazione per il collegamento a Nios4 e, dopo di che, il sistema restituirà un codice di autorizzazione.

PROCEDIMETO CON NIOS4
Attraverso la piattaforma di Nios4 bisognerà eseguire le seguenti modifiche:
	- Creare dei nuovi campi dentro la tebella Info:
		- codice di autorizzazione
		- token di Google Calendar
		- il refresh token di Google Calendar
		- il nome del calendario
		- ID del Channel di Google Calendar
		- ID della Risorsa di Google Calendar
		- token di sincronizzazione di Google Calendar
		- campo che serve per la visualizzazione del titolo dell'evento di Google Calendar. Deve fare riferimento alla tabella relativa agli eventi presenti in agenda.
		- campo che serve per la visualizzazione della descrizione dell'evento di Google Calendar. Deve fare riferimento alla tabella relativa agli eventi presenti in agenda.

	I campi da compilare manualmente saranno:
		- il codice di autorizzazione. è il codice ricavato precedentemente attraverso il consenso e l'auotrizzazione.
		- il nome del calendario. è il nome del calendario su Google Calendar. Nel caso in cui il calendario sia quello di default, il nome corrisponderà all'email collegata.
		- il campo che serve per la visualizzazione del titolo dell'evento. Per esempio per un ipotetico seme Reportone il valore di questo campo potrebbe essere "nome_cliente" che è il campo presente nella tabella "interventi_tecnici".
		- il campo che serve per la visualizzazione della descrizione dell'evento. Per esempio per un ipotetico seme Reportone il valore di questo campo potrebbe essere difetto" che è il campo presente nella tabella "interventi_tecnici".

	- Creare un nuovo campo dentro la tabella interessata per la visualizzazione dell'evento in agenda (per esempio nel caso di un Reportone la tabella sarà Rapporti Intervento). Questo campo sarà "ID Evento Calendario" che corrisponde all'id dello stesso evento su Google Calendar.

Dopo aver creato questi campi e compilato a mano solo quelli necessari, copiare e eseguire tutti gli script che troverete nella cartella facendo attenzione ad inserire i dati relativi al proprio database e al proprio server.
Il passo successivo sarà quello di creare le pagine .php dentro al proprio server. Queste pagine le troverà dentro la cartella apposita. Fare attenzione ad inserire i dati giusti corrispondenti al proprio lavoro.
Creare anche un nuovo database dentro al proprio server. Questo database deve contenere una tabella (nome a piacere) con le seguenti colonne:
	- deve avere un contatore autoincrementale id.
	- deve avere una colonna dove salvare il token relativo al proprio database su Nios4.
	- deve avere una colonna dove salvare il nome identificativo del database su Nios4.
	- deve avere una colonna dove salvare il Refresh Token relativo a Google Calendar.
	- deve avere una colonna dove salvare il nome della tabella relativo agli eventi da visualizzare in agenda su Nios4.

Dopo aver eseguito tutti gli script e aver creato tutte le pagine .php, si troverà un nuovo simbolo a forma di stella dentro a Nios4, dove cliccandoci troverà "Collega a Google Calendar". A questo punto, collegando Google Calendar a Nios4 si compileranno in maniera automatica tutti gli altri campi presenti in "I tuoi dati" nella tabella Info. Per vederli in maniera effettiva chiudere l'applicazione e riaprirla.

COME FUNZIONA IL PROCEDIMENTO?
Eseguiti i procedimenti descritti precedentemente, ora Nios4 sarà collegata a Google Calendar. Quindi quando si cercherà di aggiungere, modificare o cancellare un nuovo evento in agenda da Nios4, automaticamente questo evento sarà aggiunto, modificato o cancellato in Google Calendar nel calendario specificato. Stesso identico procedimento in maniera inversa.
ATTENZIONE: Quando si aggiunge un nuovo evento da Nios4 RICORDARSI DI SINCRONIZZARE IN MANIERA MANUALE il programma; altrimenti non riuscirebbe a identificare l'id dell'evento su Google Calendar e quindi non riuscirebbe a eseguire successive modifiche o cancellazioni dell'evento.




