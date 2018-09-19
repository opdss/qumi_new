var utils = {

    specialStr: '!@#$%^&*()_+=-',
    /**
     * 生成随机字符串
     * @param $num 长度
     * @param $has 是否含有特殊字符
     * @param special 特俗字符
     * @returns {string}
     */
    genRandStr: function (num, has, special) {
        num = num || 16;
        var str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        var specialStr = has ? (special ? special : utils.specialStr) : '';
        if (has) {
            str += specialStr;
        }
        var len = str.length
        var res = '';
        for (var i = 0; i < num; i++) {
            res += str[Math.floor(Math.random() * len)];
        }
        return res;
    },


    getFmtDateTime: function (timestamp, showMs) {
        var date = timestamp && typeof timestamp == 'number' ? new Date(timestamp) : typeof timestamp == 'object' ? timestamp : new Date();
        var s1 = "-";
        var s2 = ":";
        var pad = function (num) {
            return num < 10 ? "0" + num.toString() : num;
        }
        return date.getFullYear()
            + s1 + pad(date.getMonth() + 1)
            + s1 + pad(date.getDate())
            + " "
            + pad(date.getHours())
            + s2 + pad(date.getMinutes())
            + s2 + pad(date.getSeconds())
            + (showMs ? '.' + date.getMilliseconds() : '');
    },

    getTimestamp: function (fmtDate) {
        if (fmtDate) {
            return Date.parse(new Date(fmtDate)) / 1000;
        }
        return Date.parse(new Date()) / 1000;
    },

    fmtSec: function (sec) {
        var fmt = [
            [60, '秒'],
            [60, '分'],
            [24, '小时'],
            [365, '天'],
            [1, '年'],
        ]
        var idx = 0, fmtStr = '', k = 0;
        while (sec > fmt[idx][0] && idx < (fmt.length - 1)) {
            k = parseInt(sec % fmt[idx][0])
            sec = parseInt(sec / fmt[idx][0])
            fmtStr = k + fmt[idx][1] + fmtStr;
            idx++;
        }
        fmtStr = sec + fmt[idx][1] + fmtStr;
        return fmtStr;
    },

    //得到标准时区的时间的函数
    getZoneTime: function (timezone) {
        //参数i为时区值数字，比如北京为东八区则输进8,西5输入-5
        if (typeof timezone !== 'number') {
            timezone = 8;
        }
        var dt = new Date();
        //本地时间与GMT时间的时间偏移差
        var offset = dt.getTimezoneOffset() * 60000;
        //得到现在的格林尼治时间
        var utcTime = dt.getTime() + offset + 3600000 * timezone;
        return utils.getFmtDateTime(utcTime);
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

    swal : {
        error : function (msg) {
            swal('处理失败', msg, 'error');
        },
        success : function (msg, reload) {
            swal({
                    title: "处理成功",
                    text: msg,
                    type: "success",
                },
                function(){
                    if (reload){
                        window.location.reload();
                    }
                });
        }
    }
    
}