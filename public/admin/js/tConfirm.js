/*BO平台的的提示插件　tom added on Mar 31, 2016*/
(function (window, document, $, undefined) {
    "use strict";
    String.prototype.format = function (args) {
        if (arguments.length > 0) {
            var result = this;
            if (arguments.length == 1 && typeof (args) == "object") {
                for (var key in args) {
                    var reg = new RegExp("({" + key + "})", "g");
                    result = result.replace(reg, args[key]);
                }
            }
            else {
                for (var i = 0; i < arguments.length; i++) {
                    if (arguments[i] == undefined) {
                        return "";
                    }
                    else {
                        var reg = new RegExp("({[" + i + "]})", "g");
                        result = result.replace(reg, arguments[i]);
                    }
                }
            }
            return result;
        }
        else {
            return this;
        }
    };
    var H = $("html"),
        W = $(window),
        D = $(document),
        C = $.tConfirm = function () {
            C.open.apply(arguments);
        }, btnEnum = { //按钮类型
            ok: parseInt("0001", 2), //确定按钮
            cancel: parseInt("0010", 2), //取消按钮
            okcancel: parseInt("0011", 2) //确定&&取消
        }, //触发事件类型
        eventEnum = {
            ok: 1,
            cancel: 2,
            close: 3
        }, popType = {
            info: {
                title: "Info",
                icon: "0 0",//蓝色i
                btn: eventEnum.ok
            },
            success: {
                title: "Success",
                icon: "0 -48px",//绿色对勾
                btn: eventEnum.ok
            },
            error: {
                title: "Error",
                icon: "-48px -48px",//红色叉
                btn: eventEnum.ok
            },
            confirm: {
                title: "Prompt",
                icon: "-48px 0",//黄色问号
                btn: eventEnum.okcancel
            },
            warning: {
                title: "Warning",
                icon: "0 -96px",//黄色叹号
                btn: eventEnum.okcancel
            },
            input: {
                title: "Input",
                icon: "",
                btn: eventEnum.ok
            },
            custom: {
                title: "",
                icon: "",
                btn: eventEnum.ok
            }
        }, createPopId = function () {  //重生popId,防止id重复
            var i = "pop_" + (new Date()).getTime() + parseInt(Math.random() * 100000);//弹窗索引
            if ($("#" + i).length > 0) {
                return createPopId();
            } else {
                return i;
            }
        }
    $.extend(C, {
        defaults: {//属性
            title: "", //自定义的标题
            icon: "", //图标
            btn: btnEnum.ok, //按钮,默认单按钮
            //事件
            onOk: $.noop,//点击确定的按钮回调
            onCancel: $.noop,//点击取消的按钮回调
            onClose: $.noop,//弹窗关闭的回调,返回触发事件
            type: 'info' ,
            overlay:false
        },
        open: function (opts) {
            if (!opts) {
                return;
            }
            if (typeof opts == "string") {
                var c=opts;
                opts = {};
                opts.body= c;
            }
            if (!$.isPlainObject(opts)) {
                opts = {};
            }
            console.debug(typeof opts,opts,$.isPlainObject(opts)) ;
            C.opts = $.extend(true, {}, C.defaults, opts);
            var popId = createPopId();//弹窗索引
            var type=opts.type?opts.type:'info';
            var icon=popType[type].icon;
            var title=opts.title?opts.title:'';
           var overLayCss= !opts.overlay?null:{
                css : {
                    'background' : 'rgba(0, 0, 0, 0.7)'
                }
            };
            var body=opts.body;
            var $ok = $("<a>").addClass("sgBtn").addClass("ok").text("OK");//确定按钮
            var $cancel = $("<a>").addClass("sgBtn").addClass("cancel").text("Cancel");//取消按钮
            var btns = {ok: $ok};
            if(type=='confirm'){
                btns = {ok: $ok, cancel: $cancel};
            }
            if(opts.onOk){
                $ok.click(opts.onOk);
            } if(opts.onCancel){
                $cancel.click(opts.onCancel);
            }
            title= ('<div class="ttBox"><a class="clsBtn" href="javascript:parent.$.fancybox.close();"></a><span class="tt">{0}</span></div>').format(title);
            var msg=('<div class="popBox" id="{0}">' +
            '{1}' +
            '<div class="txtBox"><div class="bigIcon" style="background-position: {2};"></div><p>{3}</p></div><div class="btnArea"></div>' +
            '</div>').format(popId,title,icon,body);
            $.fancybox.open({
                closeBtn: false,
                //'type':'iframe',
                type: 'inline',
                padding: 0,
                content:msg,
                afterLoad: function () {},
                helpers: {
                    overlay : overLayCss
                }, afterShow: function () {
                    $.each(btns, function(i, n){
                      $("#"+popId).find(".btnArea").append(n);
                    });
                    $("#"+popId).find(".sgBtn").attr("href","javascript:parent.$.fancybox.close();");
                }
            });

        }
    })
    $.fn.tConfirm = function (options) {
         if (C.open(options) !== false) {
            e.preventDefault();
        }
        return this;
    };
}(window, document, jQuery));