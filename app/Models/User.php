<?php
/**
 * User.php for deploy.
 * @author SamWu
 * @date 2018/7/4 17:12
 * @copyright istimer.com
 */

namespace App\Models;

class User extends Base
{
	/**
	 * 表名
	 * @var string
	 */
	protected $table = "user";

	/**
	 * 主键
	 * @var string
	 */
	protected $primaryKey = 'uid';


	/**
	 * The attributes that should be mutated to dates.
	 * @var array
	 */
	protected $dates = ['deleted_at'];

	/**
	 * toArray时在数组中想要隐藏的属性。
	 *
	 * @var array
	 */
	protected $hidden = ['password'];

	/**
	 * toArray时在数组中可见的属性。
	 *
	 * @var array
	 */
	//protected $visible = ['username', 'nickname'];

	/**
	 * 用户的ns
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function userNs()
	{
		return $this->hasMany('\App\Models\UserNs', 'uid', 'uid');
	}

	public function resetPassword($password)
    {
        $this->password = self::passwordHash($password);
        return $this->save();
    }

	/**
	 * 邮箱和密码检验用户
	 * @param $email
	 * @param $password
	 * @return bool
	 */
	public static function login($email, $password)
	{
		if (!$email || !$password) {
			return false;
		}
		$user = self::where('email', $email)->first();
		if (empty($user) || !self::passwordVerify($password, $user->password)) {
			return false;
		}
		return $user;
	}

	public static function passwordHash($password)
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}

	public static function passwordVerify($password, $hash)
	{
		return password_verify($password, $hash);
	}

    /**
     * 设置用户默认模版
     * @param $template_id
     * @return bool
     */
	public function setDefTemplate($template_id)
	{
		$this->def_template_id = $template_id;
		return $this->save();
	}
}