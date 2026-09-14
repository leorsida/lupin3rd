# lupin3rd.org — Presenza e messaggi privati

**Versione:** 1.0 · 14.09.2026
**Stato:** proposta, nessuna riga scritta
**Prerequisito:** fase comunità (C1, C2, C3, C5 parziale, C7 fatte)

---

## 0. Leggere prima del resto

Queste due funzioni non sono neutre rispetto a quello che il sito dichiara. Non
sono impossibili — è il tuo sito — ma **una delle due contraddice una frase che
oggi è pubblicata su `/comunita/`**, e l'altra contraddice il perimetro che
abbiamo scritto tre giorni fa. Il piano parte da lì perché se si parte dal
codice la contraddizione resta e la scopre un lettore.

Quello che il sito dice adesso, in pagina:

> «Un account qui serve a **una cosa sola**: firmare quello che scrivi sotto gli
> articoli e risponderne. Niente bacheca personale, niente punteggi, niente rete
> di amicizie.»

> **Non conserviamo** — Indirizzi IP oltre le 24 ore di finestra antispam ·
> **Cronologia di lettura, tempo sulla pagina, provenienza** · Nomi reali,
> documenti, numeri di telefono.

> «Pubblichiamo pseudonimo, ruolo e data di iscrizione. Mai l'email, mai il
> paese, **mai l'ultimo indirizzo usato per entrare**.»

E `docs/PIANO-FASE-COMUNITA.md` §1, non-obiettivi dichiarati:

