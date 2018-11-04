<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/11/4
 * Time: 22:23
 */
namespace App\Controllers;

use App\Functions;
use App\Libraries\Email;
use App\Models\DomainOffer;
use Slim\Http\Request;
use Slim\Http\Response;

class Offer extends Base
{
    protected static $captchaKey = 'offer_form';

    /**
     * @pattern /api/offer/send[/{domain_id}]
     * @name api.offer.send
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function send(Request $request, Response $response, $args)
    {
        $domain_id = isset($args['domain_id']) ? (int)$args['domain_id'] : 0;
        $domain_id = $domain_id ?: intval($request->getParsedBodyParam('id'));
        $captcha = $request->getParsedBodyParam('captcha');

        if (!$captcha) {
            return $this->json(3, '请输入验证码！');
        }

        if (strtolower($captcha) !== $this->sessCaptcha(self::$captchaKey)) {
            return $this->json(3, '您输入的验证码错误！');
        }

        if (!$domain_id || !($domainModel = \App\Models\Domain::with('user')->find($domain_id))) {
            $this->log('error', __METHOD__ . ' => 非法域名id', [$domain_id, $request->getParsedBody()]);
            return $this->json(3);
        }
        $nickname = $request->getParsedBodyParam('nickname');
        $price = (int)$request->getParsedBodyParam('price');
        $content = $request->getParsedBodyParam('content');
        $email = $request->getParsedBodyParam('email');
        if (!Functions::verifyEmail($email)) {
            return $this->json(3, '联系邮箱不对，填个正确的吧，有诚意点！');
        }

        $offerModel = new DomainOffer();
        $offerModel-> nickname = $nickname;
        $offerModel->price = $price;
        $offerModel->content = $content;
        $offerModel->email = $email;
        $offerModel->domain_id = $domain_id;

        if ($offerModel->save()) {
            $body = '你好，[' . $nickname . ']对你的域名[' . $domainModel->name . ']很感兴趣，报价：' . $price . '，并留言：' . $content . '，他的邮箱是：' . $email . '，有空联系吧，祝老板交易成功';
            if (!Email::factory()->insertQueue($domainModel->user->email, $body, '客户来啦！')) {
                $this->log('error', 'offer:'.$offerModel->id.'插入邮件队列失败', $offerModel->toArray());
            }
            return $this->json(0);
        }
        return $this->json(1);
    }

    /**
     * @pattern /offer/form
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function form(Request $request, Response $response, $args)
    {
        $data['captchaImg'] = $this->getCaptchaImg(self::$captchaKey, 150, 36);
        return $this->view('offer/form.twig', $data);
    }

}