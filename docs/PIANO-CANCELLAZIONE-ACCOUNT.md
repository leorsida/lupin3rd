# lupin3rd.org — Cancellazione dell'account

**Versione:** 1.0 · 20.09.2026
**Prerequisito:** fase 2 chiusa (`/account/` in piedi, `Account`, `Profile_Fields`, `Community`)
**Riempie:** il riquadro «Andarsene» di `page-templates/template-account.php`, che oggi dice che la cancellazione «non è ancora qui»
**Promessa già pubblicata:** `/privacy/` — «Cancellare un account non cancella i commenti che hai già pubblicato: restano nella discussione, ma passano a firma "account rimosso"»

---

## 1. Cosa vuol dire «logica»

Nessuna riga lascia il database. L'account viene **ritirato**: non può più
entrare, sparisce dalla comunità e da `/membri/`, i suoi dati personali vengono
sovrascritti, e i suoi interventi restano dove sono a firma «account rimosso».

La distinzione che conta non è fra cancellare e nascondere. È fra **i dati
personali**, che vengono davvero sovrascritti, e **la riga utente**, che resta
perché è ciò a cui i commenti sono agganciati. Se sparisse la riga, i commenti
resterebbero comunque — con `user_id` che punta al nulla e il nome d'autore
congelato nella tabella dei commenti, che è il peggiore dei due mondi: il
pseudonimo resterebbe scritto e il legame si perderebbe.

Non è una scorciatoia: è ciò che rende la promessa mantenibile. Ma va detto
chiaro che la legittimità di questo schema sta **nell'elenco di cosa viene
sovrascritto** (§3), non nel flag. Non sono un avvocato: se vuoi una conferma
sul punto, quello è il paragrafo da far leggere.

### Perché non `wp_delete_user()`

- Riassegna o elimina i post: gli articoli di un collaboratore ritirato
  sparirebbero o cambierebbero firma senza che nessuno lo decida.
- I commenti sopravvivono comunque, ma orfani e con lo pseudonimo ancora
  scritto dentro `comment_author`.
- È irreversibile per errore di un clic, e non lascia alla redazione modo di
  rispondere a una richiesta su una moderazione passata.

---

## 2. Cosa fa il lettore

Nel riquadro «Andarsene» di `/account/`, un pulsante apre un terzo pannello
accanto a «Profilo pubblico» e «Indirizzo e password». Il modulo chiede:

1. **la password attuale** — stessa difesa del cambio credenziali;
2. **lo pseudonimo, scritto a mano** — l'unica cosa che distingue un gesto
   deciso da un clic sbagliato;
3. una casella **«cancella anche l'iscrizione alla newsletter»**, accesa di
   default (§5).

Sotto, in chiaro, cosa succede e cosa non succede. Poi `admin_post`, nonce,
e ritorno su `/` con un messaggio.

**È immediata e irreversibile, e lo dice prima.** Ogni meccanismo di ripensamento
— finestra di grazia, link di riattivazione — richiede di conservare per
qualche giorno esattamente i dati che si è appena chiesto di togliere, più un
token, più un'email da spedire a un indirizzo che stiamo per sovrascrivere. Su
un sito i cui lettori possono avere motivi seri per andarsene, «fra sette
giorni» non è una cortesia. La digitazione dello pseudonimo è la guardia contro
l'errore; il resto sarebbe teatro con conservazione dei dati in più.

**La redazione non passa di qui.** Chi ha `edit_posts` riceve un rifiuto e
l'indicazione di rivolgersi a wp-admin: perdere un account redazionale per
autoservizio è un problema diverso, e va gestito da chi può riassegnare gli
articoli.

---

## 3. Cosa viene sovrascritto, e cosa resta

Questo elenco è il cuore del lavoro. Vive in un posto solo,
`inc/class-retirement.php`, perché il giorno che `Profile_Fields` aggiunge un
campo qualcuno deve trovare subito dove dichiararlo.

| Dato | Destino |
|---|---|
| `user_email` | sostituito con un indirizzo non instradabile e unico (`retired-<id>@invalid`) |
| `user_url`, `display_name`, `nickname`, nome e cognome | svuotati |
| `l3_pseudonym` | svuotato, e lo pseudonimo passa a `retire_pseudonym()` (90 giorni di blocco) |
| `l3_bio`, `l3_pgp_fingerprint`, `l3_pgp_verified`, `l3_languages`, `l3_avatar_seed` | cancellati |
| `l3_show_join_date`, `l3_notify_replies` | cancellati |
| ruolo | rimosso (`set_role('')`): nessuna capacità, nemmeno `read` |
| sessioni | tutte distrutte, su ogni dispositivo |
| `l3_hint` | cookie cancellato nella stessa risposta |
| `l3_retired_at` | scritto: è il solo dato **nuovo**, e serve alle query |
| `user_login`, `ID`, `user_registered` | restano: sono l'aggancio dei commenti |
| commenti e articoli | restano, a firma «account rimosso» |
| `l3_approved_contributions` | resta: è un contatore, non dice chi eri |

L'indirizzo `@invalid` è deliberato: `.invalid` è riservato dalla RFC 2606 e
non risolve, quindi nessun invio può partire per sbaglio. L'unicità richiesta
da WordPress è soddisfatta dall'id.

---

## 4. Cosa smette di mostrarlo

