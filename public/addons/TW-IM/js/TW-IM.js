layui.use(['notice'], function () {
    var notice = layui.notice;

    var IM = new Vue({
        el: '#IM',
        data: {

        },
        methods: {

        }
    })

    var IMChat = new Vue({
        el: '#IM-chat',
        data: {
            //WebSocket对象
            socket: {
                IM: null, //对象
                MaxRetryCount: 3, // 最大重连次数
                CurrentRetryCount: 0, // 储存重连次数
                timer: null,
                gateway: 'wss', //是否开启ssl
                url: document.domain, //域名
                port: 8888, //端口号
                FailMsg: [], //储存发送失败的信息
            },
            //声音对象
            audio: {
                context: new(window.AudioContext || window.webkitAudioContext)(),
                source: null,
                buffer: null
            },
            //信息列表
            message: [{
                    belong: 'self',
                    name: 'L',
                    time: '2019-09-16 11:11:15',
                    content: '欢迎来到TwelveT'
                },
                {
                    belong: 'other',
                    name: '纸飞机',
                    time: '2019-09-16 11:11:15',
                    content: 'vue欢迎您'
                },
            ],
            info: '',
        },
        mounted: function () {
            // 初始化，连接websocket
            if ("WebSocket" in window) {
                this.ConnectSocket();
            } else {
                notice.error('您的浏览器不支持WebSocket');
            }
            // //添加请求拦截器
            // axios.interceptors.request.use(function (config) {
            //     //开始请求，开始动画
            //     window.loading = layer.load();
            //     //返回操作信息
            //     return config
            // }, error => {
            //     //返回错误信息
            //     return Promise.reject(error)
            // })
            // //添加响应拦截器
            // axios.interceptors.response.use(function (response) {
            //     //更新验证码
            //     vue.$options.methods.captchaDo();
            //     //请求完成结束动画
            //     layer.close(window.loading);
            //     //返回操作信息
            //     return response
            // }, error => {
            //     //更新验证码
            //     vue.$options.methods.captchaDo();
            //     //关闭动画
            //     layer.close(window.loading);
            //     //返回错误信息
            //     return Promise.reject(error)
            // })
        },
        methods: {
            //连接通信服务器
            ConnectSocket() {
                // 实例化socket
                this.socket.IM = new WebSocket(`${this.socket.gateway}:${this.socket.url}:${this.socket.port}`)
                // 监听socket连接
                this.socket.IM.onopen = this.socketOpen;
                // 监听socket错误信息
                this.socket.IM.onerror = this.socketError;
                // 监听socket消息
                this.socket.IM.onmessage = this.socketMessage;
                // 监听socket是否被关闭
                this.socket.IM.onclose = this.socketClose;
            },
            //重连WebSocket
            retry_webSocket() {
                //判断是否达到最大重连数
                if (this.socket.CurrentRetryCount < this.socket.MaxRetryCount) {
                    //记录连接次数
                    this.socket.CurrentRetryCount++;
                    //调用连接fun
                    this.ConnectSocket();
                    notice.info(`正在进行${this.socket.CurrentRetryCount}次重连 WebSocket 中...`);
                } else {
                    //提示超出最大限制
                    notice.info('超出最大重连数，默认关闭连接');
                    //清除定时器
                    if (this.socket.Timer != null) {
                        clearInterval(this.socket.Timer);
                    }
                }
            },
            // 监听socket连接
            socketOpen() {
                //清除重连数
                this.socket.CurrentRetryCount = 0;
                //清除所有计时器
                if (this.socket.Timer != null) {
                    clearInterval(this.socket.Timer);
                }
                // 重新发送所有出错的消息
                if (this.socket.FailMsg.length > 0) {

                    for (let i in this.socket.FailMsg) {
                        this.socket.ws_send(this.socket.FailMsg[i]);

                    }
                }
                //定时发送心跳
                this.socket.Timer = setInterval(this.socketSend, 28000);
                notice.success('欢迎使用TW-IM');
            },
            // 监听socket消息
            socketMessage(msg) {
                // 声音
                this.loadAudioFile('/addons/TW-IM/audio/message_prompt.wav');
                // 转码json
                console.log(msg);
                //let data = JSON.parse(msg.data);
                this.message.push({
                    belong: 'other',
                    name: '纸飞机',
                    time: '2019-09-16 11:11:15',
                    content: msg.data
                });
            },
            //用于处理特殊信息
            socketSend(message) {
                //判断是否存在信息
                if (!message) {
                    //设置为心跳
                    message = {
                        c: 'Message',
                        f: 'ping'
                    };
                }
                //判读服务是否正常
                if (this.socket.IM && this.socket.IM.readyState == 1) {
                    this.socket.IM.send(JSON.stringify(message));
                } else {
                    notice.error('信息发送错误');
                    //存储信息，当通讯正常时将其重新发送
                    this.socket.FailMsg.push(message);
                }
            },
            // 监听socket错误信息
            socketError(e) {
                console.log('WebSocket:', e);
                notice.error("WebSocket无响应,即将重连")
            },
            //socket被关闭使用的处理
            socketClose() {
                //判断当前是否存在重连计时器
                if (this.socket.Timer != null) {
                    clearInterval(this.socket.Timer);
                }
                notice.error('WebSocket已无法通讯');
                //判断是否拥有重连权限
                if (this.socket.MaxRetryCount) {
                    //每3秒重新连接一次
                    this.socket.Timer = setInterval(this.retry_webSocket, 3000);
                }
            },
            //信息声音处理
            playSound() {
                this.audio.source = this.audio.context.createBufferSource();
                this.audio.source.buffer = this.audio.buffer;
                //禁止重播
                this.audio.source.loop = false;
                this.audio.source.connect(this.audio.context.destination);
                //立即播放
                this.audio.source.start(0);
            },
            //远程音频地址处理
            loadAudioFile(url) {
                //通过XHR下载音频文件
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.responseType = 'arraybuffer';
                xhr.onload = function (e) {
                    //下载完成
                    IMChat.audio.context.decodeAudioData(this.response,
                        function (buffer) {
                            //解码成功时的回调函数
                            IMChat.audio.buffer = buffer;
                            //执行播放
                            IMChat.playSound();
                        },
                        function (e) {
                            //解码出错时的回调函数
                            console.log('TW-IM音频解码失败', e);
                            notice.error('TW-IM音频解码失败');
                        });
                };
                xhr.send();
            },
            sendInfo() {
                let data = {
                    c: 'Message',
                    f: 'sendMessage',
                    data: this.info
                };
                //发送信息
                this.socket.IM.send(JSON.stringify(data));
                this.message.push({
                    belong: 'self',
                    name: 'L',
                    time: '2019-09-16 11:11:15',
                    content: this.info
                });
                //清空信息框
                this.info = '';
            },
        }
    })
})