> Fuori perimetro, e vanno rifiutati se ricompaiono: **messaggi privati fra
> account** (la cassetta sicura è un'altra cosa), punteggi e classifiche, follow
> fra membri, notifiche push, login social, upload di immagini di profilo.

C'è anche un precedente diretto. **D-025** ha ridefinito «account attivi» come
«ha pubblicato almeno un intervento» invece di «ha fatto un accesso negli ultimi
dodici mesi» *proprio perché* il sito non tiene traccia di quando qualcuno entra.
Una colonna «ultima interazione» è esattamente il registro che quella decisione
diceva di non avere.

Tre esiti possibili, tutti legittimi:

1. **Le funzioni entrano e il testo pubblico cambia di conseguenza.** Va
   riscritto `OD/comunita.html`, la sua versione inglese, e le sezioni che il
   tema rende da `page-templates/template-community.php`.
2. **Le funzioni entrano in una forma che non contraddice niente.** È possibile
   per entrambe, con qualche rinuncia. È quello che questo piano raccomanda.
3. Non entrano.

Quello che non è un esito possibile è: entrano e il testo resta com'è.

---

## 1. Chi è in linea adesso

### 1.1 La differenza che decide tutto

| | Cosa pubblica | Cosa conserva |
|---|---|---|
| **A · Numero aggregato** «diciotto persone stanno leggendo» | un numero | un contatore che scade da solo, niente per singolo account |
| **B · Pallino per account, su richiesta** | «attivo di recente» accanto a chi lo ha acceso | un istante per account, corto, che scade da solo |
| **C · Pallino per account, sempre** | quando ogni membro è davanti allo schermo | come B, ma imposto a tutti |

**A** non contraddice niente e non richiede nemmeno una decisione. **B**
richiede una riga in più nel colophon («se lo accendi, il tuo profilo mostra che
sei stato attivo di recente»), ma resta coerente con «pubblichiamo pseudonimo,
ruolo e data di iscrizione» perché è il membro a scegliere. **C** richiede di
riscrivere quella frase: pubblicheresti un fatto comportamentale continuo su una
persona con uno pseudonimo, su un sito che scrive di sorveglianza.

**Raccomandazione: A subito, B come opzione del profilo.** A è mezza giornata e
dà alla pagina comunità la cosa che il lettore cerca davvero — «questo posto è
vivo». B è un'altra mezza giornata e lascia la scelta a chi la subisce.

### 1.2 Perché non una colonna su `wp_users`

Tre motivi, in ordine di quanto costa scoprirli tardi.

1. **`wp_users` è del core.** Il tema non crea tabelle e non altera quelle di
   WordPress — è scritto in `PIANO-FASE-COMUNITA.md` §6. Una colonna aggiunta a
   mano sopravvive agli aggiornamenti per fortuna, non per contratto, e ogni
   strumento che fa un dump/restore selettivo la perde in silenzio.
2. **È una scrittura per ogni pagina vista.** Un membro che legge dieci pagine
   sono dieci `UPDATE` sulla tabella più letta del sito. Va comunque limitata a
   una ogni N minuti, e a quel punto il posto naturale del valore non è più una
   colonna.
3. **Una colonna accumula, una cache scade.** È la differenza fra «il sito
   conserva quando sei stato attivo» e «il sito sa, per i prossimi dieci minuti,
   che qualcuno lo era». La seconda frase è vera anche in un dump del database:
   non c'è niente da leggere.

**Dove va invece:** una chiave di cache per account con TTL (`l3_seen_<id>`,
scadenza 10 minuti) più un insieme dei presenti per il conteggio. Con
`object-cache.php` collegato a Redis — che nello stack c'è già ma **non è
ancora collegato** (vedi §4) — questo non tocca mai il database. Senza, sono
transient in `wp_options` e la scrittura limitata diventa obbligatoria invece
che consigliata.

### 1.3 Le fette

| | Consegna | Verifica |
|---|---|---|
| **P1** | `Presence`: segna il presente su `init`, non più di una volta ogni 5 minuti per account, TTL 10 minuti; nessuna scrittura per chi non è connesso | test: due richieste ravvicinate dello stesso account producono una sola scrittura |
| **P2** | Il numero su `/comunita/`, accanto agli altri quattro | e2e: la pagina mostra il conteggio e non nomina nessuno |
| **P3** | Opzione «mostra che sono attivo» in `/account/`, spenta di default; pallino sul profilo e nell'elenco iscritti solo per chi l'ha accesa | e2e: un profilo senza l'opzione non contiene il pallino né la parola «attivo»; con l'opzione, contiene «attivo di recente» e **non** un orario |
| **P4** | Riga nel colophon e in `/comunita/` che dice cosa succede | — |

**Granularità: mai un orario.** «Attivo di recente» e non «attivo 3 minuti fa».
Un orario al minuto su un profilo pubblico è un tracciato delle abitudini di una
persona, ricostruibile da chiunque guardi la pagina abbastanza spesso — che è
sorveglianza fatta col sito, non sul sito.

**Stima: P1+P2 mezza giornata · P3+P4 mezza giornata.**

---

## 2. Messaggi privati

### 2.1 Il conflitto, e non è di forma

Qui non si tratta di riscrivere una frase. Una casella di posta interna cambia
tre cose insieme, e vanno guardate prima di scrivere una riga.

**Diventa una superficie di moderazione che la redazione non vede.** Oggi ogni
parola che un account scrive a un altro passa sotto un articolo, in pubblico,
dopo la moderazione dei primi tre interventi. Le molestie, quando arrivano,
arrivano dove si vedono. Una casella privata le sposta dove non si vedono, e da
quel momento servono blocco, segnalazione, limiti di frequenza e qualcuno che
legga le segnalazioni — o il sito ha costruito un canale di molestie e lo ha
chiamato funzionalità.

**Diventa il dato più sensibile che il sito abbia mai tenuto.** L'inventario di
oggi è: pseudonimo, email, interventi pubblicati. Il primo è inventato, il terzo
è già pubblico. Un archivio di messaggi privati su una rivista che pubblica di
sequestri, analisi forense e disclosure coordinata è una cosa che qualcuno, prima
o poi, verrà a chiedere — con un provvedimento o senza.

**Toglie al sito una frase che oggi lo definisce.** «Un account qui serve a una
cosa sola» è ripetuta tre volte nel prototipo, in tre pagine diverse. È la
descrizione più corta di cosa questo posto non vuole essere.

### 2.2 Quattro forme, in ordine di costo

| | Cosa | Costo | Cosa promette ancora |
|---|---|---|---|
| **M0** | Niente. Ci si risponde sotto l'articolo. | zero | tutto |
| **M1** | **Contatto per chiave.** Il profilo mostra l'impronta PGP, e chi vuole scrive fuori dal sito. | zero: il campo c'è già | tutto — e il sito non tiene niente perché non passa da lì |
| **M2** | **Casella minima.** Uno a uno, testo semplice, senza allegati, con blocco, segnalazione, limiti e cancellazione automatica. | 4–5 giorni | quasi tutto, con il colophon riscritto |
| **M3** | Messaggistica vera: conversazioni, notifiche, tempo reale. | settimane | poco |

**M1 è la risposta che questo sito darebbe.** È già mezza costruita: il profilo
ha l'impronta, la redazione la controfirma, e «Scrivigli in privato» oggi porta
alla cassetta sicura. Basterebbe far portare quel pulsante alla chiave del
membro, con due righe che spiegano come si usa. Costa niente e non crea un
archivio.

**M2 è la risposta ragionevole se M1 non basta** — e non basta se l'obiettivo è
che due lettori qualunque, senza PGP, possano scriversi.

### 2.3 Se si fa M2: i vincoli, prima del codice

Nessuno di questi è rifinitura. Se uno salta, quello che resta è M3 travestito.

| | Vincolo | Perché |
|---|---|---|
| 1 | **La casella è chiusa di default.** Si apre dalle impostazioni. | Ricevere messaggi da sconosciuti è una cosa che si sceglie, non che si subisce iscrivendosi. |
| 2 | **Scrive solo chi è uscito dalla coda di moderazione** (tre interventi accettati). | Un account aperto due minuti fa non deve poter scrivere a nessuno: è il costo d'ingresso per chi molesta. |
| 3 | **Limite di frequenza**: N messaggi al giorno, e non più di uno a un destinatario che non ha mai risposto. | Toglie l'invio in massa senza toccare la conversazione vera. |
| 4 | **Blocco** per destinatario, immediato e silenzioso. | — |
| 5 | **Segnalazione alla redazione**, che sblocca la lettura di *quel* messaggio e solo di quello. | La moderazione deve poter vedere ciò che le viene segnalato, e nient'altro. |
| 6 | **Cancellazione automatica** dopo 180 giorni, senza copia. | Un archivio che non invecchia è un archivio che qualcuno verrà a chiedere. |
| 7 | **Cifratura a riposo** con chiave derivata dai sali di WordPress. | Difende da un dump del database. **Non** difende dal server: va scritto così, perché scrivere «cifrati» e basta è una promessa più grande di quella che si mantiene. |
| 8 | **Esportazione e cancellazione** agganciate agli strumenti privacy del core. | Il resto dell'account già lo fa. |
| 9 | **Nessuna notifica che riveli il contenuto.** L'email dice «hai un messaggio», mai il testo. | Il testo in chiaro in una casella di posta altrui annulla tutto il resto. |

**Dove vivono.** Tipo di contenuto privato `l3_message`, come `l3_subscriber`:
niente tabelle, backup e cancellazione insieme al resto del sito, e niente
orfani se il tema viene disattivato. Autore = mittente, meta = destinatario,
contenuto = testo cifrato. Nessuna interfaccia in bacheca se non quella delle
segnalazioni.

### 2.4 Le fette, se si fa M2

| | Consegna | Verifica |
|---|---|---|
| **M2.1** | Tipo di contenuto, cifratura, invio e lettura; casella in `/account/` | test unitario su cifra/decifra; e2e: la casella risponde 302 a uno sconosciuto |
| **M2.2** | Apertura della casella, blocco, limiti di frequenza | e2e: un invio a casella chiusa non scrive niente; l'undicesimo invio del giorno viene rifiutato |
| **M2.3** | Segnalazione e vista di moderazione | test: un messaggio non segnalato non è leggibile dalla redazione |
| **M2.4** | Ritenzione a 180 giorni via cron; esportazione e cancellazione | test: un messaggio con data finta oltre la soglia sparisce al passaggio del cron |
| **M2.5** | Colophon, `/comunita/`, `OD/` riallineati | — |

**Stima: 4,5 giornate**, più mezza per il riallineamento del prototipo.

---

## 3. Decisioni da prendere

| ID | Domanda | Raccomandazione |
|---|---|---|
| **D-027** | Presenza: aggregata, per account su richiesta, o per account sempre? | aggregata subito, per account **su richiesta** |
| **D-028** | La presenza sta in cache con scadenza, non in una colonna? | sì |
| **D-029** | Messaggi: M0, M1, M2 o M3? | M1 se basta; M2 con i nove vincoli se non basta |
| **D-030** | Ritenzione dei messaggi | 180 giorni, dichiarati in pagina |
| **D-031** | Chi riscrive il colophon e `OD/`, e quando | prima del rilascio, non dopo |

---

## 4. Prerequisiti tecnici

**Redis non è collegato.** Nello stack c'è (`compose.yaml`, `WP_REDIS_HOST`), ma
in `wp/wp-content/` non c'è `object-cache.php`: WordPress non lo sta usando, e
ogni transient finisce in `wp_options`. Per la presenza è la differenza fra zero
scritture sul database e una ogni cinque minuti per membro connesso. Va
collegato prima di P1, ed è mezza giornata che serve comunque al resto del sito.

**Il cron di WordPress è a richiesta.** La cancellazione automatica dei messaggi
dipende da `wp_cron`, che gira quando qualcuno visita il sito. Su un sito con
poco traffico questo significa che la ritenzione dichiarata non è quella reale.
Va agganciato a un cron di sistema (`wp cron event run --due-now`) e va detto
nel `docker/README.md`.
