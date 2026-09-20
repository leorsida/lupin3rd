# lupin3rd.org — Chiusura dell'account

**Versione:** 2.0 · 20.09.2026 — *la 1.0 sovrascriveva i dati personali; questa
li lascia intatti e li rende irraggiungibili. Vedi §2.*
**Prerequisito:** fase 2 chiusa (`/account/` in piedi, `Account`, `Profile_Fields`, `Community`)
**Riempie:** il riquadro «Andarsene» di `page-templates/template-account.php`, che oggi dice che la cancellazione «non è ancora qui»
**Promessa già pubblicata:** `/privacy/` — «Cancellare un account non cancella i commenti che hai già pubblicato: restano nella discussione, ma passano a firma "account rimosso"»

---

## 1. Cosa vuol dire «logica»

Niente viene eliminato e **niente viene sovrascritto**. L'account viene
**chiuso**: non entra più, sparisce dal sito pubblico, i suoi interventi
restano dove sono a firma «account rimosso», e la riga utente resta identica a
com'era per chi guarda dal backend.

Due conseguenze, ed è bene tenerle a mente insieme.

**La prima è buona: è reversibile.** Siccome non si distrugge niente, la
redazione può riaprire un account chiuso per errore, o su richiesta, e tutto
torna com'era — pseudonimo, biografia, chiave. Nella versione precedente di
questo piano non era possibile e la cancellazione doveva essere definitiva;
ora la riapertura è un pulsante in wp-admin (§4).

**La seconda va guardata in faccia: questo non cancella i dati personali.**
L'email, la biografia e l'impronta PGP restano nel database. È una scelta
legittima — la redazione conserva la traccia di chi ha scritto cosa — ma
significa che il gesto **non è una cancellazione**, è una chiusura. Vedi §2.

### Perché non `wp_delete_user()`

- Riassegna o elimina i post: gli articoli di un collaboratore chiuso
  sparirebbero o cambierebbero firma senza che nessuno lo decida.
- I commenti sopravvivono comunque, ma orfani e con lo pseudonimo ancora
  scritto dentro `comment_author`.
- È irreversibile, ed è esattamente ciò che questo piano ha deciso di evitare.

---

## 2. Come va chiamata, e cosa va corretto nei testi

Se i dati restano, chiamarla «cancellazione» sarebbe una dichiarazione falsa
su una pagina che abbiamo costruito apposta perché non ne contenga. Tre
interventi, non opzionali:

1. **Il pulsante e il riquadro** su `/account/` dicono *chiudi l'account*, non
   *cancella i miei dati*. Il testo spiega in due righe cosa resta visibile a
   chi (nessuno) e cosa resta nel database (tutto, per la redazione).
2. **`/privacy/`** continua a dire che si può chiedere la cancellazione — ed è
   vero, come *richiesta alla redazione*. Va aggiunta la distinzione fra il
   pulsante, che chiude, e la richiesta, che cancella davvero.
3. **Una cancellazione definitiva su richiesta** resta un percorso manuale
   della redazione, fuori da questo piano. Va nominata, però, perché il diritto
   alla cancellazione non si soddisfa con un flag.

Non sono un avvocato: questo è il punto da far leggere a chi lo è.

---

## 3. Cosa fa il lettore

Nel riquadro «Andarsene» di `/account/`, un pulsante apre un terzo pannello
accanto a «Profilo pubblico» e «Indirizzo e password». Il modulo chiede:

1. **la password attuale** — stessa difesa del cambio credenziali;
2. **lo pseudonimo, scritto a mano** — l'unica cosa che distingue un gesto
   deciso da un clic sbagliato;
3. una casella **«cancella anche l'iscrizione alla newsletter»**, accesa di
   default (§6).

Sotto, in chiaro: sparisci dal sito, i tuoi commenti restano senza il tuo nome,
non puoi più entrare, e per riaprire devi scrivere alla redazione.

**La redazione non passa di qui.** Chi ha `edit_posts` riceve un rifiuto e
l'indicazione di rivolgersi a wp-admin: chiudere un account redazionale per
autoservizio è un problema diverso, e va gestito da chi può riassegnare gli
articoli.

---

## 4. Lo stato, e come si vede dal backend

Un solo cambiamento di stato, scelto perché wp-admin lo mostri **senza che
serva scrivere niente**:

