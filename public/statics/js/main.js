layui.config({
    base: '/statics/layui/modules/'      //自定义layui组件的目录
}).extend({ //设定组件别名
    utils :  'utils',
    echarts :  'echarts',
});

layui.use(['layer', 'element'], function(){
    var idx
    layui.$('.tips').toggle(function () {
        var that = this;
        idx = layer.tips($(this).data('title'), that);
    },function () {
        layer.close(idx)
    })
});