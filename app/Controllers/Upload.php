<?php
/**
 * Created by PhpStorm.
 * User: wuxin
 * Date: 2018/9/26
 * Time: 00:18
 */
namespace App\Controllers;

use App\Libraries\Config;
use App\Libraries\File;
use SebastianBergmann\CodeCoverage\Node\Directory;
use Slim\Http\Request;
use Slim\Http\Response;

class Upload extends Base
{

    /**
     * @pattern /upload/layui
     * @name upload.layui
     * @method post
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function layui(Request $request, Response $response, $args)
    {
        $conf = Config::upload('image');
        $files = $request->getUploadedFiles();
        $file = $files['file'];
        if (empty($file)) {
            return $response->withJson($this->retLayui(1, '上传失败'));
        }
        $size = $file->getSize();
        if ($conf['maxSize'] && $size>$conf['maxSize']) {
            return $response->withJson($this->retLayui(1, '上传文件过大，最大为:' . ($conf['maxSize']/100) .'kb'));
        }

        $type = $this->getType($file->getClientMediaType());
        if (!empty($conf['allows']) && !in_array($type, $conf['allows'])) {
            return $response->withJson($this->retLayui(1, '上传文件类型不符合，只允许上传类型:' . implode(',', $conf['allows'])));
        }
        $key = md5_file($file->file) . '.' . $type;
        $rootPath = $conf['path'];
        $path = 'upload/images';
        $fullPath = $rootPath.$path;
        if (!file_exists($fullPath) && !File::mkDir($fullPath)) {
            $this->log('error', '创建上传目录失败', [$fullPath]);
            return $response->withJson($this->retLayui(1, '创建上传目录失败:' . $fullPath));
        }

        if (!move_uploaded_file($file->file, $fullPath.DIRECTORY_SEPARATOR.$key)) {
            return $response->withJson($this->retLayui(3, '上传失败'));
        }
        return $response->withJson($this->retLayui(0, 'success', [
            'src' => $conf['domain'].'/'.$path.'/'.$key
        ]));
    }

    private function getType($mime)
    {
        $ret = '';
        if ($mime) {
            $mimes = Config::get('mimes');
            foreach ($mimes as $type => $item) {
                if (is_array($item)) {
                    if (in_array($mime, $item)) {
                        $ret = $type;
                        break;
                    }
                } else {
                    if ($mime == $item) {
                        $ret = $type;
                        break;
                    }
                }
            }
        }
        return $ret;
    }

    private function retLayui($code, $msg = '', $data=[])
    {
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
    }
}