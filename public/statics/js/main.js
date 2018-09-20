layui.config({
    base: '/statics/layui/modules/'      //自定义layui组件的目录
}).extend({ //设定组件别名
    utils :  'utils',
});

layui.use(['layer', 'element'], function(){});