layui.define(['layer', 'jquery'], function(exports){

    //查看对象类型
    var type = function(obj) {
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
    }

    //是否启用ajax的全局loading
    var hasLoading = true;
    //ajax的全局loading的index
    var loading;
    //设置ajax的全局配置
    layui.$.ajaxSetup({
        timeout : 10000,
        //dataType: "json",
        beforeSend: function (obj) {
            if (hasLoading) {
                loading = layer.load(2, {time: 10000});
            }
        },
        error: function (res) {
            layer.alert('网络可能有点不正常，刷新再看看', {icon: 2});
        },
        complete: function (res) {
            if (hasLoading) {
                layer.close(loading);
            }
        }
    });
    //对响应成功(httpcode=200)的请求进行errCode预处理
    var ajaxProcess = function (res, success, error) {
        if (res.errCode == 0) {
            if (success) {
                success(res.data, res);
            }
        } else if (res.errCode == -1) {
            layer.alert('请重新登陆！', function () {
                location.href = '/login';
            })
        } else if (res.errCode == 2) {
            layer.alert('你没有相关权限！', {icon: 2})
        } else {
            if (error) {
                error(res);
            } else {
                layer.alert(res.errMsg, {icon: 2});
            }
        }
    }
    //封装ajax 的curd 方法
    var ajax = {
        get : function (url, success, error) {
            layui.$.ajax({'url':url, 'method':'get', 'dataType': "json", 'success':function (res) {ajaxProcess(res, success, error)}});
        },
        post : function (url, data, success, error) {
            layui.$.ajax({'url':url, 'method':'post', 'dataType': "json", 'data':data, 'success':function (res) {ajaxProcess(res, success, error)}});
        },
        put : function (url, data, success, error) {
            layui.$.ajax({'url':url, 'method':'put', 'dataType': "json", 'data':data, 'success':function (res) {ajaxProcess(res, success, error)}});
        },
        delete : function (url, data, success, error) {
            layui.$.ajax({'url':url, 'method':'delete', 'dataType': "json", 'data':data, 'success':function (res) {ajaxProcess(res, success, error)}});
        }
    }

    //预设的表单字段校验
    var verifyFieldAll = {
        //邮件发送的六位数字code
        code: function(value){
            if(value.length != 6){
                return '请输入邮件收到的六位验证码';
            }
        },
        //图片4位验证码
        captcha: function(value){
            if(value.length != 4){
                return '请输入4位验证码';
            }
        },
        //密码
        passwd: [/(.+){6,20}$/, '密码必须6到20位'],
        description : function(value){
            if(value.length > 500){
                return '简介最多不超过五百字';
            }
        },
        title : function (value) {
            if(value.length == 0){
                return '标题不能为空';
            }
            if(value.length > 100){
                return '标题最多不超过一百个字';
            }
        },
        name : function (value) {
            if(value.length == 0){
                return '名称不能为空';
            }
            if(value.length > 60){
                return '名称最多不超过60个字符';
            }
        },
        path : function (value) {
            if(value.length == 0){
                return '地址路径不能为空';
            }
            if(value.length > 20){
                return '地址路径最多不超过20个字符';
            }
        },
        varchar : function (value) {
            if(value.length > 100){
                return '最大长度不超过100个字符';
            }
        },
    }

    //url处理相关
    var urlHelper = {
        getParam : function(url, ref) {
            var str = "";
            // 如果不包括此参数
            if (url.indexOf(ref+'=') == -1){
                return "";
            }
            str = url.substr(url.indexOf('?') + 1);
            arr = str.split('&');
            for (i in arr) {
                var paired = arr[i].split('=');
                if (paired[0] == ref) {
                    return paired[1];
                }
            }
            return "";
        },
        putParam : function(url, ref, value) {
            // 如果没有参数
            if (url.indexOf('?') == -1)
                return url + "?" + ref + "=" + value;
            // 如果不包括此参数
            if (url.indexOf(ref+'=') == -1)
                return url + "&" + ref + "=" + value;
            var arr_url = url.split('?');
            var base = arr_url[0];
            var arr_param = arr_url[1].split('&');
            for (i = 0; i < arr_param.length; i++) {
                var paired = arr_param[i].split('=');
                if (paired[0] == ref) {
                    paired[1] = value;
                    arr_param[i] = paired.join('=');
                    break;
                }
            }
            return base + "?" + arr_param.join('&');
        },
        delParam : function(url, ref) {
            // 如果不包括此参数
            if (url.indexOf(ref) == -1)
                return url;
            var arr_url = url.split('?');
            var base = arr_url[0];
            var arr_param = arr_url[1].split('&');
            var index = -1;
            for (i = 0; i < arr_param.length; i++) {
                var paired = arr_param[i].split('=');
                if (paired[0] == ref) {
                    index = i;
                    break;
                }
            }
            if (index == -1) {
                return url;
            } else {
                arr_param.splice(index, 1);
                return base + "?" + arr_param.join('&');
            }
        }
    };

    //导出模块对象
    var obj = {
        type : type,
        setAjaxLoading : function (hl) {
            hasLoading = hl ? true : false;
        },
        //重新封装ajax 的curd请求方法
        ajax : ajax,
        //默认数据表格配置
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
                if (!res.hasOwnProperty("data")) {
                    res['data'] = {count:0, records:[]}
                }
                return {
                    "code": res.errCode, //解析接口状态
                    "msg": res.errMsg, //解析提示文本
                    "count": res['data']['count'], //解析数据长度
                    "data": res['data']['records'] //解析数据列表
                };
            }
        },
        //根据id删除
        delById : function (url, ids, success) {
            if (type(ids) == 'array') {
                var msg = '确定删除选中的'+ids.length+'条数据吗？';
            } else {
                var msg = '确定删除选中的数据吗？';
            }
            layer.confirm(msg, function(index){
                ajax.delete(url, {id: ids}, function (res) {
                    success(res)
                    layer.msg('删除成功！')
                    layer.close(index);
                });
            });
        },
        //格式化处理domainStr
        formatDomainStr : function (domainStr) {
            var spl = ',';
            domainStr = domainStr.replace(/[\r|\n| |，]+/g, ',').replace(/^,+|,+$/g, '').replace(/,{1,}/g, spl);
            var domains = [];
            var reg = /^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}/;
            (domainStr.split(spl)).forEach(function (value) {
                var _val = value.split('.')
                if (_val.length > 2) {
                    value = _val[_val.length-2]+'.'+_val[_val.length-1]
                }
                if (reg.test(value) && domains.indexOf(value) == -1) {
                    domains.push(value)
                }
            });
            return domains;
        },
        //校验邮箱
        verifyEmail : function(email){
            if (!email) {
                return false;
            }
            var reg = new RegExp("^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$"); //正则表达式
            if(!reg.test(email)){ //正则验证不通过，格式不对
                return false;
            }else{
                return true;
            }
        },
        //一些预设的自定义字段校验
        getVerifyByField : function (fields) {
            if (!fields) {
                return verifyFieldAll;
            }
            if (type(fields) == 'string') {
                return verifyFieldAll['fields'] ? verifyFieldAll['fields'] : function () {}
            }
            if (type(fields) == 'array') {
                var res = {};
                fields.forEach(function (value) {
                    if (verifyFieldAll[value]) {
                        res[value] = verifyFieldAll[value];
                    }
                })
                return res;
            }
            return {};
        },
        urlHelper : urlHelper,
    };
    //输出接口
    exports('utils', obj);
});