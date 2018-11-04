/**
 * Created by wuxin on 2018/11/4.
 */

layui.use(['layer', 'form', 'utils'], function(){
    var $ = layui.$
    var form = layui.form
    var utils = layui.utils
    $('body').on('click', '.send-offer', function () {
        var $this = $(this);
        var id = $this.data('id');
        if (!id) {
            layer.msg('sorry,洒家暂时联系不上米主，请稍后再试试！');
            return false;
        }
        $.get('/offer/form?raw=1&id='+id, function (res) {
            var id = 'offer_form';
            layer.open({
                title: '联系米主',
                id: id,
                type: 1,
                area: ['480px', ],
                content: res,
                btn: ['确认', '取消'],
                yes: function (index) {
                    $('#' + id).find('button.submit').trigger('click')
                },
                success: function () {
                    form.render()
                }
            });
        })

        //modal表单的监听添加
        form.on('submit(send_offer)', function (data) {
            utils.ajax.post('/api/offer/send/'+id, data.field, function (res) {
                layer.alert('米主已成功收到你的消息！', function (index) {
                    layer.closeAll();
                });
            });
            return false;
        })
    });

});