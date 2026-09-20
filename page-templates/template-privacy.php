<?php
/**
 * Template Name: Privacy
 *
 * Written from the code rather than from a model document: every row in the
 * tables below corresponds to something this theme actually writes, and the
 * «what we do not keep» list is short because it names only things that are
 * absent by construction — there is no field, no table and no request for
 * them.
 *
 * When the code changes, this page is wrong until somebody changes it too.
 * `docs/llmwiki/17-privacy.md` lists what to check.
 *
 * @package Lupin3rd
 */

use Lupin3rd\Authentication;
use Lupin3rd\Community;
use Lupin3rd\Template_Tags;

defined( 'ABSPATH' ) || exit;

get_header();

the_post();
?>

<section class="section">
	<div class="container stack">
		<?php
		Template_Tags::breadcrumb(
			array(
				array(
					'label' => __( 'Home', 'lupin3rd' ),
					'url'   => home_url( '/' ),
				),
				array(
					'label' => get_the_title(),
					'url'   => '',
				),
			)
		);
		?>

		<p class="eyebrow"><?php esc_html_e( 'Privacy · scritta guardando il codice, non un modello', 'lupin3rd' ); ?></p>
		<h1><?php the_title(); ?></h1>

		<div class="prose">
			<?php the_content(); ?>
		</div>
	</div>
</section>

