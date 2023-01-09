function on_user_item(id, master_url) {
    var user_role_menu = $("#user_role_menu").val();
    var user_role_menus = user_role_menu.split(",");
    var index = $.inArray("/admin/memberItem", user_role_menus);   //结果：index=1
    if (index <= 0) {
        return false;
    }
    var token = $("#token").val();
    if (!id) {
        toastr.warning('个人信息错误');
        return false;
    }
    $("#op_id").val(id);
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/memberItem",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {id: id, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            $('.id').text(rs.data.id);
            $('.pretty_id').text(rs.data.pretty_id);
            $('.nickname').text(rs.data.nickname);
            $('.username').text(rs.data.username);
            $('.login_time').text(rs.data.login_time);
            $('.leavetime').text(rs.data.leavetime);
            $('.register_time').text(rs.data.register_time);
            $('.login_ip').text(rs.data.login_ip);
            $('.guild').text(rs.data.guild);
            $('.coin').text(rs.data.coin);
            $('.gold_coin').text(rs.data.gold_coin);
            $('.diamond').text(rs.data.diamond);
            $(".avatar").attr("src", rs.data.avatar);
            $('.intro').text(rs.data.intro);
            $('.sex').text(rs.data.sex);
            $('.pretty_avatar').text(rs.data.pretty_avatar);
            $('.deviceid').text(rs.data.deviceid);
            $('.invitcode').text(rs.data.invitcode);
            if (rs.data.black == 1) {
                $(".unsealings").css('display', 'block');
                $(".blacks").css('display', 'none');
            } else {
                $(".blacks").css('display', 'block');
                $(".unsealings").css('display', 'none');
            }
            if (rs.data.attestation == 1) {
                $(".clear-attention").css('display', 'block');
            } else {
                $(".clear-attention").css('display', 'none');
            }    
            if (rs.data.threeBlack == 1) {
                $(".undothree1").css('display', 'block');
                $(".threeblacks").css('display', 'none');
            } else {
                $(".undothree1").css('display', 'none');
                $(".threeblacks").css('display', 'block');
            }
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
    var a = $.inArray("/admin/editMemberPretty", user_role_menus);   //结果：index=1
    if (a <= 0) {
        $('.exit-pretty').remove();
    }
    var b = $.inArray("/admin/editMemberNickname", user_role_menus);   //结果：index=1
    if (b <= 0) {
        $('.exit-nickname').remove();
    }
    var c = $.inArray("/admin/editMemberUsername", user_role_menus);   //结果：index=1
    if (c <= 0) {
        $('.exit-phone').remove();
    }
    var c = $.inArray("/admin/editMemberIntro", user_role_menus);   //结果：index=1
    if (c <= 0) {
        $('.exit-intro').remove();
    }
    //头像框地址及权限
    var c = $.inArray("/admin/editPrettyAvatar", user_role_menus);   //结果：index=1
    if (c <= 0) {
        $('.exit-pretty_avatar').remove();
    }
    var d = $.inArray("/admin/getForumListByWhere", user_role_menus);   //结果：index=1
    if (d <= 0) {
        $('.historydongtai-record').remove();
    }
    var e = $.inArray("/admin/getCoinDetailReceivingList", user_role_menus);   //结果：index=1
    if (e <= 0) {
        $('.receiving-giftdongtai').remove();
    }
    var f = $.inArray("/admin/getCoinDetailGivingList", user_role_menus);   //结果：index=1
    if (f <= 0) {
        $('.giving-gift').remove();
    }

    var g = $.inArray("/admin/getMemberMoneyDouList", user_role_menus);   //结果：index=1
    if (g <= 0) {
        $('.user-pay').remove();
    }
    
    var h = $.inArray("/admin/getChargeDetailList", user_role_menus);   //结果：index=1
    if (h <= 0) {
        $('.user-pay-list').remove();
    }

    var g = $.inArray("/admin/getMemberScoreList", user_role_menus);   //结果：index=1
    if (g <= 0) {
        $('.user-score').remove();
    }

    var h = $.inArray("/admin/getMemberScoreList", user_role_menus);   //结果：index=1
    if (h <= 0) {
        $('.user-score-list').remove();
    }

    var i = $.inArray("/admin/getMemberMoneyDiamondOneList", user_role_menus);   //结果：index=1
    if (i <= 0) {
        $('.user-diamond-add-list').remove();
    }

    var j = $.inArray("/admin/getMemberMoneyDiamondTwoList", user_role_menus);   //结果：index=1
    if (j <= 0) {
        $('.user-diamond-lessent-list').remove();
    }
    //用户登录记录
    var f = $.inArray("/admin/getLoginList", user_role_menus);   //结果：index=1
    if (f <= 0) {
        $('.entry-records').remove();
    }
    //用户登录记录
    var f = $.inArray("/admin/getForbidList", user_role_menus);   //结果：index=1
    if (f <= 0) {
        $('.forbid-records').remove();
    }
    //清除用户信息
    var f = $.inArray("/admin/editMemberAttention", user_role_menus);   //结果：index=1
    if (f <= 0) {
        $('.clear-attention').remove();
    }


    $("#user_item").modal('show');
}


$(".blacks").click(function () {
    $('#operations').modal('show');
})
$(".threeblacks").click(function () {
    $('#operations1').modal('show');
})

