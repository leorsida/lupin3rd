#!/usr/bin/env bash
#
# Seed the local site with enough content to exercise every template.
#
# Idempotent: every object is looked up by slug and then created or brought up
# to date, so running it twice is safe and re-running it after editing this
# file actually applies the edit. Development only — it overwrites the demo
# content it owns, so never point it at a real installation.
#
#   ./tools/seed-demo-content.sh                 create or update
#   ./tools/seed-demo-content.sh --reset         clean up, then create or update
#   ./tools/seed-demo-content.sh --trash-dupes   move duplicates to the trash only
#   ./tools/seed-demo-content.sh --status        list what is published
#   ./tools/seed-demo-content.sh --fix-authors   sign pieces left at post_author 0
#
set -euo pipefail

# Two things every call needs.
#
# 1. `docker compose run` attaches stdin. A wp call inside a `... | while read`
#    loop would swallow the rest of the loop's input, which is exactly how the
#    first version of the cleanup below did nothing at all.
#
# 2. `--user`. WP-CLI with no user has no `unfiltered_html` capability, so
#    kses runs over the content and strips HTML comments — which is what block
#    delimiters are. Seeding block content without a user therefore stores
#    plain HTML with every `<!-- wp:… -->` removed, silently. Here that meant
#    the tutorials lost their legal-scope notice and the theme correctly
#    refused to publish them.
# The theme is its own repository, so this script no longer sits next to the
# compose file: find it by walking up, and run from there, so it works from any
# directory.
STACK="$(cd "$(dirname "$0")" && pwd)"
while [ "$STACK" != "/" ] && [ ! -f "$STACK/compose.yaml" ]; do
  STACK="$(dirname "$STACK")"
done
if [ ! -f "$STACK/compose.yaml" ]; then
  echo "compose.yaml non trovato sopra $(dirname "$0"): lancia lo script dentro l'albero del progetto." >&2
  exit 1
fi
cd "$STACK"

wp_raw() { docker compose run --rm -T wp "$@" </dev/null; }

ADMIN_ID="$(wp_raw user list --role=administrator --format=ids 2>/dev/null | tr -d '\r' | awk '{print $1}')"
if [ -z "$ADMIN_ID" ]; then
  echo "Nessun amministratore trovato: i blocchi verrebbero spogliati da kses." >&2
  exit 1
fi

wp() { wp_raw --user="$ADMIN_ID" "$@"; }

# Return the ID of a post with this slug, or nothing.
find_post() { wp post list --post_type="$1" --name="$2" --post_status=any --field=ID --format=ids 2>/dev/null | tr -d '\r' | awk '{print $1}'; }

# Create the post, or bring an existing one up to date. Echoes the ID either
# way, and says on stderr which of the two it did.
#
# Updating matters: without it, changing the seed's content here would have no
# effect on a site that has already been seeded — the slug is taken, the post
# is skipped, and the script silently does nothing. That is what "idempotent"
# must not be allowed to mean.
ensure_post() {
  local type="$1" slug="$2" title="$3" excerpt="$4" content="$5" parent="${6:-0}" order="${7:-0}"
  local id; id="$(find_post "$type" "$slug")"

  if [ -n "$id" ]; then
    wp post update "$id" \
      --post_title="$title" --post_excerpt="$excerpt" --post_content="$content" \
      --post_parent="$parent" --menu_order="$order" --post_author="$ADMIN_ID" --post_status=publish >/dev/null
    echo "    updated $type $id ($slug)" >&2
    echo "$id"
    return
  fi

  # --user sets who is running the command, not who signs the piece: WP-CLI
  # leaves post_author at 0 unless it is told. An unsigned piece has no author
  # card and no author in its JSON-LD.
  id=$(wp post create --porcelain \
    --post_type="$type" --post_status=publish --post_name="$slug" \
    --post_title="$title" --post_excerpt="$excerpt" --post_content="$content" \
    --post_parent="$parent" --menu_order="$order" --post_author="$ADMIN_ID" | tr -d '\r')
  echo "    created $type $id ($slug)" >&2
  echo "$id"
}

# --- optional cleanup -------------------------------------------------------
# A duplicate is a post with the same type and title as an earlier one. Slug
# suffixes are not a reliable signal: a duplicated dossier keeps its children
# under their original slugs, so they hide under a "-2" parent.
#
# Everything is moved to the trash, never deleted, so it stays recoverable from
# the admin until somebody empties it on purpose.
RESET=""
if [ "${1:-}" = "--reset" ]; then RESET="yes"; fi

