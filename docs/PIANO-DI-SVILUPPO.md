# lupin3rd.org — Piano di sviluppo del tema WordPress

**Versione:** 1.0 · 12.09.2026
**Fonte di verità del design:** `OD/` (prototipo Open Design, 17 pagine IT + 17 EN, `OD/assets/app.css`, `OD/assets/app.js`)
**Fonte di verità dell'architettura:** `OD/struttura-wordpress.html`
**Base di conoscenza:** `docs/llmwiki/` — 17 pagine in inglese, tre rigenerate dal prototipo
**Ambiente locale:** `compose.yaml` alla radice, istruzioni in `docker/README.md`

---

## 0. Decisioni prese

| Tema | Tema **standalone** `lupin3rd`, senza parent. Riusa la toolchain di `understrap-child` (`src/build/`, `package.json`, script npm `css` / `js` / `dist` / `watch`). Niente Bootstrap. |
|---|---|
| Architettura PHP | **Singleton**: un bootstrap `Theme` unico + moduli-servizio, ciascuno istanziato una sola volta via `::instance()`. |
| Lingua del codice | Inglese rigoroso: nomi di file, classi, funzioni, metodi, variabili, costanti, hook, commenti, docblock. |
| CSS | Un unico foglio compilato `css/lupin3rd.css`. Sorgenti in partial SCSS sotto `src/sass/`, un solo entry point. |
| JS | Un unico bundle compilato `js/lupin3rd.js`. Sorgenti in moduli ES sotto `src/js/`, un solo entry point. |
| Comunità / account | **Fase 2**, modulo separato. La fase 1 consegna il tema editoriale pubblicabile. |
| Multilingua | **Fase 1 solo italiano.** Struttura predisposta (text domain, stringhe traducibili, slug), integrazione Polylang rimandata. |
| Blocchi | **Blocchi Gutenberg veri** (`register_block_type` + `block.json` + render PHP server-side). |
| Licenza | MIT, come dichiarato in `struttura-wordpress.html` (compatibile GPL). |

### Scostamento consapevole dal prototipo

`OD/struttura-wordpress.html` descrive un **tema a blocchi (FSE)** con `templates/*.html` e `parts/*.html`. Noi costruiamo un **tema classico PHP**. La mappa dei template va quindi tradotta (§3). Il `theme.json` resta e serve comunque: palette dell'editor, `appearanceTools`, blocco dei colori custom, stili dell'editor. Al termine della fase 1 va aggiornato `OD/struttura-wordpress.html` (e la sua versione EN) perché il colophon pubblico descriva il tema che esiste davvero.

---

## 0.1 La llmwiki

`docs/llmwiki/` è la base di conoscenza del progetto, scritta per essere letta prima da una macchina e poi da una persona. Serve a due cose: dare a chiunque (o a qualunque assistente) il contesto completo senza dover aprire prototipo, foglio di stile e core di WordPress; e tenere una sola versione della verità su token, classi, template, convenzioni e decisioni.

È **tutta in inglese**, come il codice. L'italiano sopravvive solo in `12-glossary.md`, che è la tabella di traduzione vincolante fra il prototipo (italiano) e il tema (inglese).

| Pagina | Contenuto |
|---|---|
| `README.md` | regole della wiki, come rigenerarla |
| `00-index.md` | mappa e punti fermi |
| `01-project.md` | cos'è il sito, vincoli non negoziabili |
| `02-design-tokens.md` | **generata** — tutti i token da `app.css` |
| `03-components.md` | **generata** — 35 sezioni, ~330 selettori, partial SCSS corrispondente |
| `04-template-map.md` | schermata → file PHP → URL → fase |
| `05-content-model.md` | CPT, tassonomie, meta, permalink |
| `06-php-architecture.md` | trait Singleton, registry, responsabilità dei moduli |
| `07-js-contract.md` | **generata** — inventario dei `data-*` |
| `08-blocks-and-patterns.md` | `l3/code`, `l3/notice`, vincolo del perimetro legale |
| `09-seo-and-geo.md` | metadati, JSON-LD, feed, superficie generativa |
| `10-security-and-privacy.md` | cosa chiude il tema, CSP, cache e sessione |
| `11-conventions.md` | lingua, naming, layout dei file, stile |
| `12-glossary.md` | traduzione vincolante IT → EN |
| `13-decisions.md` | log delle decisioni (D-001 … D-008) |
| `14-open-questions.md` | Q1 … Q9, chi decide e cosa blocca (Q9 chiusa da D-009) |
| `15-local-environment.md` | lo stack locale e cosa **non** dimostra |

Tre pagine sono **derivate meccanicamente** dal prototipo e non si modificano a mano:

```
python3 docs/llmwiki/tools/sync_from_od.py
```

Va rilanciato dopo ogni modifica a `OD/` e il risultato va committato. Le domande aperte di §9 sono la stessa lista di `14-open-questions.md`: quel file è la copia viva, questa è l'istantanea.

---

## 0.2 Ambiente locale

Lo stack Docker è alla radice del progetto e riproduce quello di produzione dichiarato nel colophon: **PHP 8.3, MariaDB 11.4, FrankenPHP, Redis**.

