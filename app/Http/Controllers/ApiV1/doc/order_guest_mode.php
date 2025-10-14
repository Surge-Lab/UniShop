<?php

/**
 * @OA\Schema(
 *     schema="SearchOrderByEmailRequest",
 *     type="object",
 *     required={"email"},
 *     @OA\Property(property="email", type="string", format="email", example="testuser@example.com", description="邮箱地址"),
 *     @OA\Property(property="search_pwd", type="string", example="123456", description="查询密码（如果系统开启查询密码功能）")
 * )
 */

/**
 * @OA\Schema(
 *     schema="SearchOrderByEmailData",
 *     type="object",
 *     @OA\Property(
 *         property="orders",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="order_sn", type="string", example="ABC123DEF456GHI7"),
 *             @OA\Property(property="title", type="string", example="王者荣耀点券 x 1"),
 *             @OA\Property(property="actual_price", type="string", example="98.00"),
 *             @OA\Property(property="status", type="integer", example=4),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01 12:00:00")
 *         )
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="Order",
 *     @OA\Property(property="id", type="integer", example=1, description="订单ID"),
 *     @OA\Property(property="user_id", type="integer", example=11, description="用户ID,0表示游客订单"),
 *     @OA\Property(property="order_sn", type="string", example="ABC123DEF456GHI7", description="订单号"),
 *     @OA\Property(property="goods_id", type="integer", example="11", description="商品ID"),
 *     @OA\Property(property="coupon_id", type="integer", example="11", description="优惠券ID"),
 *     @OA\Property(property="title", type="string", example="王者荣耀点券 x 1", description="订单标题"),
 *     @OA\Property(property="type", type="integer", example=1, description="类型：1自动发货 2人工处理"),
 *     @OA\Property(property="type_text", type="string", example="自动发货", description="类型：1自动发货 2人工处理"),
 *     @OA\Property(property="goods_price", type="float", example="1.00", description="商品价格"),
 *     @OA\Property(property="buy_amount", type="integer", example="1", description="购买数量"),
 *     @OA\Property(property="coupon_discount_price", type="float", example="0.00", description="优惠券折扣"),
 *     @OA\Property(property="wholesale_discount_price", type="float", example="0.00", description="批发折扣"),
 *     @OA\Property(property="total_price", type="float", example="1.00", description="商品总价"),
 *     @OA\Property(property="actual_price", type="float", example="1.00", description="实际支付"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *     @OA\Property(property="info", type="string", example="", description="订单备注"),
 *     @OA\Property(property="pay_id", type="integer", example="10", description="支付方式ID"),
 *     @OA\Property(property="buy_ip", type="string", example="10.0.0.1", description="购买IP"),
 *     @OA\Property(property="trade_no", type="string", example="", description="交易号"),
 *     @OA\Property(property="status", type="integer", example=1, description="状态"),
 *     @OA\Property(property="status_text", type="string", example="待支付"),
 *     @OA\Property(property="coupon_ret_back", type="float", example="0", description="优惠券退还"),
 *     @OA\Property(property="search_pwd", type="string", example="123456", description="查询密码"),
 *     @OA\Property(property="is_agent", type="integer", example=0, description="是否代理商订单：0-否，1-是"),
 *     @OA\Property(property="agent_order_sn", type="string", nullable=true, example=null, description="代理订单号"),
 *     @OA\Property(property="agent_data", type="string", nullable=true, example=null, description="代理数据JSON"),
 *     @OA\Property(property="is_supplier", type="integer", example=0, description="是否供应商订单：0-否，1-是"),
 *     @OA\Property(property="supplier_id", type="integer", example=0, description="供应商ID"),
 *     @OA\Property(property="supplier_order_sn", type="string", nullable=true, example=null, description="供应商订单号"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01 12:00:00"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01 12:00:00"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null, description="删除时间"),
 *     @OA\Property(
 *         property="goods",
 *         type="object",
 *         description="关联的商品信息",
 *         @OA\Property(property="id", type="integer", example=1, description="商品ID"),
 *         @OA\Property(property="title", type="string", example="王者荣耀点券", description="商品标题"),
 *         @OA\Property(property="price", type="float", example="98.00", description="商品价格"),
 *         @OA\Property(property="type", type="integer", example=1, description="商品类型"),
 *         @OA\Property(property="status", type="integer", example=1, description="商品状态")
 *     ),
 *     @OA\Property(
 *         property="coupon",
 *         type="object",
 *         nullable=true,
 *         description="关联的优惠券信息",
 *         @OA\Property(property="id", type="integer", example=1, description="优惠券ID"),
 *         @OA\Property(property="discount", type="float", example="10.00", description="优惠金额")
 *     ),
 *     @OA\Property(
 *         property="payment",
 *         type="object",
 *         nullable=true,
 *         description="关联的支付方式信息",
 *         @OA\Property(property="id", type="integer", example=1, description="支付方式ID"),
 *         @OA\Property(property="name", type="string", example="支付宝", description="支付方式名称"),
 *         @OA\Property(property="payment", type="string", example="alipay", description="支付方式标识")
 *     )
 * )
 *
 * @OA\Schema(
 *      schema="OrderListResponse",
 *      @OA\Property(property="code", type="integer", example=200),
 *      @OA\Property(property="message", type="string", example="获取成功"),
 *      @OA\Property(
 *          property="data",
 *          type="array",
 *          @OA\Items(ref="#/components/schemas/Order")
 *      )
 *  )
 *
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="code", type="integer", example=400),
 *     @OA\Property(property="message", type="string", example="请求参数错误"),
 *     @OA\Property(property="data", type="null", example=null)
 * )
 *
 * @OA\Schema(
 *     schema="CreateOrderRequest",
 *     type="object",
 *     required={"gid", "email", "buy_amount"},
 *     @OA\Property(property="gid", type="integer", example=1, description="商品ID"),
 *     @OA\Property(property="email", type="string", format="email", example="testuser@abc.com", description="邮箱地址"),
 *     @OA\Property(property="payway", type="integer", example=1, description="支付方式编号"),
 *     @OA\Property(property="buy_amount", type="integer", example=1, description="购买数量")
 * )
 *
 * @OA\Schema(
 *     schema="CreateOrderData",
 *     type="object",
 *     @OA\Property(property="order_sn", type="string", example="ABC123DEF456GHI7", description="订单号")
 * )
 *
 * @OA\Schema(
 *     schema="SearchOrderRequest",
 *     type="object",
 *     required={"order_sn"},
 *     @OA\Property(property="order_sn", type="string", example="9EXSMLNZQ511YLZZ", description="订单号")
 * )
 *
 * @OA\Schema(
 *     schema="SearchOrderData",
 *     type="object",
 *     @OA\Property(
 *         property="order",
 *         ref="#/components/schemas/Order"
 *     )
 * )
 */


