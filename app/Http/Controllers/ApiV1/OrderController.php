<?php

namespace App\Http\Controllers\ApiV1;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\BaseController;
use App\Models\Goods;
use App\Models\Order;
use App\Service\OrderProcessService;
use App\Service\ApiOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * API V1 订单控制器
 *
 * Class OrderController
 * @package App\Http\Controllers\ApiV1
 * @author: Assimon
 * @email: Ashang@utf8.hk
 * @blog: https://utf8.hk
 * Date: 2024/01/01
 */
class OrderController extends BaseController
{
    /**
     * 订单服务层
     * @var \App\Service\ApiOrderService
     */
    private $apiOrderService;
    /**
     * 订单处理层.
     * @var OrderProcessService
     */
    private $orderProcessService;

    public function __construct()
    {
        $this->apiOrderService     = app('Service\ApiOrderService');
        $this->orderProcessService = app('Service\OrderProcessService');
    }

     /**
      * 通过订单号查询单
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function searchOrderBySN(Request $request)
    {
        try {
            $orderSN = $request->input('order_sn');
            
            if (empty($orderSN)) {
                return response()->json([
                    'code' => 400,
                    'message' => __('dujiaoka.prompt.server_illegal_request'),
                    'data' => null
                ]);
            }

            // 自动检测用户登录状态
            $user = auth('sanctum')->user();
            $userId = $user ? $user->id : 0;

            $order = $this->apiOrderService->detailOrderSN($orderSN, $userId);
            
            if (!$order) {
                return response()->json([
                    'code' => 404,
                    'message' => __('dujiaoka.prompt.order_does_not_exist'),
                    'data' => null
                ]);
            }

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'order' => $order->load(['coupon', 'payment', 'goods'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 用户订单列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userOrders(Request $request)
    {
        $user = $request->user();
        try {
            // 获取查询参数
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 15);
            $keyword = $request->input('keyword');

            // 构建查询
            $query = Order::where('user_id',$user->id)->orderBy('id', 'desc');

            // 关键词搜索
            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('order_sn', 'like', "%{$keyword}%")
                        ->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            }

            // 获取商品列表
            $paginate = $query->paginate($perPage, ['*'], 'page', $page);

            // 处理商品数据
            $orderData = $paginate->getCollection()->map(function ($item) {
                return $this->formatOrderData($item);
            });

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $orderData,
                'pagination' => [
                    'current_page' => $paginate->currentPage(),
                    'per_page' => $paginate->perPage(),
                    'total' => $paginate->total(),
                    'last_page' => $paginate->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取列表失败：' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


    /**
     * 用户订单明细
     * @param Request $request
     * @param int $order_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function userOrderDetail(Request $request,int $id)
    {
        try {
            $user = $request->user();

            if (empty($id)) {
                return response()->json([
                    'code' => 400,
                    'message' => __('dujiaoka.prompt.server_illegal_request'),
                    'data' => null
                ], 400);
            }

            $order = $this->apiOrderService->detailOrder($id,$user->id);

            if (!$order) {
                return response()->json([
                    'code' => 404,
                    'message' => __('dujiaoka.prompt.order_does_not_exist'),
                    'data' => null
                ], 404);
            }

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'order' => $order->load(['coupon', 'payment', 'goods'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function searchOrderByEmail(Request $request)
    {
        try {
            $email = $request->input('email');
            $searchPwd = $request->input('search_pwd', '');

            // 验证邮箱参数
            if (empty($email)) {
                return response()->json([
                    'code' => 400,
                    'message' => __('dujiaoka.prompt.server_illegal_request'),
                    'data' => null
                ]);
            }

            // 验证查询密码（如果开启）
            if (
                dujiaoka_config_get('is_open_search_pwd', \App\Models\BaseModel::STATUS_CLOSE) == \App\Models\BaseModel::STATUS_OPEN &&
                empty($searchPwd)
            ) {
                return response()->json([
                    'code' => 400,
                    'message' => __('dujiaoka.prompt.server_illegal_request'),
                    'data' => null
                ]);
            }

            $orders = $this->apiOrderService->withEmailAndPassword($email, $searchPwd);
            
            if (!$orders || $orders->isEmpty()) {
                return response()->json([
                    'code' => 404,
                    'message' => __('dujiaoka.prompt.no_related_order_found'),
                    'data' => null
                ]);
            }

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'orders' => $orders->load(['coupon', 'payment', 'goods']),
                    'total' => $orders->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }


     /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function searchOrderByBrowser(Request $request)
    {
        try {
            $cookies = Cookie::get('dujiaoka_orders');
            
            if (empty($cookies)) {
                return response()->json([
                    'code' => 404,
                    'message' => __('dujiaoka.prompt.no_related_order_found_for_cache'),
                    'data' => null
                ]);
            }

            $orderSNS = json_decode($cookies, true);
            
            if (!is_array($orderSNS) || empty($orderSNS)) {
                return response()->json([
                    'code' => 404,
                    'message' => __('dujiaoka.prompt.no_related_order_found_for_cache'),
                    'data' => null
                ]);
            }

            $orders = $this->apiOrderService->byOrderSNS($orderSNS);
            
            if (!$orders || $orders->isEmpty()) {
                return response()->json([
                    'code' => 404,
                    'message' => __('dujiaoka.prompt.no_related_order_found'),
                    'data' => null
                ]);
            }

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'orders' => $orders->load(['coupon', 'payment', 'goods']),
                    'total' => $orders->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }


     /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $this->apiOrderService->validatorCreateOrder($request);
            $goods = $this->apiOrderService->validatorGoods($request);
            $this->apiOrderService->validatorLoopCarmis($request);
            // 设置商品
            $this->orderProcessService->setGoods($goods);
            // 优惠码
            $coupon = $this->apiOrderService->validatorCoupon($request);
            // 设置优惠码
            $this->orderProcessService->setCoupon($coupon);
            Log::info("createOrder:".json_encode($goods));
            $otherIpt = $this->apiOrderService->validatorChargeInput($goods, $request);
            $this->orderProcessService->setOtherIpt($otherIpt);
            // 数量
            $this->orderProcessService->setBuyAmount($request->input('buy_amount'));
            // 支付方式
            $this->orderProcessService->setPayID($request->input('payway',0));
            // 下单邮箱
            $this->orderProcessService->setEmail($request->input('email'));
            // ip地址
            $this->orderProcessService->setBuyIP($request->getClientIp());
            // 查询密码
            $this->orderProcessService->setSearchPwd($request->input('search_pwd', ''));
            // 创建订单
            $order = $this->orderProcessService->createOrder();
            DB::commit();
            // 设置订单cookie
            $this->queueCookie($order->order_sn);
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'order_sn' => $order->order_sn,
                ]
            ]);
        } catch (RuleValidationException $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'message' => $exception->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * 设置订单cookie.
     * @param string $orderSN 订单号.
     */
    private function queueCookie(string $orderSN) : void
    {
        // 设置订单cookie
        $cookies = Cookie::get('dujiaoka_orders');
        if (empty($cookies)) {
            Cookie::queue('dujiaoka_orders', json_encode([$orderSN]));
        } else {
            $cookies = json_decode($cookies, true);
            array_push($cookies, $orderSN);
            Cookie::queue('dujiaoka_orders', json_encode($cookies));
        }
    }