if [ "${1:-}" = "--trash-dupes" ] || [ -n "$RESET" ]; then
  echo "==> looking for duplicates"
  # Done in one pass inside WordPress rather than by parsing CSV in the shell:
  # titles contain commas, and a duplicated dossier keeps its instalments under
  # their original slugs, so neither the slug nor a CSV column is a reliable
  # signal. Same type plus same title is.
  wp eval '
    $types = array( "post", "tutorial", "dossier", "page" );
    $seen  = array();
    $n     = 0;
    foreach ( get_posts( array(
        "post_type"   => $types,
        "post_status" => array( "publish", "draft", "pending", "private" ),
        "numberposts" => -1,
        "orderby"     => "ID",
        "order"       => "ASC",
    ) ) as $post ) {
        $key = $post->post_type . "|" . $post->post_title;
        if ( isset( $seen[ $key ] ) ) {
            foreach ( get_posts( array(
                "post_type"   => $post->post_type,
                "post_parent" => $post->ID,
                "numberposts" => -1,
                "post_status" => "any",
            ) ) as $child ) {
                wp_trash_post( $child->ID );
                printf( "    trashed %s %d (instalment of %d)\n", $child->post_type, $child->ID, $post->ID );
                $n++;
            }
            wp_trash_post( $post->ID );
            printf( "    trashed %s %d — %s\n", $post->post_type, $post->ID, $post->post_title );
            $n++;
            continue;
        }
        $seen[ $key ] = true;
    }
    printf( "%d moved to the trash\n", $n );
  '
  echo
  echo "==> what is left"
  for type in post tutorial dossier page; do
    printf '    %-9s %s\n' "$type" "$(wp post list --post_type="$type" --post_status=publish --format=count | tr -d '\r')"
  done
  echo
  echo "Nothing was deleted permanently."
  [ -n "$RESET" ] || exit 0
  echo
fi

# --- fix authors ------------------------------------------------------------
# Pieces created before `--post_author` was added to this script sit at
# post_author 0: no byline, no author card, no `author` in the JSON-LD, and no
# member profile for the e2e tests to find. Re-seeding would fix it and throw
# away everything else; this signs them where they are.
if [ "${1:-}" = "--fix-authors" ]; then
  echo "==> signing unsigned pieces as user ${ADMIN_ID}"
  wp eval '
    $fixed = 0;
    foreach ( get_posts( array(
      "post_type"   => array( "post", "tutorial", "dossier" ),
      "post_status" => "any",
      "numberposts" => -1,
      "fields"      => "ids",
    ) ) as $id ) {
      if ( 0 === (int) get_post_field( "post_author", $id ) ) {
        wp_update_post( array( "ID" => $id, "post_author" => '"$ADMIN_ID"' ) );
        $fixed++;
      }
    }
    echo $fixed . " piece(s) signed\n";
  '
  exit 0
fi

# --- check ------------------------------------------------------------------
# Says what is actually stored, which is the only way to tell a content problem
# from a tooling one. Reports, for every tutorial: its status, whether the block
# delimiters survived, and whether the legal-scope notice is there.
if [ "${1:-}" = "--check" ]; then
  echo "admin user id: ${ADMIN_ID}"
  echo
  wp eval '
    $rows = get_posts( array(
      "post_type"   => array( "tutorial", "dossier", "post" ),
      "post_status" => array( "publish", "draft", "pending", "private" ),
      "numberposts" => -1,
      "orderby"     => "post_type",
    ) );
    foreach ( $rows as $p ) {
      $has_delims = str_contains( $p->post_content, "<!-- wp:" );
      $blocks     = array_filter( array_map( fn( $b ) => $b["blockName"], parse_blocks( $p->post_content ) ) );
      $legal      = class_exists( "Lupin3rd\\Blocks" )
        ? ( Lupin3rd\Blocks::has_legal_notice( $p->post_content ) ? "yes" : "no" )
        : "?";
      printf(
        "%-9s %-8s delimiters:%-4s legal:%-4s blocks:[%s]  %s\n",
        $p->post_type, $p->post_status,
        $has_delims ? "yes" : "NO", $legal,
        implode( ",", array_unique( $blocks ) ),
        mb_substr( $p->post_title, 0, 46 )
      );
      if ( "tutorial" === $p->post_type ) {
        printf( "          stored: %s\n", str_replace( "\n", " ", mb_substr( $p->post_content, 0, 150 ) ) );
      }
    }
  '
  exit 0
fi

# --- status -----------------------------------------------------------------
if [ "${1:-}" = "--status" ]; then
  for type in post tutorial dossier page; do
    printf '%-9s %s published\n' "$type" "$(wp post list --post_type="$type" --post_status=publish --format=count | tr -d '\r')"
    wp post list --post_type="$type" --post_status=publish --fields=ID,post_title --format=csv 2>/dev/null \
      | tr -d '\r' | tail -n +2 | sed 's/^/    /'
  done
  exit 0
