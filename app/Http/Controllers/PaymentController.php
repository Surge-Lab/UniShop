<?php

namespace App\Http\Controllers;

use App\Exceptions\RuleValidationException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RechargeOrder;
use App\Service\BalanceService;
use App\Service\OrderProcessService;
use App\Service\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends BaseController
{

    /**
     * 支付网关
     * @var \App\Models\Payment
     */
    protected $payGateway;


    /**
     * 订单
     * @var \App\Models\Order
     */
    protected $order;

    /**
     * 订单服务层
     * @var \App\Service\OrderService
     */
    protected $orderService;

    /**
     * 支付服务层
     * @var \App\Service\PaymentService
     */
    protected $paymentService;

    /**
     * 订单处理层.
     * @var OrderProcessService
     */
    protected $orderProcessService;
    /**
     * 订单处理层.
     * @var BalanceService
     */
    protected $balanceService;


    public function __construct()
    {
        $this->orderService        = app('Service\OrderService');
//        $this->paymentService      = app('Service\PaymentService');
        $this->orderProcessService = app('Service\OrderProcessService');
        $this->balanceService = app('Service\BalanceService');
    }

    /**
     * 订单检测
     *
     * @param string $orderSN
     * @throws RuleValidationException
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function checkOrder(string $orderSN,$userId=0)
    {
        // 订单
        $this->order = $this->orderService->detailOrderSN($orderSN,$userId);
        if (!$this->order) {
            throw new RuleValidationException(__('dujiaoka.prompt.order_does_not_exist'));
        }
        // 订单过期
        if ($this->order->status == Order::STATUS_EXPIRED) {
            throw new RuleValidationException(__('dujiaoka.prompt.order_is_expired'));
        }
        // 已经支付了
        if ($this->order->status > Order::STATUS_WAIT_PAY) {
            throw new RuleValidationException(__('dujiaoka.prompt.order_already_paid'));
        }
    }

    /**
     * 加载支付网关
     *
     * @param string $orderSN 订单号
     * @param string $payCheck 支付标识
     * @throws RuleValidationException
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function loadGateWay(string $orderSN, string $payCheck)
    {
//        $this->checkOrder($orderSN);
//        // 支付配置
//        $this->payGateway = $this->paymentService->detailByCheck($payCheck);
//        if (!$this->payGateway) {
//            throw new RuleValidationException(__('dujiaoka.prompt.pay_gateway_does_not_exist'));
//        }
//        // 临时保存支付方式
//        $this->order->pay_id = $this->payGateway->id;
//        $this->order->save();
    }

    public function paymentGateway(string $orderSN){
        try {
            $this->checkOrder($orderSN);
            $bccomp = bccomp($this->order->actual_price, 0.00, 2);
            // 如果订单金额为0 代表无需支付，直接成功
            if ($bccomp == 0) {
                $this->orderProcessService->completedOrder($this->order->order_sn, 0.00);
                return redirect(url('detail-order-sn', ['orderSN' => $this->order->order_sn]));
            }

            $payment = Payment::find($this->order->pay_id);
            if (!$payment || $payment->enable !== 1) abort(500, __('Payment method is not available'));
            $paymentService = new PaymentService($payment->payment, $payment->id);
            $result = $paymentService->pay([
                'trade_no' => $orderSN,
                'total_amount' =>  bcmul($this->order->actual_price, 100, 0), // 转换为分
                'user_id' => 0,//$this->order->user_id,
//                'stripe_token' => $request->input('token')
            ]);
            return response()->json(['msg' => 'success', 'code' => 200,'data'=>$result]);
        } catch (RuleValidationException $exception) {
            return response()->json(['msg' => 'fail', 'code' => 400002]);
        }
    }

    public function check(string $orderSN){
        try{
            $this->checkOrder($orderSN);
            return response()->json(['msg' => 'success', 'code' => 200,'data'=>[
                "order_sn"=>$orderSN,
                "status"=>$this->order->status
            ]]);
        }catch (RuleValidationException $exception) {
            return response()->json(['msg' => 'fail', 'code' => 400002]);
        }
    }

    public function notify($method, $uuid, Request $request)
    {
        try {
            // 记录回调日志
            Log::info('支付回调接收', [
                'method' => $method,
                'uuid' => $uuid,
                'params' => $request->all(),
                'ip' => $request->ip()
            ]);
            
            $paymentService = new PaymentService($method, null, $uuid);
            $verify = $paymentService->notify($request->input());
            
            if (!$verify) {
                Log::error('支付回调签名验证失败', [
                    'method' => $method,
                    'params' => $request->all()
                ]);
                abort(500, 'verify error');
            }
            
            Log::info('支付回调验证成功', [
                'trade_no' => $verify['trade_no'],
                'actual_price' => $verify['actual_price'],
                'callback_no' => $verify['callback_no']
            ]);
            
            if (!$this->handle($verify['trade_no'],$verify['actual_price'], $verify['callback_no'])) {
                abort(500, 'handle error');
            }
            
            Log::info('支付回调处理成功', ['trade_no' => $verify['trade_no']]);
            die(isset($verify['custom_result']) ? $verify['custom_result'] : 'success');
        } catch (\Exception $e) {
            Log::error('支付回调异常', [
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'fail');
        }
    }

    public function rechargeNotify($method, $uuid, Request $request)
    {
        try {
            $paymentService = new PaymentService($method, null, $uuid);
            $verify = $paymentService->notify($request->input());
            if (!$verify) abort(500, 'verify error');
            if (!$this->rechargeHandle($verify['trade_no'],$verify['actual_price'], $request->input())) {
                abort(500, 'handle error');
            }
            die(isset($verify['custom_result']) ? $verify['custom_result'] : 'success');
        } catch (\Exception $e) {
            abort(500, 'fail');
        }
    }

    private function handle($tradeNo,$totalAmount, $callbackNo)
    {
        $order = Order::where('order_sn', $tradeNo)->first();
        if (!$order) {
            abort(500, 'order is not found');
        }
        
        // 订单状态处理逻辑
        if ($order->status === Order::STATUS_WAIT_PAY) {
            // 正常情况：订单待支付，处理支付成功
            $this->balanceService->completedOrder($tradeNo, $totalAmount, $callbackNo);
            return true;
        }
        
        if ($order->status === Order::STATUS_COMPLETED) {
            // 订单已完成，防止重复处理（支付平台可能重复回调）
            Log::info('订单已完成，跳过重复处理', [
                'order_sn' => $tradeNo,
                'status' => $order->status
            ]);
            return true;
        }
        
        // 异常情况：用户已付款，但订单状态异常（过期/取消等）
        if (in_array($order->status, [Order::STATUS_EXPIRED, Order::STATUS_CANCELLED, Order::STATUS_FAILURE])) {
            Log::error('⚠️ 支付异常：用户已付款但订单状态异常', [
                'order_sn' => $tradeNo,
                'current_status' => $order->status,
                'actual_price' => $totalAmount,
                'callback_no' => $callbackNo,
                'message' => '用户已支付成功，但订单已过期/取消，需要人工处理'
            ]);
            
            // 将订单改为异常状态，等待人工处理
            $order->status = Order::STATUS_ABNORMAL;
            $order->trade_no = $callbackNo;
            $order->info = "支付异常：用户已付款({$totalAmount}元)，但订单原状态为{$order->status}，已标记为异常，请管理员及时处理。回调订单号：{$callbackNo}";
            $order->save();
        
            
            return true; // 返回成功，避免支付平台重复回调
        }
        
        // 其他状态（待处理、处理中等），记录日志
        Log::warning('订单状态不是待支付，跳过处理', [
            'order_sn' => $tradeNo,
            'current_status' => $order->status,
            'actual_price' => $totalAmount
        ]);
        return true;
    }

    private function rechargeHandle($tradeNo,$totalAmount, $callbackNo)
    {
        $order = RechargeOrder::where('order_sn', $tradeNo)->first();
        if (!$order) {
            abort(500, 'order is not found');
        }
        // 如果订单不是待支付状态，说明已处理过，直接返回成功（防重复处理）
        if ($order->status !== RechargeOrder::STATUS_PENDING) {
            Log::info('充值订单状态不是待支付，跳过处理', [
                'order_sn' => $tradeNo,
                'current_status' => $order->status,
                'expected_status' => RechargeOrder::STATUS_PENDING
            ]);
            return true;
        }
        $this->orderProcessService->completedOrder($tradeNo, $totalAmount, $callbackNo);
        return true;
    }
}