$('.del_black').change(function(){
    var time = $(this).val();
    var tip = $(this).find("option:selected").text();
    var desc = $.trim($("#desc").val());
    if (desc == '') {
        toastr.warning('备注不可为空');
        $(this)[0][0].selected = true;
        return false;
    }
    $('#op_time').val(time);
    $('#desc_msg').val(time);
    $('#is_ok').modal('show');
    //$("#is_ok_msg").html(' 您确定要封禁此用户：' + time + ' 天? ')
    $("#is_ok_msg").html(' 您确定要封禁此用户：' + tip );
})

$('.del_black1').click(function () {
    var time = $(this).val();
    var tip = $(this).text();
    console.log(time)
    console.log(tip)
    return ;
    var desc = $.trim($("#desc").val());
    if (desc == '') {
        toastr.warning('备注不可为空');
        return false;
    }
    $('#op_time').val(time);
    $('#desc_msg').val(time);
    $('#is_ok').modal('show');
    //$("#is_ok_msg").html(' 您确定要封禁此用户：' + time + ' 天? ')
    $("#is_ok_msg").html(' 您确定要封禁此用户：' + tip );
})

function is_ok_black() {
    if($('#op_time').val() == '永久'){
        var time = -1;
    }else{
        var time = $('#op_time').val();
    }
    var desc = $('#desc').val();
    var id = $('#op_id').val();
    var token = $('#token').val();
    var master_url = '/admin/memberBlacks';
    var arr = [];
    $("input[name = 'feng']:checked").each(function(i){
        arr.push($(this).val());
    });
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/memberBlacks",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {id: id, master_url: master_url, token: token, time: time, desc: desc, arr: arr},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            setTimeout(location(), 1000);   //延迟5秒刷新页面

        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

    function location() {
        window.location.href = window.location.href;
    }

    return false;
}

