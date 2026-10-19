<?php partial('header', ['section' => '', 'pageTitle' => 'Page not found']); ?>
<div class="container page-404">
    <h1>404</h1>
    <p>We couldn't find that page. It may have been moved or removed.</p>
    <a class="btn btn-primary" href="<?= url() ?>">Back to Home</a>
</div>
<?php partial('footer'); ?>
