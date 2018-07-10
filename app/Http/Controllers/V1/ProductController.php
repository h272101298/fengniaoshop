<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProductCategoryPost;
use App\Http\Requests\ProductTypePost;
use App\Libraries\Wxxcx;
use App\Modules\Product\Model\Product;
use App\Modules\Store\Model\Store;
use App\Modules\User;
use function foo\func;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class ProductController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function createProductType(ProductTypePost $post)
    {
        $id = $post->id?$post->id:0;
        $parent = $post->parent?$post->parent:0;
        $data = [
            'title'=>$post->title,
            'logo'=>$post->logo?$post->logo:''
        ];
        if ($this->handle->addProductType($id,$data,$parent)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function getProductTypes()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $title = Input::get('title');
        $level = Input::get('level',0);
        $parent = Input::get('parent',0);
        $types = $this->handle->getProductTypes($page,$limit,$title,$level,$parent);
        $this->handle->formatProductTypes($types['data']);
        return response()->json([
            'msg'=>'ok',
            'data'=>$types
        ]);
    }
    public function delProductType()
    {
        $id = Input::get('id');
        if ($this->handle->delProductType($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function getProductTypesTree()
    {
        $data = $this->handle->getProductTypesTree();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addProductCategory(ProductCategoryPost $post)
    {
        $categories = $post->categories;
        $type_id = $post->type_id;
        foreach ($categories as $category){
            $id = isset($category['id'])?$category['id']:0;
            $detail = $category['detail'];
            $detail = array_column($detail,'content');
            $this->handle->addProductCategory($id,$type_id,$category['title'],getStoreId(),$detail);
        }
        return jsonResponse([
            'msg'=>'ok'
        ],200);
    }
    public function delProductCategory()
    {
        $id = Input::get('id');
        if ($this->handle->delProductCategory($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'参数错误！'
        ],400);
    }
    public function getProductCategories()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $title = Input::get('title','');
        $data = $this->handle->getProductCategories(getStoreId(),$page,$limit,$title);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addProduct(Request $post)
    {
        $id = $post->id?$post->id:0;
        $data = [
            'store_id'=>getStoreId(),
            'name'=>$post->name,
            'detail'=>$post->detail,
            'brokerage'=>$post->brokerage,
//            'express'=>$post->express,
            'express'=>0,
//            'express_price'=>$post->express_price,
            'express_price'=>0,
            'share_title'=>$post->share_title,
            'share_detail'=>$post->share_detail,
            'type_id'=>$post->type_id,
            'norm'=>$post->norm,
        ];
        $stock = $post->stock;
        $norm = $post->norm;
        $product_id = $this->handle->addProduct($id,$data);
        if ($product_id){
            foreach ($stock as $item){
                if ($norm!='fixed'){
                    $swap = [];
                    foreach ($item['detail'] as $detail){
                        $detail_id = $this->handle->addProductCategorySnapshot($product_id,$detail);
                        array_push($swap,$detail_id);
                    }
                    sort($swap);
                    $detail = implode(',',$swap);
                }else{
                    $detail = 'fixed';
                }
                $stockData = [
                    'product_id'=>$product_id,
                    'cover'=>$item['cover'],
                    'price'=>$item['price'],
                    'origin_price'=>$item['origin_price'],
                    'product_detail'=>$detail
                ];
                $images = $item['images'];
                $stock_id = $this->handle->addStock($id,$stockData);
                foreach ($images as $image){
                    $this->handle->addStockImage($stock_id,$image);
                }
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function getProducts()
    {
        $state = Input::get('state');
        $name = Input::get('name');
        $deleted = Input::get('deleted',1);
        $storeId = $this->handle->getStoresId($name);
        $type_id = $this->handle->getProductTypesId($name);
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        if (checkPermission(Auth::id(),'productlistall')) {
            $data = $this->handle->getProducts($storeId,$type_id,$page,$limit,$name,0,$state,$deleted);
        }else{
            $data = $this->handle->getProducts([getStoreId()],$type_id,$page,$limit,$name,0,$state,$deleted);
        }
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function delProduct()
    {
        $id = Input::get('id',0);
        if ($this->handle->delProduct($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            '操作失败！'
        ]);
    }
    public function softDelProduct()
    {
        $id = Input::get('id',0);
        $product = $this->handle->getProductById($id);
        if ($product->deleted==1) {
            $data = [
                'deleted' => 0
            ];
            if ($this->handle->addProduct($id,$data)){
                return jsonResponse([
                    'msg'=>'ok'
                ]);
            }
        }
        if ($this->handle->softDelProduct($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            '操作失败！'
        ]);
    }
    public function getProductsApi()
    {
        $name = Input::get('name');
        $type = Input::get('type');
        $data = $this->handle->getProductsApi($name,$type);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getProductApi()
    {
        $id = Input::get('id');
        $product = $this->handle->getProduct($id);
        $user_id = getRedisData(Input::get('token'))?getRedisData(Input::get('token')):0;
        $product->collect = $this->handle->checkCollect($user_id,$product->id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$product
        ]);
    }
    public function getProductAssesses()
    {
        $product_id = Input::get('product_id');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getProductAssesses($product_id,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function checkProduct()
    {
        $id = Input::get('id');
        $product = $this->handle->getProductById($id);
        $review = $product->review==0?1:0;
        $data = [
            'review'=>$review
        ];
        if ($this->handle->addProduct($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作错误！'
        ],400);
    }
    public function shelfProduct()
    {
        $id = Input::get('id');
        $product = $this->handle->getProductById($id);
        $state = $product->state==0?1:0;
        $data = [
            'state'=>$state
        ];
        if ($this->handle->addProduct($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作错误！'
        ],400);
    }
    public function getStock()
    {
        $product_id = Input::get('product_id');
        $detail = Input::get('detail');
        if ($detail){
            $detail = explode(',',$detail);
            sort($detail);
            $detail = array_filter($detail,function ($item){
                return $item!='';
            });
            $detail = implode(',',$detail);
        }
        $stock = $this->handle->getStock($product_id,$detail);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$stock
        ]);
    }
    public function addCart(Request $post)
    {
        $user = getRedisData($post->token);
        $stock = $this->handle->getStockById($post->stock_id);
        $product = $this->handle->getProductById($stock->product_id);
        if ($this->handle->addCart($user,$stock->id,$product->store_id,$post->number)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function delCarts()
    {
        $id = Input::get('id');
        $id = explode(',',$id);
        if ($this->handle->delCarts($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }

    }
    public function getCarts()
    {
        $user_id = getRedisData(Input::get('token'));
        $carts = $this->handle->getCarts($user_id);
        $carts = $this->handle->formatCarts($carts);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$carts
        ]);
    }
    public function addCollect()
    {
        $product_id = Input::get('product_id');
        $user_id = getRedisData(Input::get('token'));
        $count = $this->handle->checkCollect($user_id,$product_id);
        if ($count!=0){
            return jsonResponse([
                'msg'=>'已收藏该商品！'
            ],400);
        }
        if ($this->handle->addCollect($user_id,$product_id)){
            return jsonResponse(['msg'=>'收藏成功！']);
        }
        return jsonResponse(['msg'=>'操作失败！'],400);
    }
    public function getCollects()
    {
        $user_id = getRedisData(Input::get('token'));
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $collects = $this->handle->getUserCollect($user_id,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$collects
        ]);
    }
    public function delCollect()
    {
        $id = Input::get('id');
        if ($this->handle->delCollect($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ]);
    }
    public function getProductQrCode()
    {
        $project_id = Input::get('project_id');
        $wx =  new Wxxcx(config('weChat.appId'),config('weChat.appSecret'));
        $data = [
            'scene'=>"project_id=" . $project_id,
            'page'=>"pages/goods/detail/detail"
        ];
        $data = json_encode($data);
        $token = $wx->getAccessToken();
        $qrcode = $wx->get_http_array('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$token['access_token'],$data,'json');
        return response()->make($qrcode,200,['content-type'=>'image/gif']);
    }
    public function addHot()
    {
        $product_id = Input::get('product_id');
        if ($this->handle->addHot($product_id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
//        $notifyList = $this->handle->getNotifyList();
//        $product = $this->handle->getProductById($product_id);
//        $stock = $this->handle->getStockById()
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function addNew()
    {
        $product_id = Input::get('product_id');
        if ($this->handle->addNew($product_id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function addOffer()
    {
        $product_id = Input::get('product_id');
        if ($this->handle->addOffer($product_id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function getRecommendList()
    {
        $type = Input::get('type');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        switch ($type){
            case 'hot':
                $data = $this->handle->getHotList($page,$limit);
                break;
            case 'new':
                $data = $this->handle->getNewList($page,$limit);
                break;
            case 'offer':
                $data = $this->handle->getOfferList($page,$limit);
                break;
            default:
                $data = $this->handle->getHotList($page,$limit);
                break;
        }
        $this->handle->formatRecommendList($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addHotType()
    {
        $type_id = Input::get('type_id');
        if ($this->handle->addHotType($type_id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'已超出热门分类数量限制！'
        ],400);
    }
    public function getHotTypes()
    {
        $data = $this->handle->getHotTypes();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }

}