function is_ok_black3() {
    var id = $('#op_id').val();
    var reason = $('#reason').val();
    var token = $('#token').val();
    var master_url = '/admin/threeBlack';
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/threeBlack",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {id: id, reason: reason, master_url: master_url, token: token},
        success: function (rs) {
            console.log(rs)
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            setTimeout(location(), 1000);   //延迟5秒刷新页面

        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

    function location() {
        window.location.href = window.location.href;
    }

    return false;
}




$('.unsealings').click(function () {
    var nickname = $(".nickname").text();
    $('#del_unsealings').modal('show')
    $("#unsealings_msg").html(' 您确定要解除封号昵称为：' + nickname + ' ? ');
})
$('.undothree1').click(function () {
    var nickname = $(".nickname").text();
    $('#undothree').modal('show')
    $("#undothree_msg").html(' 您确定要解除封号昵称为：' + nickname + ' ? ');
})


function del_unsealings() {
    var id = $("#op_id").val();
    var desc = $.trim($("#descs").val());
    if (desc == '') {
        toastr.warning('备注不可为空');
        return false;
    }
    var master_url = '/admin/memberUnsealings'
    var token = $('#token').val()
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/memberUnsealings",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {blackinfo: id, master_url: master_url, token: token, desc: desc,type: 4},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            setTimeout(location(), 1000);   //延迟5秒刷新页面

        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

    function location() {
        window.location.href = window.location.href;
    }

    return false;
}

/**解三封*/
function undothree() {
    var id = $("#op_id").val();
    var master_url = '/admin/unDoThreeBlack'
    var token = $('#token').val()
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/unDoThreeBlack",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {id: id, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            setTimeout(location(), 1000);   //延迟5秒刷新页面

        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

    function location() {
        window.location.href = window.location.href;
    }

    return false;
}



$('.exit-pretty').click(function () {
    var pretty_id = $('.pretty_id').text();
    $("#member_val").val(pretty_id);
    $("#old_val").val(pretty_id);
    $("#type_val").val(1);
    $(".edit_member_msg").text('请输入新靓号ID: ');
    $("#edit_member").modal('show');
})

function edit_member_attention() {
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: '/admin/editMemberAttention',//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {master_url: '/admin/editMemberAttention', token: $('#token').val(), id: $("#op_id").val()},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            $("#edit_member_attention").modal('hide');
            setTimeout(location, 1000);   //延迟1秒刷新页面
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
}
function edit_member_ok() {
    var old_val = $("#old_val").val();
    var member_val = $("#member_val").val();
    if (old_val == member_val) {
        toastr.warning('填写的内容与存在的内容一致,请征求修改修改');
        return false;
    }
    var id = $("#op_id").val();
    var type_val = $("#type_val").val();
    if (type_val == 1) {
        var go_url = "/admin/editMemberPretty";
        var master_url = "/admin/editMemberPretty";

    } else if (type_val == 2) {
        var go_url = "/admin/editMemberNickname";
        var master_url = "/admin/editMemberNickname";

    } else if (type_val == 3) {
        var go_url = "/admin/editMemberUsername";
        var master_url = "/admin/editMemberUsername";
    }else if (type_val == 4) {
        var go_url = "/admin/editMemberIntro";
        var master_url = "/admin/editMemberIntro";
    }else if (type_val == 5) {
        var go_url = "/admin/avatarossFile";
        var master_url = "/admin/avatarossFile";
    }else if(type_val == 6){
        var go_url = "/admin/editPrettyAvatar";
        var master_url = "/admin/editPrettyAvatar";
    }else {
        toastr.warning('操作失败');
    }
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: go_url,//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {id: id, master_url: master_url, token: $('#token').val(), member_val: member_val, id: id},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            $("#edit_member").modal('hide');
            if (type_val == 1) {
                $(".pretty_id").text(member_val);
            } else if (type_val == 2) {
                $(".nickname").text(member_val);
            } else if (type_val == 3) {
                $(".username").text(member_val);
            }else if (type_val == 4) {
                $(".intro").text(member_val);
            }else if (type_val == 5) {
                $(".avatar").text(member_val);
            }else if(type_val == 6){
                $(".pretty_avatar").text(member_val);
            }
            setTimeout(location, 1000);   //延迟1秒刷新页面
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
    function location() {
        window.location.href = window.location.href;
    }
    return false;

}

$('.exit-nickname').click(function () {
    var nickname = $('.nickname').text();
    $("#member_val").val(nickname);
    $("#old_val").val(nickname);
    $("#type_val").val(2);
    $(".edit_member_msg").text('请输入新的昵称: ');
    $("#edit_member").modal('show');
})

$('.exit-phone').click(function () {
    var username = $('.username').text();
    $("#member_val").val(username);
    $("#old_val").val(username);
    $("#type_val").val(3);
    $(".edit_member_msg").text('请输入新的手机号: ');
    $("#edit_member").modal('show');
})

//修改简介弹出层
$('.exit-intro').click(function () {
    var intro = $('.intro').text();
    $("#member_val").val(intro);
    $("#old_val").val(intro);
    $("#type_val").val(4);
    $(".edit_member_msg").text('请输入新的简介: ');
    $("#edit_member").modal('show');
})

//修改头像
$('.exit-avatar').click(function () {
    var avatar = $('.avatar').text();
    $("#member_val").val(avatar);
    $("#old_val").val(avatar);
    $("#type_val").val(5);
    //$(".edit_member_msg").text('请输入新的简介: ');
    $("#edit_member").modal('show');
})

//修改头像框弹出层
$('.exit-pretty-avatar').click(function () {
    var pretty_avatar = $('.pretty_avatar').text();
    $("#member_val").val(pretty_avatar);
    $("#old_val").val(pretty_avatar);
    $("#type_val").val(6);
    $(".edit_member_msg").text('请输入新地址: ');
    $("#edit_member").modal('show');
})

//清空用户实名信息
$('.clear-attention').click(function () {
    $("#type_val").val(7);
    $("#edit_member_attention").modal('show');
})

$('.historydongtai-record').click(function () {
    var forum_uid = $('#op_id').val();
    var master_url = '/admin/getForumListByWhere'
    var token = $('#token').val();

    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: '/admin/getForumListByWhere',//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {forum_uid: forum_uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $(rs.data).each(function (i, n) {
                    $("#historical_dynamics_tbody").prepend("<tr>" +
                        "<td class='text-center'>" + n.id + "</td>" +
                        "<td class='text-center'>" + n.nickname + "</td>" +
                        "<td class='text-center'>" + n.forum_uid + "</td>" +
                        "<td class='text-center' id='forum_content' title = " + n.forum_content + ">" + n.forum_content + "</td>" +
                        "<td class='text-center' id='forum_image'></td>" +
                        "<td class='text-center' id='forum_voice'></td>" +
                        "</tr>");
                    if (n.forum_image.length > 0) {
                        $(n.forum_image).each(function (is, ns) {
                            $("#forum_image").prepend(
                                "<img src = " + ns + " alt='缩略图' class='thumbnail col-sm-4 col-md-4'>"
                            );
                        })
                    }
                    if (n.forum_voice != '') {
                        $("#forum_voice").prepend(
                            "<audio src=" + n.forum_voice + " controls='controls' style='width:100px;height: 20px;'></audio>"
                        );
                    }
                });
            } else {
                $("#historical_dynamics_tbody").prepend("<tr class='no-records-found'><td colspan='7' class='text-center'>没有找到匹配的记录</td></tr>");
            }
            $('#historical_dynamics').modal('show')
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

    $(function () {

        //鼠标位于 a标签上方时发生 mouseover 事件
        $("#forum_content").mouseover(function (e) {
            this.Mytitle = this.title;//获取超链接 title属性的内容
            this.title = ""; //设置 title属性内容为空
            $("body").append("<div id='div_toop'>" + this.Mytitle + "</div>");//将要显示的内容添加到 新建 div标签中 并追加到 body 中
            $("#div_toop")
                .css({
                    //设置 div 内容位置
                    "top": (e.pageY + 10) + "px",
                    "position": "absolute",//添加绝对位置
                    "left": (e.pageX + 20) + "px"
                }).show("fast");// show(spe.ed,callback) speed: xian'shi'su'du
        }).mouseout(function () { //鼠标指针从 a标签 上离开时 发生mouseout 事件
            this.title = this.Mytitle;
            $("#div_toop").remove();//移除对象
        }).mousemove(function (e) { //鼠标指针在 a标签 中移动时 发生mouseout 事件
            $("#div_toop")
                .css({
                    //设置 div 内容位置
                    "top": (e.pageY + 10) + "px",
                    "position": "absolute",//添加绝对位置
                    "left": (e.pageX + 20) + "px"
                });
        });
    });


})

$('.receiving-giftdongtaiss').click(function () {
    var touid = $('#op_id').val();
    var master_url = '/admin/getCoinDetailReceivingList'
    var token = $('#token').val();

    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: '/admin/getCoinDetailReceivingList',//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {touid: touid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }

            if (rs.data.list.length > 0) {
                $(rs.data.list).each(function (i, n) {
                    $("#coin_detail_receiving_tbody").prepend("<tr>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.id + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.room_id + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.content + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.uid + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.giftid + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.giftcount + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.gift_name + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.addtime + "</td>" +
                        "</tr>");
                });
            } else {
                $("#coin_detail_receiving_tbody").prepend("<tr class='no-records-found'><td colspan='8' class='text-center'>没有找到匹配的记录</td></tr>");
            }

            $('#coin_detail_receiving').modal('show')
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
//1001033
})

