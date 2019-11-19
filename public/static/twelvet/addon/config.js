//layui时间插件
layui.use(['laydate', 'upload', 'layer', 'form', 'notice'], function () {
    var laydate = layui.laydate,
        upload = layui.upload,
        notice = layui.notice;
        layer = layui.layer;
    //定义插件名称
    const theme = window.location.search.substring(1).split('=')[1];
    //绑定时间选择器
    $('.datetimepicker').each(function () {
        laydate.render({
            elem: this,
            theme: 'molv',
            type: 'datetime'
        })
    })
    //绑定数组参数渲染
    if ($(".fields").length > 0) {
        //实时监控数组改变
        $(document).on('change keyup', '.fields input,.fields textarea', function () {
            var name = $(this).closest("dl").data("name");
            //定义数组
            var data = {};
            //获取textarea数据对象
            var textarea = $("textarea[name='twelvet[" + name + "]']", ctn);
            //获取主要容器
            var ctn = textarea.closest(".fields");
            //遍历容器中的元素
            $.each($("input", ctn), function (i, e) {
                //定义正则
                var reg = /\[(\w+)\]\[(\w+)\]$/g;
                //匹配数据
                var match = reg.exec($(this).data('fields'));
                //判断是否匹配成功
                if (!match) return true;
                //为数组定义名称，符合命名规则 twelvet0
                match[2] = "twelvet" + parseInt(match[2]);
                //判断是否存在此参数，不存在先定义为数组
                if (typeof data[match[2]] == 'undefined') {
                    data[match[2]] = {};
                }
                //添加二维参数
                data[match[2]][match[1]] = e.value;
            })
            //定义结果
            var result = {};
            //遍历二维数组，由二维变一维
            $.each(data, function (i, e) {
                //判断是否存在key值，不存在不允许添加
                if (e.key != '') {
                    result[e.key] = e.value;
                }
            });
            //赋值参数
            textarea.val(JSON.stringify(result));
        })
        //追加列表事件
        $('.fields').on('click', '.append', function (e, params) {
            //获取容器
            var ctn = $(this).closest('.fields');
            //获取列表个数
            var index = ctn.data("index");
            //转换为数字
            index = index ? parseInt(index) : 0;
            //增加唯一参数ID
            ctn.data("index", index + 1);
            //获取名称参数
            var name = ctn.data("name");
            //获取所有data数据
            var data = ctn.data();
            //判断是否有参数传入,没有任何参数传进作为数组给予配置
            var params = params ? params : {
                key: '',
                value: ''
            };
            //定义数组，替换字符串
            var twelvet = {
                index: index,
                name: name,
                data: data,
                params: params
            };
            //模板参数替换
            var html = '<dd><input type="text" data-fields="' + name + '[key][' + index + ']" class="form-control" value="' + twelvet.params.key + '" size="10" /> <input type="text" data-fields="' + name + '[value][' + index + ']" class="form-control" value="' + twelvet.params.value + '" /> <button class="btn btn-danger remove"><i class="fa fa-times"></i></button></dd>';
            //写入当前节点下
            $(html).insertBefore($(this).closest("dd"));
        })
        //渲染数据
        $('.fields').each(function () {
            //储存本对象,待添加数据
            var ctn = $(this);
            //获取参数
            var textarea = ctn.find('textarea').val();
            //判断是否存在数据
            if (textarea == '') return true;
            //获取并且解析json数据
            try {
                var params = JSON.parse(textarea);
            } catch (Exception) {
                layer.msg(Exception);
            }
            $.each(params, function (k, v) {
                $(".append", ctn).trigger('click', {
                    key: k,
                    value: v
                });
            });
        })
        //删除事件
        $('.fields').on('click', '.remove', function () {
            //提前获取参数,防止移除节点后无法正常获取
            var name = $(this).closest("dl").data("name");
            //删除节点
            $(this).closest("dd").remove();
            reFields(name);
        })
    }
    //图片配置信息绑定
    if ($('.img-thumbnail').length || $('.img-thumbnails').length > 0) {
        //实时监控图片缩略图地址的改变
        $(document).on('change keyup', '.thumbnail', function () {
            //获取参数
            var param = $(this).val();
            //获取列表
            var ul = $(this).closest('.form-row').find('ul');
            //参数为空的停止本次渲染
            if (param == '') {
                //清空信息
                ul.empty();
                return true
            }
            var params = {};
            if ($(this).attr('data-img-type') == 'image') {
                //赋值为数组
                params = [param];
            } else {
                //分割参数
                params = param.split(',');
            }
            //清空信息
            ul.empty();
            //遍历渲染
            params.forEach(el => {
                ul.append("<li class='col-sm-3 col-xs-auto'><a href='" + el + "' target='_blank'><img src='" + el + "'  class='img-responsive'></a><a href='javascript:;' class='btn btn-danger img-trash'><i class='fa fa-trash'></i></a></li>")
            });
        })
        //进行第一次渲染
        $('.thumbnail').trigger("change");
        //动态绑定删除事件
        $('.img-preview').on('click', '.img-trash', function () {
            var e = $(this).closest('.form-row');
            //获取input元素
            var input = e.find('.thumbnail');
            //获取数组
            var params = input.val().split(',');
            //获取当前节点位置
            var index = $(this).parent().index();
            //移除指定数组元素
            params.splice(index, 1).join(',');
            //重新赋值
            input.val(params).trigger("change");
        })
        //配置文件管理器
        $('.manage').on('click', function () {
            //获取唯一id
            var input_id = $(this).data("input-id") ? $(this).data("input-id") : "";
            //获取对象
            var input = $("#" + input_id);
            layer.open({
                type: 2,
                title: '静态配置文件管理',
                maxmin: true,
                area: ['90%', '90%'],
                content: './manage/name/' + theme,
                success:function (layero,index) {
                    //向管理窗口发送操作对象
                    window['layui-layer-iframe' + index].parentInput = input;
                }
            })
        })
        //绑定图片缩略图上传功能
        upload.render({
            elem: '.img-upload',
            url: './upload/name/' + theme,
            field: 'image',
            accept: 'image',
            before: function () {
                //等待提示
                layer.load(0, {
                    shade: 0.3
                });
            },
            done: function (res) {
                //关闭等待提示
                layer.close(layer.load());
                if (res.state) {
                    //获取当前id
                    var id = this.id;
                    //判断单多缩略图,并重新赋值
                    $(id).val(res.msg).trigger('change');
                    //提示信息
                    notice.success("上传成功");
                } else {
                    notice.error(res.msg);
                }
            },
            error: function () {
                //关闭等待提示
                layer.close(layer.load());
                layer.msg('上传接口发生错误', {
                    time: 5000,
                    icon: 2
                });
            }
        })
        //绑定图片缩略图上传功能
        upload.render({
            elem: '.img-uploads',
            url: './upload/name/' + theme,
            field: 'image',
            accept: 'image',
            multiple: true,
            before: function () {
                //等待提示
                layer.load(0, {
                    shade: 0.3
                });
            },
            done: function (res) {
                //关闭等待提示
                layer.close(layer.load());
                if (res.state) {
                    //获取当前id
                    var id = this.id;
                    //判断单多缩略图,并重新赋值
                    if ($(id).val() == '') {
                        $(id).val(res.msg).trigger('change');
                    } else {
                        $(id).val($(id).val() + ',' + res.msg).trigger('change');
                    }
                    //提示信息
                    notice.success("上传成功");
                } else {
                    notice.error(res.msg);
                }
            },
            error: function () {
                //关闭等待提示
                layer.close(layer.load());
                layer.msg('上传接口发生错误', {
                    time: 5000,
                    icon: 2
                });
            }
        })
    }
    //绑定保存按钮处理地址
    $('.btn-preservation').click(function () {
        $.ajax({
            url: location.href,
            type: 'post',
            dataTepy: 'json',
            data: $('#config-form').serialize(),
            success: function (res) {
                if(res.state){
                    // 执行关闭layui 
                    parent.layer.close(
                        parent.layer.getFrameIndex(window.name)
                    );
                    parent.layui.notice.success("保存成功");
                }else{
                    notice.error(res.msg);
                }
            }
        })
    })
})