/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 后台主框架
 */

$.twelvet = {
	// 窗口主要函数
	init: function () {
		//检测屏幕宽度
		if (parseInt($(document.body).outerWidth(true)) < 769) {
			$.twelvet.sideNav()
		}
		// pc开关导航
		$('#switch-nav').click($.twelvet.sideNav)
		// wap开关导航
		$('#wap-nav').find('.fa-times-circle').click($.twelvet.sideNav)
		// 全屏控制按钮
		$('a[data-toggle="fullscreen"]', '.justify-content-end').on('click', $.twelvet.fullScreen)
		// 侧边导航滚动条
		$('#slim-right').slimScroll({
			height: '100%',
			railOpacity: 1,
			opacity: 5,
			alwaysVisible: false,
			wheelStep: 10
		});
		// 全局下拉导航
		$('[data-toggle="dropdown"]').on('click', function () {
			$('[t-label=' + $(this).attr('id') + ']').toggle()
		})
		// 窗口左按钮
		$("#window-tab-left").click($.twelvet.scrollLeft);
		// 窗口右按钮
		$("#window-tab-right").click($.twelvet.scrollRight);
		// 侧边导航点击展开子分类
		$("#side-menu li>a").click($.twelvet.menuRetractable)
		// 额外窗口操作 
		$('.addtabs').on('click', function (e) {
			e.preventDefault();
			$.twelvet.addtabs($(this));
		})
		// 监听活动窗口
		$('#side-menu').on('click', '[addtabs]', function (e) {
			// 判断当前对象是否符合框架要求
			if ($(this).attr('data-url').indexOf("javascript:;") !== 0) {
				// 禁止浏览器默认事件
				if ($(this).is("a")) e.preventDefault();
				//判断是否手机端
				if (parseInt($(document.body).outerWidth(true)) < 768) {
					// 判断是否存在class
					if (!$('body').hasClass('menu-hide')) {
						$('body').addClass('menu-hide');
					} else {
						$('body').removeClass('menu-hide');
					}
				}
				// 执行窗口变化事件
				$.twelvet.addtabs($(this));
			}
		})
		// 拥有此功能执行事件绑定
		if (history.pushState) {
			//浏览器前进后退事件
			$(window).on("popstate", function (e) {
				// 获取属性
				let state = e.originalEvent.state;
				// 获取成功触发点击事件（直接触发菜单，兼容删除后自动恢复）
				if (state) {
					let dom = state.id ? "a[addtabs='" + state.id + "']" : "a[data-url='" + state.url + "']";
					$(dom).data("pushstate", true).trigger("click");
				}
			});
		}
		// 进入页面时检测配置参数是否存在窗口路径（自动打开窗口）
		if (config.referer) {
			// 定义搜索字符串
			let dom = "a[data-url='" + config.referer + "']";
			// 获取指定点击对象
			dom = $(dom);
			// 寻找是否存在二级目录中
			let treeviewMenu = dom.closest(".treeview-menu");
			if (treeviewMenu) {
				// 触发点击二级目录
				treeviewMenu.prev('a[data-url="javascript:;"]').trigger('click');
			}
			// 触发窗口打开
			dom.trigger('click');
		}
		// 监听活动窗口（关闭是事件按钮）
		$('#window-content').on('click', '.T-window-tab>i', $.twelvet.windowClose)
		// 监听活动窗口
		$('#window-content').on('click', '.T-window-tab', function (e) {
			// 禁止浏览器默认事件
			e.preventDefault();
			// 处理浏览器页面标题地址
			let data = $.twelvet.menuDate($(this));
			// 移除活动样式
			$('.window-active').removeClass('window-active');
			// 隐藏所有显示的iframe
			$('.iframe:visible').hide();
			// 改变活动窗口
			$(this).addClass('window-active');
			// 显示自身
			$(".iframe[src='" + data.url + "']").show();
		});
		//初始化active
		$('#window-load').click(function () {
			$('.iframe:visible')[0].contentWindow.location.reload(true);
			$(this).parent().hide()
		})
		// 关闭全部选项卡
		$('#window-closeAll').click(function () {
			//选中除了第一个元素其他关闭
			$('.iframe').not(':first').remove();
			$('.T-window-tab').not(":first").remove();
			//为第一个元素添加class
			let o = $(".T-window-tab:first").trigger('click');
			// 处理浏览器页面标题地址
			$.twelvet.menuDate(o);
			$('.iframe:first').show();
			$(this).parent().hide()
		});
		// 关闭其他选项卡
		$('#window-closeOter').click(function () {
			//关闭除了活动窗口和首页的窗口
			$('.T-window-tab').not(":first , .window-active").remove();
			$('.iframe').not(":first , :visible").remove();
			//恢复margin-left值
			$('#window-content').css('margin-left', '0');
			$(this).parent().hide()
		});
	},
	// 窗口全屏控制
	fullScreen(e) {
		// 返回 html dom 中的root 节点 <html>
		let element = document.documentElement;
		// 判断全屏状态
		let state = document.fullscreenElement || document.msFullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement || false;
		if (!state) {
			// 判断浏览器设备类型
			if (element.requestFullscreen) {
				element.requestFullscreen();
			} else if (element.mozRequestFullScreen) { // 兼容火狐
				element.mozRequestFullScreen();
			} else if (element.webkitRequestFullscreen) { // 兼容谷歌
				element.webkitRequestFullscreen();
			} else if (element.msRequestFullscreen) { // 兼容IE
				element.msRequestFullscreen();
			}
		} else {
			//	退出全屏
			if (document.exitFullscreen) {
				document.exitFullscreen();
			} else if (document.mozCancelFullScreen) {
				document.mozCancelFullScreen();
			} else if (document.webkitCancelFullScreen) {
				document.webkitCancelFullScreen();
			} else if (document.msExitFullscreen) {
				document.msExitFullscreen();
			}
		}
		e.preventDefault();
	},
	// 窗口左按钮
	scrollLeft: function () {
		//获取偏移度
		let marginLeftVal = Math.abs(parseInt($('#window-content').css('margin-left')));
		// 计算窗口标签窗口实际可用宽度
		let containerWidth = $("#window").outerWidth(true) - $.twelvet.sumWidth($("#window").children().not("#window-container"));
		//获取content的实际宽度
		let contentWidth = $("#window-content").width();
		//实际滚动宽度
		let scrollVal = 0;
		//判断是否大于可视化宽度：是否需要翻页
		if (contentWidth < containerWidth) {
			return false;
		} else {
			//保存选项卡第一个对象
			let tabElement = $(".T-window-tab:first");
			//保存超出宽度
			let offsetVal = 0;

			//遍历寻找超出的宽度
			while ((offsetVal + $(tabElement).outerWidth(true)) <= marginLeftVal) {
				//连续保存宽度
				offsetVal += $(tabElement).outerWidth(true);
				//保存下一个对象
				tabElement = $(tabElement).next();
			}
			//清0重新计算
			offsetVal = 0;
			if ($.twelvet.sumWidth($(tabElement).prevAll()) > containerWidth) {
				//遍历寻找超出的宽度:宽度小于
				while ((offsetVal + $(tabElement).outerWidth(true)) < containerWidth && tabElement.length > 0) {
					//连续保存宽度
					offsetVal += $(tabElement).outerWidth(true);
					//保存下一个对象
					tabElement = $(tabElement).prev();
				}
				//计算需要偏移的度数:元素前的所有元素
				let scrollVal = $.twelvet.sumWidth($(tabElement).prevAll());
			}
			$('#window-content').animate({
				marginLeft: 0 - scrollVal + 'px'
			}, 'last');
		}
	},
	// 窗口右按钮
	scrollRight: function () {
		// 获取偏移度
		let marginLeftVal = Math.abs(parseInt($('#window-content').css('margin-left')));
		// 计算窗口标签窗口实际可用宽度
		let containerWidth = $("#window").outerWidth(true) - $.twelvet.sumWidth($("#window").children().not("#window-container"));
		// 获取content的实际宽度
		let contentWidth = $("#window-content").outerWidth(true);
		// 实际滚动宽度
		let scrollVal = 0;
		// 判断是否大于可视化宽度：是否需要翻页
		if (contentWidth < containerWidth) {
			return false;
		} else {
			// 保存选项卡第一个对象
			let tabElement = $(".T-window-tab:first");
			// 保存超出宽度
			let offsetVal = 0;

			// 遍历寻找即将超出的宽度，以它为第一个开始计算
			while ((offsetVal + $(tabElement).outerWidth(true)) <= marginLeftVal) {
				// 连续保存宽度
				offsetVal += $(tabElement).outerWidth(true);
				// 保存下一个对象
				tabElement = $(tabElement).next();
			}
			// 清0重新使用
			offsetVal = 0;
			// 遍历寻找超出的宽度:宽度小于可视宽度
			while ((offsetVal + $(tabElement).outerWidth(true)) < containerWidth && tabElement.length > 0) {
				// 连续保存宽度
				offsetVal += $(tabElement).outerWidth(true);
				// 保存下一个对象
				tabElement = $(tabElement).next();
			}
			// 计算需要偏移的度数:元素前的所有元素
			scrollVal = $.twelvet.sumWidth($(tabElement).prevAll());
			if (scrollVal > 0) {
				$('#window-content').animate({
					marginLeft: 0 - scrollVal + 'px'
				}, 'last');
			}
		}
	},
	// 自动滚动到窗口标签可见状态
	autoScroll: function (o) {
		// 获取当前窗口标签的前\后所有标签的长度
		let marginLeftVal = $.twelvet.sumWidth($(o).prevAll()),
			marginRightVal = $.twelvet.sumWidth($(o).nextAll());
		// 计算窗口标签窗口实际可用宽度
		let containerWidth = $("#window").outerWidth(true) - $.twelvet.sumWidth($("#window").children().not("#window-container"));
		// 预定义实际宽度
		let scrollVal = 0;
		// 当前窗口右边所有标签的宽度 <= (窗口标签实际可用宽度 - 当前窗口标签宽度 - 当前窗口下一个标签的宽度)
		if (marginRightVal <= (containerWidth - $(o).outerWidth(true) - $(o).next().outerWidth(true))) {
			// 当前窗口右边所有标签的宽度 < (窗口标签实际可用宽度 - )当前窗口下一个标签的宽度
			if (marginRightVal < (containerWidth - $(o).next().outerWidth(true))) {
				// 将当前元素的左边所有标签宽度总和赋值
				scrollVal = marginLeftVal;
				// 定义当前窗口标签
				let tabElement = o;
				// (定义的平滑度 - 当前窗口标签) > (当前所有窗口标签宽度 - 实际可用宽度)
				let tempWidth = $("#window-content").outerWidth() - containerWidth;
				while ((scrollVal - $(tabElement).outerWidth()) > tempWidth) {
					// 当前所需平滑度减去下一个标签的宽度
					scrollVal -= $(tabElement).prev().outerWidth();
					// 将当前标签保存至下一个标签
					tabElement = $(tabElement).prev();
				}
			}
		}
		// 当前窗口标签的所有左边标签宽度 > (窗口标签实际可用宽度 - 当前窗口标签宽度 - 当前窗口上一个标签的宽度) 
		else if (marginLeftVal > (containerWidth - $(o).outerWidth(true) - $(o).prev().outerWidth(true))) {
			// 获取左元素宽度 - 当窗口上一个标签宽度
			scrollVal = marginLeftVal - $(o).prev().outerWidth(true);
		}
		$('#window-content').animate({
			marginLeft: 0 - scrollVal + 'px'
		}, "fast");
	},
	// 窗口关闭操作
	windowClose: function (e) {
		// 禁止浏览器默认事件
		e.preventDefault();
		let o = $(this).parent();
		//判断是否需要添加样式
		if (o.hasClass("window-active")) {
			//判断往前还是往后添加样式并改变操作对象:具有代表一
			if (o.next().length === 1) {
				//添加样式
				var active = o.next().addClass('window-active');
				//显示活动窗口
				$(".iframe[src='" + active.attr('data-url') + "']").show();
			} else {
				//添加样式
				var active = o.prev().addClass('window-active');
				//显示活动窗口
				$(".iframe[src='" + active.attr('data-url') + "']").show();
			}
			//从活动窗口中移除
			$(".iframe[src='" + o.attr('data-url') + "']").remove();
			//移除活动窗口
			$(".iframe[src='" + active.attr('data-url') + "']").not(':first').remove();
			o.remove();
			// 处理浏览器页面标题地址
			$.twelvet.menuDate(active);
		} else {
			//从活动窗口中移除
			$(".iframe[src='" + o.attr('data-url') + "']").remove();
			o.remove();
		}
		// 自动滚动标签窗口
		$.twelvet.autoScroll(active);
		// 禁止冒泡
		e.stopPropagation();
	},
	// 菜单平滑操作
	sideNav: function () {
		// 判断是否存在class
		if (!$('body').hasClass('menu-hide')) {
			$('body').addClass('menu-hide');
		} else {
			$('body').removeClass('menu-hide');
		}
	},
	// 二级菜单打开操作
	menuRetractable: function (e) {
		// 获取当前点击元素以及下一个元素
		let $this = $(this);
		let checkElement = $this.next();
		// 得到li
		let parent_li = $this.parent("li");
		// 检查是否是二级菜单以及是否可见
		if ((checkElement.is('.treeview-menu')) && (checkElement.is(':visible'))) {
			// 关闭此二级菜单
			checkElement.slideUp(300);
			//移除样式
			parent_li.removeClass('active');
		} else if ((checkElement.is('.treeview-menu')) && (checkElement.is(':hidden'))) {
			// 得到主要菜单主ul
			let parent = $this.parents('ul').first();
			// 关闭所有打开的菜单
			let ul = parent.find('ul:visible').slideUp(300);
			ul.parent('li').removeClass('active');
			//为自身添加样式
			parent_li.addClass('active');
			// 打开this菜单
			checkElement.slideDown(300);
		}

		// 如果this是二级菜单立即禁止跳转
		if (checkElement.is('.treeview-menu')) {
			e.preventDefault();
		}
	},
	// 计算元素集合的总宽度
	sumWidth: (es) => {
		let width = 0;
		$(es).each(function () {
			width += $(this).outerWidth(true);
		});
		return width;
	},
	// 处理浏览器页面标题地址
	menuDate: function (o) {
		let id = o.attr('addtabs') ? o.attr('addtabs') : null;
		let title = $.trim(o.text());
		let url = o.attr('data-url');
		let state = ({
			id: id,
			url: url,
			title: title
		});
		// 动态设置框架页面标题
		document.title = title;
		// 动态设置显示地址（不进行跳转）
		if (history.pushState && !o.data("pushstate")) {
			// 处理url地址
			let pushurl = url.indexOf("ref=addtabs") === -1 ? (url + (url.indexOf("?") > -1 ? "&" : "?") + "ref=addtabs") : url;
			try {
				window.history.pushState(state, title, pushurl);
			} catch (e) {
				console.log(e);
			}
		}
		// 设置完成后清空地址历史
		o.data("pushstate", null);
		return state
	},
	// 窗口活动操作
	addtabs: function (o) {
		// 处理浏览器页面标题地址
		let data = $.twelvet.menuDate(o);

		// 隐藏所有其他iframe
		$('.iframe').not("[src='" + data.url + "']").hide();
		// 尝试寻找窗口
		let windowTab = $('#window-content').find('.T-window-tab[data-url="' + data.url + '"]');
		// 无法寻找到窗口立即创建
		if (windowTab.length == 0) {
			//创建新元素
			var record = $("<a class='T-window-tab' addtabs='" + data.id + "' data-url='" + data.url + "'>" + data.title + "<i class='fa fa-times-circle'></i></a>");
			let iframe = $('<iframe class="iframe" src="' + data.url + '"  width="100%" height="100%" frameborder="0"></iframe>');
			//添加活动窗口
			$('#window-content').append(record);
			$('#content').append(iframe);
			//切换导航
			$('.window-active').removeClass('window-active');
			//添加活动标识
			record.addClass('window-active');
		} else {
			// 切换导航
			$('.window-active').removeClass('window-active');
			// 添加活动标识
			var record = windowTab.addClass('window-active');
			$(".iframe[src='" + data.url + "']").css('display', 'inline-block');
		}
		// 自动滚动窗口
		$.twelvet.autoScroll(record);
	}
}

$(function () {
	// 执行初始化
	$.twelvet.init();
})