     /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userCreateOrder(Request $request)
    {
        $user = $request->user();
        DB::beginTransaction();
        try {
            $this->apiOrderService->validatorCreateOrder($request);
            $goods = $this->apiOrderService->validatorGoods($request);
            $this->apiOrderService->validatorLoopCarmis($request);
            // 设置商品
            $this->orderProcessService->setGoods($goods);
            // 优惠码
            $coupon = $this->apiOrderService->validatorCoupon($request);
            // 设置优惠码
            $this->orderProcessService->setCoupon($coupon);
            $otherIpt = $this->apiOrderService->validatorChargeInput($goods, $request);
            $this->orderProcessService->setOtherIpt($otherIpt);
            // 数量
            $this->orderProcessService->setBuyAmount($request->input('buy_amount'));
            // 支付方式
            $this->orderProcessService->setPayID($request->input('payway',0));
            // 下单邮箱
            $this->orderProcessService->setEmail($request->input('email'));
            // ip地址
            $this->orderProcessService->setBuyIP($request->getClientIp());
            // 查询密码
            $this->orderProcessService->setSearchPwd($request->input('search_pwd', ''));
            // 创建订单
            $order = $this->orderProcessService->createOrder($user->id);
            DB::commit();
            // 设置订单cookie
            $this->queueCookie($order->order_sn);
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'order_sn' => $order->order_sn,
                ]
            ]);
        } catch (RuleValidationException $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'message' => $exception->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * 生成订单号
     *
     * @return string
     */
    private function generateOrderSN()
    {
        return date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    /**
     * 处理自动发货
     *
     * @param Order $order
     * @param Goods $goods
     * @param int $buyAmount
     * @return void
     */
    private function processAutomaticDelivery($order, $goods, $buyAmount)
    {
        // 使用行级锁获取未出售的卡密
        $carmis = \App\Models\Carmis::where('goods_id', $goods->id)
            ->where('status', \App\Models\Carmis::STATUS_UNSOLD)
            ->lockForUpdate() // 行级锁防止并发
            ->limit($buyAmount)
            ->get();

        if ($carmis->count() < $buyAmount) {
            throw new \Exception('卡密库存不足');
        }

        // 更新卡密状态为已出售
        foreach ($carmis as $carmi) {
            $carmi->status = \App\Models\Carmis::STATUS_SOLD;
            $carmi->order_id = $order->id;
            $carmi->save();
        }

        // 更新商品销量
        $goods->increment('sales_volume', $buyAmount);
    }

    private function formatOrderData($order)
    {
        $order['status_text'] = $this->getStatusText($order->status);
        $order['type_text'] = $order->type==1?"自动发货":"人工处理";
       return $order;
    }


    /**
     * 取消订单
     * 
     * @OA\Post(
     *     path="/api/v1/user/order/{id}/cancel",
     *     tags={"订单-登录模式"},
     *     summary="取消订单",
     *     description="取消指定ID的订单（仅限待支付状态）",
     *     operationId="cancelOrder",
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="订单ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="订单取消成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="订单取消成功"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="order",
     *                     ref="#/components/schemas/Order"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="请求参数错误",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授权访问",
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
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrder(Request $request, int $id)
    {
        try {
            $user = $request->user();
            
            if (empty($id)) {
                return response()->json([
                    'code' => 400,
                    'message' => __('dujiaoka.prompt.server_illegal_request'),
                    'data' => null
                ], 400);
            }

            // 获取订单
            $order = $this->apiOrderService->detailOrder($id, $user->id);
            
            if (!$order) {
                return response()->json([
                    'code' => 404,
                    'message' => __('dujiaoka.prompt.order_does_not_exist'),
                    'data' => null
                ], 404);
            }

            // 检查订单状态是否可以取消（只允许待支付状态）
            if ($order->status !== Order::STATUS_WAIT_PAY) {
                return response()->json([
                    'code' => 400,
                    'message' => '只有待支付状态的订单才能取消',
                    'data' => null
                ], 400);
            }

        // 执行取消订单逻辑（传入 user_id 进行二次验证）
        $result = $this->orderProcessService->cancelOrder($order, $user->id);
        
        if ($result) {
            return response()->json([
                'code' => 200,
                'message' => '订单取消成功',
                'data' => [
                    'order' => $order->fresh()->load(['coupon', 'payment', 'goods'])
                ]
            ]);
        } else {
            return response()->json([
                'code' => 500,
                'message' => '订单取消失败',
                'data' => null
            ], 500);
        }

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * 获取状态文本
     *
     * @param int $status
     * @return string
     */
    private function getStatusText($status)
    {
        $statusMap = [
            \App\Models\Order::STATUS_WAIT_PAY => '待支付',
            \App\Models\Order::STATUS_PENDING => '待处理',
            \App\Models\Order::STATUS_PROCESSING => '处理中',
            \App\Models\Order::STATUS_COMPLETED => '已完成',
            \App\Models\Order::STATUS_FAILURE => '失败',
            \App\Models\Order::STATUS_EXPIRED => '过期',
            \App\Models\Order::STATUS_ABNORMAL => '异常',
            \App\Models\Order::STATUS_CANCELLED => '已取消'
        ];

        return $statusMap[$status] ?? '未知状态';
    }
}
