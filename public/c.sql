-- 功能反馈模块

-- 查询
select 
s.staffName, u.loginName, (select dataName from wst_datas where catId = 18 and dataVal = 1) as feedbackTypeName, f.feedbackContent, f.contactInfo, f.handleContent, f.createTime, f.handleTime, f.feedbackStatus
from 
wst_feedbacks as f 
left join wst_users as u on f.userId = u.userId
left join wst_staffs as s on f.staffId = s.staffId
where 
u.userId = 13 and
f.feedbackContent like '%暗示%' 
and
f.feedbackId = 1 
and
f.createTime between '2020-07-12 00:00:00' and '2020-07-14 23:59:59'



-- 商品咨询

-- 查询
select 
u.loginName, g.goodsImg, g.goodsName, gc.consultContent, gc.reply, gc.isShow
from 
wst_goods_consult gc 
left join wst_goods g on gc.goodsId = g.goodsId
left join wst_users u on gc.userId = u.userId;
where 
gc.consultContent like '%舒服%'
and
gc.consultType = 1
