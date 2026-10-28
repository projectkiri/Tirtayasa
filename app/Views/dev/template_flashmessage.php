<?php if (!is_null(session()->get('message'))): ?>
    <div class="alert alert-info" role="alert">
        <?= session()->get('message') ?>
    </div>
<?php endif; ?>
