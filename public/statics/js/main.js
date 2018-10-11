layui.config({
    base: '/statics/layui/modules/'      //自定义layui组件的目录
}).extend({ //设定组件别名
    utils :  'utils',
    echarts :  'echarts',
});

layui.use(['layer', 'element'], function(){
    var $ = layui.$
    var idx
    $('body').on('mouseenter', '.tips', function () {
        var that = this;
        idx = layer.tips($(this).data('title'), that, {
            tips: 1
        });
    })

    $('body').on('mouseleave', '.tips', function () {
        layer.close(idx)
    })
});