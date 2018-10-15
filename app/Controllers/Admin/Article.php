<?php
/**
 * Article.php for qumi_new
 * @author SamWu
 * @date 2018/9/25 16:14
 * @copyright boyaa.com
 */
namespace App\Controllers\Admin;

use App\Controllers\Base;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Article
 * @middleware App\Middleware\Auth|App\Middleware\Rtime
 * @package App\Controllers\Admin
 */
class Article extends Base
{
	/**
	 * @pattern /admin/article
	 * @auth admin|文章管理
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{
		$data = [];
		return $this->view('admin/article/index.twig', $data);
	}

	/**
	 * @pattern /admin/article/create
	 * @auth admin
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function create(Request $request, Response $response, $args)
	{
		$data = [];
		return $this->view('admin/article/create.twig', $data);
	}

	/**
	 * @pattern /admin/articles
	 * @auth admin
	 * @name api.admin.article.get
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */
	public function get(Request $request, Response $response, $args)
	{
		$data = [];
		return $this->json($data);
	}
}