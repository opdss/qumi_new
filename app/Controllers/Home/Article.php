<?php
/**
 * Article.php for qumi
 * @author SamWu
 * @date 2018/8/3 11:08
 * @copyright boyaa.com
 */
namespace App\Controllers\Home;

use App\Controllers\Base;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class Article
 * @middleware App\Middleware\Rtime
 * @package App\Controllers
 */
class Article extends Base
{

	/**
	 * //@pattern /article
	 * @name article
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function index(Request $request, Response $response, $args)
	{

	}

	/**
	 * //@pattern /article/{id:[0-9]+}
	 * @name article.detail
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function detail(Request $request, Response $response, $args)
	{
		$id = intval($args['id']);
		$data = [];
		if (!$id || !($articleModel = \App\Models\Article::find($id))) {
			return $this->view('404.twig',$data);
		}
		$data['article'] = $articleModel;
		return $this->view('article/detail.twig', $data);
	}
}