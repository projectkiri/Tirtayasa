<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
    <head>
        <title>Edit API Key | KIRI Developers</title>
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
        &nbsp;
        <div class="row">
            <div class="large-12 columns">
                <?php $this->load->view('dev/template_flashmessage'); ?>
                <form data-abide action="/dev/apikeys/edit" method="POST">
                    <input type="hidden" name="verifier" value="<?= $verifier ?>"/>
                    <label>
                        API Key:
                        <input type="text" name="verifier" disabled value="<?= $verifier ?>"/>
                    </label>
                    <label>
                        Domain filter:
                        <input type="text" name="domainFilter" required value="<?= $domainFilter ?>" size="64"/>
                    </label>
                    <label>
                        Description:
                        <input type="text" name="description" value="<?= $description ?>" size="256"/>
                    </label>
                    <button type="submit">Update</button>
                    <a href="/dev/apikeys/list" class="button secondary">Cancel</a>
                </form>
            </div>
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
