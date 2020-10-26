<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\LoginPost;
use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class UserController extends Controller
{
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    /**
     * 后台用户登录
     *
     * @param LoginPost $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginPost $post)
    {
        $username = $post->username;
        $password = $post->password;
        if (Auth::attempt(['username'=>$username,'password'=>$password],false)){
            $store = $this->handle->getUserStore(Auth::id());
            if ($store){
                setStoreId($store->id);
            }
            return jsonResponse([
                'msg'=>'ok',
                'data'=>[
                    'role'=>$this->handle->getUserRole(Auth::id()),
                    'name'=>$username,
                    'store_id'=>getStoreId()
                ]
            ]);
        }
        return jsonResponse([
            'msg'=>'用户名或密码错误！'
        ],401);
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->flush();
        return jsonResponse([
            'msg'=>'ok'
        ]);

    }
    public function addUser(Request $post)
    {
        $id = $post->id?$post->id:0;
        $data = [
            'username'=>$post->username,
            'password'=>bcrypt($post->password),
            'phone'=>$post->phone,
        ];
        $role = $post->role?$post->role:0;
        if ($this->handle->addUser($id,$data,$role)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'ERROR'
        ],400);
    }
    public function getUsers()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getUsers($page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function listProxyApply()
    {
        $search = Input::get('search','');
        $state = Input::get('state',0);
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getProxyApplies($search,$search,$state,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function passProxyApply()
    {
        $id = Input::get('id');
        if ($this->handle->passProxyApply($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'当前状态不允许操作！'
        ],400);
    }
    public function rejectProxyApply()
    {
        $id = Input::get('id');
        if ($this->handle->rejectProxyApply($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'当前状态不允许操作！'
        ],400);
    }
    public function getProxyList()
    {
        $search = Input::get('search');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getProxyUsers($search,$search,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getWithdrawApplies()
    {
        $name = Input::get('name');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getWithdrawApplies($name,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function passWithdrawApply()
    {
        $withdraw = $this->handle->getWithdrawApply(Input::get('id'));
        if ($withdraw->state!=1){
            return jsonResponse([
                'msg'=>'当前状态不可用！'
            ],400);
        }
        $data = [
            'state'=>2
        ];
        if ($this->handle->addWithdrawApply($withdraw->id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ]);
    }
    public function rejectWithdrawApply()
    {
        $withdraw = $this->handle->getWithdrawApply(Input::get('id'));
        if ($withdraw->state!=1){
            return jsonResponse([
                'msg'=>'当前状态不可用！'
            ],400);
        }
        $data = [
            'state'=>3
        ];
        if ($this->handle->addWithdrawApply($withdraw->id,$data)){
            $this->handle->addUserAmount($withdraw->user_id,$withdraw->price);
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ]);
    }
    public function getBrokerageList()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $name = Input::get('name');
        if ($name){
            $user_id = $this->handle->getProxyUserIdByName($name);
            $data = $this->handle->getBrokerageList($page,$limit,$user_id);
        }else{
            $data = $this->handle->getBrokerageList($page,$limit);
        }

        $this->handle->formatUserBrokerageList($data['data']);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