fi

echo "==> site options"
wp option update blogname "lupin3rd.org"
wp option update blogdescription "Quello che l'informatica fa quando nessuno guarda."
wp option update timezone_string "Europe/Rome"
wp option update posts_per_page 9
wp rewrite structure '/%year%/%monthnum%/%postname%/' --hard

echo "==> taxonomies"
for pair in "Sicurezza:sicurezza" "Privacy:privacy" "Hardware:hardware" "OSINT:osint" \
            "Crypto:crypto" "Reverse engineering:reverse-engineering" "Diritto & etica:diritto-etica"; do
  wp term create category "${pair%%:*}" --slug="${pair##*:}" >/dev/null 2>&1 || true
done
for pair in "Base:base" "Medio:medio" "Avanzato:avanzato"; do
  wp term create difficulty "${pair%%:*}" --slug="${pair##*:}" >/dev/null 2>&1 || true
done
for tag in osint firmware anonimato ctf disclosure forense metadati router; do
  wp term create post_tag "$tag" --slug="$tag" >/dev/null 2>&1 || true
done

echo "==> news"
n1=$(ensure_post post "il-ponte-che-ha-perso-41-milioni-in-nove-minuti" \
  "Il ponte che ha perso 41 milioni in nove minuti" \
  "Una firma accettata due volte, nove minuti di finestra, e un ponte cross-chain svuotato. Abbiamo rifatto la cronologia dai log pubblici." \
  "<p>Un ponte cross-chain accetta una prova di deposito su una catena e libera i fondi corrispondenti su un'altra. Il 22 agosto uno di questi ha liberato fondi per una prova che aveva già onorato.</p><h2>Cosa fa un ponte, in due passaggi</h2><p>Il primo passaggio blocca. Il secondo libera. Fra i due c'è una firma, ed è lì che si è rotto.</p><h2>I nove minuti</h2><p>Il primo prelievo anomalo e l'ultimo distano nove minuti e undici secondi.</p>")
wp post term set "$n1" category crypto >/dev/null

n2=$(ensure_post post "tor-non-e-un-mantello-dellinvisibilita" \
  "Tor non è un mantello dell'invisibilità" \
  "Tor protegge il percorso, non il contenuto e non le tue abitudini. Cosa copre davvero, e cosa continua a lasciare scoperto." \
  "<p>Tor anonimizza il percorso di rete. Non anonimizza chi sei quando fai login, né il modo in cui scrivi.</p><h2>Cosa copre</h2><p>L'indirizzo di partenza, verso il servizio di destinazione.</p><h2>Cosa non copre</h2><p>Il livello applicativo: sessioni, metadati dei file, stile di scrittura.</p>")
wp post term set "$n2" category privacy >/dev/null
wp post term set "$n2" post_tag anonimato >/dev/null

n3=$(ensure_post post "nis2-e-ricercatori-indipendenti" \
  "NIS2 e ricercatori indipendenti" \
  "La direttiva parla di segnalazione coordinata. Cosa cambia, in pratica, per chi trova una vulnerabilità e non lavora per nessuno." \
  "<p>La disclosure coordinata entra nel testo. Il punto è chi si assume il rischio nel frattempo.</p><h2>Cosa dice il testo</h2><p>Segnalazione entro finestre definite, a un punto di contatto nazionale.</p>")
wp post term set "$n3" category diritto-etica >/dev/null
wp post term set "$n3" post_tag disclosure >/dev/null