```
compose.yaml            servizi
.env / .env.example     porte, credenziali, salt (.env non si committa)
docker/Caddyfile        due siti: :80 WordPress, :81 prototipo OD
docker/php/Dockerfile   FrankenPHP + estensioni (mysqli, gd, exif, intl, zip, redis, opcache)
docker/php/php.ini      impostazioni di sviluppo
docker/README.md        istruzioni operative
wp/wp-config.php        legge tutto dall'ambiente, nessun segreto nel file
wp/wp-content/mu-plugins/00-local-environment.php   posta verso Mailpit, badge LOCAL
```

| Servizio | Ruolo | Profilo |
|---|---|---|
| `app` | FrankenPHP: Caddy con PHP 8.3 incorporato | default |
| `db` | MariaDB 11.4 | default |
| `redis` | cache oggetti, senza volume | default |
| `wp` | WP-CLI, a richiesta | `cli` |
| `adminer` | browser del database | `tools` |
| `mailpit` | intercetta ogni messaggio in uscita | `tools` |
| `node` | build del tema, opzionale | `build` |

| URL | Cosa |
|---|---|
| `http://localhost:8080` | il sito |
| `http://localhost:8081` | **il prototipo OD, servito accanto al sito** |
| `http://localhost:8082` | Adminer |
| `http://localhost:8025` | Mailpit |

Il prototipo è servito di fianco apposta: in fase 4 il lavoro è confrontare due schede alla stessa larghezza di finestra, e non deve servire un secondo strumento.

```sh
cp .env.example .env        # oppure usa il .env già generato con salt vere
docker compose up -d --build
docker compose run --rm wp core install --url="http://localhost:8080" \
  --title="lupin3rd.org" --admin_user=redazione --admin_password='cambiala' \
  --admin_email=dev@lupin3rd.test --skip-email
```

**Cosa lo stack non riproduce, di proposito:** TLS, la cache di pagina statica, la `Content-Security-Policy`. Sono le tre cose che sarebbe un errore credere verificate solo perché il sito gira in locale. In particolare la cache statica assente è il motivo per cui il nodo §7.4 va deciso ragionandoci, non provandolo. Dettagli in `docs/llmwiki/15-local-environment.md`.

---

## 1. Stato di partenza rilevato

| Elemento | Stato |
|---|---|
| WordPress | 7.1 in `wp/` |
| `wp-config.php` | **assente** — l'installazione locale non è ancora configurata |
| Plugin | solo `akismet` e `hello.php`. Nessun plugin SEO, nessun Polylang |
| Temi | `understrap` 1.2.4 + `understrap-child` 1.2.0 (Feb 2023) |
| Prototipo OD | 17 pagine IT + 17 EN, CSS 41,8 KB, JS 21,6 KB, zero dipendenze esterne |
| Toolchain | Node 22 disponibile. **PHP non disponibile** nell'ambiente di lavoro corrente |
| Versionamento | la cartella **non è un repository git** |

---

## 2. Architettura del tema

### 2.1 Albero dei file

```
wp-content/themes/lupin3rd/
├── style.css                       # solo header del tema (nessuna regola)
├── theme.json                      # palette editor, appearanceTools, custom colors off
├── functions.php                   # unico bootstrap: autoload + Theme::instance()->boot()
├── screenshot.png                  # 1200×900
│
├── index.php  front-page.php  home.php
├── archive.php  category.php  category-crypto.php  taxonomy-difficulty.php
├── single.php  single-tutorial.php  single-dossier.php
├── page.php  search.php  404.php  author.php  comments.php
├── header.php  footer.php  sidebar.php
│
├── page-templates/
│   ├── template-community.php
│   └── template-sign-in.php
│
├── template-parts/                 # equivalenti classici dei parts/ FSE
│   ├── header/{statusbar,navbar,account-menu,language-switcher,search-overlay}.php
│   ├── content/{card,log-row,feature,empty-state,generative-mark}.php
│   ├── single/{hero,meta-row,table-of-contents,corrections,related}.php
│   ├── archive/{filter-bar,pagination,breadcrumb}.php
│   ├── sidebar/{widget-market,widget-tags,widget-links}.php
│   └── footer/{columns,bottom-bar}.php
│
├── inc/                            # classi PHP, una classe per file
│   ├── trait-singleton.php
│   ├── class-theme.php             # registry dei moduli, boot()
│   ├── class-setup.php             # theme supports, menu, image size, sidebar
│   ├── class-assets.php            # enqueue CSS/JS unici, inline theme script
│   ├── class-content-types.php     # CPT tutorial, dossier + tassonomie
│   ├── class-meta-fields.php       # register_post_meta + pannelli editor
│   ├── class-blocks.php            # register_block_type dei blocchi l3/*
│   ├── class-patterns.php          # block patterns (testata tutorial, ecc.)
│   ├── class-marks.php             # marche generative + avatar deterministici
│   ├── class-template-tags.php     # helper di output usati dai template
│   ├── class-comment-walker.php    # markup commenti secondo OD
│   ├── class-archive-filters.php   # filtri tassonomia con fallback server
│   ├── class-search.php            # ricerca + endpoint per l'overlay
│   ├── class-seo.php               # title, description, canonical, OG, robots
│   ├── class-schema.php            # JSON-LD
│   ├── class-geo.php               # llms.txt, estratti machine-readable, feed puliti
│   ├── class-feeds.php             # feed per tassonomia, feed autore off
│   ├── class-security.php          # XML-RPC, REST users, enumerazione, upload
│   ├── class-performance.php       # cache-friendliness, preload, cleanup head
│   └── class-i18n.php              # text domain, formati data, hook multilingua
│
├── blocks/
│   ├── code/{block.json,render.php,edit.js,editor.scss}
│   └── notice/{block.json,render.php,edit.js,editor.scss}
│
├── src/
│   ├── sass/
│   │   ├── lupin3rd.scss           # UNICO entry: @use di tutti i partial
│   │   ├── editor.scss             # stili dell'editor a blocchi
│   │   └── partials/_tokens.scss _reset.scss _layout.scss _typography.scss
│   │       _statusbar.scss _navbar.scss _buttons.scss _cards.scss _marks.scss
│   │       _tags.scss _post-lists.scss _filters.scss _empty-state.scss
│   │       _widgets.scss _forms.scss _tables.scss _prose.scss _code.scss
│   │       _toc.scss _search.scss _pagination.scss _breadcrumb.scss
│   │       _footer.scss _identity.scss _comments.scss _tabs.scss
│   │       _market.scss _utilities.scss
│   ├── js/
│   │   ├── lupin3rd.js             # UNICO entry: import dei moduli
│   │   └── modules/theme-switch.js mobile-nav.js search-overlay.js
│   │       taxonomy-filters.js code-copy.js table-of-contents.js
│   │       form-validation.js tabs.js modal.js
│   └── build/                      # copiata da understrap-child
│
├── css/    js/    languages/
```

