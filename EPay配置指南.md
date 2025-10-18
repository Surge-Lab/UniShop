# EPay支付配置指南

## 📋 您的配置信息

```json
{
    "url": "https://xppyiy.epusdt.com",
    "pid": "88418077",
    "key": "Qt2gObdGsm06"
}
```

---

## 🚀 快速配置（推荐方法）

### **步骤1：更新配置**

在您的服务器或本地执行：

```bash
php update_epay_config.php
```

这个脚本会自动：
- ✅ 查找EPay支付方式
- ✅ 更新配置参数
- ✅ 启用支付方式
- ✅ 显示配置结果

---

### **步骤2：测试配置**

```bash
php test_epay_payment.php
```

这个脚本会：
- ✅ 验证配置完整性
- ✅ 生成测试支付URL
- ✅ 验证签名算法
- ✅ 显示notify_url和return_url

---

## 📝 手动配置方法

### **方法A：通过管理后台**

1. 访问：`https://shoptest1.surgelab.io/admin`
2. 登录管理员账号
3. 找到 **支付管理** → **支付方式**
4. 找到 **EPay** 并点击编辑
5. 填入配置：
   ```
   URL: https://xppyiy.epusdt.com
   PID: 88418077
   KEY: Qt2gObdGsm06
   ```
6. 确保 **启用状态** 为开启
7. 保存

---

### **方法B：直接修改数据库**

#### **找到表名**

首先确认您的表前缀，查看 `.env` 文件中的 `DB_PREFIX`：

```bash
# 如果没有前缀
UPDATE payment 
SET config = '{"url":"https://xppyiy.epusdt.com","pid":"88418077","key":"Qt2gObdGsm06"}',
    enable = 1
WHERE payment = 'EPay';

# 如果有前缀（如dujiaoka_）
UPDATE dujiaoka_payment 
SET config = '{"url":"https://xppyiy.epusdt.com","pid":"88418077","key":"Qt2gObdGsm06"}',
    enable = 1
WHERE payment = 'EPay';
```

#### **执行方式**

**在宝塔面板：**
1. 数据库 → 选择您的数据库 → phpMyAdmin
2. 选择SQL标签
3. 粘贴上面的SQL
4. 点击执行

**在命令行：**
```bash
mysql -u用户名 -p密码 数据库名 << EOF
UPDATE payment 
SET config = '{"url":"https://xppyiy.epusdt.com","pid":"88418077","key":"Qt2gObdGsm06"}',
    enable = 1
WHERE payment = 'EPay';
EOF
```

---

## 🔍 验证配置是否成功

### **检查数据库**

```sql
SELECT id, payment, name, enable, config 
FROM payment 
WHERE payment = 'EPay';
```

**期望结果：**
```
id: 5
payment: EPay
name: 易支付
enable: 1
config: {"url":"https://xppyiy.epusdt.com","pid":"88418077","key":"Qt2gObdGsm06"}
```

---

### **检查系统日志**

在Laravel日志中应该能看到：

```bash
tail -f storage/logs/laravel.log
```

创建订单时会看到：
```
[2024-10-18 10:00:00] local.INFO: 创建支付 {"payment": "EPay", "config": {...}}
```

---

## 📦 配置后的自动生成参数

系统会自动生成以下URL：

### **notify_url（通知地址）**
```
https://shoptest1.surgelab.io/pay/notify/EPay/{uuid}
```
- EPay支付成功后会自动调用这个地址
- 系统会验证签名并更新订单状态
- **必须配置**，否则订单不会自动更新

### **return_url（返回地址）**
```
https://shoptest1.surgelab.io/bill/{订单号}
```
- 用户支付完成后浏览器跳转到这个页面
- 显示订单详情和支付结果
- **推荐配置**，提升用户体验

---

## 🧪 测试支付流程

### **1. 创建测试订单**

通过API创建订单：

```bash
curl -X POST https://shoptest1.surgelab.io/api/v1/order/create \
  -H "Content-Type: application/json" \
  -d '{
    "goods_id": 1,
    "buy_amount": 1,
    "email": "test@example.com",
    "search_pwd": "123456"
  }'
```

### **2. 选择EPay支付**

```bash
curl -X POST https://shoptest1.surgelab.io/api/v1/payment/create \
  -H "Content-Type: application/json" \
  -d '{
    "order_sn": "订单号",
    "payway": 5
  }'
```

### **3. 获取支付URL**

响应示例：
```json
{
  "code": 200,
  "message": "创建成功",
  "data": {
    "type": 1,
    "data": "https://xppyiy.epusdt.com/submit.php?..."
  }
}
```

### **4. 访问支付URL**

- 在浏览器中打开返回的URL
- 完成支付流程
- 支付成功后检查订单状态

---

## 🔧 常见问题排查

### **问题1：签名错误 (Signature error)**

**原因：**
- config中的key配置错误
- 签名算法不匹配

**解决：**
1. 确认key是否正确：`Qt2gObdGsm06`
2. 联系EPay平台确认签名算法
3. 检查是否需要URL编码

---

### **问题2：订单状态不更新**

**原因：**
- notify_url无法访问
- 服务器防火墙阻止
- 签名验证失败

**解决：**
1. 检查服务器是否能从外网访问
2. 查看Laravel日志：`storage/logs/laravel.log`
3. 测试notify_url是否可访问：
   ```bash
   curl https://shoptest1.surgelab.io/pay/notify/EPay/{uuid}
   ```

---

### **问题3：支付URL无法打开**

**原因：**
- EPay平台地址错误
- pid或key配置错误

**解决：**
1. 确认URL：`https://xppyiy.epusdt.com` 可以访问
2. 登录EPay后台确认配置参数
3. 联系EPay技术支持

---

## 📊 支付流程图

```
用户创建订单
    ↓
选择EPay支付
    ↓
系统生成支付URL
    ↓
参数：
- url: https://xppyiy.epusdt.com
- pid: 88418077
- key: Qt2gObdGsm06 (用于签名)
- notify_url: https://shoptest1.surgelab.io/pay/notify/EPay/{uuid}
- return_url: https://shoptest1.surgelab.io/bill/{订单号}
    ↓
用户跳转到EPay平台支付
    ↓
支付成功
    ↓
【异步】EPay → notify_url (更新订单状态)
    ↓
【同步】用户浏览器 → return_url (显示结果)
    ↓
完成
```

---

## ✅ 配置检查清单

在正式使用前，请确认：

- [ ] 数据库中EPay的config包含完整的url、pid、key
- [ ] EPay支付方式的enable字段为1（已启用）
- [ ] 能够正常生成支付URL
- [ ] 签名验证通过
- [ ] notify_url可以从外网访问
- [ ] 测试订单支付成功后状态正常更新
- [ ] 用户支付完成后能正常跳转

---

## 📞 技术支持

如果配置过程中遇到问题：

1. **查看日志**：`storage/logs/laravel.log`
2. **运行测试**：`php test_epay_payment.php`
3. **联系EPay平台**：确认配置参数是否正确
4. **检查服务器**：确保notify_url可以从外网访问

---

## 🎉 配置完成

配置成功后，EPay支付将自动出现在支付方式列表中，用户可以正常使用！

