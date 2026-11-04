<?php
// Rodapé comum + assistente Capi.
$capiContexto = $capiContexto ?? 'geral';
?>
<footer class="rodape">
    <p><strong>VendeAí</strong> — Encontrou, gostou, VendeAí!</p>
    <p class="pequeno">Projeto acadêmico • PHP, MySQL e XAMPP</p>
</footer>

<?php
// O Capi do canto aparece em todas as páginas, menos nas que pedirem $esconderCapi = true
if (empty($esconderCapi)) {
    include __DIR__ . '/capi.php';
}
?>
<script src="<?= url('assets/js/script.js') ?>"></script>
</body>
</html>
