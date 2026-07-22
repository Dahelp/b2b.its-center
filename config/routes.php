<?php

use ishop\Router;

Router::add('^product/modal$', ['controller' => 'Product', 'action' => 'modal']);
Router::add('^podbor/filter-search$', ['controller' => 'Podbor', 'action' => 'filterSearch']);
Router::add('^podbor/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Podbor', 'action' => 'index']);
Router::add('^cart/status$', ['controller' => 'Cart', 'action' => 'status']);
Router::add('^cart/show$', ['controller' => 'Cart', 'action' => 'view']);
Router::add('^user/update-order$', ['controller' => 'User', 'action' => 'updateOrder']);
Router::add('user/retry1c$', ['controller' => 'User', 'action' => 'retry1c']);
Router::add('api/save-marks$', ['controller' => 'Api', 'action' => 'saveMarks']);
Router::add('order/download-marks$', ['controller' => 'User', 'action' => 'downloadMarks']);
Router::add('user/proxy-pdf$', ['controller' => 'User', 'action' => 'proxyPdf']);
Router::add('city/search$', ['controller' => 'City', 'action' => 'search']);

//Protector//
Router::add('^protector/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Protector', 'action' => 'view']);
//AndProtector//
//Brand//
Router::add('^brand/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Brand', 'action' => 'view']);
//AndBrand//
//Disk//
Router::add('^disk/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Disk', 'action' => 'view']);
//AndDisk//
//Tipshiny//
Router::add('^tipshiny/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Tipshiny', 'action' => 'view']);
//AndTipshiny//
//Rim//
Router::add('^rim/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Rim', 'action' => 'view']);
//AndRim//
//Pages//
Router::add('^pages/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Pages', 'action' => 'view']);
//AndPages//
//News//
Router::add('^news/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'News', 'action' => 'view']);
//AndNews//
//Services//
Router::add('^services/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Services', 'action' => 'view']);
//AndServices//
//Promo//
Router::add('^promo/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Promo', 'action' => 'view']);
//AndPromo//
//Articles//
Router::add('^articles/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Articles', 'action' => 'view']);
//AndArticles//
//Size//
Router::add('^size/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Size', 'action' => 'view']);
//AndSize//
//Ground//
Router::add('^ground/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Ground', 'action' => 'view']);
//AndGround//
//  Add here

// default routes
Router::add('^$', ['controller' => 'Main', 'action' => 'index']);
Router::add('^(?P<controller>[a-z-]+)/?(?P<action>[a-z-]+)?$');
