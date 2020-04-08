<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html class="no-js" lang="en">
    <head>
        <!-- Hello branch mapbox! -->
        <title>KIRI</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="<?= $this->lang->line('meta-description') ?>" />
        <meta name="author" content="Project Kiri (KIRI)" />
        <meta name="google-site-verification" content="9AtqvB-LWohGnboiTyhtZUXAEcOql9B-8lDjo_wcUew" />
      <!-- ganti ke bootstrap -->
        <link rel="stylesheet" href="/ext/bootstrap/css/bootstrap.min.css" />
        <!-- openlayers -->
        <!-- <link rel="stylesheet" href="/ext/openlayers/ol.css" /> -->
        <!-- Mapbox -->
        <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
        <script src='https://api.mapbox.com/mapbox.js/v3.3.0/mapbox.js'></script>
        <link href='https://api.mapbox.com/mapbox.js/v3.3.0/mapbox.css' rel='stylesheet' />
        <link rel="stylesheet" href="/stylesheets/styleIndex.css" />
        <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
        <script src="/ext/bootstrap/js/vendor/modernizr.js"></script>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row order-3">
                <div id="controlpanel" class="col-lg-3 order-lg-9">
                    <div class="col">
                        <img class="mx-auto d-block" src="/images/kiri200.png" alt="KIRI logo"/>
                    </div>

                    <div class="row paddingControl paddingBottom">
                        <div class="col-sm-5">
                            <select id="regionselect" class="form-control">
                                <?php foreach ($regions as $key => $value): ?>
                                    <option value="<?= $key ?>"<?= ($region == $key ? ' selected' : '') ?>><?= $value['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-7">
                            <select id="localeselect" class="form-control">
                                <?php foreach ($languages as $key => $value): ?>
                                    <option value="<?= $key ?>"<?= ($locale == $key ? ' selected' : '') ?>><?= $value['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row paddingControl">
                        <div class="col-sm-2">
                            <span for="startInput" class="align-middle"><?= $this->lang->line('From') ?>:</span>
                        </div>
                        <div class="col-sm-10">
                            <input type="text" id="startInput" class="form-control" value="" placeholder="<?= $this->lang->line('placeholder-from') ?>">
                        </div>
                    </div>
                    <div class="row paddingControl">
                        <div class="col-lg-12">
                            <select id="startSelect" class="hidden"></select>
                        </div>
                    </div>
                    <div class="row paddingControl">
                        <div class="col-sm-2">
                            <span for="finishInput" class="align-middle"><?= $this->lang->line('To') ?>:</span>
                        </div>
                        <div class="col-sm-10">
                            <input type="text" id="finishInput" class="form-control" value="" placeholder="<?= $this->lang->line('placeholder-to') ?>">
                        </div>
                    </div>
                    <div class="row paddingControl">
                        <div class="col-lg-12">
                            <select id="finishSelect" class="hidden"></select>
                        </div>
                    </div>
                    <div class="row paddingControl">
                        <div class="btn-group fullwidth" role="group">
                            <div class="col-sm-6">
                                <a href="#" class="btn btn-primary btn-block" id="findbutton"><strong><?= $this->lang->line('Find') ?>!</strong></a>
                            </div>
                            <div class="col-sm-3">
                                <a href="#" class="btn btn-light btn-block" id="swapbutton"><img src="images/swap.png" alt="swap"></a>
                            </div>
                            <div class="col-sm-3">
                                <a href="#" class="btn btn-light btn-block" id="resetbutton"><img src="images/reset.png" alt="reset"></a>
                            </div>
                        </div>
                    </div>
                    <div class="row paddingControl">
                        <div class="col-lg-12" id="routingresults">
                            <div id="results-section-container"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <footer>
                                <a href="<?= $this->lang->line('url-legal') ?>"><?= $this->lang->line('Legal') ?></a> | 
                                <a href="<?= $this->lang->line('url-feedback') ?>"><?= $this->lang->line('Feedback') ?></a> | 
                                <a href="<?= $this->lang->line('url-about') ?>"><?= $this->lang->line('About KIRI') ?></a><br/><br/>
                                <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                                <input type="hidden" name="cmd" value="_s-xclick">
                                <input type="hidden" name="hosted_button_id" value="WKWS26A57WHJG">
                                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                                <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                                </form>
                            </footer>
                            &nbsp;
                        </div>
                    </div>
                    <?php if (!is_null($youtube)): ?>
                        <div class="row">
                            <div id="youtubepromo" class="reveal-modal small" data-reveal="">
                                <h3><?= $youtube['label'] ?></h3>
                                <div class="flex-video">
                                    <iframe width="640" height="480" src="//www.youtube.com/embed/<?= $youtube['code'] ?>" frameborder="0" allowfullscreen></iframe>
                                </div>
                                <a class="close-reveal-modal">&#215;</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div id="map" class="col-lg-9"></div>
            </div>
        </div>
        <script src="/ext/bootstrap/js/vendor/jquery.js"></script>
        <script src="/ext/bootstrap/js/vendor/fastclick.js"></script>
        <script src="/ext/bootstrap/js/bootstrap.min.js"></script>
        <!-- Google Maps -->
        <script>
            var map;
            function initMap() {
              map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: -6.175389, lng: 106.827167},
                zoom: 15
              });
            }
        </script>
        <!-- openlayers -->
        <script src="/ext/openlayers/ol.js"></script>
        <!-- Mapbox -->
        <script>
        L.mapbox.accessToken = '<your access token here>';
        var map = L.mapbox.map('map')
            .setView([-6.175389, 106.827167], 9)
            .addLayer(L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'));
        </script>
        <script>
            var region = '<?= $region ?>';
            var input_text = <?= json_encode($inputText) ?>;
            var coordinates = <?= json_encode($inputCoordinate) ?>;
        </script>
        <script src="/mainpage/js/protocol.js"></script>
        <script src="/mainpage/js/main.js?locale=<?= $locale ?>"></script>
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
