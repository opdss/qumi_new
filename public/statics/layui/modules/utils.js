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
    var ajaxProcess = function (res, success) {
        if (res.errCode == 0) {
            success(res.data, res);
        } else {
            layer.alert(res.errMsg, {icon: 2});
        }
    }
    var obj = {
        ajax : {
            get : function (url, success) {
                layui.$.ajax({'url':url, 'method':'get', 'success':function (res) {ajaxProcess(res, success)}});
            },
            post : function (url, data, success) {
                layui.$.ajax({'url':url, 'method':'post', 'data':data, 'success':function (res) {ajaxProcess(res, success)}});
            },
            put : function (url, data, success) {
                layui.$.ajax({'url':url, 'method':'put', 'data':data, 'success':function (res) {ajaxProcess(res, success)}});
            },
            delete : function (url, data, success) {
                layui.$.ajax({'url':url, 'method':'delete', 'data':data, 'success':function (res) {ajaxProcess(res, success)}});
            }
        },
        tableOptions : {
            page: { //支持传入 laypage 组件的所有参数（某些参数除外，如：jump/elem） - 详见文档
                layout: ['limit', 'count', 'prev', 'page', 'next', 'skip'] //自定义分页布局
                ,curr: 1 //设定初始在第 1 页
                ,limits : [10, 20, 30, 50, 100]
                ,limit : 20
                ,groups: 5 //只显示 1 个连续页码
                ,first: '首页' //不显示首页
                ,last: '尾页' //不显示尾页

            }
            ,parseData: function(res){ //将原始数据解析成 table 组件所规定的数据
                return {
                    "code": res.errCode, //解析接口状态
                    "msg": res.errMsg, //解析提示文本
                    "count": res.data.count, //解析数据长度
                    "data": res.data.records //解析数据列表
                };
            }
        },
        type : function(obj) {
            var toString = Object.prototype.toString;
            var map = {
                '[object Boolean]': 'boolean',
                '[object Number]': 'number',
                '[object String]': 'string',
                '[object Function]': 'function',
                '[object Array]': 'array',
                '[object Date]': 'date',
                '[object RegExp]': 'regExp',
                '[object Undefined]': 'undefined',
                '[object Null]': 'null',
                '[object Object]': 'object'
            };
            return map[toString.call(obj)];
        },

        delById : function (url, ids, success) {
            if (obj.type(ids) == 'array') {
                var msg = '确定删除选中的'+ids.length+'条数据吗？';
            } else {
                var msg = '确定删除选中的数据吗？';
            }
            layer.confirm(msg, function(index){
                obj.ajax.delete(url, {id: ids}, function (res) {
                    success(res)
                    layer.msg('删除成功！')
                    layer.close(index);
                });
            });
        }
    };
    //输出接口
    exports('utils', obj);
});