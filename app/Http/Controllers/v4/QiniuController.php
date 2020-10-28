<?php

namespace App\Http\Controllers\v4;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Qiniu\Auth;

class QiniuController extends Controller
{
    //
    private $accessKey = "jIFPDW2kqbYITI0cBU8wxLdi0W420Ieb98cB7Qou";
    private $secretKey = "9ObYhaaeQMC0VBWl0oNQb36CjTf4zeZxE5RXCgqB";
    private $bucket = "fengniaoshangcheng";
    public function getToken(){
        $auth=new Auth($this->accessKey,$this->secretKey);
        $uptoken=$auth->uploadToken($this->bucket);

        $ret=['uptoken'=>$uptoken];
        return response()->json($ret);
    }
}
