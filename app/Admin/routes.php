<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {
    $router->get('/', 'HomeController@index');
    $router->get('goods/supplier-group', 'GoodsController@supplierGoodsGroups');
    $router->get('goods/supplier-goods', 'GoodsController@supplierGoods');
    $router->get('goods/supplier-goods-data', 'GoodsController@supplierGoodsData');
    $router->resource('goods', 'GoodsController');
    $router->resource('goods-group', 'GoodsGroupController');
    $router->resource('carmis', 'CarmisController');
    $router->resource('coupon', 'CouponController');
    $router->resource('emailtpl', 'EmailtplController');
//    $router->get('payment', 'PaymentController@index');
//    $router->get('payment/create', 'PaymentController@create');
//    $router->post('payment/update/{id}', 'PaymentController@update');
//    $router->post('payment/save', 'PaymentController@save');
    $router->resource('payment', 'PaymentController');
    $router->resource('supplier', 'SupplierController');
    $router->resource('pay', 'PayController');
    $router->resource('order', 'OrderController');
    $router->resource('users', 'UserController');
    $router->resource('customers', 'CustomerController');
    $router->post('customers/adjust-balance', 'CustomerController@adjustBalance');
    $router->get('customers/{userId}/balance-logs', 'CustomerController@balanceLogs');
    $router->get('import-carmis', 'CarmisController@importCarmis');
    $router->get('system-setting', 'SystemSettingController@systemSetting');
    $router->get('email-test', 'EmailTestController@emailTest');
});
