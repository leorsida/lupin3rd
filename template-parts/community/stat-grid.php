<?php
/**
 * A row of counted things.
 *
 * @package Lupin3rd
 *
 * @var array{stats?: array<int, array{num: string, label: string}>} $args
 */

defined( 'ABSPATH' ) || exit;

$l3_stats = $args['stats'] ?? array();

if ( ! $l3_stats ) {
	return;
}
?>
<div class="statgrid">
	<?php foreach ( $l3_stats as $l3_stat ) : ?>
		<div class="stat">
			<span class="stat-num"><?php echo esc_html( (string) $l3_stat['num'] ); ?></span>
			<span class="stat-lab"><?php echo esc_html( (string) $l3_stat['label'] ); ?></span>
		</div>
	<?php endforeach; ?>
</div>