/**
 * 创建订单
 *
 * @OA\Post(
 *     path="/api/v1/create_order",
 *     operationId="createOrder",
 *     tags={"订单-游客模式"},
 *     summary="创建订单",
 *     description="创建新的商品订单（非登录状态）",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CreateOrderRequest"),
 *             @OA\Examples(
 *                 example="example1",
 *                 summary="创建订单示例",
 *                 value={
 *                     "gid": 1,
 *                     "email": "testuser@abc.com",
 *                     "payway": 1,
 *                     "buy_amount": 1
 *                 }
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="创建成功",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/BaseResponse"),
 *                 @OA\Schema(
 *                     type="object",
 *                     @OA\Property(
 *                         property="data",
 *                         ref="#/components/schemas/CreateOrderData"
 *                     ),
 *                     required={"data"}
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="请求参数错误",
 *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="服务器内部错误",
 *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
 *     )
 * )
 */


/**
 * 通过订单号查询订单
 *
 * @OA\Post(
 *     path="/api/v1/order/search-by-sn",
 *     operationId="searchOrderBySN",
 *     tags={"订单-游客模式"},
 *     summary="通过订单号查询订单",
 *     description="通过订单号查询单个订单的详细信息。user_id参数：0表示游客查询（查询user_id为NULL或0的订单），其他值表示登录用户查询（精确匹配user_id）",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/SearchOrderRequest"),
 *             @OA\Examples(
 *                 example="example1",
 *                 summary="游客查询订单示例",
 *                 value={"order_sn": "9EXSMLNZQ511YLZZ", "user_id": 0}
 *             ),
 *             @OA\Examples(
 *                 example="example2",
 *                 summary="登录用户查询订单示例",
 *                 value={"order_sn": "9EXSMLNZQ511YLZZ", "user_id": 123}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="查询成功",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/BaseResponse"),
 *                 @OA\Schema(
 *                     type="object",
 *                     @OA\Property(
 *                         property="data",
 *                         ref="#/components/schemas/SearchOrderData"
 *                     ),
 *                     required={"data"}
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="请求参数错误",
 *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="订单不存在",
 *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="服务器内部错误",
 *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
 *     )
 * )
 */


/**
 * 通过邮箱查询订单
 *
 * @OA\Post(
 *     path="/api/v1/order/search-by-email",
 *     operationId="searchOrderByEmail",
 *     tags={"订单-游客模式"},
 *     summary="通过邮箱查询订单",
 *     description="通过邮箱地址查询该邮箱下的所有订单（最多返回5个）",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/SearchOrderByEmailRequest"),
 *             @OA\Examples(
 *                 example="example1",
 *                 summary="通过邮箱查询订单示例",
 *                 value={
 *                     "email": "testuser@example.com",
 *                     "search_pwd": "123456"
 *                 }
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="查询成功",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="orders",
 *                     type="array",
 *                     @OA\Items(ref="#/components/schemas/Order")
 *                 ),
 *                 @OA\Property(property="total", type="integer", description="订单总数", example=2)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="请求参数错误",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="integer", example=400),
 *             @OA\Property(property="message", type="string", example="邮箱地址不能为空"),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="未找到相关订单",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="未找到相关订单"),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="服务器内部错误",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="服务器内部错误"),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     )
 * )
 */

/**
 * 通过浏览器缓存查询订单
 *
 * @OA\Post(
 *     path="/api/v1/order/search-by-browser",
 *     operationId="searchOrderByBrowser",
 *     tags={"订单-游客模式"},
 *     summary="通过浏览器缓存查询订单",
 *     description="查询浏览器Cookie中保存的订单信息",
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 description="无需参数，系统会自动从Cookie中获取订单号"
 *             ),
 *             @OA\Examples(
 *                 example="example1",
 *                 summary="通过浏览器缓存查询订单示例",
 *                 value={}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="查询成功",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="orders",
 *                     type="array",
 *                     @OA\Items(ref="#/components/schemas/Order")
 *                 ),
 *                 @OA\Property(property="total", type="integer", description="订单总数", example=1)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="未找到缓存订单",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="未找到浏览器缓存中的订单"),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="服务器内部错误",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="服务器内部错误"),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     )
 * )
 */

