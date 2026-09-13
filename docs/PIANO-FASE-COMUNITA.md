# lupin3rd.org — Fase 2: comunità e account

**Versione:** 1.0 · 13.09.2026
**Prerequisito:** fase 1 chiusa (tema editoriale, qualità verificata: `composer qa`, `docker compose --profile qa run --rm e2e`)
**Fonte di verità del design:** `OD/comunita.html`, `OD/accesso.html`, `OD/profilo.html`, `OD/profilo-connesso.html` (+ le versioni EN)
**Contratto dei nomi:** `docs/llmwiki/12-glossary.md` — vincolante, già scritto in fase 1

---

## 1. Cosa consegna questa fase

Un account serve a **una cosa sola**: firmare quello che scrivi sotto gli
articoli e risponderne. Il prototipo lo dice tre volte, in tre pagine diverse.
Non c'è bacheca, non c'è punteggio, non c'è rete di amicizie. Ogni funzione che
non serva a firmare un intervento va discussa prima di essere scritta.

| # | Consegna | Pagina OD |
|---|---|---|
| 1 | Tre ruoli (`l3_member`, `l3_contributor`, redazione) e i loro permessi | `comunita.html` |
| 2 | Profilo pubblico `/membri/<pseudonimo>/` | `profilo.html` |
| 3 | Indice della comunità `/comunita/` | `comunita.html` |
| 4 | Accesso e registrazione fuori da `wp-login.php`, su `/accesso/` | `accesso.html` |
| 5 | Secondo fattore TOTP | `accesso.html`, `comunita.html` |
| 6 | Area personale `/account/` | `profilo-connesso.html` |
| 7 | Esportazione e cancellazione dei dati | `comunita.html` |
| 8 | Testata che si accorge della sessione senza rompere la cache (D-017) | `index.html` |

### Non-obiettivi dichiarati