$('.giving-giftss').click(function () {
    var uid = $('#op_id').val();
    var master_url = '/admin/getCoinDetailGivingList'
    var token = $('#token').val();

    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: '/admin/getCoinDetailGivingList',//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.list.length > 0) {
                $(rs.data.list).each(function (i, n) {
                    $("#coin_detail_receiving_tbody").prepend("<tr>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.id + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.room_id + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.content + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.touid + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.giftid + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.giftcount + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.gift_name + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.addtime + "</td>" +
                        "</tr>");
                });
            } else {
                $("#coin_detail_receiving_tbody").prepend("<tr class='no-records-found'><td colspan='8' class='text-center'>没有找到匹配的记录</td></tr>");
            }

            $('#coin_detail_receiving').modal('show')
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
//1001033
})

//用户登录记录
$('.entry-records').click(function () {
    var uid = $('#op_id').val();
    var master_url = '/admin/getLoginList'
    var token = $('#token').val();

    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: '/admin/getLoginList',//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.list.length > 0) {
                $(rs.data.list).each(function (i, n) {
                    $("#login_detail_receiving_tbody").prepend("<tr>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.id + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.login_ip + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.imei + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.device_id + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.channel + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.ctime + "</td>" +
                        "</tr>");
                });
            } else {
                $("#login_detail_receiving_tbody").prepend("<tr class='no-records-found'><td colspan='5' class='text-center'>没有找到匹配的记录</td></tr>");
            }

            $('#login_detail_receiving').modal('show')
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
})

//用户封禁记录
$('.forbid-records').click(function () {
    var uid = $('#op_id').val();
    var master_url = '/admin/getForbidList'
    var token = $('#token').val();
	$("#forbid_detail_tbody").empty()
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: '/admin/getForbidList',//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.list.length > 0) {
                $(rs.data.list).each(function (i, n) {
                    $("#forbid_detail_tbody").prepend("<tr>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.user_id + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.forbid_type + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.time + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.reason + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.create_time + "</td>" +
                        "<td class='text-center' style='table-layout: auto;'>" + n.admin_name + "</td>" +
                        "</tr>");
                });
            } else {
                $("#forbid_detail_tbody").prepend("<tr class='no-records-found'><td colspan='5' class='text-center'>没有找到匹配的记录</td></tr>");
            }

            $('#forbid_detail').modal('show')
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
})

$('.user-pay').click(function () {
    var uid = $('#op_id').val();
    var master_url = '/admin/getMemberMoneyDouList'
    var token = $('#token').val();
    var user_role_menu = $("#user_role_menu").val();
    var user_role_menus = user_role_menu.split(",");
    var index = $.inArray("/admin/addMemberMoneyDou", user_role_menus);   //结果：index=1
    if (index <= 0) {
        $('.user_pay_div').remove();
    }
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/getMemberMoneyDouList",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $(rs.data).each(function (i, n) {
                    $("#use_pay_tbody").prepend("<tr>" +
                        "<td class='text-center'>" + n.id + "</td>" +
                        "<td class='text-center'>" + n.money + "</td>" +
                        "<td class='text-center'>" + n.desc + "</td>" +
                        "<td class='text-center'>" + n.created_time + "</td>" +
                        "<td class='text-center'>" + n.status + "</td>" +
                        "<td class='text-center'>" + n.created_user + "</td>" +

                        "</tr>");
                });
            } else {
                $("#use_pay_tbody").prepend("<tr class='no-records-found user-pay-no'><td colspan='5' class='text-center'>没有找到匹配的记录</td></tr>");
            }

        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
    $('#use_pay').modal('show');
})

$('.user-score').click(function () {
    var uid = $('#op_id').val();
    var master_url = '/admin/getMemberScoreList'
    var token = $('#token').val();
    var user_role_menu = $("#user_role_menu").val();
    var user_role_menus = user_role_menu.split(",");
    var index = $.inArray("/admin/addMemberScore", user_role_menus);   //结果：index=1
    if (index <= 0) {
        $('.user_score_div').remove();
    }
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/getMemberScoreList",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {

            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $(rs.data).each(function (i, n) {
                    $("#user_score_tbody").prepend("<tr>" +
                        "<td class='text-center'>" + n.id + "</td>" +
                        "<td class='text-center'>" + n.money + "</td>" +
                        "<td class='text-center'>" + n.desc + "</td>" +
                        "<td class='text-center'>" + n.created_time + "</td>" +
                        "<td class='text-center'>" + n.status + "</td>" +
                        "<td class='text-center'>" + n.created_user + "</td>" +

                        "</tr>");
                });
            } else {
                $("#user_score_tbody").prepend("<tr class='no-records-found user-score-no'><td colspan='5' class='text-center'>没有找到匹配的记录</td></tr>");
            }

        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
    $('#user_score').modal('show');
})

$('.user-gold').click(function () {
    var uid = $('#op_id').val();
    var master_url = '/admin/getMemberGoldList'
    var token = $('#token').val();
    var user_role_menu = $("#user_role_menu").val();
    var user_role_menus = user_role_menu.split(",");
    var index = $.inArray("/admin/getMemberGoldList", user_role_menus);   //结果：index=1
    if (index <= 0) {
        $('.use_gold_div').remove();
    }
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/getMemberGoldList",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {

            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $(rs.data).each(function (i, n) {
                    $("#use_gold_tbody").prepend("<tr>" +
                        "<td class='text-center'>" + n.id + "</td>" +
                        "<td class='text-center'>" + n.money + "</td>" +
                        "<td class='text-center'>" + n.desc + "</td>" +
                        "<td class='text-center'>" + n.created_time + "</td>" +
                        "<td class='text-center'>" + n.status + "</td>" +
                        "<td class='text-center'>" + n.created_user + "</td>" +

                        "</tr>");
                });
            } else {
                $("#use_gold_tbody").prepend("<tr class='no-records-found user-gold-no'><td colspan='5' class='text-center'>没有找到匹配的记录</td></tr>");
            }

        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
    $('#use_gold').modal('show');
})

