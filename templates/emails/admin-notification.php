<div>
<h2>Nowe zgłoszenie: <?php echo esc_html($mode); ?></h2>
<p><strong>Klient:</strong> <?php echo esc_html($name); ?></p>
<p><strong>Email:</strong> <?php echo esc_html($email); ?></p>
<p><strong>Telefon:</strong> <?php echo esc_html($phone); ?></p>
<p><strong>Kwota:</strong> <?php echo esc_html($price); ?> zł</p>
<p><strong>Szczegóły:</strong> <?php echo esc_html($details); ?></p>
<p><strong>Uwagi:</strong><br><?php echo nl2br(esc_html($msg)); ?></p>
</div>
