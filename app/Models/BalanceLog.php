<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceLog extends Model
{
    protected $table = 'balance_logs';

    // 变动类型常量
    const TYPE_RECHARGE = 1;        // 充值
    const TYPE_CONSUME = 2;         // 消费
    const TYPE_REFUND = 3;          // 退款
    const TYPE_REWARD = 4;          // 奖励
    const TYPE_DEDUCT = 5;          // 扣除
    const TYPE_TRANSFER_IN = 6;     // 转账转入
    const TYPE_TRANSFER_OUT = 7;    // 转账转出
    const TYPE_AGENT_CONSUME = 8;   // 代理订单消费
    const TYPE_ADMIN_ADJUST = 9;    // 管理员调整

    // 来源类型常量
    const SOURCE_RECHARGE_ORDER = 'recharge_order';
    const SOURCE_ORDER = 'order';
    const SOURCE_AGENT_ORDER = 'agent_order';
    const SOURCE_REFUND = 'refund';
    const SOURCE_REWARD = 'reward';
    const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'log_sn',
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'title',
        'description',
        'admin_user',
        'extra_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'extra_data' => 'json',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 变动类型文本映射
     */
    public static function getTypeText($type)
    {
        $typeMap = [
            self::TYPE_RECHARGE => '充值',
            self::TYPE_CONSUME => '消费',
            self::TYPE_REFUND => '退款',
            self::TYPE_REWARD => '奖励',
            self::TYPE_DEDUCT => '扣除',
            self::TYPE_TRANSFER_IN => '转账转入',
            self::TYPE_TRANSFER_OUT => '转账转出',
        ];

        return $typeMap[$type] ?? '未知类型';
    }

    /**
     * 获取变动类型文本
     */
    public function getTypeTextAttribute()
    {
        return self::getTypeText($this->type);
    }

    /**
     * 来源类型文本映射
     */
    public static function getSourceTypeText($sourceType)
    {
        $sourceTypeMap = [
            self::SOURCE_RECHARGE_ORDER => '充值订单',
            self::SOURCE_ORDER => '订单',
            self::SOURCE_REFUND => '退款',
            self::SOURCE_REWARD => '奖励',
            self::SOURCE_MANUAL => '手动操作',
        ];

        return $sourceTypeMap[$sourceType] ?? '未知来源';
    }

    /**
     * 获取来源类型文本
     */
    public function getSourceTypeTextAttribute()
    {
        return self::getSourceTypeText($this->source_type);
    }

    /**
     * 多态关联来源对象
     */
    public function source()
    {
        return $this->morphTo();
    }

    /**
     * 检查是否为增加余额
     */
    public function isIncrease()
    {
        return $this->amount > 0;
    }

    /**
     * 检查是否为减少余额
     */
    public function isDecrease()
    {
        return $this->amount < 0;
    }

    /**
     * 生成流水号
     */
    public static function generateLogSn()
    {
        return 'BL' . date('YmdHis') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 创建余额变动记录
     */
    public static function createLog($data)
    {
        if (!isset($data['log_sn'])) {
            $data['log_sn'] = self::generateLogSn();
        }

        return self::create($data);
    }
}