$('#user_pay_add').click(function () {
    var money = $('#use_pay_money').val();
    var desc = $('#use_pay_desc').val();
    if (money == '') {
        toastr.warning('请正确输入充值金额');
    }
    if (desc == '') {
        toastr.warning('请正确输入充值备注');
    }
    var uid = $('#op_id').val();
    var master_url = '/admin/addMemberMoneyDou'
    var token = $('#token').val();

    if (money > 0) {
        var types = '+';
    } else if (money < 0) {
        var types = '-';
    } else {
        toastr.warning('参数错误');
        return false;
    }

    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/addMemberMoneyDou",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token, desc: desc, money: money},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);

            $("#use_pay_tbody").prepend("<tr>" +
                "<td class='text-center'>" + rs.data.id + "</td>" +
                "<td class='text-center'>" + rs.data.money + "</td>" +
                "<td class='text-center'>" + rs.data.desc + "</td>" +
                "<td class='text-center'>" + rs.data.created_time + "</td>" +
                "<td class='text-center'>" + types + "</td>" +
                "<td class='text-center'>" + rs.data.created_user + "</td>" +
                "</tr>");

            $(".user-pay-no").remove();
            //var coin = Number($('.coin').text()) + Number(rs.data.money);
            //$('.coin').text(coin);
            if (money > 0) {
                var coin = Number($('.coin').text()) + Number(rs.data.money);
                $('.coin').text(coin);
            } else if (money < 0) {
                var coin = Number($('.coin').text()) - Number(rs.data.money);
                $('.coin').text(coin);
            }
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

})

$('#user_score_add').click(function () {
    var money = $('#user_score_money').val();
    var desc = $('#user_score_desc').val();
    if (money == '') {
        toastr.warning('请正确输入积分数量');
    }
    if (desc == '') {
        toastr.warning('请正确输入积分备注');
    }
    var uid = $('#op_id').val();
    var master_url = '/admin/addMemberScore'
    var token = $('#token').val();

    if (money > 0) {
        var types = '+';
    } else if (money < 0) {
        var types = '-';
    } else {
        toastr.warning('参数错误');
        return false;
    }

    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/addMemberScore",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token, desc: desc, money: money},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);

            $("#use_score_tbody").prepend("<tr>" +
                "<td class='text-center'>" + rs.data.id + "</td>" +
                "<td class='text-center'>" + rs.data.money + "</td>" +
                "<td class='text-center'>" + rs.data.desc + "</td>" +
                "<td class='text-center'>" + rs.data.created_time + "</td>" +
                "<td class='text-center'>" + types + "</td>" +
                "<td class='text-center'>" + rs.data.created_user + "</td>" +
                "</tr>");

            $(".user-score-no").remove();
            if (money > 0) {
                var coin = Number($('.coin').text()) + Number(rs.data.money);
                $('.coin').text(coin);
            } else if (money < 0) {
                var coin = Number($('.coin').text()) - Number(rs.data.money);
                $('.coin').text(coin);
            }
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

})

$('#user_gold_add').click(function () {
    var money = $('#use_gold_money').val();
    var desc = $('#use_gold_desc').val();
    if (money == '') {
        toastr.warning('请正确输入充值金额');
    }
    if (desc == '') {
        toastr.warning('请正确输入充值备注');
    }
    var uid = $('#op_id').val();
    var master_url = '/admin/addMemberGold'
    var token = $('#token').val();

    if (money > 0) {
        var types = '+';
    } else if (money < 0) {
        var types = '-';
    } else {
        toastr.warning('参数错误');
        return false;
    }

    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/addMemberGold",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token, desc: desc, money: money},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);

            $("#use_gold_tbody").prepend("<tr>" +
                "<td class='text-center'>" + rs.data.id + "</td>" +
                "<td class='text-center'>" + rs.data.money + "</td>" +
                "<td class='text-center'>" + rs.data.desc + "</td>" +
                "<td class='text-center'>" + rs.data.created_time + "</td>" +
                "<td class='text-center'>" + types + "</td>" +
                "<td class='text-center'>" + rs.data.created_user + "</td>" +
                "</tr>");

            $(".user-gold-no").remove();

            if (money > 0) {
                var coin = Number($('.gold_coin').text()) + Number(rs.data.money);
                $('.gold_coin').text(coin);
            } else if (money < 0) {
                var coin = Number($('.gold_coin').text()) - Number(rs.data.money);
                $('.gold_coin').text(coin);
            }
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

})

$('.user-score-list').click(function () {
    var uid = $('#op_id').val();
    var master_url = '/admin/getMemberScoreList'
    var token = $('#token').val();
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: '/admin/getMemberScoreList',//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $(rs.data).each(function (i, n) {
                    $("#use_pay_list_tbody").prepend("<tr>" +
                        "<td class='text-center'>" + n.id + "</td>" +
                        // "<td class='text-center'>" + n.platform + "</td>" +
                        "<td class='text-center'>" + n.content + "</td>" +
                        "<td class='text-center'>" + n.rmb + "</td>" +
                        "<td class='text-center'>" + n.addtime + "</td>" +
                        "</tr>");
                });
            } else {
                $("#use_pay_list_tbody").prepend("<tr class='no-records-found'><td colspan='4' class='text-center'>没有找到匹配的记录</td></tr>");
            }

            $('#use_pay_list').modal('show')
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
})

