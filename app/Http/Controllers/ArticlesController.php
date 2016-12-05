<?php

namespace App\Http\Controllers;

use App\Jobs\ConfrimOrderQueue;
use App\Jobs\UpdateEscQueue;
use App\Models\WechatImage;
use App\Models\WechatTxt;
use App\Models\WechatVoice;
use App\WeChat\Count;
use App\WeChat\Order;
use App\WeChat\Response;
use App\WeChat\Usage;
use Carbon\Carbon;
use DB;
use App\Models\WechatArticle;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\Image;
use EasyWeChat\Message\News;
use Illuminate\Http\Request;
use EasyWeChat\Message\Text;
use Illuminate\Support\Facades\Input;
use App\WeChat\Tour;
use App\Http\Requests;

//use Illuminate\Support\Facades\Cache;

class ArticlesController extends Controller
{
    public $app;
    public $js;
    public $count;
    public $usage;

    public function __construct(Application $app)
    {
        $this->app=$app;
        $this->js=$this->app->js;
        $this->count=new Count();
        $this->usage=new Usage();
    }

    public function second_article($sellid,$openid,$info_id)
    {
        $usage = new Usage();
        $openid = $usage->authcode($openid, 'DECODE', 0);

        //设置se_info_send阅读
        DB::table('se_info_send')
            ->where('sellid',$sellid)
            ->where('wx_openid',$openid)
            ->where('info_id',$info_id)
            ->update(['is_read'=>1,'readtime'=>Carbon::now()]);

        //增加阅读数
        DB::table('se_info_detail')
            ->where('id',$info_id)
            ->increment('hits');

        //找出对应url并跳转
        $row=DB::table('se_info_detail')
            ->where('id',$info_id)
            ->first();

        $this->count->insert_hits('1285',$openid);
        $url='http://e.hengdianworld.com/WeixinOpenId.aspx?nexturl='.$row->article_url;
        return redirect($url);
    }

    public function second_article_detail(Request $request)
    {
        $id=$request->input('id');
        $openid=$request->input('openid');
        $article = WechatArticle::find($id);
        if (!$article || $article->online=='0' ||$article->enddate<Carbon::now())
        {
            abort(404);
        }
        else {
            return view('articles.seconddetail', compact('article', 'id', 'openid'));
        }
    }


    public function index()
    {
        $articles = DB::table('wx_article')->where('title', 'like', '门票%')->orderBy('id', 'desc')->skip(0)->take(2)->get();
        return view('articles.index', compact('articles'));
    }

    public function show($id)
    {
        $article = WechatArticle::find($id);

        return view('articles.show', compact('article'));
    }

    public function detail(Request $request)
    {
        $id=$request->input('id');
        $wxnumber=$request->input('wxnumber');

        $wxnumber=$this->usage->authcode($wxnumber,'DECODE',0);
        $openid=$request->input('openid');

        if ($wxnumber)
        {
            $openid=$wxnumber;
        }

        $article = WechatArticle::find($id);
        if (!$article || $article->online=='0' ||$article->enddate<Carbon::now())
        {
            abort(404);
        }
        else {
            $temp_rando=mt_rand();
//            $temp_rando=1;
            DB::table('a_test')
                ->insert(['test'=>$temp_rando,'wx_openid'=>$openid]);

            $this->count->add_article_hits($id);
            $this->count->insert_hits($id,$openid);

            return view('articles.detail', compact('article', 'id', 'openid','temp_rando'));
        }
    }

    public function aaa(Request $request)
    {
        $id=$request->input('id');
        $openid=$request->input('openid');
        $this->count->insert_hits($id,$openid);
    }

}
