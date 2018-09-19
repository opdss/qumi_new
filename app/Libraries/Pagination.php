<?php
/**
 * Pagination.php for zg-moudou.
 * @author SamWu
 * @date 2017/7/4 14:08
 * @copyright istimer.com
 */
namespace App\Libraries;

class Pagination {

    private $total = 0; //数据表中总记录数
    private $pageSize = 10; //每页显示行数
    private $listNum = 4; //分页列表显示数量
    private $pageName = 'page'; //当前分页参数，默认是page
	private $query = array();

	private $page = 1;// 当前页
	private $pageTotal; //页数

    private $path;

    //可初始化参数
    private $initProp = [
    	'total', 'pageSize', 'listNum', 'pageName', 'query',
	];

    public $config = array(
        "prev" => "【上一页】",
        "next" => "【下一页】",
        "first" => "【首 页】",
        "last" => "【尾 页】",
        'div_prev' => '<div>',
        'div_next' => '</div>',
    );

    public $temp = array(
        'total' => '&nbsp;共有<b>{#total}</b>个记录&nbsp;',
        'info' => '&nbsp;每页显示<b>{#pageSize}</b>条，本页<b>{#start}-{#end}</b>条&nbsp;',
        'limit' => '&nbsp;<b>{#page}/{#pageTotal}</b>页&nbsp;',

        'list_active' => '&nbsp;<a href="{#url}">{#page}</a>&nbsp;', //分页列表没有链接模版
        'list_not_active' => '&nbsp;<a href="javascript:void(0)">{#page}</a>&nbsp;',//分页列表有链接模版
        'fl_active' => '&nbsp;<a href="{#url}">{#title}</a>&nbsp;', //首页尾页有链接模版
        'fl_not_active' => '&nbsp;{#title}&nbsp;', //首页尾页没有链接模版
        'pn_active' => '&nbsp;<a href="{#url}">{#title}</a>&nbsp;',  //上一页下一页有链接模版
        'pn_not_active' => '&nbsp;{#title}&nbsp;', //上一页下一页没有链接模版
        'go_page' => '&nbsp;<input type="text" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>{#pageTotal})?{#pageTotal}:this.value;location=\'{#baseUrl}&{#pageName}=\'+page;}" value="{#page}" style="width:25px"><input type="button" value="GO" onclick="javascript:var page=(this.previousSibling.value>{#pageTotal})?{#pageTotal}:this.previousSibling.value;location=\'{#baseUrl}&{#pageName}=\'+page;">&nbsp;'
    );

    /*
     * $total
     * $pageSize
     */
    public function __construct($total, $pageSize = 10, $query = array()) {
    	if (is_array($total)) {
    		foreach ($total as $k=>$v) {
    			if (in_array($k, $this->initProp)) {
					$this->{$k} = $v;
				}
				if ($k == 'query') {
					$this->query = $this->getQuery($v);
				}
			}
		} else {
			$this->total = $total;
			$this->pageSize = $pageSize;
			$this->query = $this->getQuery($query);
		}
		if (isset($_GET[$this->pageName]) && intval($_GET[$this->pageName])) {
			$this->page = intval($_GET[$this->pageName]);
		}
        $this->pageTotal = ceil($this->total / $this->pageSize);
    }

    public function setTemp(...$params) {
        if (count($params) == 1) {
            if (is_array($params[0])) {
                $this->temp = array_merge($this->temp, $params[0]);
            }
        } else {
            if (is_string($params[1])) {
                $this->temp[$params[0]] = $params[1];
            }
        }
        return $this;
    }

    public function setConfig(...$params) {
        if (count($params) == 1) {
            if (is_array($params[0])) {
                $this->config = array_merge($this->config, $params[0]);
            }
        } else {
            if (is_string($params[1])) {
                $this->config[$params[0]] = $params[1];
            }
        }
        return $this;
    }

	/**
	 * 模板替换
	 * @param $temp
	 * @param $data
	 * @return mixed
	 */
    private function assign($temp, $data) {
        $search = array();
        $value = array();
        foreach ($data as $k => $v) {
            $search[] = '{#'.$k.'}';
            $value[] = $v;
        }
        return str_replace($search, $value, $this->temp[$temp]);
    }

	/**
	 * 动态获取url Query参数
	 * @param $querys 主动设定的query参数
	 * @return array
	 */
    private function getQuery($querys) {
        $url = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        $parse = parse_url($url);
        $query = array();
        if (isset($parse["query"])) {
            parse_str($parse['query'], $query);
        }
        $query = array_merge($query, $querys);
        if (isset($query[$this->pageName])) unset($query[$this->pageName]);
        $this->path = $parse['path'];
        return $query;
    }

	/**
	 * 获取某个分页的完整url
	 * @param int $page
	 * @return string
	 */
    private function getUrl($page=0) {
        $query = $this->query;
        $page AND $query[$this->pageName] = $page;
        return $this->path . '?' . http_build_query($query);
    }

