<?php

namespace App\Payments;

use App\Exceptions\RuleValidationException;
use App\Models\BalanceLog;
use App\Models\Goods;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use App\Service\ApiOrderService;
use App\Service\BalanceService;
use App\Service\OrderProcessService;
use App\Service\SupplierService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 余额支付网关
 * 
 * @author assimon<ashang@utf8.hk>
 */
class BalancePay 
{
    protected $config;
    /**
     * @var BalanceService
     */
    protected $balanceService;
    /**
     * @var ApiOrderService
     */
    protected $apiOrderService;
    /**
     * @var OrderProcessService
     */
    protected $orderProcessService;

    public function __construct($config)
    {
        $this->config = $config;
        $this->balanceService = app(BalanceService::class);
        $this->apiOrderService = app(ApiOrderService::class);
        $this->orderProcessService = app(OrderProcessService::class);
    }

    /**
     * 返回支付配置表单
     *
     * @return array
     */
    public function form()
    {
        return [];
    }

    /**
     * 处理支付请求
     *
     * @param array $order 订单信息
     * @return array
     */
    public function pay($order)
    {
        try {
            // 获取当前用户ID（从订单信息中获取）
            $userId = $order['user_id'] ?? 0;
            if (!$userId) {
                throw new \Exception('用户未登录');
            }
            
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('用户不存在');
            }

            // 检查用户余额是否足够
            $orderAmount = $order['total_amount'];
            $requiredBalance = $orderAmount;

            if ($user->amount < $requiredBalance) {
                throw new \Exception('余额不足，当前余额：' . $user->amount . '元，需要：' . $requiredBalance . '元');
            }

            // 执行余额扣减
            DB::beginTransaction();
            try {
                // 锁定用户记录
                $user = User::where('id', $user->id)->lockForUpdate()->first();
                
                // 再次检查余额（防止并发问题）
                if ($user->amount < $requiredBalance) {
                    throw new \Exception('余额不足');
                }

                // 记录余额变动（此方法内部已经包含扣减余额的操作）
                $this->balanceService->decreaseBalance(
                    $user->id,
                    $orderAmount,
                    BalanceLog::TYPE_CONSUME,
                    BalanceLog::SOURCE_ORDER,
                    null,
                    '商品购买',
                    '订单号：' . $order['order_sn']
                );
                
                $trade_no = $order['trade_no'];
                $orderModel = $this->apiOrderService->detailOrderSN($order['order_sn'],$userId);

                if($orderModel && $orderModel->is_supplier>0){
                    $supplier = Supplier::query()->where('id', $orderModel->supplier_id)->first();
                    if($supplier){
                        $supplierService = new SupplierService($supplier['method'],$orderModel->supplier_id);
                        //todo 上游商品下单
                        $goods=Goods::query()->where('id',$orderModel->goods_id)->first();
                        $formatIpt = format_charge_input($goods->other_ipu_cnf);
                        $otherIpt = explode(PHP_EOL,$orderModel->info);
                        $ois = [];
                        foreach ($formatIpt as $item) {
                            foreach($otherIpt as $ipt){
                                $arrIpt = explode(':',$ipt);
                                if($item['desc'] == $arrIpt[0]){
                                    $ois[$item['field']]=$arrIpt[1];
                                }
                            }
                        }
                        $res = $supplierService->buyGoods($orderModel->order_sn,$orderModel->email,$orderModel->goods_id,$orderModel->buy_amount,$ois);
                        if($res['code'] != 200){
                            throw new RuleValidationException($res['message']);
                        }
                    }else{
                        throw new RuleValidationException(__('dujiaoka.prompt.goods_does_not_exist'));
                    }
                }

                // 完成订单处理（自动发货或人工处理）
                $this->orderProcessService->completedOrder($order['order_sn'], $orderAmount, $trade_no, $userId);

                DB::commit();


                // 刷新订单数据，获取最新状态
//                $orderModel = $this->apiOrderService->detailOrderSN($order['order_sn'], $userId);

                // 余额支付直接成功，返回成功状态
                return [
                    'type' => 2, // 2表示直接支付成功
                    'data' => [
                        'paid' => true,
                        'order_sn' => $order['order_sn'],
                        'trade_no' => $trade_no,
                        'amount' => $orderAmount,
                        'balance_after' => bcsub($user->amount, $orderAmount, 2),
                        // 'status' => $orderModel->status,
                        // 'status_text' => $orderModel->status == Order::STATUS_COMPLETED ? '已完成' : '待处理'
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('balancepay exception:',['message'=>$e->getMessage(),'trace'=>$e->getTraceAsString()]);
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return [
                'type' => -1, // 表示支付失败
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 处理支付回调（余额支付不需要回调，但需要实现接口）
     *
     * @param array $params
     * @return array|false
     */
    public function notify($params)
    {
        // 余额支付不需要异步回调，直接返回false
        // 因为余额支付是同步的，在pay方法中已经完成了支付处理
        return false;
    }
}