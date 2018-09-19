<?php
    $message = '';
    $db = FileDB::getInstance('./books.log');
    $G = array_merge($_GET,$_POST);
    $act = isset($G['act']) ? trim($G['act']) : null;
    if ($act == 'add') {
        $price = intval($G['price']);
        if ($G['name'] && $price) {
            if ($db->insert(array('name'=>$G['name'], 'price'=>$price))){
                $message = '添加图书成功！';
            } else {
                $message = '添加图书失败!';
            }
        }else {
            $message = '添加图书失败!';
        }
    } elseif ($act == 'del') {
        if ($G['_id']) {
            if ($db->del($G['_id'])){
                $message = '删除图书成功！';
            } else {
                $message = '删除图书失败!';
            }
        }
    } elseif ($act == 'jisuan') {
        $booksId = $G['books'];
        $bookCount = count($booksId);
        $books = [];
        $mixBook = [];
        $priceCount = 0;
        foreach ($db->getAll() as $item) {
            if (in_array($item['_id'], $booksId)) {
                if (empty($mixBook)) {
                    $mixBook = $item;
                } else {
                    if ($mixBook['price'] > $item['price']) {
                        $mixBook = $item;
                    }
                }
                $priceCount += $item['price'];
                array_push($books, $item);
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>图书</title>
</head>
<body>
<div style="width: 100%;text-align: center;padding: 10px;"><?php echo $message;?></div>
<div>
    <div style="width: 50%; height:240px;float: left">
        <form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
            <fieldset>
                <legend>添加图书:</legend>
                书名: <input type="text" name="name"><br>
                价钱: <input type="text" name="price"><br>
                <input type="hidden" name="act" value="add">
                <button type="submit">添加</button>
            </fieldset>
        </form>
    </div>
    <div style="width: 50%; height:240px;overflow:auto;float: left">
        <fieldset>
            <legend>所有图书:</legend>
            <table border="1">
                <thead>
                <tr>
                    <td>书名</td>
                    <td>价钱</td>
                    <td>操作</td>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($db->getAll() as $item):?>
                <tr>
                    <td><?php echo $item['name'];?></td>
                    <td><?php echo '¥'.$item['price'];?></td>
                    <td><a href="<?php echo $_SERVER['PHP_SELF'];?>?act=del&_id=<?php echo $item['_id'];?>">删除</a></td>
                </tr>
                <?php endforeach?>
                </tbody>
            </table>
        </fieldset>
    </div>
    <div style="width: 50%; height:240px;float: left">
        <form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
        <fieldset>
            <legend>选购图书:</legend>
            <?php foreach ($db->getAll() as $item):?>
                <input type="checkbox" name="books[]" value="<?php echo $item['_id'];?>" id="ip_<?php echo $item['_id'];?>"> <label for="ip_<?php echo $item['_id'];?>"><?php echo $item['name'].'(¥'.$item['price'].')';?></label>
            <?php endforeach?>
            <input type="hidden" name="act" value="jisuan">
            <br/>
            <button type="submit">计算价格</button>
        </fieldset>
        </form>
    </div>
    <div style="width: 50%; height:240px;float: left">

        <fieldset>
            <legend>计算价钱:</legend>
            <?php if ($act == 'jisuan'):?>
                <p>一共购买了 <?php echo $bookCount;?> 本书</p>
                <ul>
                    <?php foreach ($books as $item):?>
                    <li><?php echo $item['name'];?>:¥<?php echo $item['price'];?></li>
                    <?php endforeach;?>
                </ul>
                <?php if (count($booksId) >= 3):?>
                    <p>总数超过三件，其中《<?php echo $mixBook['name'];?>》价格最低，减免¥<?php echo $mixBook['price'];?></p>
                <?php endif;?>
                <p>总共：¥<?php echo count($booksId) >= 3 ? $priceCount-$mixBook['price'] : $priceCount;?></p>
            <?php endif;?>
        </fieldset>
    </div>
</div>
</body>
</html>

<?php
class FileDB
{
    static private $ins = null;

    private $file;

    private function __construct($file)
    {
        if (file_exists($file) && is_writable($file)){
            $this->file = $file;
        } else {
            if (!touch($file)) {
                echo ('数据文件不可写');
                exit;
            }
            $this->file = $file;
        }

    }

    static public function getInstance($file)
    {
        if (!self::$ins || !self::$ins instanceof self) {
            self::$ins = new self($file);
        }
        return self::$ins;
    }

    static private function genId()
    {
        return uniqid();
    }

    public function insert(array $book)
    {
        if (empty($book)) {
            return false;
        }
        $book['_id'] = self::genId();
        return $this->_w($book) ? $book['_id'] : false;
    }

    public function del($_id)
    {
        $res = $this->_r();
        foreach ($res as $k => $item) {
            if ($item['_id'] == $_id) {
                unset($res[$k]);
                break;
            }
        }
        return $this->_w($res, true);
    }
    public function getAll()
    {
        return $this->_r();
    }
    private function _w($data, $isClear=false)
    {
        if ($isClear) {
            return file_put_contents($this->file, serialize($data));
        }
        $res = $this->_r();
        array_push($res, $data);
        return file_put_contents($this->file, serialize($res));
    }

    private function _r()
    {
        $res = file_get_contents($this->file);
        return $res ? unserialize($res) : [];
    }
}

?>