| Cosa | Valore |
|---|---|
| ruolo | passa a `l3_closed`, un ruolo nuovo **senza alcuna capacità**, nemmeno `read` |
| `l3_closed_at` | timestamp della chiusura |
| `l3_closed_role` | il ruolo che aveva prima, così la riapertura lo rimette |
| sessioni | tutte distrutte, su ogni dispositivo |
| `l3_hint` | cookie cancellato nella stessa risposta |
| password, email, meta del profilo | **intatti** |

Il ruolo è la chiave di tutto il §4: la lista utenti di WordPress ha già una
colonna «Ruolo» e già offre i link di filtro per ruolo. Chiamandolo
**«Chiuso»** si ottiene gratis sia la visibilità nella riga, sia il
«Chiuso (3)» in cima alla tabella per vederli tutti.

Sopra a questo, tre aggiunte piccole in `Retirement`:

- **una colonna «Chiuso il»** nella lista utenti, con la data;
- **una classe sulla riga**, perché un account chiuso si riconosca scorrendo;
- **nella scheda dell'utente**, un avviso in cima e un pulsante
  **«Riapri l'account»**, che rimette `l3_closed_role` e toglie le due meta.
  Solo per chi ha `edit_users`, con nonce.

L'accesso è rifiutato sul filtro `authenticate`, con lo stesso messaggio
indistinto usato per password sbagliata e account inesistente: dire «questo
account è stato chiuso» a chi prova una password è raccontare qualcosa di un
lettore a chi lo sta cercando.

---

## 5. Dove smette di comparire

Qui sta il peso del lavoro, e **più che nella versione precedente**: siccome
non si sovrascrive niente, un punto dimenticato non mostra un vuoto, mostra lo
pseudonimo vero. Nascondere non è metà del lavoro, è il lavoro.

| Dove | Cosa cambia |
|---|---|
| `Profile_Fields::pseudonym()` | oggi rende «account rimosso» solo se l'utente **non esiste**; deve farlo anche se è chiuso |
| filtri `get_the_author_display_name`, `get_comment_author` | già passano da `Profile_Fields`: verificare che coprano anche il ritorno di cui sopra |
| `Profile_Fields::wants_replies()` | `false` per un chiuso |
| `Reply_Notifications::participants()` | esclude i chiusi — altrimenti l'email parte verso un indirizzo che quella persona non voleva più sentire |
| `Community::stats()`, `recent_members()`, `most_active()`, `contributions_by()` | escludono i chiusi |
| `/membri/<pseudonimo>/` | **410 Gone**, non 404: la pagina c'era e non c'è più, ed è quello che va detto a un motore di ricerca |
| sitemap degli utenti | verificare che i chiusi non ci siano |
| avatar | il seme resta nel database ma non deve più uscire: `Security::replace_gravatar()` disegna il motivo generico per un account chiuso |
| feed dei commenti | **da verificare**: il feed scrive il nome d'autore per una via sua |
| `/wp-json/wp/v2/comments` | **da verificare**: `author_name` potrebbe leggere il campo grezzo invece di `get_comment_author()` |

Le ultime due righe sono le uniche due incognite di questo piano, e vanno
misurate prima di scrivere il resto: se REST e feed leggono
`comment_author` dalla tabella senza passare dal filtro, lo pseudonimo esce da
lì e nessun test sulle pagine se ne accorge.

---

## 6. La newsletter è un consenso diverso

Un indirizzo può essere iscritto alla newsletter **senza** avere un account, e
viceversa. Sono due consensi distinti, quindi chiudere l'uno non può decidere
in silenzio per l'altro — in nessuna delle due direzioni. Da qui la casella nel
modulo (§3), accesa di default perché è quello che quasi tutti si aspettano, ma
visibile e disattivabile. Se l'indirizzo non risulta iscritto, la casella non
compare affatto.

E, come per gli utenti, **la redazione lo deve vedere**. Gli iscritti sono post
di tipo `l3_subscriber` con una meta `l3_subscriber_user` che punta
all'account: basta una colonna in quella lista che dica «account chiuso» quando
quella meta punta a un utente con ruolo `l3_closed`. Serve a capire, guardando
la lista, perché un indirizzo è ancora lì ma la persona non c'è più.

---

## 7. Moduli e file

