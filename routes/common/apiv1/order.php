<?php
/**
 * API V1 订单相关路由
 *
 * @author    assimon<ashang@utf8.hk>
 * @copyright assimon<ashang@utf8.hk>
 * @link      http://utf8.hk/
 */
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v1', 'namespace' => 'ApiV1'], function () {

    // 创建订单
    Route::post('create_order', 'OrderController@createOrder');
    // 用户信息（需要认证）
    Route::group(['middleware' => 'auth:sanctum'], function () {
        // 创建订单
        Route::post('user/create_order', 'OrderController@userCreateOrder');
        // 通过订单号查询订单
        Route::get('user/order/list', 'OrderController@userOrders');
        Route::get('user/order/{id}', 'OrderController@userOrderDetail');
        // 取消订单
        Route::post('user/order/{id}/cancel', 'OrderController@cancelOrder');
    });
    
    // 通过订单号查询订单
    Route::post('order/search-by-sn', 'OrderController@searchOrderBySN');
    
    // 通过邮箱查询订单
    Route::post('order/search-by-email', 'OrderController@searchOrderByEmail');
    
    // 通过浏览器缓存查询订单
    Route::post('order/search-by-browser', 'OrderController@searchOrderByBrowser');
});