$('.user-pay-list').click(function () {
    var uid = $('#op_id').val();
    var master_url = '/admin/getChargeDetailList'
    var token = $('#token').val();

    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: '/admin/getChargeDetailList',//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $(rs.data).each(function (i, n) {
                    $("#use_pay_list_tbody").prepend("<tr>" +
                        "<td class='text-center'>" + n.id + "</td>" +
                        // "<td class='text-center'>" + n.platform + "</td>" +
                        "<td class='text-center'>" + n.content + "</td>" +
                        "<td class='text-center'>" + n.rmb + "</td>" +
                        "<td class='text-center'>" + n.addtime + "</td>" +
                        "</tr>");
                });
            } else {
                $("#use_pay_list_tbody").prepend("<tr class='no-records-found'><td colspan='4' class='text-center'>没有找到匹配的记录</td></tr>");
            }

            $('#use_pay_list').modal('show')
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
})


$(".user-diamond-add-list").click(function () {
    $('#diamond_type').val(1);

    var uid = $('#op_id').val();
    var master_url = '/admin/getMemberMoneyDiamondOneList';
    var token = $('#token').val();
    var user_role_menu = $("#user_role_menu").val();
    var user_role_menus = user_role_menu.split(",");
    var index = $.inArray("/admin/addMemberMoneyDiamondOne", user_role_menus);   //结果：index=1
    if (index <= 0) {
        $('.user_diamond_div').remove();
    }
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/getMemberMoneyDiamondOneList",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $(rs.data).each(function (i, n) {
                    $("#user_diamond_tbody").prepend("<tr>" +
                        "<td class='text-center'>" + n.id + "</td>" +
                        "<td class='text-center'>" + n.money + "</td>" +
                        "<td class='text-center'>" + n.desc + "</td>" +
                        "<td class='text-center'>" + n.created_time + "</td>" +
                        "<td class='text-center'>" + n.status + "</td>" +
                        "<td class='text-center'>" + n.created_user + "</td>" +
                        "</tr>");
                });
            } else {
                $("#user_diamond_tbody").prepend("<tr class='no-records-found user-diamond-no'><td colspan='6' class='text-center'>没有找到匹配的记录</td></tr>");
            }

        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

    $('#user_diamond').modal('show');
})

$("#user_diamond_add").click(function () {
    var money = $('#user_diamond_money').val();
    if (money == '') {
        toastr.warning('请正确输入金额');
        return false;
    }
    var desc = $('#user_diamond_desc').val();
    if (desc == '') {
        toastr.warning('请正确输入备注');
        return false;
    }

    var status = $('#diamond_type').val();
    if (status == 1) {
        var master_url = '/admin/addMemberMoneyDiamondOne';
        var types = '+';
    } else if (status == 2) {
        var master_url = '/admin/addMemberMoneyDiamondTwo';
        var types = '-';
    } else {
        toastr.warning('参数错误');
        return false;
    }

    var token = $('#token').val();
    var uid = $('#op_id').val();

    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: master_url,//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token, money: money, desc: desc},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            $("#user_diamond_tbody").prepend("<tr>" +
                "<td class='text-center'>" + rs.data.id + "</td>" +
                "<td class='text-center'>" + rs.data.money + "</td>" +
                "<td class='text-center'>" + rs.data.desc + "</td>" +
                "<td class='text-center'>" + rs.data.created_time + "</td>" +
                "<td class='text-center'>" + types + "</td>" +
                "<td class='text-center'>" + rs.data.created_user + "</td>" +
                "</tr>");
            $('.user-diamond-no').remove();
            if (status == 1) {
                var diamond = Number($('.diamond').text()) + Number(rs.data.money);
                $('.diamond').text(diamond);
            } else if (status == 2) {
                var diamond = Number($('.diamond').text()) - Number(rs.data.money);
                $('.diamond').text(diamond);
            }
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

})

$('.user-diamond-lessent-list').click(function () {
    $('#diamond_type').val(2);
    var uid = $('#op_id').val();
    var master_url = '/admin/getMemberMoneyDiamondTwoList';
    var token = $('#token').val();
    var user_role_menu = $("#user_role_menu").val();
    var user_role_menus = user_role_menu.split(",");
    var index = $.inArray("/admin/addMemberMoneyDiamondTwo", user_role_menus);   //结果：index=1
    if (index <= 0) {
        $('.user_diamond_div').remove();
    }
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/getMemberMoneyDiamondTwoList",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $(rs.data).each(function (i, n) {
                    $("#user_diamond_tbody").prepend("<tr>" +
                        "<td class='text-center'>" + n.id + "</td>" +
                        "<td class='text-center'>" + n.money + "</td>" +
                        "<td class='text-center'>" + n.desc + "</td>" +
                        "<td class='text-center'>" + n.created_time + "</td>" +
                        "<td class='text-center'>" + n.status + "</td>" +
                        "<td class='text-center'>" + n.created_user + "</td>" +
                        "</tr>");
                });
            } else {
                $("#user_diamond_tbody").prepend("<tr class='no-records-found user-diamond-no'><td colspan='6' class='text-center'>没有找到匹配的记录</td></tr>");
            }

        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });

    $('#user_diamond').modal('show');
})


$('body').on('hidden.bs.modal', '#historical_dynamics', function () {
    $("#historical_dynamics_tbody tr").remove();
});

