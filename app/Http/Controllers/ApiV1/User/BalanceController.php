<?php

namespace App\Http\Controllers\ApiV1\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\RechargeOrder;
use App\Service\BalanceService;
use App\Service\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * 用户余额控制器
 */
class BalanceController extends Controller
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance(Request $request)
    {
        $user    = $request->user();
        $balance = $this->balanceService->getUserBalance($user->id);

        return response()->json([
            'code'    => 200,
            'message' => '获取成功',
            'data'    => [
                'balance' => $balance
            ]
        ]);
    }

    /**
     * 创建充值订单
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createRechargeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'pay_id' => 'required|integer',
        ], [
            'amount.required' => '充值金额不能为空',
            'amount.numeric'  => '充值金额必须为数字',
            'amount.min'      => '充值金额不能小于0.01',
            'amount.max'      => '充值金额不能超过999999.99',
            'pay_id.required' => __('dujiaoka.prompt.please_select_mode_of_payment'),
            'pay_id.integer'  => __('dujiaoka.prompt.please_select_mode_of_payment'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code'    => 422,
                'message' => $validator->errors()->first(),
                'data'    => null
            ], 422);
        }

        try {
            $user    = $request->user();
            $amount  = $request->input('amount');
            $pay_id  = $request->input('pay_id');
            $payment = Payment::query()->find($pay_id);
            if (!$payment)
                throw new \Exception('支付方式不存在');


            $rechargeOrder = $this->balanceService->createRechargeOrder(
                $user->id,
                $amount,
                $pay_id,
                $payment->payment
            );

            $paymentService = new PaymentService($payment->payment, $payment->id);
            $result         = $paymentService->pay([
                'trade_no'     => $rechargeOrder->order_sn,
                'total_amount' => bcmul($rechargeOrder->actual_amount, 100, 0), // 转换为分
                'user_id'      => $rechargeOrder->user_id
            ]);

            return response()->json([
                'code'    => 200,
                'message' => 'success',
                'data'    => [
                    'order_sn'     => $rechargeOrder->order_sn,
                    'amount'       => $rechargeOrder->amount,
                    'status'       => $rechargeOrder->status,
                    "payment_data" => $result,
                    'expired_at'   => $rechargeOrder->expired_at->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code'    => 500,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

    /**
     * 获取充值订单列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function getRechargeOrders(Request $request)
    {
        $user     = $request->user();
        $page     = $request->input('page', 1);
        $pageSize = min($request->input('page_size', 20), 100);

        $query = RechargeOrder::where('user_id', $user->id);

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        $data = [
            'current_page' => $orders->currentPage(),
            'per_page'     => $orders->perPage(),
            'total'        => $orders->total(),
            'data'         => $orders->getCollection()->map(function ($order) {
                return [
                    'id'             => $order->id,
                    'order_sn'       => $order->order_sn,
                    'amount'         => $order->amount,
                    'actual_amount'  => $order->actual_amount,
                    'bonus_amount'   => $order->bonus_amount,
                    'status'         => $order->status,
                    'status_text'    => $order->status_text,
                    'payment_method' => $order->payment_method,
                    'created_at'     => $order->created_at->format('Y-m-d H:i:s'),
                    'expired_at'     => $order->expired_at ? $order->expired_at->format('Y-m-d H:i:s') : null,
                ];
            })
        ];

        return response()->json([
            'code'    => 200,
            'message' => 'success',
            'data'    => $data
        ]);
    }

    /**
     * 获取余额变动记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalanceLogs(Request $request)
    {
        $user = $request->user();
        $page     = $request->input('page', 1);
        $pageSize = min($request->input('page_size', 20), 100);

        $filters = [];
        if ($request->has('type')) {
            $filters['type'] = $request->input('type');
        }

        $logs = $this->balanceService->getUserBalanceLogs($user->id, $page, $pageSize, $filters);

        $data = [
            'current_page' => $logs->currentPage(),
            'per_page'     => $logs->perPage(),
            'total'        => $logs->total(),
            'data'         => $logs->getCollection()->map(function ($log) {
                return [
                    'id'             => $log->id,
                    'log_sn'         => $log->log_sn,
                    'type'           => $log->type,
                    'type_text'      => $log->type_text,
                    'amount'         => $log->amount,
                    'balance_before' => $log->balance_before,
                    'balance_after'  => $log->balance_after,
                    'title'          => $log->title,
                    'description'    => $log->description,
                    'created_at'     => $log->created_at->format('Y-m-d H:i:s'),
                ];
            })
        ];

        return response()->json([
            'code'    => 200,
            'message' => 'success',
            'data'    => $data
        ]);
    }
}