### 2.2 Il pattern Singleton, concretamente

```php
// inc/trait-singleton.php
trait Singleton {
    private static ?self $instance = null;
    public static function instance(): static {
        return static::$instance ??= new static();
    }
    private function __construct() { $this->register_hooks(); }
    private function __clone() {}
    public function __wakeup(): void { throw new \LogicException( 'Cannot unserialize a singleton.' ); }
    abstract protected function register_hooks(): void;
}
```

`functions.php` non contiene logica: registra l'autoloader e chiama `Theme::instance()->boot()`. `Theme` è l'unico punto che conosce l'elenco dei moduli e li avvia in ordine; ogni modulo aggancia i propri hook nel proprio costruttore.

> **Nota onesta sul Singleton:** rende difficili i test unitari (non puoi iniettare un doppio) e nasconde le dipendenze. Per un tema è un compromesso accettabile e ti dà quello che vuoi: un solo punto d'ingresso, nessuna funzione globale sparsa, nessun doppio hook. Lo mitigo tenendo i moduli **sottili** e la logica pura in metodi statici testabili.

---

## 3. Mappa dei template: da FSE (OD) a tema classico

| Schermata | OD (FSE) | Tema classico | URL |
|---|---|---|---|
| Home | `templates/front-page.html` | `front-page.php` | `/` |
| Archivio generale | `templates/archive.html` | `archive.php` | `/archivio/` |
| Archivio categoria | `templates/category.html` | `category.php` | `/categoria/<slug>/` |
| Categoria Crypto | `templates/category-crypto.html` | `category-crypto.php` | `/categoria/crypto/` |
| Notizia | `templates/single.html` | `single.php` | `/2026/08/<slug>/` |
| Tutorial | `templates/single-tutorial.html` | `single-tutorial.php` | `/tutorial/<slug>/` |
| Dossier | `templates/single-dossier.html` | `single-dossier.php` | `/dossier/<slug>/` |
| Pagina statica | `templates/page.html` | `page.php` | `/<slug>/` |
| Ricerca | `templates/search.html` | `search.php` | `/?s=` |
| 404 | `templates/404.html` | `404.php` | — |
| Comunità | `templates/page-comunita.html` | `page-templates/template-community.php` | `/comunita/` | *(fase 2)* |
| Profilo membro | `templates/author.html` | `author.php` | `/membri/<pseudonimo>/` | *(fase 2)* |
| Accesso | `templates/page-accesso.html` | `page-templates/template-sign-in.php` | `/accesso/` | *(fase 2)* |
| Area account | `templates/page-account.html` | `page-templates/template-account.php` | `/account/` | *(fase 2)* |

Le sei `parts/` FSE (`header`, `statusbar`, `sidebar-filtri`, `account`, `sidebar-mercato`, `footer`) diventano i `template-parts/` elencati in §2.1.

---

## 4. Modello dei contenuti

**Tipi di post**

| Slug | Tipo | Campi extra |
|---|---|---|
| `post` | nativo — Notizia | fonte primaria, stato della verifica, data ultimo aggiornamento |
| `tutorial` | CPT | difficoltà, tempo stimato, prerequisiti, repository di laboratorio, hash del campione |
| `dossier` | CPT gerarchico | puntate ordinate manualmente, allegati documentali, nota metodologica **obbligatoria** |

**Tassonomie**

