<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><?php if (!is_null($this->session->flashdata('message'))): ?>
    <div class="alert alert-info" role="alert">
        <?= $this->session->flashdata('message') ?>
    </div>
<?php endif; ?>
