<?php

namespace App\Service;

use App\Models\BalanceLog;
use App\Models\RechargeOrder;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * 创建充值订单
     *
     * @param int $userId 用户ID
     * @param float $amount 充值金额
     * @param string $payId 支付通道ID
     * @param string $paymentMethod 支付方式
     * @param array $extraData 额外数据
     * @return RechargeOrder
     */
    public function createRechargeOrder($userId, $amount, $payId,$paymentMethod, $extraData = [])
    {
        $orderSn = $this->generateRechargeOrderSn();
        
        $data = array_merge([
            'order_sn' => $orderSn,
            'user_id' => $userId,
            'amount' => $amount,
            'actual_amount' => $amount, // 默认实际到账金额等于充值金额
            'bonus_amount' => 0.00,     // 默认无赠送金额
            'pay_id' => $payId,
            'payment_method' => $paymentMethod,
            'buy_ip' => request()->ip(),
            'status' => RechargeOrder::STATUS_PENDING,
            'expired_at' => now()->addMinutes(10), // 10分钟过期
        ], $extraData);

        return RechargeOrder::create($data);
    }

    /**
     * 处理充值成功
     *
     * @param RechargeOrder $rechargeOrder
     * @param string $tradeNo 第三方支付订单号
     * @param array $paymentData 支付相关数据
     * @return bool
     * @throws Exception
     */
    public function handleRechargeSuccess(RechargeOrder $rechargeOrder, $tradeNo = '', $paymentData = [])
    {
        if ($rechargeOrder->isPaid()) {
            throw new Exception('订单已支付，请勿重复操作');
        }

        DB::beginTransaction();
        try {
            // 更新充值订单状态
            $rechargeOrder->update([
                'status' => RechargeOrder::STATUS_PAID,
                'trade_no' => $tradeNo,
                'payment_data' => $paymentData,
                'paid_at' => now(),
            ]);

            // 增加用户余额
            $totalAmount = $rechargeOrder->actual_amount + $rechargeOrder->bonus_amount;
            $this->increaseBalance(
                $rechargeOrder->user_id,
                $totalAmount,
                BalanceLog::TYPE_RECHARGE,
                BalanceLog::SOURCE_RECHARGE_ORDER,
                $rechargeOrder->id,
                '余额充值',
                "充值订单：{$rechargeOrder->order_sn}"
            );

            // 标记订单完成
            $rechargeOrder->update([
                'status' => RechargeOrder::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 增加用户余额
     *
     * @param int $userId 用户ID
     * @param float $amount 增加金额
     * @param int $type 变动类型
     * @param string $sourceType 来源类型
     * @param int $sourceId 来源ID
     * @param string $title 变动标题
     * @param string $description 变动描述
     * @param string $adminUser 操作管理员
     * @param array $extraData 扩展数据
     * @return BalanceLog
     * @throws Exception
     */
    public function increaseBalance($userId, $amount, $type, $sourceType = null, $sourceId = null, $title = '', $description = '', $adminUser = '', $extraData = [])
    {
        if ($amount <= 0) {
            throw new Exception('增加金额必须大于0');
        }

        return $this->changeBalance($userId, $amount, $type, $sourceType, $sourceId, $title, $description, $adminUser, $extraData);
    }

    /**
     * 减少用户余额
     *
     * @param int $userId 用户ID
     * @param float $amount 减少金额
     * @param int $type 变动类型
     * @param string $sourceType 来源类型
     * @param int $sourceId 来源ID
     * @param string $title 变动标题
     * @param string $description 变动描述
     * @param string $adminUser 操作管理员
     * @param array $extraData 扩展数据
     * @return BalanceLog
     * @throws Exception
     */
    public function decreaseBalance($userId, $amount, $type, $sourceType = null, $sourceId = null, $title = '', $description = '', $adminUser = '', $extraData = [])
    {
        if ($amount <= 0) {
            throw new Exception('减少金额必须大于0');
        }

        return $this->changeBalance($userId, -$amount, $type, $sourceType, $sourceId, $title, $description, $adminUser, $extraData);
    }

    /**
     * 变更用户余额（核心方法）
     *
     * @param int $userId 用户ID
     * @param float $amount 变动金额（正数增加，负数减少）
     * @param int $type 变动类型
     * @param string $sourceType 来源类型
     * @param int $sourceId 来源ID
     * @param string $title 变动标题
     * @param string $description 变动描述
     * @param string $adminUser 操作管理员
     * @param array $extraData 扩展数据
     * @return BalanceLog
     * @throws Exception
     */
    protected function changeBalance($userId, $amount, $type, $sourceType = null, $sourceId = null, $title = '', $description = '', $adminUser = '', $extraData = [])
    {
        DB::beginTransaction();
        try {
            // 锁定用户记录，防止并发操作
            $user = User::where('id', $userId)->lockForUpdate()->first();
            if (!$user) {
                throw new Exception('用户不存在');
            }

            $balanceBefore = $user->amount ?? 0;
            $balanceAfter = $balanceBefore + $amount;

            // 检查余额是否足够（减少余额时）
            if ($amount < 0 && $balanceAfter < 0) {
                throw new Exception('用户余额不足');
            }

            // 更新用户余额
            $user->update(['amount' => $balanceAfter]);

            // 创建余额变动记录
            $balanceLog = BalanceLog::createLog([
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'title' => $title,
                'description' => $description,
                'admin_user' => $adminUser,
                'extra_data' => $extraData,
            ]);

            DB::commit();
            return $balanceLog;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 获取用户余额
     *
     * @param int $userId
     * @return float
     */
    public function getUserBalance($userId)
    {
        $user = User::find($userId);
        return $user ? ($user->amount ?? 0) : 0;
    }

    /**
     * 生成充值订单号
     *
     * @return string
     */
    protected function generateRechargeOrderSn()
    {
        return 'RO' . date('YmdHis') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 获取用户余额变动记录
     *
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserBalanceLogs($userId, $page = 1, $pageSize = 20, $filters = [])
    {
        $query = BalanceLog::where('user_id', $userId);

        // 应用过滤条件
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($pageSize, ['*'], 'page', $page);
    }

}