<section class="section">
	<div class="container grid-main-side">
		<div class="stack prose">

			<h2><?php esc_html_e( 'In breve', 'lupin3rd' ); ?></h2>
			<p><?php esc_html_e( 'Leggere questo sito non richiede un account e non lascia traccia di te da nessuna parte. Non c\'è analitica, non ci sono pubblicità, non c\'è nessuno script di terze parti nelle pagine. Quello che conserviamo lo conserviamo perché ce l\'hai dato tu — un commento, un indirizzo per la newsletter, un account — e lo trovi elencato qui sotto riga per riga.', 'lupin3rd' ); ?></p>

			<h2><?php esc_html_e( 'Chi tratta i dati', 'lupin3rd' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: site name. */
					esc_html__( 'Il titolare del trattamento è la redazione di %s. Per qualunque richiesta relativa ai tuoi dati, scrivi all\'indirizzo pubblicato nella pagina dei contatti, possibilmente cifrando il messaggio con la chiave che trovi nella barra in cima al sito.', 'lupin3rd' ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>

			<h2><?php esc_html_e( 'Se leggi e basta', 'lupin3rd' ); ?></h2>
			<p><?php esc_html_e( 'Non raccogliamo niente. Nessun cookie viene scritto finché non fai qualcosa che ne richieda uno: commentare, iscriverti, entrare, o cambiare il tema della pagina. Il server registra le richieste per il tempo necessario a farlo funzionare e a difenderlo da un attacco, e quei registri non vengono usati per costruire profili né incrociati con nient\'altro.', 'lupin3rd' ); ?></p>

			<h2><?php esc_html_e( 'Se lasci un commento', 'lupin3rd' ); ?></h2>
			<p><?php esc_html_e( 'Conserviamo lo pseudonimo con cui firmi, l\'indirizzo email, l\'eventuale sito che indichi e il testo del commento. Lo pseudonimo e il testo sono pubblici: è il senso di un commento. L\'email no: serve alla moderazione e a poterti avvisare, non viene pubblicata e non finisce in nessuna lista.', 'lupin3rd' ); ?></p>
			<p><strong><?php esc_html_e( 'Non registriamo il tuo indirizzo IP né il tuo browser.', 'lupin3rd' ); ?></strong> <?php esc_html_e( 'WordPress li scriverebbe insieme al commento e li terrebbe per sempre; questo sito li scarta prima che vengano salvati. Contro lo spam usiamo la moderazione manuale dei primi commenti di ogni account e lo strumento di segnalazione, che non richiedono di sapere da dove scrivi.', 'lupin3rd' ); ?></p>
			<p><?php esc_html_e( 'Se spunti la casella «ricorda», il tuo browser tiene pseudonimo ed email per non fartele riscrivere. Sono cookie del tuo browser: cancellandoli spariscono, e noi non li leggiamo mai.', 'lupin3rd' ); ?></p>

			<h2><?php esc_html_e( 'Se ti iscrivi alla newsletter', 'lupin3rd' ); ?></h2>
			<p><?php esc_html_e( 'Conserviamo l\'indirizzo email e la data di iscrizione, e nient\'altro. L\'iscrizione vale solo dopo che hai seguito il link di conferma che ti arriva: così nessuno può iscrivere l\'indirizzo di qualcun altro. Non usiamo nessun servizio esterno di invio: gli indirizzi stanno in questo database e da nessun\'altra parte.', 'lupin3rd' ); ?></p>
			<p><?php esc_html_e( 'Nelle email non c\'è nessun tracciamento: niente immagini che segnalano l\'apertura, niente link riscritti per contare i clic. Ogni messaggio porta un collegamento per disiscriverti che funziona senza doverti autenticare.', 'lupin3rd' ); ?></p>

			<h2><?php esc_html_e( 'Se apri un account', 'lupin3rd' ); ?></h2>
			<p><?php esc_html_e( 'Chiediamo tre cose: uno pseudonimo, un indirizzo email e una password. Non chiediamo — e non vogliamo — nome reale, documento, numero di telefono, data di nascita, azienda o fotografia.', 'lupin3rd' ); ?></p>

			<table class="ds-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Cosa', 'lupin3rd' ); ?></th>
						<th><?php esc_html_e( 'Chi lo vede', 'lupin3rd' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr><td><?php esc_html_e( 'Pseudonimo, ruolo, data di iscrizione', 'lupin3rd' ); ?></td><td><?php esc_html_e( 'tutti', 'lupin3rd' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Due righe di presentazione, lingue, impronta PGP', 'lupin3rd' ); ?></td><td><?php esc_html_e( 'tutti, se le scrivi', 'lupin3rd' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Commenti pubblicati', 'lupin3rd' ); ?></td><td><?php esc_html_e( 'tutti', 'lupin3rd' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Indirizzo email', 'lupin3rd' ); ?></td><td><?php esc_html_e( 'solo la redazione', 'lupin3rd' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Password', 'lupin3rd' ); ?></td><td><?php esc_html_e( 'nessuno: è salvata come impronta, non in chiaro', 'lupin3rd' ); ?></td></tr>
				</tbody>
			</table>

			<p><?php esc_html_e( 'Il profilo pubblico non mostra mai l\'email, il paese, l\'ultimo accesso o l\'indirizzo da cui sei entrato. Non è una scelta di interfaccia: non esistono campi che possano contenerli.', 'lupin3rd' ); ?></p>
			<p><?php esc_html_e( 'L\'immagine di profilo non è Gravatar. È un motivo geometrico disegnato dal sito a partire da un codice casuale legato all\'account, quindi nessun servizio esterno riceve l\'impronta della tua email quando qualcuno apre una pagina.', 'lupin3rd' ); ?></p>
			<p><?php esc_html_e( 'La data di iscrizione la puoi nascondere dal profilo pubblico. Non teniamo traccia di quando entri: non esiste un registro degli accessi, ed è il motivo per cui il sito non mostra chi è in linea in questo momento.', 'lupin3rd' ); ?></p>

			<h2><?php esc_html_e( 'Cosa non facciamo', 'lupin3rd' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Nessuna analitica, nessun contatore, nessun pixel.', 'lupin3rd' ); ?></li>
				<li><?php esc_html_e( 'Nessuna pubblicità e nessuna profilazione, oggi e per scelta.', 'lupin3rd' ); ?></li>
				<li><?php esc_html_e( 'Nessun carattere tipografico, foglio di stile o script caricato da un altro dominio.', 'lupin3rd' ); ?></li>
				<li><?php esc_html_e( 'Nessuna cronologia di lettura: non sappiamo cosa hai letto né quanto ci sei rimasto.', 'lupin3rd' ); ?></li>
				<li><?php esc_html_e( 'Nessuna vendita, cessione o condivisione di dati con terzi.', 'lupin3rd' ); ?></li>
			</ul>

			<h2><?php esc_html_e( 'Cookie', 'lupin3rd' ); ?></h2>
			<p><?php esc_html_e( 'Tutti i cookie di questo sito sono suoi: nessuno appartiene a un altro dominio. Nessuno serve a profilare, e nessuno viene scritto prima che tu faccia qualcosa che lo richieda.', 'lupin3rd' ); ?></p>

			<table class="ds-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nome', 'lupin3rd' ); ?></th>
						<th><?php esc_html_e( 'A cosa serve', 'lupin3rd' ); ?></th>
						<th><?php esc_html_e( 'Durata', 'lupin3rd' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>wordpress_logged_in_*</code>, <code>wordpress_sec_*</code></td>
						<td><?php esc_html_e( 'Tenerti connesso. Scritti solo se accedi.', 'lupin3rd' ); ?></td>
						<td><?php esc_html_e( 'fino a 14 giorni', 'lupin3rd' ); ?></td>
					</tr>
					<tr>
						<td><code>l3_hint</code></td>
						<td><?php esc_html_e( 'Dice alla testata come chiamarti e quale avatar disegnare. Contiene pseudonimo, due iniziali e un percorso: niente che autorizzi qualcosa.', 'lupin3rd' ); ?></td>
						<td><?php esc_html_e( '14 giorni', 'lupin3rd' ); ?></td>
					</tr>
					<tr>
						<td><code>comment_author_*</code></td>
						<td><?php esc_html_e( 'Ricorda pseudonimo ed email nel modulo dei commenti. Scritti solo se spunti quella casella.', 'lupin3rd' ); ?></td>
						<td><?php esc_html_e( 'circa un anno', 'lupin3rd' ); ?></td>
					</tr>
					<tr>
						<td><code>l3_consent</code></td>
						<td><?php esc_html_e( 'Ricorda che hai già visto l\'avviso, così non te lo riproponiamo a ogni pagina.', 'lupin3rd' ); ?></td>
						<td><?php esc_html_e( '180 giorni', 'lupin3rd' ); ?></td>
					</tr>
					<tr>
						<td><code>l3-theme</code></td>
						<td><?php esc_html_e( 'Il tema chiaro o scuro che hai scelto. Non è un cookie: resta nel tuo browser e non viene mai spedito al server.', 'lupin3rd' ); ?></td>
						<td><?php esc_html_e( 'finché non lo cancelli', 'lupin3rd' ); ?></td>
					</tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Riquadri incorporati', 'lupin3rd' ); ?></h3>
			<p><?php esc_html_e( 'Ogni tanto un articolo incorpora qualcosa che sta altrove — un video, una mappa, un documento. Quel riquadro, per caricarsi, fa sapere a chi lo ospita che sei su questa pagina, e da lì in poi vale la privacy di quel sito, non la nostra. Per questo non lo carichiamo da soli: al suo posto trovi un riquadro vuoto che dice quale dominio verrebbe contattato. Se vuoi vederlo lo carichi tu, quella volta soltanto: non esiste un\'opzione per permetterli tutti, perché non vogliamo ricordarci una scelta del genere al posto tuo.', 'lupin3rd' ); ?></p>

			<h2><?php esc_html_e( 'Per quanto tempo', 'lupin3rd' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'I commenti pubblicati restano finché resta l\'articolo: sono parte della discussione.', 'lupin3rd' ); ?></li>
				<li><?php esc_html_e( 'Un indirizzo iscritto alla newsletter resta finché non ti disiscrivi. Un\'iscrizione mai confermata viene cancellata.', 'lupin3rd' ); ?></li>
				<li><?php esc_html_e( 'I dati dell\'account restano finché l\'account esiste.', 'lupin3rd' ); ?></li>
			</ul>

			<h2><?php esc_html_e( 'I tuoi diritti', 'lupin3rd' ); ?></h2>
			<p><?php esc_html_e( 'Puoi chiedere di vedere quello che abbiamo su di te, di correggerlo, di portartelo via o di cancellarlo, e puoi opporti a un trattamento o revocare un consenso che avevi dato. Scrivici e rispondiamo entro trenta giorni. Se pensi che stiamo sbagliando, puoi rivolgerti al Garante per la protezione dei dati personali.', 'lupin3rd' ); ?></p>
			<p><?php esc_html_e( 'Due cose diverse, ed è giusto saperlo prima di scegliere. Dal tuo account puoi chiuderlo da solo, subito: sparisci dal sito, non entri più, e quello che hai scritto resta nelle discussioni a firma «account rimosso». Chiudere però non cancella: il tuo indirizzo e quello che avevi scritto nel profilo restano nel nostro database, raggiungibili solo dalla redazione.', 'lupin3rd' ); ?></p>
			<p><?php esc_html_e( 'Se vuoi che spariscano davvero, chiedicelo: la cancellazione vera la facciamo a mano, e ti rispondiamo entro trenta giorni. In nessuno dei due casi spariscono i commenti già pubblicati, e una correzione che hai portato a un articolo non se ne va perché te ne sei andato.', 'lupin3rd' ); ?></p>

			<h2><?php esc_html_e( 'Dove stanno i dati', 'lupin3rd' ); ?></h2>
			<p><?php esc_html_e( 'Su un server che gestiamo noi, in Europa. Non ci sono trasferimenti verso paesi terzi, perché non c\'è nessun servizio esterno a cui trasferirli.', 'lupin3rd' ); ?></p>
		</div>

		<aside>
			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Cosa resta nel browser', 'lupin3rd' ); ?></p>
				<p class="widget-note"><?php esc_html_e( 'L\'avviso con l\'elenco completo di quello che resta nel tuo browser si riapre quando vuoi.', 'lupin3rd' ); ?></p>
				<p>
					<button class="btn btn-secondary btn-sm" type="button" data-consent-open hidden>
						<?php esc_html_e( 'Rivedi l\'avviso', 'lupin3rd' ); ?>
					</button>
				</p>
			</div>

			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Il tuo account', 'lupin3rd' ); ?></p>
				<p class="widget-note"><?php esc_html_e( 'Da lì cambi quello che di te si vede, scegli se ricevere avvisi e chiudi l\'account. Per l\'esportazione e per una cancellazione vera, scrivi alla redazione.', 'lupin3rd' ); ?></p>
				<p>
					<a class="btn btn-secondary btn-sm" href="<?php echo esc_url( Authentication::account_url() ); ?>">
						<?php esc_html_e( 'Vai al tuo profilo', 'lupin3rd' ); ?>
					</a>
				</p>
			</div>

			<div class="widget">
				<p class="widget-title"><?php esc_html_e( 'Come si modera', 'lupin3rd' ); ?></p>
				<p class="widget-note">
					<?php
					printf(
						/* translators: %d: how many contributions are read before an account leaves the queue. */
						esc_html__( 'I primi %d commenti di ogni account vengono letti prima di comparire. È la difesa dallo spam che ci siamo scelti al posto di registrare da dove scrivi.', 'lupin3rd' ),
						(int) Community::MODERATION_THRESHOLD
					);
					?>
				</p>
			</div>
		</aside>
	</div>
</section>

<?php
get_footer();
