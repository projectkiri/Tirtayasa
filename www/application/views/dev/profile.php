<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
    <head>
        <title>Profile | KIRI Developers</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="author" content="Project Kiri (KIRI)" />
        <meta name="google-site-verification" content="9AtqvB-LWohGnboiTyhtZUXAEcOql9B-8lDjo_wcUew" />
        <link rel="stylesheet" href="/ext/foundation/css/foundation.min.css" />
        <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
        <script src="/ext/foundation/js/vendor/modernizr.js"></script>
    </head>
    <body>
        <?php $this->load->view('dev/template_topbar'); ?>
        <div class="row">
            <div class="large-3 columns">&nbsp;</div>
            <div class="large-6 columns">
                &nbsp;
                <?php if (!is_null($this->session->flashdata('message'))): ?>
                    <div data-alert class="alert-box info radius">
                        <?= $this->session->flashdata('message') ?>
                    </div>
                <?php endif; ?>
                <form data-abide action="/dev/profile" method="POST">
                    <input type="hidden" name="post" value="true"/>                    
                    <label>
                        E-mail:
                        <input type="email" name="email" disabled value="<?= $email ?>"/>
                    </label>
                    <label>
                        Password:
                        <input type="password" name="password"/>
                    </label>
                    <label>
                        Password again:
                        <input type="password" name="confirmpassword"/>
                    </label>
                    <label>
                        Full Name:
                        <input type="text" name="fullname" required value="<?= $fullname ?>"/>
                    </label>
                    <label>
                        Company (optional):
                        <input type="text" name="company" required value="<?= $company ?>"/>
                    </label>                    
                    <button type="submit" class="expand">Update</button>
                </form>
            </div>
            <div class="large-3 columns">&nbsp;</div>
        </div>
        <script src="/ext/foundation/js/vendor/jquery.js"></script>
        <script src="/ext/foundation/js/vendor/fastclick.js"></script>
        <script src="/ext/foundation/js/foundation.min.js"></script>
        <script src="/ext/foundation/js/foundation/foundation.alert.js"></script>
        <script src="js/foundation/foundation.abide.js"></script>
        <script>
            (function (i, s, o, g, r, a, m) {
                i['GoogleAnalyticsObject'] = r;
                i[r] = i[r] || function () {
                    (i[r].q = i[r].q || []).push(arguments)
                }, i[r].l = 1 * new Date();
                a = s.createElement(o),
                        m = s.getElementsByTagName(o)[0];
                a.async = 1;
                a.src = g;
                m.parentNode.insertBefore(a, m)
            })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

            ga('create', 'UA-36656575-2', 'kiri.travel');
            ga('require', 'displayfeatures');
            ga('send', 'pageview');
        </script>
    </body>
</html>