$('body').on('hidden.bs.modal', '#coin_detail_receiving', function () {
    $("#coin_detail_receiving_tbody tr").remove();
});

$('body').on('hidden.bs.modal', '#coin_detail_giving', function () {
    $("#coin_detail_giving_tbody tr").remove();
});

$('body').on('hidden.bs.modal', '#login_detail_receiving', function () {
    $("#login_detail_receiving_tbody tr").remove();
});

$('body').on('hidden.bs.modal', '#use_pay', function () {
    $("#use_pay_tbody tr").remove();
});

$('body').on('hidden.bs.modal', '#user_score', function () {
    $("#user_score_tbody tr").remove();
});

$('body').on('hidden.bs.modal', '#use_gold', function () {
    $("#use_gold_tbody tr").remove();
});

$('body').on('hidden.bs.modal', '#use_pay_list', function () {
    $("#use_pay_list_tbody tr").remove();
});

$('body').on('hidden.bs.modal', '#use_score_list', function () {
    $("#user_score_list_tbody tr").remove();
});

$('body').on('hidden.bs.modal', '#user_diamond', function () {
    $("#user_diamond_tbody tr").remove();
});
$('body').on('hidden.bs.modal', '#equipmentLists', function () {
    $("#equipmentLists li").remove();
});
$('body').on('hidden.bs.modal', '#give_gift', function () {
    $("#give_gift tbody tr").remove();
});


$('.userInfoBtn').click(function () {
        $('.common-user-info-box').css('display','block');
        $('.equipment-management-box').css('display','none');
        $('.login-info-box').css('display','none');
        $(this).addClass("change_action").siblings().removeClass("change_action");

})

