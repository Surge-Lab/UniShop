<?php

namespace App\Admin\Controllers;

use App\Models\User as UserModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class CustomerController extends AdminController
{
    /**
     * 页面标题
     *
     * @var string
     */
    protected $title = '客户管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(UserModel::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'DESC');
            
            $grid->column('id', 'ID')->sortable();
            $grid->column('email', '邮箱')->sortable();
            $grid->column('amount', '余额')->sortable()->display(function ($amount) {
                return '¥' . number_format($amount, 2);
            });
            $grid->column('status', '状态')->display(function ($status) {
                return $status == 1 ? '<span class="badge badge-success">正常</span>' : '<span class="badge badge-danger">禁用</span>';
            });
            $grid->column('secret_key', 'API密钥')->limit(20)->copyable();
            $grid->column('created_at', '注册时间')->sortable();
            $grid->column('updated_at', '更新时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id', 'ID');
                $filter->like('email', '邮箱');
                $filter->between('amount', '余额');
                $filter->select('status', '状态')->options([
                    1 => '正常',
                    2 => '禁用'
                ]);
                $filter->between('created_at', '注册时间')->datetime();
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
                // 使用统一的按钮样式
                $actions->append('<a href="javascript:void(0);" class="grid-action-button balance-adjust" data-id="'.$this->id.'" data-email="'.$this->email.'" data-amount="'.$this->amount.'"><i class="fa fa-money"></i> 调整余额</a>');
                $actions->append('<a href="'.admin_url('customers/'.$this->id.'/balance-logs').'" class="grid-action-button"><i class="fa fa-history"></i> 余额记录</a>');
            });

            $grid->tools(function (Grid\Tools $tools) {
                $tools->batch(function (Grid\Tools\BatchActions $batch) {
                    $batch->disableDelete();
                });
            });

            // 添加JavaScript和模态框
            $grid->footer(function () {
                return view('admin.customer.balance_adjust');
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, UserModel::class, function (Show $show) {
            $show->field('id', 'ID');
            $show->field('email', '邮箱');
            $show->field('amount', '余额');
            $show->field('status', '状态')->using([
                1 => '正常',
                2 => '禁用'
            ]);
            $show->field('secret_key', 'API密钥');
            $show->field('created_at', '注册时间');
            $show->field('updated_at', '更新时间');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(UserModel::class, function (Form $form) {
            $form->display('id');
            
            $form->email('email', '邮箱')
                ->required()
                ->rules('required|email|unique:users,email,' . $form->getKey())
                ->help('邮箱地址，必须唯一');
            
            $form->password('password', '密码')
                ->rules('required|min:6')
                ->help('密码长度至少6位');
            
            $form->currency('amount', '余额')
                ->symbol('¥')
                ->default(0.00)
                ->help('用户账户余额');
            
            $form->select('status', '状态')
                ->options([
                    1 => '正常',
                    2 => '禁用'
                ])
                ->default(1)
                ->required()
                ->help('用户状态：正常可正常使用，禁用将无法登录');
            
            $form->text('secret_key', 'API密钥')
                ->default(function () {
                    return UserModel::generateSecret();
                })
                ->rules('required|unique:users,secret_key,' . $form->getKey())
                ->help('用户API密钥，用于API调用，系统会自动生成');

            $form->display('created_at', '注册时间');
            $form->display('updated_at', '更新时间');

            $form->saving(function (Form $form) {
                // 如果是编辑模式且密码为空，则不更新密码
                if ($form->isEditing() && empty($form->password)) {
                    $form->deleteInput('password');
                }
            });
        });
    }

    /**
     * 调整用户余额
     */
    public function adjustBalance(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric',
            'type' => 'required|in:increase,decrease,set',
            'reason' => 'required|string|max:255'
        ]);

        try {
            \DB::beginTransaction();
            
            $user = UserModel::findOrFail($request->user_id);
            $balanceService = new \App\Service\BalanceService();
            
            $oldAmount = $user->amount;
            $newAmount = $request->amount;
            $type = $request->type;
            $reason = $request->reason;
            
            if ($type === 'increase') {
                // 增加余额
                $balanceService->increaseBalance(
                    $user->id,
                    $newAmount,
                    \App\Models\BalanceLog::TYPE_ADMIN_ADJUST,
                    'admin_adjust',
                    null,
                    '管理员调整余额',
                    $reason,
                    admin_user()->name ?? '管理员'
                );
                $message = "成功为用户 {$user->username} 增加余额 ¥{$newAmount}";
            } elseif ($type === 'decrease') {
                // 减少余额
                if ($user->amount < $newAmount) {
                    throw new \Exception('用户余额不足，无法减少');
                }
                $balanceService->decreaseBalance(
                    $user->id,
                    $newAmount,
                    \App\Models\BalanceLog::TYPE_ADMIN_ADJUST,
                    'admin_adjust',
                    null,
                    '管理员调整余额',
                    $reason,
                    admin_user()->name ?? '管理员'
                );
                $message = "成功为用户 {$user->username} 减少余额 ¥{$newAmount}";
            } else {
                // 设置余额
                $difference = $newAmount - $user->amount;
                if ($difference > 0) {
                    $balanceService->increaseBalance(
                        $user->id,
                        $difference,
                        \App\Models\BalanceLog::TYPE_ADMIN_ADJUST,
                        'admin_adjust',
                        null,
                        '管理员设置余额',
                        $reason,
                        admin_user()->name ?? '管理员'
                    );
                } elseif ($difference < 0) {
                    $balanceService->decreaseBalance(
                        $user->id,
                        abs($difference),
                        \App\Models\BalanceLog::TYPE_ADMIN_ADJUST,
                        'admin_adjust',
                        null,
                        '管理员设置余额',
                        $reason,
                        admin_user()->name ?? '管理员'
                    );
                }
                $message = "成功设置用户 {$user->username} 余额为 ¥{$newAmount}";
            }
            
            \DB::commit();
            
            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'old_amount' => $oldAmount,
                    'new_amount' => $user->fresh()->amount
                ]
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('调整用户余额失败', [
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'type' => $request->type,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => '操作失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取用户余额变动记录
     */
    public function balanceLogs(\Illuminate\Http\Request $request, $userId)
    {
        $user = UserModel::findOrFail($userId);
        $logs = \App\Models\BalanceLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // 使用 Dcat Admin 的内容包装器
        return \Dcat\Admin\Layout\Content::make()
            ->title('余额变动记录')
            ->description('用户：' . $user->email)
            ->body(view('admin.customer.balance_logs', compact('user', 'logs')));
    }
}
   