- `category` (nativa) — Sicurezza, Privacy, Hardware, OSINT, Crypto, Reverse engineering, Diritto & etica
- `difficulty` — `base` / `medio` / `avanzato`, solo su `tutorial`
- `post_tag` (nativa) — libera, curata a mano

**Permalink**: `/%year%/%monthnum%/%postname%/` per le notizie; `/tutorial/%postname%/` e `/dossier/%postname%/` per i CPT; feed per categoria e per tag.

Tutti i meta registrati con `register_post_meta` (`show_in_rest` dove serve all'editor, mai per i dati sensibili), niente plugin di campi personalizzati.

---

## 5. Fasi di lavoro

> **Stato al 13.09.2026 (sera), verificato sul sito locale.** Fasi 0–6 fatte. La fase 7 è stata verificata sulle pagine vere e regge: JSON-LD `Organization/WebSite/TechArticle/HowTo` valido, canonical, Open Graph con il PNG della marca generato e servito, `llms.txt`, `robots.txt`, feed, sitemap, `/wp/v2/users` che risponde 401, 404 che suggerisce il tag più vicino, filtri d'archivio che filtrano davvero lato server (`?l3_format=tutorial` → 3 righe su 9). Fase 8: audit di accessibilità fatto; Lighthouse no.
>
> **Manca per chiudere la fase 1**, in ordine di quanto costa accorgersene tardi:
>
> | # | Cosa | Dove |
> |---|---|---|
> | ~~1~~ | ~~Correlati e riquadro autore~~ — **fatti**: `template-parts/single/related.php` e `author-card.php`, con `Template_Tags::related()` che pesca prima fra i vicini di categoria e tag |  |
> | ~~2~~ | ~~Newsletter~~ — **fatta e interna**: modulo `Newsletter`, tipo di contenuto privato `l3_subscriber`, doppio opt-in, collegamento all'account. Resta da provare il giro completo del modulo |  |
> | 3 | **In standby per decisione.** Colonna mercato di Crypto e sua fonte dati (§7.18) | `template-parts/sidebar/widget-market.php` |
> | 4 | **In standby per decisione.** Il selettore di lingua punta a `/en/`, che in fase 1 è 404 | `template-parts/header/language-switcher.php` |
> | ~~5~~ | ~~«Segnala» nei commenti~~ — **fatto**: modulo `Comment_Reports`, modulo POST senza JavaScript, contatore e colonna in bacheca, nessun dato sul segnalante. Non nasconde niente da solo |  |
> | ~~6~~ | ~~File .pot~~ — **fatto**: `languages/lupin3rd.pot`, 161 stringhe, generato con `wp i18n make-pot` |  |
> | 7 | phpcs e phpstan **configurati** (`composer.json`, `phpcs.xml.dist`, `phpstan.neon.dist`, servizio `qa` in compose). **Mai eseguiti**: il proxy di rete da qui non raggiunge Packagist. La prima passata è tua | `docker compose --profile qa run --rm qa` |
> | 8 | L'interfaccia in redazione non è mai stata vista: pannello dei campi e blocco della pubblicazione senza «perimetro legale». Serve una sessione da amministratore, che non passa da me | fase 6, verifica |
> | ~~9~~ | ~~Test end-to-end~~ — **scritti**: `tools/e2e/`, 30 asserzioni su quattro file, zero dipendenze. **Mai eseguiti contro il sito**: da qui non raggiungo localhost:8080. `docker compose --profile qa run --rm e2e` |  |
> | 10 | `sync_from_od.py` da rilanciare, llmwiki da riallineare, `OD/struttura-wordpress.html` e `OD/en/wordpress-structure.html` ancora descrivono un tema a blocchi | fase 9 |
> | 11 | Decisioni aperte: repo solo-tema, `css/` e `js/` compilati dentro o fuori, deploy (§7.23–7.25) | prima del primo commit |
>
> ~~Stato al 12.09.2026: fasi 0–5 realizzate~~: il tema `lupin3rd` esiste in `wp/wp-content/themes/lupin3rd`, la build è verde, i 47 file PHP passano `php -l`, i 15 moduli si avviano con 38 hook registrati, il porting CSS è verificato 341 regole su 341. Restano: fase 6 (blocchi e pattern), fase 7 (SEO/GEO è scritta ma non verificata su pagine reali), fase 8 (audit) e fase 9 (rifinitura).


### Fase 0 — Fondamenta · ~0,5 g
1. ~~`wp-config.php` + database locale~~ **fatto**: stack Docker alla radice (`compose.yaml`, `docker/`, `wp/wp-config.php`, `.env`). Vedi §0.2.
2. `git init` sul progetto. Il `.gitignore` di base c'è già; il perimetro del repository resta da decidere (§7.23, §7.24).
3. Scaffold `wp-content/themes/lupin3rd/` con header `style.css`, `functions.php`, `index.php` minimo → il tema si attiva.
4. Copia e adattamento di `src/build/`, `package.json`, `.browserslistrc`, `phpcs.xml.dist`, `phpstan.neon.dist` da `understrap-child`; rimozione di Bootstrap, FontAwesome, jQuery, browser-sync se non serve.
5. `npm install` + verifica che `npm run dist` produca `css/lupin3rd.css` e `js/lupin3rd.js`.

**Verifica:** tema attivabile, build verde, phpcs verde su file vuoti.

### Fase 1 — Sistema visivo · ~1,5 g
1. Porting di `OD/assets/app.css` (41,8 KB, 44 sezioni) nei partial SCSS di §2.1, **una sezione = un partial**, ordine di cascata invariato.
2. `_tokens.scss` contiene i sei token OKLCH + derivati + tipografia + scala + spaziature. È l'unico punto in cui si definiscono colore e tipografia, come dichiara OD.
3. Variante chiara sotto `:root[data-theme="light"]`.
4. `theme.json`: stessi sei slug (`bg`, `surface`, `fg`, `muted`, `border`, `accent`), `"custom": false`, `appearanceTools: true`.
5. `src/sass/editor.scss` per far somigliare l'editor al front-end.

**Verifica:** diff visivo del CSS compilato contro `app.css` (stesso insieme di selettori, nessuna regola persa); confronto a schermo con le pagine del prototipo.

### Fase 2 — Nucleo PHP · ~1 g
`trait-singleton.php`, `class-theme.php`, `class-setup.php`, `class-assets.php`, `class-i18n.php`.
`Assets` accoda **un solo CSS e un solo JS**, con versione da `filemtime`, `defer` sullo script, e stampa nel `<head>` lo snippet inline che imposta `data-theme` **prima del primo paint** (vedi §7.10 per il nodo CSP).

**Verifica:** una pagina vuota carica esattamente due asset del tema, zero jQuery, zero richieste esterne.

### Fase 3 — Modello dei contenuti · ~1 g
`class-content-types.php`, `class-meta-fields.php`, permalink, regole di rewrite, flush al cambio tema. Dati di prova: 12 notizie, 6 tutorial, 2 dossier con puntate.

**Verifica:** i permalink di §4 risolvono; i meta compaiono e si salvano dall'editor.

### Fase 4 — Template e partial · ~3 g
Nell'ordine: `header.php` + `footer.php` + partial di testata → `front-page.php` → `archive.php` / `category.php` → `single.php` → `single-tutorial.php` → `single-dossier.php` → `page.php` → `search.php` → `404.php` → `comments.php` + walker.
Ogni template riusa esclusivamente le classi del sistema OD: nessuna classe nuova senza aggiungerla prima al foglio SCSS.

**Verifica:** confronto a schermo pagina per pagina con il prototipo, in tema scuro e chiaro, a 1440 / 920 / 640 / 375 px.

### Fase 5 — Comportamenti JS · ~1 g
Porting di `OD/assets/app.js` (i 3 IIFE) in moduli ES sotto `src/js/modules/`, con **un solo entry** `lupin3rd.js`. I moduli della comunità restano fuori dalla fase 1 ma il modulo è già predisposto. Ogni modulo esce subito se il suo aggancio non è in pagina.

**Verifica:** ogni pagina resta usabile con JavaScript disattivato (filtri, ricerca, indice, copia codice hanno tutti un fallback server).

### Fase 6 — Blocchi, pattern, editor · ~2 g
1. `l3/code` — intestazione, nome file, pulsante di copia, numerazione opzionale, **colorazione monocromatica lato server** (nessuna libreria di highlighting, come impone OD e come impone la CSP).
2. `l3/notice` — tre varianti: nota di metodo, perimetro legale, correzione.
3. Pattern `l3/tutorial-header` — occhiello, titolo, sommario, riga metadati, figura di apertura.
4. Vincolo editoriale: un `tutorial` senza blocco «perimetro legale» **non si pubblica** (validazione in editor + blocco server su `rest_pre_insert` / `wp_insert_post_data`).

**Verifica:** i blocchi si inseriscono, si salvano, si ri-renderizzano senza «contenuto non valido»; il blocco pubblicazione scatta davvero.

### Fase 7 — SEO e GEO · ~1,5 g
Vedi §6 per il dettaglio.

### Fase 8 — Sicurezza, prestazioni, accessibilità · ~1 g
`class-security.php`, `class-performance.php`, audit a11y.

### Fase 9 — Rifinitura e consegna · ~1 g
`screenshot.png`, file `.pot`, `readme.md` del tema, phpcs/phpstan puliti, Lighthouse, axe, `sync_from_od.py` rilanciato e llmwiki allineata al codice, aggiornamento di `OD/struttura-wordpress.html` + `OD/en/wordpress-structure.html`.

**Totale fase 1: ~13,5 giornate uomo.**

### Fase 2 (successiva)
Modulo comunità (3 ruoli, profili `/membri/`, accesso fuori da `wp-login.php`, 2FA TOTP, avatar da seed, area `/account/`, esportazione e cancellazione dati) · ~6 g
Multilingua Polylang (alberi IT/`/en/`, hreflang reciproci, menu e stringhe per lingua, formati data) · ~2 g
Flusso redazionale a 6 stati con stati personalizzati e revisione tecnica/legale · ~2 g

---

## 6. SEO e GEO

> **Assunzione da confermare:** interpreto **GEO** come *Generative Engine Optimization* — farsi trovare e citare correttamente dai motori generativi — e non come geolocalizzazione. Il sito non ha una sede pubblica («redazione distribuita, server in Islanda, nessun investitore»), quindi il local SEO non avrebbe senso. Se intendevi altro, cambia poco il lavoro ma cambia il capitolo.

**SEO tecnico (dentro il tema, nessuna suite — coerente con «plugin leggero, solo meta e sitemap» di OD)**
- `<title>` via `add_theme_support('title-tag')` + filtro `document_title_parts`
- meta description dall'occhiello/sommario, mai generata a caso
- canonical, `robots` per archivi paginati, `noindex` su risultati di ricerca e archivi autore
- Open Graph e Twitter Card — l'immagine è la **marca generativa renderizzata server-side** in PNG e messa in cache, non una foto
- sitemap XML nativa di WP, con autori e tipi privati esclusi
- feed RSS per categoria e per tag; feed autore disattivati
- `hreflang` reciproci (predisposto in fase 1, attivo in fase 2)
- heading in ordine, un solo `h1`, breadcrumb reale

**Dati strutturati JSON-LD (`class-schema.php`)**
`Organization` + `WebSite` (con `SearchAction`) sitewide; `NewsArticle` sulle notizie; `HowTo` + `TechArticle` sui tutorial (con `totalTime`, `supply`, `step` presi dagli `h2`); `Article` + `isPartOf` sui dossier; `BreadcrumbList` ovunque; `Person` con `sameAs` (chiave PGP, Matrix) sugli autori — **senza email, senza ultimo accesso**, come impone OD.

**GEO (`class-geo.php`)**
- `/llms.txt` e `/llms-full.txt` generati dal tema: chi siamo, licenza (CC BY-SA 4.0 sui testi, MIT sul codice), come citarci, indice delle sezioni
- **estratto citabile** in cima a ogni articolo: 2-3 frasi autonome, comprensibili fuori contesto — è anche buon giornalismo, non solo GEO
- riquadro «Verifica finale» / «In sintesi» come blocco strutturato, non prosa libera
- date esplicite (`datePublished`, `dateModified`) e **correzioni in fondo al pezzo mai cancellate**, già previste da OD: sono esattamente ciò che rende un articolo citabile con fiducia
- HTML semantico e stabile: gli slug non cambiano, i vecchi URL restano
- `robots.txt` che decide esplicitamente quali crawler generativi ammettere — **decisione editoriale, non tecnica: va presa in redazione**

---

## 7. Cosa potresti aver dimenticato

Ordinato per quanto costa accorgersene tardi.

### Bloccanti

**7.1 — ~~Non c'è un ambiente PHP funzionante~~ — RISOLTO.** Lo stack Docker di §0.2 copre `wp-config.php`, database, PHP 8.3, Redis e WP-CLI. Resta vero che io, da qui, non posso avviarlo né vederlo: la validazione l'ho fatta sulla sintassi e sulla coerenza fra compose, `.env`, Caddyfile e `wp-config.php`, non su un container in esecuzione. Il primo `docker compose up -d --build` va fatto da te.

**7.2 — ~~Meta key e data-attribute del prototipo sono in italiano~~ — DECISO (D-010).** OD specifica `l3_pseudonimo`, `l3_bio`, `l3_pgp_fpr`, `l3_avatar_seed`, `l3_interventi_ok`, e il JS usa `data-quando-dentro`, `data-carica-altri`, `data-forza`, `data-segnala`… Il tuo vincolo dice *tutto in inglese*. Sono contratti: rinominarli dopo costa. **Proposta:** identificatori di codice in inglese (`l3_pseudonym`, `data-when-signed-in`), slug pubblici e URL in italiano perché sono contenuto (`/tutorial/`, `/categoria/`, `/membri/`). Va decisa ora e va riflessa in `OD/` perché prototipo e tema non divergano.

**7.3 — ~~Lo switch tema senza lampo bianco confligge con la CSP~~ — DECISO (D-011).** OD vuole `data-theme` impostato prima del primo paint (script inline nel `<head>`) *e* una `Content-Security-Policy` senza script esterni. Uno script inline richiede `'unsafe-inline'` (che vanifica la CSP) oppure un **hash SHA-256** dello snippet, oppure un nonce per richiesta (che rompe la cache di pagina statica). **Consiglio:** hash fisso, calcolato in fase di build e scritto nell'header a livello di Nginx.

**7.4 — ~~L'HTML pubblico non deve dipendere dall'utente~~ — DECISO (D-017).** OD prevede pagine servite da file + Redis, ma anche una testata che cambia quando sei connesso. Se il PHP stampa lo stato sessione nell'HTML, ogni pagina diventa unica. **Soluzione:** la testata esce sempre in stato «disconnesso» e il JS la aggiorna leggendo un cookie non-HttpOnly minimale, oppure ESI/edge-include. Va deciso in fase 1, non in fase 2, perché condiziona il markup della testata.

### Costosi

**7.5 — Il sito non usa fotografie.** Le immagini sono **marche generative** (`.mark--bars/grid/scan` + una glifo monospace) e gli avatar sono pattern deterministici dal seed. Serve quindi: una funzione che deriva variante e glifo dallo slug o dall'ID in modo stabile; un fallback quando c'è comunque una featured image; e — per Open Graph, che vuole un file vero — un **renderer server-side che produce il PNG della marca e lo mette in cache**. Non è banale ed è facile scoprirlo alla fine.

**7.6 — I filtri d'archivio di OD nascondono contenuto lato client.** Bellissimi, ma: senza JS non funzionano, e un motore vede tutti i post stampati a prescindere dal filtro. Servono varianti URL reali (`/archivio/?categoria=osint`) che facciano la query server, con il filtro client come acceleratore. Vale anche per l'overlay di ricerca.

**7.7 — Il walker dei commenti va scritto da zero.** Il markup OD (`.comment`, `.comment-nested`, `.comment-tools`, «rispondi a», «segnala») non somiglia a niente che WordPress produca di suo. Aggiungi: moderazione preventiva, niente Gravatar (va filtrato `pre_get_avatar`), e commenti che restano firmati «account rimosso» dopo la cancellazione dell'utente.

**7.8 — Il vincolo «perimetro legale obbligatorio nei tutorial» è validazione vera.** Deve valere sia dall'editor a blocchi (REST) sia da WP-CLI sia da import. Due punti di controllo, non uno.

**7.9 — I campi custom senza plugin richiedono UI.** `register_post_meta` salva, ma non disegna niente. Per l'editor a blocchi serve un pannello React (`PluginDocumentSettingPanel`) — quindi un secondo bundle JS per l'editor, che **non** viola il vincolo «un solo file JS» perché è front-end vs back-end, ma va detto esplicitamente.

**7.10 — La 404 che «suggerisce i tag più vicini allo slug».** È logica di similarità (distanza di Levenshtein sui termini, o `LIKE` sugli slug). Piccola ma va progettata, e va messa in cache o diventa una query per ogni bot che sbaglia URL.

### Da non dimenticare

**7.11 — Font.** OD usa Times New Roman + monospace **di sistema**. Zero webfont: nessun Google Fonts, nessun file locale, nessun `@font-face`. Il child Understrap si porta dietro FontAwesome: va eliminato, non ereditato.

**7.12 — Stili dell'editor.** `theme.json` + `editor.scss`: in redazione devono vedere quello che verrà pubblicato, altrimenti impaginano a occhio.

**7.13 — Accessibilità.** Il prototipo fa già le cose giuste (skip-link, `:focus-visible`, `aria-current`, `aria-pressed`, `prefers-reduced-motion`, difficoltà codificata **con blocchi e non con colore**). Nel passaggio a PHP si perdono facilmente. Test tastiera + axe in fase 8, non alla fine.

**7.14 — Sicurezza lato tema:** XML-RPC spento, `/wp/v2/users` negato agli anonimi, `?author=1` che non rivela pseudonimi, feed autore disattivati, `generator` rimosso, filtro su `wp_handle_upload` che **spoglia i metadati EXIF prima delle miniature** (OD lo chiede esplicitamente ed è coerente con un sito che pubblica tutorial sui metadati).

**7.15 — Text domain `lupin3rd`** su ogni stringa da subito, `languages/lupin3rd.pot` generato. Riscrivere le stringhe dopo è lavoro doppio.

**7.16 — Formati data:** `d.m.Y` in italiano, `M j, Y` in inglese. Centralizzato in `class-i18n.php`, mai `date()` sparso nei template.

**7.17 — Widget e sidebar.** OD ha `.widget` / `.widget-title` e una sidebar di mercato per Crypto. Decidere se sono aree widget vere (registrabili) o partial fissi. Consiglio: partial fissi, più controllo e meno superficie.

**7.18 — La colonna di mercato Crypto prende dati da qualche parte.** Nel prototipo sono finti. In produzione: quale fonte, con che frequenza, in cache dove — e attenzione, una chiamata a un'API esterna a ogni pageview rompe sia la CSP sia la promessa «nessuna terza parte».

**7.19 — Paginazione e «carica altri».** Il prototipo ha `data-carica-altri`. Con cache statica un load-more via REST è ok; via admin-ajax no.

**7.20 — La newsletter del giovedì** ha un form in home. Quale servizio? Se è esterno, contraddice CSP e «niente terze parti». Probabilmente serve un raccoglitore interno.

**7.21 — `screenshot.png`** 1200×900, e il `readme.md` del tema con la licenza MIT.

**7.22 — phpcs e phpstan** sono già configurati in `understrap-child`: vanno portati nel tema nuovo e tenuti verdi dal primo commit, non sistemati alla fine.

**7.23 — Versionamento.** Il progetto non è un repo git. OD dice «il tema è pubblico, apri il repository»: se il tema andrà pubblicato, il repo dev'essere **solo il tema**, non tutto il WordPress. Vale la pena separare i due alberi ora.

**7.24 — CSS e JS compilati: dentro o fuori dal repo?** Se il tema si distribuisce come tema pubblico, `css/` e `js/` compilati devono esserci (chi lo scarica non lancia `npm install`). Se il repo è solo sorgente, si ignorano e si compila in deploy. Decisione da prendere prima del primo commit.

**7.25 — Deploy.** Nessuno strumento è configurato. rsync? git pull + build sul server? Non urgente, ma condiziona 7.24.

**7.26 — Backup e ripristino.** OD promette «snapshot cifrati fuori sede, ripristino provato ogni trimestre». È infrastruttura, non tema, ma se il colophon lo dichiara pubblicamente deve essere vero.

---

## 8. Ordine di esecuzione consigliato

```
Fase 0 ─┬─ Fase 1 (SCSS) ────┐
        └─ Fase 2 (nucleo) ──┼─ Fase 3 (contenuti) ─ Fase 4 (template) ─┬─ Fase 5 (JS)
                             │                                          ├─ Fase 6 (blocchi)
                             │                                          └─ Fase 7 (SEO/GEO)
                                                                          └─ Fase 8 ─ Fase 9
```

Fase 1 e Fase 2 sono indipendenti e si possono fare in parallelo. Fase 4 è il collo di bottiglia: tutto il resto la aspetta.

---

## 9. Decisioni prese sulle domande aperte

Tutte e nove chiuse il 12.09.2026. Il registro completo, con le alternative scartate e il perché, è in `docs/llmwiki/13-decisions.md`.

| # | Nodo | Decisione | ADR |
|---|---|---|---|
| 7.2 | Naming | Identificatori di codice in inglese ovunque; slug pubblici e URL in italiano perché sono contenuto e sono già indicizzati. `12-glossary.md` è la traduzione vincolante, ora congelata: `OD/` va riallineato in fase 9. | D-010 |
| 7.3 | CSP e switch tema | Lo snippet inline è ammesso **per hash** SHA-256, calcolato da `tools/csp-hash.py` e scritto nell'header dal web server. Niente `unsafe-inline`, niente nonce per richiesta. La policy vale **solo sul sito pubblico**: `/wp-admin` è escluso perché l'editor a blocchi richiede `unsafe-eval`. `style-src` tiene `unsafe-inline` perché i blocchi core emettono `<style>` inline; la severità che conta è su `script-src`. | D-011 |
| 7.4 | Stato sessione e cache | Il PHP rende **sempre** la testata da disconnesso; uno script la corregge leggendo un cookie minimo non-`HttpOnly` con pseudonimo e conteggio non letti. Il cookie di sessione WordPress resta `HttpOnly` e non viene mai letto da script. Entrambi gli stati stanno nella stessa scatola da 44px, così lo scambio non muove il layout. | D-017 |
| §6 | GEO | Generative Engine Optimization, confermato. | D-008 |
| 7.18 | Dati di mercato Crypto | Mai una richiesta dal browser del lettore. Job schedulato ogni 15 minuti in un transient, ultima risposta buona tenuta a parte come fallback, timestamp sempre stampato, oltre le due ore la colonna dichiara il dato non disponibile. Provider dietro un modulo `Market_Data`, sostituibile con una modifica a un file solo. | D-012 |
| 7.20 | Newsletter | Raccolta interna: tabella propria, doppio opt-in, token di conferma e di disiscrizione, export e cancellazione dagli strumenti privacy nativi. **Costo accettato consapevolmente:** spedire dal proprio server significa SPF, DKIM, DMARC e gestione dei bounce — è esattamente il motivo per cui quasi tutti usano un provider. | D-013 |
| 7.23 | Repository | Due repository: il **tema** pubblico MIT con dentro solo il tema; il **progetto** privato con `OD/`, `docs/`, `docker/`, `compose.yaml`, `tools/`. `wp/` in nessuno dei due: il core sono 60 MB di codice altrui che lo stack reinstalla a comando. Scartati submodule e bind mount del tema: funzionano, ma aggiungono un livello di indirezione che poi va spiegato a WP-CLI, phpcs, IDE e a ogni contributore. | D-014 |
| 7.24 | Asset compilati | Il repository del tema **committa** `css/` e `js/`: un tema scaricato deve funzionare senza `npm install`. `node_modules/` mai. Da presidiare: la deriva fra sorgenti e compilati — controllo in pre-commit o in CI, previsto in fase 9. | D-015 |
| §6 | Crawler generativi | Segue dalla licenza, non dall'umore. **Ammessi** quelli che leggono per rispondere citando (`OAI-SearchBot`, `Claude-SearchBot`, `PerplexityBot`) e i recuperi su richiesta del lettore (`ChatGPT-User`, `Claude-User`). **Non ammessi** i raccoglitori per corpora di addestramento (`GPTBot`, `ClaudeBot`, `meta-externalagent`, `CCBot`, `Google-Extended`), non perché addestrare sia illegittimo ma perché **lo share-alike di CC BY-SA non è onorabile da un set di pesi**: la condizione è inadempibile, quindi la licenza non è concessa. Motivo da scrivere in `llms.txt`. Due onestà da tenere nel file: `robots.txt` è una richiesta, non un controllo; e l'elenco invecchia, va rivisto ogni trimestre con la data. | D-016 |

Resta una sola cosa non decisa e non decidibile da qui: il **provider dei dati di mercato** (§7.18) è reversibile per costruzione, ma la scelta finale è della redazione.
