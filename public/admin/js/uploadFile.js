/*
上传图片至OSS
 */
//初始化fileinput控件（第一次初始化）

$("#input_upload_file").fileinput({
    language:'zh',                                          // 多语言设置，需要引入local中相应的js，例如locales/zh.js
    theme: "explorer-fa",                               // 主题
    uploadUrl: task_file_uplad_url,         // 上传地址
    minFileCount: 1,                                        // 最小上传数量
    maxFileCount: 1,                                        // 最大上传数量
    overwriteInitial: false,                        // 覆盖初始预览内容和标题设置
    showCancel:false,                                       // 显示取消按钮
    showZoom:false,                                         // 显示预览按钮
    showCaption:false,                                  // 显示文件文本框
    dropZoneEnabled:false,                          // 是否可拖拽
    uploadLabel:"上传附件",                         // 上传按钮内容
    browseLabel: '选择附件',                            // 浏览按钮内容
    showRemove:false,                                       // 显示移除按钮
    browseClass:"layui-btn",                        // 浏览按钮样式
    uploadClass:"layui-btn",                        // 上传按钮样式
    uploadExtraData: {'taskId':taskId,'createBy':userId,'createByname':username},   // 上传数据
    hideThumbnailContent:true,                  // 是否隐藏文件内容
    fileActionSettings: {                               // 在预览窗口中为新选择的文件缩略图设置文件操作的对象配置
        showRemove: true,                                   // 显示删除按钮
        showUpload: true,                                   // 显示上传按钮
        showDownload: false,                            // 显示下载按钮
        showZoom: false,                                    // 显示预览按钮
        showDrag: false,                                        // 显示拖拽
        removeIcon: '<i class="fa fa-trash"></i>',   // 删除图标
        uploadIcon: '<i class="fa fa-upload"></i>',     // 上传图标
        uploadRetryIcon: '<i class="fa fa-repeat"></i>'  // 重试图标
    },

    initialPreview: [                                                                   //初始预览内容
        "https://picsum.photos/1920/1080?image=101",
        "https://picsum.photos/1920/1080?image=102",
        "https://picsum.photos/1920/1080?image=103"
    ],
    initialPreviewConfig: [                                                     // 初始预览配置 caption 标题，size文件大小 ，url 删除地址，key删除时会传这个
        {caption: "picture-1.jpg", size: 329892, width: "120px", url: task_file_delete_url, key: 101},
        {caption: "picture-2.jpg", size: 872378, width: "120px", url: task_file_delete_url, key: 102},
        {caption: "picture-3.jpg", size: 632762, width: "120px", url: task_file_delete_url, key: 103}
    ]
});
// 上传成功回调
$("#input-ke-2").on("filebatchuploadcomplete", function() {
    layer.msg("上传附件成功");
    setTimeout("closeUpladLayer()",2000)
});
// 上传失败回调
$('#input-ke-2').on('fileerror', function(event, data, msg) {
    layer.msg(data.msg);
    tokenTimeOut(data);
});