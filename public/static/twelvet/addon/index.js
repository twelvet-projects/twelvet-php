! function () {
    "use strict";
    window.twelvet = {
        config: {
            // 初始化配置信息
            init: function () {
                // 获取父页面的配置
                // twelvet.config = window.parent.config;
                // if (!twelvet.config) twelvet.layui.layer.msg('无法配置全局信息，这将导致无法正常运行', {
                //     icon: 2
                // });
            }
        },
        // layui对象
        layui: {
            init: function () {
                twelvet.layui.table = layui.table;
                twelvet.layui.form = layui.form;
                twelvet.layui.upload = layui.upload;
                twelvet.layui.layer = layui.layer;
                twelvet.layui.notice = layui.notice;
            },
            layer: null,
            notice: null,
            table: null,
            upload: null,
            layer: null,
            notice: null
        },
        // 全局加载对象
        load: null,
        init: function () {
            // 配置layui
            layui.use(['table', 'upload', 'layer', 'notice'], function () {
                twelvet.layui.table = layui.table;
                twelvet.layui.form = layui.form;
                twelvet.layui.upload = layui.upload;
                twelvet.layui.layer = layui.layer;
                twelvet.layui.notice = layui.notice;

                // 尝试初始化配置信息
                // twelvet.config.init();
                twelvet.layui.table.render({
                    elem: '#data',
                    url: 'addon/downloaded',
                    id: 'data',
                    toolbar: '#drTool',
                    cellMinWidth: 80, //全局定义常规单元格的最小宽度
                    cols: [
                        [{
                                field: 'url',
                                title: '前台',
                                templet: '#home',
                                width: 50,
                                align: 'center'
                            },
                            {
                                field: 'title',
                                minWidth: 300,
                                title: '插件名称',
                                align: 'center'
                            },
                            {
                                field: 'intro',
                                title: '插件简介',
                                align: 'center'
                            },
                            {
                                field: 'author',
                                title: '作者',
                                align: 'center'
                            },
                            {
                                field: 'price',
                                title: '价格',
                                align: 'center'
                            },
                            {
                                field: 'version',
                                title: '版本',
                                align: 'center'
                            },
                            {
                                field: 'downloads',
                                title: '下载次数',
                                align: 'center'
                            },
                            {
                                field: 'state',
                                title: '状态',
                                width: 100,
                                templet: '#state',
                                align: 'center'
                            },
                            {
                                title: '操作',
                                toolbar: '#operation',
                                width: 150,
                                align: 'center'
                            }
                        ]
                    ],
                    page: true
                });

                //解决数据重载后事件绑定失效
                var drivingTool = function () {
                    //离线安装接口
                    twelvet.layui.upload.render({
                        elem: '#locaInstall',
                        url: '{:url("localInstall")}',
                        field: 'package',
                        accept: 'file',
                        exts: 'zip',
                        size: 10000,
                        before: function (obj) {
                            loading = layer.load();
                        },
                        done: function (res) {
                            layer.close(loading);
                            if (res.state) {
                                notice.success(res.msg);
                                table.reload('data', {
                                    done: function () {
                                        //重新执行事件绑定
                                        drivingTool()
                                    }
                                })
                            } else {
                                notice.error(res.msg);
                            }
                        },
                        error: function (index, upload) {
                            layer.close(loading);
                            notice.error('上传失败,请检查上传接口');
                        }
                    });
                }

                //监听单元操作
                twelvet.layui.table.on('tool(data)', function (obj) {
                    var data = obj.data;
                    if (obj.event === 'del') {
                        var confirmMsg = '<div>\
                                            卸载：' + data.title + '<br>\
                                            请自行检查数据沉余，部分需要手动清理<br>\
                                            <span style="color:red">重要数据请做好备份，完成执行后所有数据都将不可逆！</span>\
                                        </div>';
                        layer.confirm(confirmMsg, function (item) {
                            //发送ajax删除
                            $.ajax({
                                url: '{:url("uninstall")}',
                                type: 'post',
                                data: 'title=' + data.title + '&name=' + data.name + '&force=' + 'false',
                                dataType: 'json',
                                beforeSend: function () {
                                    loading = layer.load(0);
                                },
                                success: function (result) {
                                    //关闭加载动画
                                    layer.close(loading);
                                    //提示删除信息
                                    notice.success(result.msg);
                                    //实时更新
                                    obj.del();
                                    //关闭
                                    layer.close(item);
                                }
                            });
                        });
                    } else if (obj.event === 'edit') {
                        modfyIframe = layer.open({
                            type: 2,
                            title: '配置信息',
                            maxmin: true,
                            content: '{:url("config")}' + '?name=' + data.name,
                            area: ['90%', '95%']
                        })
                    }
                });

                //监听状态启用，禁用
                twelvet.layui.form.on('switch(state)', function (obj) {
                    //判断操作方式
                    var action = '';
                    if (obj.elem.checked) {
                        action = 'enable';
                    } else {
                        action = 'disable';
                    }
                    //发送ajax请求状态
                    $.ajax({
                        url: '{:url("state")}',
                        type: 'post',
                        data: 'name=' + this.value + '&action=' + action + '&force=false',
                        dataType: 'json',
                        beforeSend: function () {
                            loading = layer.load(0);
                        },
                        success: function (result) {
                            //关闭加载动画
                            layer.close(loading);
                            //提示信息
                            notice.success(result.msg);
                        }
                    })
                });

                //执行第一次的事件绑定
                drivingTool();

            });




        }
    }

    // 执行初始化
    twelvet.init();

}(window)