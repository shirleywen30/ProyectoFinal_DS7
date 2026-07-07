    </main>
    <footer class="site-footer">
        <div class="site-footer-inner">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Proyecto académico desarrollado en PHP nativo + MySQL.</p>
            <p class="visit-counter">Visitantes totales: <strong><?= (int) ($totalVisits ?? 0) ?></strong></p>
            <p><a href="<?= BASE_URL ?>/views/admin/login.php">Acceso administrador</a></p>
        </div>
    </footer>
</div>
<script src="<?= asset('js/public.js') ?>"></script>
</body>
</html>
