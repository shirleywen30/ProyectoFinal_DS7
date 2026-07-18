    </main>
    <footer class="site-footer">
        <div class="site-footer-inner">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
            <p class="visit-counter">Visitantes totales: <strong><?= (int) ($totalVisits ?? 0) ?></strong></p>
        </div>
    </footer>
</div>
<script src="<?= asset('js/public.js') ?>"></script>
</body>
</html>