| File | Stato | Cosa |
|---|---|---|
| `inc/class-retirement.php` | **nuovo** | `close( \WP_User $user, bool $newsletter ): bool`, `reopen( \WP_User $user ): bool`, `is_closed( $user ): bool`, il ruolo `l3_closed`, la colonna e l'avviso in wp-admin |
| `inc/class-account.php` | modifica | `ACTION_CLOSE = 'l3_close_account'`, handler, verifica password + pseudonimo, rifiuto per chi ha `edit_posts` |
| `inc/class-authentication.php` | modifica | rifiuto sul filtro `authenticate` |
| `inc/class-profile-fields.php` | modifica | `pseudonym()` e `wants_replies()` consultano `Retirement::is_closed()` |
| `inc/class-community.php` | modifica | le quattro query di §5 |
| `inc/class-reply-notifications.php` | modifica | `participants()` |
| `inc/class-security.php` | modifica | avatar generico per un account chiuso |
| `inc/class-newsletter.php` | modifica | disiscrizione di un indirizzo, verifica se è iscritto, colonna in lista |
| `page-templates/template-account.php` | modifica | il pannello, al posto del testo «non è ancora qui» |
| `page-templates/template-privacy.php` | modifica | la distinzione fra chiusura e cancellazione (§2) |
| `author.php` | modifica | il 410 |
| `src/js/modules/account-close.js` | **nuovo, piccolo** | abilita il pulsante solo quando lo pseudonimo digitato combacia; senza JS il modulo funziona lo stesso, perché è il server a verificarlo |
| `inc/class-theme.php` | modifica | `Retirement::class` nel registro |

Nessun `Mail::send()` nuovo.

---

## 8. Le fette, in ordine

1. **Misurare le due incognite di §5**: cosa scrivono davvero il feed dei
   commenti e l'endpoint REST. Prima di tutto il resto, perché possono
   aggiungere lavoro.
2. **`Retirement`, senza interfaccia**: ruolo, `close()`, `reopen()`,
   `is_closed()`. Verificabile da WP-CLI prima che esista un bottone.
3. **Le esclusioni** di §5, con gli e2e che le coprono. Prima dell'interfaccia:
   sono la parte che si dimentica.
4. **Il backend**: colonna, riga, avviso, «Riapri l'account».
5. **Il 410** su `/membri/`.
6. **Handler e pannello** su `/account/`, con nonce, password e pseudonimo.
7. **La newsletter**: casella nel modulo e colonna nella lista.
8. **Il modulo JS**, che è comodità e basta.
9. **Copy**: il riquadro «Andarsene» e le righe su `/privacy/` (§2).

---

## 9. Test

E2E, perché è dove i difetti di questo tema sono sempre stati visibili:

- un account chiuso non entra più, con le credenziali di prima, e il messaggio
  di rifiuto è lo stesso di una password sbagliata;
- il suo profilo risponde 410;
- i suoi commenti leggono «account rimosso» **nella pagina, nel feed e in
  REST** — tre asserzioni, non una;
- non compare in `/comunita/` né fra i più attivi;
- `l3_close_account` senza nonce è rifiutato; con nonce ma password sbagliata,
  o pseudonimo non combaciante, è rifiutato;
- un account con `edit_posts` è rifiutato;
- una notifica di risposta su un articolo dove il chiuso aveva commentato **non
  produce alcun invio**;
- dopo `reopen()`, pseudonimo e biografia tornano quelli di prima.

L'ultimo è il test che tiene onesta la promessa di §1: la reversibilità è un
argomento solo finché qualcuno la verifica.

---

## 10. Rischi noti

- **Un punto dimenticato in §5 mostra lo pseudonimo vero.** È il rischio
  principale di questa versione del piano, ed è il motivo per cui le
  esclusioni vengono prima dell'interfaccia.
- **Lo pseudonimo resta occupato per sempre**, perché la riga utente resta e
  `pseudonym_is_free()` interroga gli utenti. Nessuno può prenderselo: è la
  difesa migliore contro l'impersonificazione, ed è gratis. Ma se un giorno
  qualcuno vorrà tornare con quel nome, dovrà passare dalla riapertura.
- **Il collaboratore chiuso ha articoli pubblicati.** Restano, a firma «account
  rimosso». Va detto nel modulo: chi ha scritto un pezzo non può farlo sparire
  da qui.
- **Export.** Non è in questo piano. Finché non c'è, il riquadro «Andarsene»
  deve smettere di prometterlo insieme alla chiusura.
