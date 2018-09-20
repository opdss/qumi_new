<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/9/11
 * Time: 23:12
 */
namespace App\Libraries;

use App\Functions;
use App\Models\EmailQueue;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    private $mail = null;
    private $config;

    private $logger;

    private $logs = [];

    private function __construct()
    {
        $this->config = Config::site('email');
        $this->mail = new PHPMailer();
        $this->mail->SMTPDebug = 1;
        $this->logger = Functions::getLogger();
        $this->init();
    }

    public static function factory()
    {
        return new self();
    }

    private function init()
    {
        $this->mail->Debugoutput = $this->logger;
        // 使用smtp鉴权方式发送邮件
        $this->mail->isSMTP();
        // smtp需要鉴权 这个必须是true
        $this->mail->SMTPAuth = true;
        // 链接qq域名邮箱的服务器地址
        $this->mail->Host = 'smtp.exmail.qq.com';
        // 设置使用ssl加密方式登录鉴权
        $this->mail->SMTPSecure = 'ssl';
        // 设置ssl连接smtp服务器的远程服务器端口号
        $this->mail->Port = 465;
        // 设置发送的邮件的编码
        $this->mail->CharSet = 'UTF-8';
        // 设置发件人昵称 显示在收件人邮件的发件人邮箱地址前的发件 人姓名
        $this->mail->FromName = '趣米停靠站';
        // smtp登录的账号 QQ邮箱即可
        $this->mail->Username = $this->config['username'];
        // smtp登录的密码 使用生成的授权码
        $this->mail->Password = $this->config['password'];
        // 设置发件人邮箱地址 同登录账号
        $this->mail->From = $this->config['username'];
        // 添加该邮件的主题
        $this->mail->Subject = '趣米通知';
    }

    public function setFromName($fromName)
    {
        $this->mail->FromName = $fromName;
        return $this;
    }

    public function isHtml($isHtml = true)
    {
        $this->mail->isHTML($isHtml);
        return $this;
    }

    public function setSubject($subject)
    {
        $this->mail->Subject = $subject;
        $this->lags['subject'][] = $subject;
        return $this;
    }

    public function setBody($body)
    {
        $this->mail->Body = $body;
        $this->lags['body'] = $body;
        return $this;
    }

    public function addAddress($address)
    {
        if (!is_array($address)) {
            $address = [$address];
        }
        foreach ($address as $ads) {
            $this->mail->addAddress($ads);
            $this->lags['address'][] = $ads;
        }
        return $this;
    }

    public function addAttachment($attachment)
    {
        if (!is_array($attachment)) {
            $attachment = [$attachment];
        }
        foreach ($attachment as $att) {
            $this->mail->addAttachment($att);
            $this->lags['attachment'][] = $att;
        }
        return $this;
    }

    public function send($address = null, $body = null, $subject = null)
    {
        if ($address) {
            $this->addAddress($address);
        }
        if ($body) {
            $this->setBody($body);
        }
        if ($subject) {
            $this->setSubject($subject);
        }
        $res = $this->mail->send();
        if (!$res) {
            $this->logger->error('邮件发送失败', $this->logs);
        }
        return $res;
    }

	/**
	 * 插入邮件队列
	 * $address, $body, $subject
	 * @param array $params
	 */
    public function insertQueue(...$params)
	{
		$emailQueue = new EmailQueue();
		if (count($params) == 3) {
			if (!$params[0] || !$params[1] || !$params[2]) {
				return false;
			}
			$emailQueue->to = $params[0];
			$emailQueue->body = $params[1];
			$emailQueue->subject = $params[2];
			$emailQueue->from_name = '趣米停靠站';
			$emailQueue->level = 0;
		} elseif (count($params) == 1 && is_array($params[0])) {
		    $params = $params[0];
            if (!isset($params['to']) || !$params['to']) {
				return false;
			}
			$emailQueue->to = $params['to'];
			if (!isset($params['body']) || !$params['body']) {
				return false;
			}
			$emailQueue->body = $params['body'];

			if (!isset($params['subject']) || !$params['subject']) {
				return false;
			}
			$emailQueue->subject = $params['subject'];

			if (isset($params['attachment']) && $params['attachment']) {
				$emailQueue->attachment = json_encode($params['attachment']);
			}

			if (isset($params['from_name']) && $params['from_name']) {
				$emailQueue->from_name = $params['from_name'];
			} else {
				$emailQueue->from_name = '趣米停靠站';
			}
			$emailQueue->level = isset($params['level']) ? intval($params['level']) : 0;
		} else {
			return false;
		}
		$emailQueue->status = 1;
		$emailQueue->from = $this->config['username'];
		return $emailQueue->save();
	}
}