$('.loginUserInfo').click(function () {
    $('.common-user-info-box').css('display','none');
    $('.equipment-management-box').css('display','none');
    $('.login-info-box').css('display','block');
    $(this).addClass("change_action").siblings().removeClass("change_action");
    var uid = $('#op_id').val();
    var master_url = '/admin/loginUserInfo';
    var token = $('#token').val();
    var user_role_menu = $("#user_role_menu").val();
    var user_role_menus = user_role_menu.split(",");
    var index = $.inArray("/admin/loginUserInfo", user_role_menus);   //结果：index=1
    if (index <= 0) {
        $('.loginUserInfo').remove();
    }
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "get",    //请求类型
        url: "/admin/loginUserInfo",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {id: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            $('.div-user_id').text(rs.data.user_id);
            $('.div-login_ip').text(rs.data.login_ip);
            $('.div-channel').text(rs.data.channel);
            $('.div-device').text(rs.data.device);
            $('.div-deviceid').text(rs.data.deviceid);
            $('.div-platform').text(rs.data.platform);
            $('.div-version').text(rs.data.version);
            $('.div-imei').text(rs.data.imei);
            $('.div-idfa').text(rs.data.idfa);
            $('.div-appid').text(rs.data.appid);
            $('.div-source').text(rs.data.source);
            $('.div-simulator_info').text(rs.data.simulator_info);
            $('.div-update_time').text(rs.data.update_time);
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
})

$('.equipmentManagement').click(function () {
    $('.common-user-info-box').css('display','none');
    $('.login-info-box').css('display','none');
    $('.equipment-management-box').css('display','block');
    $(this).addClass("change_action").siblings().removeClass("change_action");
    var uid = $('#op_id').val();
    var master_url = '/admin/userPackList';
    var token = $('#token').val();
    var user_role_menu = $("#user_role_menu").val();
    var user_role_menus = user_role_menu.split(",");
    var index = $.inArray("/admin/userPackList", user_role_menus);   //结果：index=1
    if (index <= 0) {
        $('.equipmentManagement').remove();
    }
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "get",    //请求类型
        url: "/admin/userPackList",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {id: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $("#equipmentLists li").empty();
                $(rs.data).each(function (i, n) {
                    $("#equipmentLists").prepend("<li style='width: 100px'>"+
                        "<img  src="+ n.gift_img +">"+
                        "<p style='color: #00FFFF'>拥有个数 <span style='color: #0f0f0f;font-size: 14px'>"+ n.pack_num+"</span></p>"+
                        "<button class=\" btn btn-primary Details_btn selectPackInfo\" onclick='selectPackInfo(this)'>查看详情</button>"+
                        '<div class="equipmentDetails" style="display:none">'+
                            "<div style=\"display: flex;margin-top: 10px;justify-content: space-between\">"+
                            "<img width=\"80px\" src=" + n.gift_img + " alt=\"\">"+
                            "<div class=\"equipmentDetails_title\">"+
                            "<p>拥有个数 <span style='color: #1c84c6;font-size: 14px'>"+ n.pack_num+"</span></p>"+
                            " <p>价值 <span style='color: #1c84c6;font-size: 14px'>"+ n.gift_coin+"</span></p>"+
                            "<p>有效期 <span style='color: #1c84c6;font-size: 14px'>"+ n.endtime+"</span></p>"+
                            "</div>"+
                            "</div>"+
                            "<div style=\"display: flex;width:40%;margin-top:10px;justify-content: space-between;float: right\">"+
                            "<button style=\"border: none\" class=\"btn btn-danger pack_del\"  onclick=\"pack_del("+ n.gift_id +")\">删除</button>"+
                            "<button style=\"border: none\" class=\"btn btn-primary pack_adorn\" onclick=\"pack_adorn("+ n.gift_id +")\">佩戴</button>"+
                            "</div>"+
                        "</div>"+
                    "</li>");
                });
            } else {
                $("#equipmentLists").prepend("<p class='text-center' style='text-align: center'>没有找到匹配的记录</p>");
            }
            var user_role_menu = $("#user_role_menu").val();
            var user_role_menus = user_role_menu.split(",");

            var index0 = $.inArray("/admin/userPackList", user_role_menus);   //结果：index=1
            if (index0 <= 0) {
                $(".selectPackInfo").remove();
            }
            var index1 = $.inArray("/admin/userPackDel", user_role_menus);   //结果：index=1
            if (index1 <= 0) {
                $(".pack_del").remove();
            }
            var index2 = $.inArray("/admin/userPackAdorn", user_role_menus);   //结果：index=1
            if (index2 <= 0) {
                $(".pack_adorn").remove();
            }
            var index4 = $.inArray("/admin/userPackGiveList", user_role_menus);   //结果：index=1
            if (index4 <= 0) {
                $(".give_pack_list").remove();
            }
            var index5 = $.inArray("/admin/userPackGive", user_role_menus);   //结果：index=1
            if (index5 <= 0) {
                $(".user_pay_div").remove();
            }
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
});
//删除用户装备
function pack_del(gift_id){
    $('#del_pack_model').modal('show');
    $("#userPackGift").val(gift_id);
    $("#del_pack_model_msg").html(' 您确定要删除此装备吗？');
};
function del_pack_model_on(){
    var gift_id = $("#userPackGift").val();
    var uid = $('#op_id').val();
    var master_url = '/admin/userPackDel';
    var token = $('#token').val();
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/userPackDel",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {gift_id: gift_id,uid:uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            setTimeout(location, 1000);   //延迟5秒刷新页面
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
    function location() {
        window.location.href = window.location.href;
    }
    return false;
}
//佩戴用户装备
function pack_adorn(gift_id){
    $('#adorn_pack_model').modal('show');
    $("#userPackGift").val(gift_id);
    $("#adorn_pack_model_msg").html(' 您确定要佩戴此装备吗？');
};
function adorn_pack_model_on(){
    var gift_id = $("#userPackGift").val();
    var uid = $('#op_id').val();
    var master_url = '/admin/userPackAdorn';
    var token = $('#token').val();
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "post",    //请求类型
        url: "/admin/userPackAdorn",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {gift_id: gift_id,uid:uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            setTimeout(location, 1000);   //延迟5秒刷新页面
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
    function location() {
        window.location.href = window.location.href;
    }
    return false;
}
//赠送装备列表
$(".give_pack_list").click(function(){
    var uid = $('#op_id').val();
    var master_url = '/admin/userPackGiveList'
    var token = $('#token').val();
    $.ajax({
        async: false,    //表示请求是否异步处理
        type: "get",    //请求类型
        url: '/admin/userPackGiveList',//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {uid: uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            if (rs.data.length > 0) {
                $(rs.data).each(function (i, n) {
                    $("#give_gift_tbody").prepend("<tr>" +
                        "<td class='text-center'>" + n.id + "</td>" +
                        "<td class='text-center'>" + n.gift_id + "</td>" +
                        "<td class='text-center'>" + n.gift_coin + "</td>" +
                        "<td class='text-center'>" + n.give_desc + "</td>" +
                        "<td class='text-center'>" + n.gift_time + "</td>" +
                        "<td class='text-center'>" + n.created_time + "</td>" +
                        "<td class='text-center'>" + n.created_user + "</td>" +
                        "</tr>");
                });
            } else {
                $("#give_gift_tbody").prepend("<tr class='no-records-found'><td colspan='9' class='text-center'>没有找到匹配的记录</td></tr>");
            }
            $('#give_gift').modal('show');
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
});
//赠送用户装备
$("#gift_submit").click(function(){
   var  gift_id = $("#give_gift_id").val();
   var  gift_time = $("#give_gift_time").val();
   var  give_desc = $("#give_gift_desc").val();
   if(gift_id == ""){
       toastr.warning('装备ID不能为空');
       return false;
   }
    if(gift_time == ""){
        toastr.warning('有效时间不能为空');
        return false;
    }
    if(give_desc == ""){
        toastr.warning('赠送备注不能为空');
        return false;
    }
    var uid = $('#op_id').val();
    var master_url = '/admin/userPackGive';
    var token = $('#token').val();
    $.ajax({
        async: false,    //表示请求是否异步处理giving-gift
        type: "post",    //请求类型
        url: "/admin/userPackGive",//请求的 URL地址
        dataType: "json",//返回的数据类型
        data: {gift_id: gift_id,gift_time:gift_time,give_desc:give_desc,uid:uid, master_url: master_url, token: token},
        success: function (rs) {
            if (rs.code !== 200) {
                toastr.warning(rs.msg);
                return false;
            }
            toastr.success(rs.msg);
            setTimeout(location, 1000);   //延迟5秒刷新页面
        },
        error: function (rs) {
            toastr.warning('请求失败');
        }
    });
    function location() {
        window.location.href = window.location.href;
    }
    return false;

});
//详情框类名
function selectPackInfo(index){
    let parent=$(index).parent();
    $('.active').removeClass('active');
    $(index).addClass('active');
    $('.equipmentDetails').hide();
    $(parent).find('.equipmentDetails').show();
}
$(document).click(function (e) {
    var $target = $(e.target);
    //点击表情选择按钮和表情选择框以外的地方 隐藏表情选择框
    if (!$target.is('.equipmentDetails') && !$target.is('.active')) {
        $('.active').removeClass('active');
        $('.equipmentDetails').hide();
    }
});