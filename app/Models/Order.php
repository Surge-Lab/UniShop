<?php

namespace App\Models;

use App\Events\OrderUpdated;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends BaseModel
{

    use SoftDeletes;

    protected $table = 'orders';

    /**
     * 待支付
     */
    const STATUS_WAIT_PAY = 1;

    /**
     * 待处理
     */
    const STATUS_PENDING = 2;

    /**
     * 处理中
     */
    const STATUS_PROCESSING = 3;

    /**
     * 已完成
     */
    const STATUS_COMPLETED = 4;

    /**
     * 失败
     */
    const STATUS_FAILURE = 5;

    /**
     * 过期
     */
    const STATUS_EXPIRED = -1;

    /**
     * 异常
     */
    const STATUS_ABNORMAL = 6;

    /**
     * 已取消
     */
    const STATUS_CANCELLED = 7;

    /**
     * 优惠券未回退
     */
    const COUPON_BACK_WAIT = 0;

    /**
     * 优惠券已回退
     */
    const COUPON_BACK_OK = 1;

    /**
     * 非代理商订单
     */
    const NOT_AGENT_ORDER = 0;

    /**
     * 代理商订单
     */
    const AGENT_ORDER = 1;

    protected $dispatchesEvents = [
        'updated' => OrderUpdated::class
    ];


    /**
     * 状态映射
     *
     * @return array
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public static function getStatusMap()
    {
        return [
            self::STATUS_WAIT_PAY => admin_trans('order.fields.status_wait_pay'),
            self::STATUS_PENDING => admin_trans('order.fields.status_pending'),
            self::STATUS_PROCESSING => admin_trans('order.fields.status_processing'),
            self::STATUS_COMPLETED => admin_trans('order.fields.status_completed'),
            self::STATUS_FAILURE => admin_trans('order.fields.status_failure'),
            self::STATUS_ABNORMAL => admin_trans('order.fields.status_abnormal'),
            self::STATUS_EXPIRED => admin_trans('order.fields.status_expired'),
            self::STATUS_CANCELLED => admin_trans('order.fields.status_cancelled')
        ];
    }

    /**
     * 类型映射
     *
     * @return array
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public static function getTypeMap()
    {
        return [
            self::AUTOMATIC_DELIVERY => admin_trans('goods.fields.automatic_delivery'),
            self::MANUAL_PROCESSING => admin_trans('goods.fields.manual_processing')
        ];
    }

    /**
     * 代理商订单类型映射
     *
     * @return array
     */
    public static function getAgentTypeMap()
    {
        return [
            self::NOT_AGENT_ORDER => '普通订单',
            self::AGENT_ORDER => '代理商订单'
        ];
    }

    /**
     * 关联商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id');
    }

    /**
     * 关联优惠券
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id')->select('id','discount');
    }

    /**
     * 关联支付方式（旧版兼容）
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function pay()
    {
        return $this->belongsTo(Payment::class, 'pay_id');
    }
    
    /**
     * 关联支付方式
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'pay_id')->select('id','name','payment');
    }

    /**
     * 关联用户
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 检查是否为代理商订单
     *
     * @return bool
     */
    public function isAgentOrder()
    {
        return $this->is_agent === self::AGENT_ORDER;
    }

    /**
     * 获取代理数据
     *
     * @return array|null
     */
    public function getAgentData()
    {
        return $this->agent_data ? json_decode($this->agent_data, true) : null;
    }

    /**
     * 设置代理数据
     *
     * @param array $data
     * @return bool
     */
    public function setAgentData(array $data)
    {
        $this->agent_data = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this->save();
    }

}
