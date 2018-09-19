layui.define(['layer', 'jquery'], function(exports){
    //设置ajax的全局配置
    layui.$.ajaxSetup({
        timeout : 30000,
        dataType: "json",
        beforeSend: function (obj) {
            //$('body').append(loading);
            //loading.open();
        },
        error: function (res) {
            layer.alert('网络可能有点不正常，刷新再看看', {icon: 2});
        },
        complete: function (res) {
            //console.log(res);
        }
    });
    var obj = {
        'log' : function (str) {
            console.log(str);
        },
    };
    var ajaxProcess = function (res, success) {
        if (res.errCode == 0) {
            success(res.data, res);
        } else {
            layer.alert(res.errMsg, {icon: 2});
        }
    }
    obj['$'] = {
        'get' : function (url, success) {
            layui.$.ajax({'url':url, 'method':'get', 'success':function (res) {ajaxProcess(res, success)}});
        },
        'post' : function (url, data, success) {
            layui.$.ajax({'url':url, 'method':'post', 'data':data, 'success':function (res) {ajaxProcess(res, success)}});
        },
        'put' : function (url, data, success) {
            layui.$.ajax({'url':url, 'method':'put', 'data':data, 'success':function (res) {ajaxProcess(res, success)}});
        },
        'delete' : function (url, data, success) {
            layui.$.ajax({'url':url, 'method':'delete', 'data':data, 'success':function (res) {ajaxProcess(res, success)}});
        }
    }
    //输出接口
    exports('utils', obj);
});