Ritirare un account è metà del lavoro. L'altra metà è che smetta di comparire,
e sono tutti punti già scritti che vanno rivisti uno per uno.

| Dove | Cosa cambia |
|---|---|
| `Profile_Fields::pseudonym()` | oggi rende «account rimosso» solo se l'utente **non esiste**; deve farlo anche se è ritirato |
| `Profile_Fields::wants_replies()` | `false` per un ritirato |
| `Reply_Notifications::participants()` | esclude i ritirati — altrimenti l'email parte verso `@invalid` |
| `Community::stats()`, `recent_members()`, `most_active()`, `contributions_by()` | escludono i ritirati |
| `/membri/<pseudonimo>/` | **410 Gone**, non 404: la pagina c'era e non c'è più, ed è quello che va detto a un motore di ricerca |
| sitemap degli utenti | verificare che i ritirati non ci siano |
| avatar | il seme è sparito, quindi torna il motivo generico: nessuna riga da scrivere |

Il **410** è l'unica scelta qui che non è ovvia: un 404 dice «non esiste»,
lasciando il profilo in coda a essere ritentato per mesi. Un 410 lo fa togliere
dagli indici.

---

## 5. La newsletter è un consenso diverso

Un indirizzo può essere iscritto alla newsletter **senza** avere un account, e
viceversa. Sono due consensi distinti, quindi cancellare l'uno non può
decidere in silenzio per l'altro — in nessuna delle due direzioni.

Da qui la casella nel modulo, accesa di default perché è quello che quasi
tutti si aspettano, ma visibile e disattivabile. Se l'indirizzo non risulta
iscritto, la casella non compare affatto: una domanda senza oggetto è rumore.

---

## 6. Moduli e file

| File | Stato | Cosa |
|---|---|---|
| `inc/class-retirement.php` | **nuovo** | `retire( \WP_User $user, bool $newsletter ): bool`, `is_retired( $user ): bool`, l'elenco di §3, e i filtri che escludono i ritirati dalle query |
| `inc/class-account.php` | modifica | `ACTION_CLOSE = 'l3_close_account'`, handler, verifica password + pseudonimo, rifiuto per chi ha `edit_posts` |
| `inc/class-profile-fields.php` | modifica | `pseudonym()` e `wants_replies()` consultano `Retirement::is_retired()` |
| `inc/class-community.php` | modifica | le quattro query di §4 |
| `inc/class-reply-notifications.php` | modifica | `participants()` |
| `inc/class-newsletter.php` | modifica | un metodo pubblico per disiscrivere un indirizzo e uno per sapere se è iscritto |
| `page-templates/template-account.php` | modifica | il pannello, al posto del testo «non è ancora qui» |
| `author.php` | modifica | il 410 |
| `src/js/modules/account-close.js` | **nuovo, piccolo** | abilita il pulsante solo quando lo pseudonimo digitato combacia; senza JS il modulo funziona lo stesso, perché è il server a verificarlo |
| `inc/class-theme.php` | modifica | `Retirement::class` nel registro |

Nessun `Mail::send()` nuovo: non si manda una conferma a un indirizzo che si
sta sovrascrivendo. Il messaggio di avvenuta cancellazione è la pagina che si
apre dopo.

---

## 7. Le fette, in ordine

1. **`Retirement`, senza interfaccia.** Il metodo, l'elenco, `is_retired()`.
   Verificabile da WP-CLI prima che esista un bottone.
2. **Le esclusioni** (§4), con gli e2e che le coprono. Da fare *prima*
   dell'interfaccia: sono la parte che si dimentica.
3. **Il 410** su `/membri/`.
4. **Handler e pannello** su `/account/`, con nonce, password e pseudonimo.
5. **La newsletter**, casella compresa.
6. **Il modulo JS**, che è comodità e basta.
7. **Copy**: il riquadro «Andarsene», e una riga su `/privacy/` che dica che
   la cancellazione ora si chiede da soli.

---

## 8. Test

E2E, perché è dove i difetti di questo tema sono sempre stati visibili:

- un account ritirato non entra più, con le credenziali di prima;
- il suo profilo risponde 410;
- i suoi commenti leggono «account rimosso» e non lo pseudonimo;
- non compare in `/comunita/` né fra i più attivi;
- `l3_close_account` senza nonce è rifiutato; con nonce ma password sbagliata,
  o pseudonimo non combaciante, è rifiutato;
- un account con `edit_posts` è rifiutato;
- lo pseudonimo risulta occupato subito dopo.

Più un test che vale doppio: **una notifica di risposta su un articolo dove
il ritirato aveva commentato non produce alcun invio**.

---

## 9. Rischi noti

- **Il collaboratore ritirato ha articoli pubblicati.** Restano, a firma
  «account rimosso». È coerente con quello che `/privacy/` promette, ma va
  detto nel modulo: chi ha scritto un pezzo non può farlo sparire da qui.
- **`user_email` unico.** L'indirizzo `@invalid` include l'id proprio per
  questo; con una riscrittura diversa il secondo ritiro fallirebbe in silenzio.
- **I 90 giorni di blocco dello pseudonimo** valgono anche per chi volesse
  tornare con lo stesso nome. È la difesa contro l'impersonificazione e va
  tenuta, ma è una domanda che arriverà.
- **Export.** Non è in questo piano. Finché non c'è, il riquadro «Andarsene»
  deve smettere di prometterlo insieme alla cancellazione: una delle due
  arriva ora, l'altra no.