echo "==> tutorials"
# Each one carries a legal-scope notice: without it the theme refuses to
# publish a tutorial, and the seed would quietly produce drafts.
t1=$(ensure_post tutorial "metadati-cosa-raccontano-le-tue-foto-senza-chiedertelo" \
  "Metadati: cosa raccontano le tue foto senza chiedertelo" \
  "Un'immagine porta con sé molto più di quello che mostra: modello, orario, spesso le coordinate. Come leggerli, come interpretarli e come toglierli prima di pubblicare." \
  "<!-- wp:l3/notice {\"variant\":\"legal\"} --><!-- wp:paragraph --><p>Questo tutorial riguarda file e dispositivi che possiedi o che sei autorizzato a trattare. Provare queste tecniche su sistemi altrui, senza un incarico scritto, è un reato in quasi ogni giurisdizione.</p><!-- /wp:paragraph --><!-- /wp:l3/notice --><!-- wp:heading --><h2>Cosa c'è dentro un file, davvero</h2><!-- /wp:heading --><!-- wp:paragraph --><p>EXIF, XMP e IPTC sono tre contenitori diversi nello stesso file.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Leggere: il campione di prova</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Partiamo da un file che puoi scaricare e verificare.</p><!-- /wp:paragraph --><!-- wp:l3/code {\"language\":\"bash\",\"filename\":\"lettura dei metadati\",\"content\":\"# copia di lavoro, l\u2019originale resta intatto\\ncp IMG_2291.jpg lab/IMG_2291.copia.jpg\\n\\n# elenco completo, gruppo per gruppo\\nexiftool -G1 -a -s lab/IMG_2291.copia.jpg\\n\\n# solo i campi che pesano sulla privacy\\nexiftool -GPS* -SerialNumber -OwnerName lab/IMG_2291.copia.jpg\"} /--><!-- wp:l3/notice {\"variant\":\"method\"} --><!-- wp:paragraph --><p>Su un file scaricato da terzi verifica l\u2019hash prima e dopo: se cambia qualcosa che non hai toccato tu, lo strumento sta riscrivendo il contenitore e non solo i campi che hai chiesto.</p><!-- /wp:paragraph --><!-- /wp:l3/notice --><h2>Interpretare senza dedurre troppo</h2><p>Un orario non è una prova. È un indizio con un fuso orario addosso.</p><h2>Ripulire prima di pubblicare</h2><p>Ri-codificare l'immagine è più affidabile che cancellare campo per campo.</p><h2>Verifica finale</h2><p>Ricontrolla il file d'uscita con lo stesso strumento con cui hai letto quello d'ingresso.</p>")
wp post term set "$t1" difficulty base >/dev/null
wp post term set "$t1" category privacy >/dev/null
wp post term set "$t1" post_tag metadati forense >/dev/null
wp post meta update "$t1" l3_estimated_time 25 >/dev/null
wp post meta update "$t1" l3_prerequisites "Un terminale
exiftool installato
Un'immagine di prova che puoi buttare" >/dev/null
wp post meta update "$t1" l3_lab_repository "https://example.org/lupin3rd/lab-metadati" >/dev/null
wp post meta update "$t1" l3_sample_hash "9f2c4b11a7d3e5f08c1b6a49e2d7350fbc84e91a2d6f5c03b7e8a1d4c9f60253" >/dev/null

t2=$(ensure_post tutorial "quattro-pin-sul-pcb-la-seriale-di-un-router" \
  "Quattro pin sul PCB: la seriale di un router" \
  "Trovare, identificare e parlare con l'interfaccia seriale di un dispositivo consumer, senza dissaldare niente." \
  "<!-- wp:l3/notice {\"variant\":\"legal\"} --><!-- wp:paragraph --><p>Questo tutorial riguarda file e dispositivi che possiedi o che sei autorizzato a trattare. Provare queste tecniche su sistemi altrui, senza un incarico scritto, è un reato in quasi ogni giurisdizione.</p><!-- /wp:paragraph --><!-- /wp:l3/notice --><h2>Trovare i quattro pin</h2><p>Cerca una fila di fori equidistanti vicino al SoC.</p><h2>Identificarli senza indovinare</h2><p>Massa per continuità, alimentazione col multimetro, TX all'accensione.</p><h2>Parlare col dispositivo</h2><p>115200 8N1 è il punto di partenza più probabile.</p>")
wp post term set "$t2" difficulty avanzato >/dev/null
wp post term set "$t2" category hardware >/dev/null
wp post term set "$t2" post_tag firmware router >/dev/null
wp post meta update "$t2" l3_estimated_time 90 >/dev/null

t3=$(ensure_post tutorial "ghidra-per-chi-arriva-da-ida" \
  "Ghidra per chi arriva da IDA" \
  "Le corrispondenze che contano, le differenze che fanno perdere un pomeriggio, e le scorciatoie che vale la pena reimparare." \
  "<!-- wp:l3/notice {\"variant\":\"legal\"} --><!-- wp:paragraph --><p>Questo tutorial riguarda file e dispositivi che possiedi o che sei autorizzato a trattare. Provare queste tecniche su sistemi altrui, senza un incarico scritto, è un reato in quasi ogni giurisdizione.</p><!-- /wp:paragraph --><!-- /wp:l3/notice --><h2>La mappa dei comandi</h2><p>Quello che in IDA è una scorciatoia, qui è spesso una finestra.</p><h2>Il decompilatore</h2><p>Leggi il risultato come un'ipotesi, non come sorgente.</p>")
