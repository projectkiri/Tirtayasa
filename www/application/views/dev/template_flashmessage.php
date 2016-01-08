<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><?php if (!is_null($this->session->flashdata('message'))): ?>
    <div data-alert class="alert-box info radius">
        <?= $this->session->flashdata('message') ?>
    </div>
<?php endif; ?>