Fuori perimetro, e vanno rifiutati se ricompaiono: messaggi privati fra
account (la cassetta sicura è un'altra cosa), punteggi e classifiche oltre la
tabella «chi tiene viva la discussione», follow fra membri, notifiche push,
login social, upload di immagini di profilo (l'avatar è generato dal seme e
non esce dal server).

---

## 2. Il contratto, letto dal prototipo

Quello che segue non è una proposta: è quello che `OD/` già promette al
lettore. Il tema deve dire la stessa cosa.

### 2.1 Cosa il profilo pubblico mostra, e cosa non mostra

Mostra: pseudonimo, avatar generato dal seme, ruolo, etichetta «chiave
verificata» se c'è, bio, data di iscrizione *se il membro la lascia visibile*,
lingue, impronta PGP, numeri (interventi, articoli commentati, correzioni
accettate, writeup), gli ultimi interventi con l'articolo sotto cui stanno.

Non mostra **mai**: email, paese, indirizzo IP, ultimo accesso in forma
esatta, nome reale. `comunita.html` lo scrive in chiaro:

> «Pubblichiamo pseudonimo, ruolo e data di iscrizione. Mai l'email, mai il
> paese, mai l'ultimo indirizzo usato per entrare.»

Questa riga diventa un test end-to-end, non una buona intenzione.

### 2.2 Cosa si conserva

| Conserviamo | Non conserviamo |
|---|---|
| Pseudonimo, data di iscrizione, ruolo | Indirizzi IP oltre 24 ore (finestra antispam) |
| Email, visibile solo alla moderazione | Cronologia di lettura, tempo sulla pagina, provenienza |
| Interventi pubblicati e cronologia delle modifiche | Nomi reali, documenti, numeri di telefono |

### 2.3 Registrazione

Pseudonimo (3–20 caratteri, minuscole, niente spazi), email, password (almeno
dieci caratteri, con indicatore di forza), impronta PGP facoltativa, spunta
obbligatoria sulle linee guida. Nessuna verifica dell'identità, nessun invito.

### 2.4 Moderazione

I primi tre interventi passano dalla moderazione. Superati, i commenti
compaiono subito. Due segnalazioni respinte riportano l'account in coda. Il
contatore è `l3_approved_contributions`.

### 2.5 Da membro a collaboratore

Tre contributi accettati e la redazione propone il passaggio. Il passaggio è
un'azione umana in `wp-admin`, non un automatismo: il tema conta, la redazione
decide.

### 2.6 Secondo fattore

Facoltativo per i membri, **obbligatorio** per redazione e collaboratori. TOTP
oppure sfida firmata con la chiave PGP dichiarata nel profilo.

### 2.7 Cancellazione

Dall'area personale si chiede esportazione o cancellazione. Gli interventi già
pubblicati restano, ma passano a firma «account rimosso»: chi ha corretto un
articolo non sparisce dalla discussione.

---

## 3. Decisioni di questa fase

| ID | Decisione | Perché |
|---|---|---|
| **D-018** | **Due ruoli nuovi, non tre.** Il tema registra `l3_member` e `l3_contributor`. La «redazione» del prototipo è il ruolo core `editor` (più `administrator`), con l'etichetta pubblica «redazione». | Duplicare `editor` in un `l3_desk` significa riscrivere una mappa di capacità che WordPress già mantiene, e ogni plugin futuro che controlla `edit_others_posts` smetterebbe di funzionare. Il prototipo parla di tre *ruoli visibili*, non di tre righe in `wp_roles`. |
| **D-019** | **Lo pseudonimo è `user_login` ed è immutabile; `l3_pseudonym` è il nome visualizzato e si può cambiare.** Il vecchio pseudonimo resta bloccato novanta giorni (opzione `l3_retired_pseudonyms`). | `user_login` non è modificabile in WordPress senza toccare il database a mano. Il prototipo promette invece che «cambiandolo, gli interventi già pubblicati passano al nuovo nome»: si ottiene rendendo sempre il display name e mai il login. |
| **D-020** | **Il TOTP è scritto nel tema, senza plugin.** Segreto in `l3_totp_secret`, cifrato con una chiave derivata da `AUTH_SALT`; finestra ±1 intervallo; dieci codici di recupero, salvati come hash. | Il vincolo «nessuna dipendenza da plugin a pagamento» vale anche qui, e RFC 6238 sono sessanta righe di PHP. La cifratura serve perché un dump del database non deve bastare a entrare. |
| **D-021** | **`wp-login.php` non è raggiungibile dal pubblico.** `/accesso/` è l'unica porta; `wp-login.php` risponde 302 verso `/accesso/` tranne per `action=logout` e per le richieste della redazione. | Due porte con due comportamenti diversi sono due superfici da tenere chiuse. |
| **D-022** | **Le sessioni si elencano e si chiudono dall'area personale**, leggendo i token di WordPress (`WP_Session_Tokens`). Della sessione si mostra browser e sistema operativo *approssimati*, mai l'indirizzo. | Il prototipo mostra «Firefox su Linux · Roma · adesso». La città richiederebbe geolocalizzare l'IP: si mostra solo quello che già sappiamo senza conservare nulla di nuovo. **Scostamento consapevole dal prototipo, da riflettere in `OD/`.** |
| **D-023** | **Niente bozze di commento lato server.** La scheda «Bozze» dell'area personale legge quello che il browser ha salvato localmente. | Conservare testo non pubblicato per novanta giorni è esattamente il tipo di dato che il colophon promette di non avere. **Scostamento consapevole, da riflettere in `OD/`.** |
| **D-024** | **Segnalibri privati in `l3_bookmarks`** (user meta, array di ID), senza tabella e senza cronologia: si sa *cosa* è salvato, non *quando* né in che ordine è stato letto. | «Niente cronologia di lettura» è una promessa del colophon. |

---

## 4. Moduli e file

```
inc/
  class-community.php        ruoli, capacità, pseudonimi, il registro dei contributi
  class-profile-fields.php   user meta: contratto, sanificazione, schermata in wp-admin
  class-authentication.php   /accesso/, registrazione, uscita, blocco di wp-login.php
  class-two-factor.php       TOTP, codici di recupero, sfida al secondo passo
  class-account.php          /account/, sessioni, preferenze, segnalibri
  class-privacy.php          esportazione, cancellazione, firma «account rimosso»
  class-session-hint.php     il cookie minimo non-HttpOnly della testata (D-017)

page-templates/
  template-community.php     /comunita/
  template-sign-in.php       /accesso/
  template-account.php       /account/
author.php                   /membri/<pseudonimo>/

template-parts/
  header/account.php
  community/member-row.php
  community/activity-row.php
  community/stat-grid.php
  account/tab-notifications.php
  account/tab-bookmarks.php
  account/tab-sessions.php
  account/profile-modal.php
  auth/form-register.php
  auth/form-sign-in.php
  auth/form-two-factor.php

src/js/modules/
  session.js                 legge il cookie, corregge la testata, gestisce l'uscita
  tabs.js                    data-tabs
  modal.js                   data-modal-open / data-modal-close
  load-more.js               data-load-more / data-overflow
  notifications.js           data-unread / data-mark-all-read
  password-strength.js       data-password-strength
```

Nessun nuovo partial SCSS: `_identity.scss`, `_members.scss`, `_activity.scss`,
`_tabs.scss`, `_modal.scss`, `_account.scss`, `_account-area.scss`,
`_account-forms.scss`, `_counters.scss` sono già stati portati in fase 1. Se
serve una regola nuova, va aggiunta lì e non in `_parity.scss`.

---

## 5. Le fette, in ordine

Ogni fetta finisce con un comando che dice se è finita. Non si passa alla
successiva finché quel comando non è verde.

| Fetta | Consegna | Verifica |
|---|---|---|
| **C1 — Identità** ✓ | `Community` + `Profile_Fields`: ruoli, user meta, avatar dal seme, `/membri/<pseudonimo>/` con `author.php`. Slug autore riscritto da `author` a `membri`. | e2e: un profilo risponde 200, non contiene l'email dell'account, non contiene «ultimo accesso», il ruolo compare come etichetta. |
| **C2 — Indice comunità** ✓ | `/comunita/`: numeri veri (account attivi, interventi in 30 giorni, correzioni, attesa media), ultimi iscritti filtrabili per ruolo, ultimi interventi moderati, tabella dei più attivi. | e2e: la pagina elenca solo account con almeno un intervento pubblicato; il filtro per ruolo filtra davvero lato server. |
| **C3 — Porta** | `Authentication`: `/accesso/` con i due moduli, registrazione che crea un `l3_member`, uscita, `wp-login.php` chiuso. | e2e: registrazione senza nonce rifiutata; `wp-login.php` risponde 302 su `/accesso/`; `/account/` da disconnesso risponde 302. |
| **C4 — Secondo fattore** | `Two_Factor`: iscrizione TOTP nell'area personale, sfida al secondo passo, codici di recupero, obbligo per collaboratori e redazione. | test unitario sul generatore TOTP con i vettori della RFC 6238; e2e: un collaboratore senza TOTP non completa l'accesso. |
| **C5 — Area personale** | `/account/`: notifiche, segnalibri, sessioni, preferenze email, modale del profilo. | e2e: l'area risponde 200 solo con sessione; nessun dato di un altro account è raggiungibile cambiando un ID nell'URL. |
| **C6 — Dati** | `Privacy`: esportazione e cancellazione, interventi che restano firmati «account rimosso». | test: cancellato un account di prova, i suoi commenti esistono ancora e non portano più lo pseudonimo. |
| **C7 — Testata** | `Session_Hint` + `session.js`: l'HTML pubblico è identico per tutti, la testata si corregge dal cookie. | e2e: la home servita a un account connesso e a un anonimo ha lo **stesso** corpo HTML. |

Stima: C1 1 g · C2 1 g · C3 1,5 g · C4 1 g · C5 1 g · C6 0,5 g · C7 0,5 g ≈ **6,5 giorni**.

---

## 6. Cosa questa fase deve rompere di proposito

- `Security::block_author_enumeration()` oggi manda `?author=N` alla home. Con
  i profili pubblici resta giusto così: `?author=N` è enumerazione, mentre
  `/membri/<pseudonimo>/` è una pagina che il lettore ha chiesto per nome. Il
  redirect va tenuto e il test che lo controlla va tenuto.
- `Security::close_rest_to_anonymous_users()` chiude tutta la REST agli
  anonimi. La registrazione e l'accesso **non** passano dalla REST: sono
  `admin-post.php` con nonce, come la newsletter e le segnalazioni. Nessuna
  eccezione da aprire.
- Il tema non crea nessuna tabella. Se una funzione sembra chiederne una, è il
  segnale che è fuori perimetro (§1).

---

## 7. Rischi noti

| Rischio | Contromisura |
|---|---|
| Un account connesso rende una pagina diversa e la cache statica serve la pagina sbagliata a tutti | C7 è l'unico punto in cui la sessione tocca l'HTML, ed è un test |
| Il TOTP scritto a mano sbaglia la finestra temporale e blocca fuori la redazione | Vettori RFC 6238 in un test unitario, codici di recupero obbligatori all'iscrizione |
| La cancellazione di un account porta via i commenti | `Privacy` riassegna prima, cancella dopo, e il test verifica l'ordine |
| Lo pseudonimo cambia e i vecchi permalink dei profili si rompono | `user_login` non cambia mai (D-019); il permalink usa `user_nicename`, che resta agganciato al login |