wp post term set "$t3" difficulty medio >/dev/null
wp post term set "$t3" category reverse-engineering >/dev/null
wp post meta update "$t3" l3_estimated_time 40 >/dev/null

echo "==> dossier"
d=$(ensure_post dossier "il-mercato-grigio-degli-zero-day" \
  "Il mercato grigio degli zero-day" \
  "Chi compra, chi vende, a che prezzo e sotto quale giurisdizione. Un'inchiesta in quattro puntate." \
  "<h2>Perché questa inchiesta</h2><p>Il mercato esiste, è legale in gran parte, ed è opaco per scelta.</p>")
wp post term set "$d" category sicurezza >/dev/null
wp post meta update "$d" l3_method_note "Ogni cifra in queste pagine viene da almeno due fonti indipendenti, o è dichiarata come stima con il suo margine." >/dev/null
ensure_post dossier "puntata-1-chi-compra" "Puntata 1 — chi compra" \
  "I compratori istituzionali, gli integratori, e i broker che stanno in mezzo." \
  "<h2>I compratori istituzionali</h2><p>Agenzie, integratori, e i broker che stanno in mezzo.</p>" "$d" 1 >/dev/null
ensure_post dossier "puntata-2-quanto-costa" "Puntata 2 — quanto costa" \
  "Listini e sconti: il prezzo dipende più dalla catena di exploit che dal singolo bug." \
  "<h2>Listini e sconti</h2><p>Il prezzo dipende più dalla catena di exploit che dal bug.</p>" "$d" 2 >/dev/null

echo "==> pages, front page and archive"
ensure_post page "chi-siamo" "Chi siamo" "Sei persone, nessun ufficio, una regola sola." \
  "<p>Redazione distribuita, server in Islanda, nessun investitore.</p><h2>Tre regole</h2><p>Testo di prova.</p>" >/dev/null
ensure_post page "linee-guida" "Linee guida" "Cosa ci mandi, come lo trattiamo, cosa succede dopo." \
  "<p>Tre formati, tre aspettative diverse.</p><h2>Cosa deve esserci nel file</h2><p>Testo di prova.</p>" >/dev/null
ensure_post page "cassetta-sicura" "Cassetta sicura" "Materiale sensibile: solo SecureDrop o email cifrata." \
  "<p>Mai dai commenti, mai da un modulo di contatto.</p><h2>Sei cose, nell'ordine</h2><p>Testo di prova.</p>" >/dev/null
ensure_post page "struttura" "Struttura del sito" "Come è costruito lupin3rd.org: colophon tecnico." \
  "<p>Tema proprietario, tre tipi di post, tre tassonomie, nessun page builder.</p><h2>Gerarchia dei template</h2><p>Testo di prova.</p>" >/dev/null

# /comunita/ is a page with a template, not a route the theme invents: the
# heading, the standfirst and the closing paragraph are editorial copy the desk
# has to be able to rewrite. Everything counted on that page comes from the
# database, not from this content.
comm_id=$(ensure_post page "comunita" "Chi legge, corregge e alza la mano" \
  "Un account qui serve a una cosa sola: firmare quello che scrivi sotto gli articoli e risponderne." \
  "<p>Un account qui serve a una cosa sola: firmare quello che scrivi sotto gli articoli e risponderne. Niente bacheca personale, niente punteggi, niente rete di amicizie. Pseudonimo, chiave PGP se la usi, e la traccia pubblica dei tuoi interventi.</p>")
wp post meta update "$comm_id" _wp_page_template "page-templates/template-community.php" >/dev/null

# The archive is the blog index: it only gets a URL of its own once a page is
# assigned as the posts page.
home_id=$(ensure_post page "home" "Home" "" "")
arch_id=$(ensure_post page "archivio" "Archivio" "" "")
wp option update show_on_front page
wp option update page_on_front "$home_id"
wp option update page_for_posts "$arch_id"

echo "==> menu"
wp menu create "Principale" >/dev/null 2>&1 || true
if [ "$(wp menu item list principale --format=count 2>/dev/null | tr -d '\r')" = "0" ]; then
  wp menu item add-custom principale "Notizie" "/" >/dev/null
  wp menu item add-custom principale "Tutorial" "/tutorial/" >/dev/null
  wp menu item add-custom principale "Dossier" "/dossier/" >/dev/null
  wp menu item add-custom principale "Archivio" "/archivio/" >/dev/null
  wp menu item add-custom principale "Crypto" "/categoria/crypto/" >/dev/null
fi
wp menu location assign principale primary >/dev/null 2>&1 || true

wp rewrite flush --hard
echo
echo "Done. Home: http://localhost:8080/  Archive: http://localhost:8080/archivio/"
