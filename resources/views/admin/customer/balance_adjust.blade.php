<div class="modal fade" id="balanceAdjustModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">调整用户余额</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="balanceAdjustForm">
                    <input type="hidden" id="adjust_user_id" name="user_id">
                    
                    <div class="form-group">
                        <label>邮箱</label>
                        <input type="text" class="form-control" id="adjust_email" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>当前余额</label>
                        <input type="text" class="form-control" id="adjust_current_amount" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>调整类型</label>
                        <select class="form-control" name="type" id="adjust_type" required>
                            <option value="">请选择调整类型</option>
                            <option value="increase">增加余额</option>
                            <option value="decrease">减少余额</option>
                            <option value="set">设置余额</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>金额</label>
                        <div class="input-group">
                            <span class="input-group-addon">¥</span>
                            <input type="number" class="form-control" name="amount" id="adjust_amount" 
                                   step="0.01" min="0" required placeholder="请输入金额">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>调整原因</label>
                        <textarea class="form-control" name="reason" rows="3" required 
                                  placeholder="请详细说明调整原因，此信息将记录在余额变动日志中"></textarea>
                    </div>
                    
                    <div class="form-group" id="preview_group" style="display: none;">
                        <label>调整后余额</label>
                        <input type="text" class="form-control" id="adjust_preview" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="confirmAdjust">确认调整</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 点击调整余额按钮
    $('.balance-adjust').click(function() {
        var userId = $(this).data('id');
        var email = $(this).data('email');
        var amount = $(this).data('amount');
        
        $('#adjust_user_id').val(userId);
        $('#adjust_email').val(email);
        $('#adjust_current_amount').val('¥' + parseFloat(amount).toFixed(2));
        $('#balanceAdjustModal').modal('show');
    });
    
    // 调整类型变化时计算预览
    $('#adjust_type, #adjust_amount').on('change input', function() {
        var type = $('#adjust_type').val();
        var amount = parseFloat($('#adjust_amount').val()) || 0;
        var currentAmount = parseFloat($('#adjust_current_amount').val().replace('¥', '')) || 0;
        
        if (type && amount > 0) {
            var newAmount = currentAmount;
            if (type === 'increase') {
                newAmount = currentAmount + amount;
            } else if (type === 'decrease') {
                newAmount = currentAmount - amount;
            } else if (type === 'set') {
                newAmount = amount;
            }
            
            $('#adjust_preview').val('¥' + newAmount.toFixed(2));
            $('#preview_group').show();
        } else {
            $('#preview_group').hide();
        }
    });
    
    // 确认调整
    $('#confirmAdjust').click(function() {
        var formData = $('#balanceAdjustForm').serialize();
        
        $.ajax({
            url: '/admin/customers/adjust-balance',
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status) {
                    Dcat.success(response.message);
                    $('#balanceAdjustModal').modal('hide');
                    location.reload();
                } else {
                    Dcat.error(response.message);
                }
            },
            error: function(xhr) {
                var response = xhr.responseJSON;
                Dcat.error(response.message || '操作失败');
            }
        });
    });
});
</script>
