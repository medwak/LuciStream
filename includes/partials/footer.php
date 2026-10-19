</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <a href="<?= url() ?>" class="logo"><span class="logo-mark">L</span><span class="logo-text"><?= e(config('site_name')) ?></span></a>
            <p class="muted"><?= e(config('description')) ?></p>
        </div>
        <div class="footer-links">
            <a href="<?= url('browse') ?>">Browse</a>
            <a href="<?= url('schedule') ?>">Schedule</a>
            <a href="<?= url('az') ?>">A-Z List</a>
            <a href="<?= url('mylist') ?>">My List</a>
        </div>
        <p class="muted small disclaimer">
            <?= e(config('site_name')) ?> does not store any files on its server. All contents are provided by non-affiliated third parties.
            &copy; <?= date('Y') ?> <?= e(config('site_name')) ?>.
        </p>
    </div>
</footer>
<button class="back-to-top" aria-label="Back to top" data-back-to-top>↑</button>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