	/**
	 * 开始位置
	 * @return int
	 */
	private function start() {
		if ($this->total == 0) {
			return 0;
		} else {
			return ($this->page - 1) * $this->pageSize + 1;
		}
	}

	/**
	 * 结束位置
	 * @return mixed
	 */
	private function end() {
		return min($this->page * $this->pageSize, $this->total);
	}

    private function getTotal(){
    	return $this->assign('total', array('total'=>$this->total));
	}

	private function getInfo(){
		return $this->assign('info', array('pageSize'=>$this->pageSize, 'start'=> $this->start(), 'end' => $this->end()));
	}

	private function getLimit(){
		return $this->assign('limit', array('page'=>$this->page, 'pageTotal'=>$this->pageTotal));
	}
	private function getGoPage(){
		return $this->assign('go_page', array('baseUrl'=>$this->getUrl(), 'pageTotal'=>$this->pageTotal, 'pageName'=>$this->pageName, 'page'=>$this->page));
	}


	/**
	 * 第一页
	 * @return string
	 */
    private function getFirst() {
        $html = "";
        if ($this->page == 1) {
			$html .= $this->assign('fl_not_active', array('url' => 'javascript:void(0)', 'title' => $this->config["first"]));
		} else {
			$html .= $this->assign('fl_active', array('url' => $this->getUrl(1), 'title' => $this->config["first"]));
		}
        return $html;
    }

	/**
	 * 上一页
	 * @return string
	 */
    private function getPrev() {
        $html = "";
        if ($this->page == 1) {
			$html .= $this->assign('pn_not_active', array('url' => 'javascript:void(0)', 'title' => $this->config["prev"]));
		} else {
			$html .= $this->assign('pn_active', array('url' => $this->getUrl($this->page - 1), 'title' => $this->config["prev"]));
		}
        return $html;
    }

	/**
	 * 页码列表
	 * @return string
	 */
    private function getPageList() {
        $linkPage = "";
        if ($this->pageTotal <= $this->listNum) {
        	for ($i = 1; $i <= $this->pageTotal; $i++) {
        		if ($i == $this->page) {
					$linkPage .= $this->assign('list_not_active', array('url'=>'javascript:void(0)', 'page'=>$i));
				} else {
					$linkPage .= $this->assign('list_active', array('url'=>$this->getUrl($i), 'page'=>$i));
				}
			}
		} else {
			$inum = floor($this->listNum / 2);
			$offset = $this->page - $inum;
			$offset = $offset > 0 ? $offset : 1;
			for ($i = 0; $i< $this->listNum; $i++) {
				$page = $offset + $i;
				if ($page > $this->pageTotal) {
					break;
				}
				if ($page == $this->page) {
					$linkPage .= $this->assign('list_not_active', array('url'=>'javascript:void(0)', 'page'=>$page));
				} else {
					$linkPage .= $this->assign('list_active', array('url'=>$this->getUrl($page), 'page'=>$page));
				}
			}
		}
        return $linkPage;
    }

	/**
	 * 下一页
	 * @return string
	 */
    private function getNext() {
        $html = "";
        if ($this->page == $this->pageTotal) {
			$html .= $this->assign('pn_not_active', array('url' => 'javascript:void(0)', 'title' => $this->config["next"]));
		} else {
			$html .= $this->assign('pn_active', array('url' => $this->getUrl($this->page + 1), 'title' => $this->config["next"]));
		}
        return $html;
    }

	/**
	 * 最后一页
	 * @return string
	 */
    private function getLast() {
        $html = "";
        if ($this->page == $this->pageTotal) {
			$html .= $this->assign('fl_not_active', array('url' => 'javascript:void(0)', 'title' => $this->config["last"]));
		} else {
			$html .= $this->assign('fl_active', array('url' => $this->getUrl($this->pageTotal), 'title' => $this->config["last"]));
		}
        return $html;
    }

    private function getSelectNum()
	{
		$options = [10, 20, 30, 50, 100];
		$html = '<select>';
		foreach ($options as $num) {
			$html .= '<option value="10" '.($this->pageSize == $num ? 'selected' : '').'>每页'.$num.'条</option>';
		}
		$html .= '</select>';
		return '<li>'.$html.'</li>';
	}

	/**
	 * 输出
	 * @param array $display
	 * @return mixed|string
	 */
    function fpage($display = array(0, 1, 2, 3, 4, 5, 6, 7, 8)) {
    	//映射模块方法
    	$map = [
    		0 => 'getTotal',
    		1 => 'getInfo',
    		2 => 'getLimit',
    		3 => 'getFirst',
    		4 => 'getPrev',
    		5 => 'getPageList',
    		6 => 'getNext',
    		7 => 'getLast',
    		8 => 'getGoPage',
    		//9 => 'getSelectNum',
		];
        $fpage = $this->config['div_prev'];
        foreach ($display as $index) {
        	if(isset($map[$index])) {
				$fpage .= $this->{$map[$index]}();
			}
        }
        $fpage .= $this->config['div_next'];
        return $fpage;
    }

}
