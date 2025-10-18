{{-- 余额变动记录页面 --}}
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">用户余额变动记录 - {{ $user->email }}</h3>
                <div class="card-tools">
                    <a href="{{ admin_url('customers') }}" class="btn btn-sm btn-default">
                        <i class="fa fa-arrow-left"></i> 返回用户列表
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>用户ID：</strong>{{ $user->id }}
                    </div>
                    <div class="col-md-3">
                        <strong>邮箱：</strong>{{ $user->email }}
                    </div>
                    <div class="col-md-3">
                        <strong>当前余额：</strong>¥{{ number_format($user->amount, 2) }}
                    </div>
                    <div class="col-md-3">
                        <strong>记录总数：</strong>{{ $logs->total() }}
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>变动类型</th>
                                <th>变动金额</th>
                                <th>变动前余额</th>
                                <th>变动后余额</th>
                                <th>来源类型</th>
                                <th>标题</th>
                                <th>描述</th>
                                <th>操作管理员</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->id }}</td>
                                <td>
                                    @switch($log->type)
                                        @case(\App\Models\BalanceLog::TYPE_RECHARGE)
                                            <span class="badge badge-success">充值</span>
                                            @break
                                        @case(\App\Models\BalanceLog::TYPE_CONSUME)
                                            <span class="badge badge-danger">消费</span>
                                            @break
                                        @case(\App\Models\BalanceLog::TYPE_ADMIN_ADJUST)
                                            <span class="badge badge-warning">管理员调整</span>
                                            @break
                                        @case(\App\Models\BalanceLog::TYPE_REFUND)
                                            <span class="badge badge-info">退款</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">其他</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($log->amount > 0)
                                        <span class="text-success">+¥{{ number_format($log->amount, 2) }}</span>
                                    @else
                                        <span class="text-danger">¥{{ number_format($log->amount, 2) }}</span>
                                    @endif
                                </td>
                                <td>¥{{ number_format($log->balance_before, 2) }}</td>
                                <td>¥{{ number_format($log->balance_after, 2) }}</td>
                                <td>{{ $log->source_type ?? '-' }}</td>
                                <td>{{ $log->title }}</td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->admin_user ?? '-' }}</td>
                                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center">暂无记录</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($logs->hasPages())
                <div class="row">
                    <div class="col-md-12">
                        {{ $logs->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
