<?php

namespace App\Http\Controllers;

use App\Category;
use App\Link;
use App\User;
use DiDom\Document;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Sukohi\LaravelReadability\Facades\LaravelReadability;

class APIController extends Controller
{
    function __construct()
    {
        $this->middleware('login', ['except' => 'login']);
    }


    public function login(Request $request)
    {
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $js_code = $request->input('js_code');

        $post_data = [
            'appid' => env('WECHAT_APPID'),
            'secret' => env('WECHAT_SECRET'),
            'js_code' => $js_code,
            'grant_type' => 'authorization_code',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (array_key_exists('errcode', $output)) {
            return response()->json($output);
        }

        $openid = $output['openid'];
        $session_key = $output['session_key'];
        $third_session = str_random('128');

        $user = User::firstOrNew(['openid' => $openid]);
        $user->session_key = $session_key;
        $user->third_session = $third_session;
        $user->save();

        return response()->json(['third_session' => $third_session]);
    }


    public function categoryIndex(Request $request)
    {
        $categories = Auth::user()->categories->map(function ($item, $key) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'color' => $item->color,
            ];
        });

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function categoryCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:categories,name|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'ermsg' => $validator->errors()->first(),
            ]);
        }

        $name = $request->input('name');
        $colors = ['#F44336', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4', '#009688', '#4CAF50', '#8BC34A', '#CDDC39', '#FFEB3B', '#FFC107', '#FF9800', '#FF5722', '#795548'];
        $color = $colors[array_rand($colors)];
        $category = Auth::user()->categories()->create([
            'name' => $name,
            'color' => $color,
        ]);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'color' => $category->color,
        ]);
    }

    public function categoryDelete(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json([
            'msg' => 'deleted.',
        ]);
    }


    public function linkIndex(Request $request)
    {
        $links = Auth::user()->links->map(function ($item, $key) {
            return [
                'id' => $item->id,
                'url' => $item->url,
                'title' => $item->title,
                'cover' => $item->cover,
                'category_id' => $item->category_id,
                'tags' => $item->tags,
                'abstract' => $item->abstract,
                'share_url' => ($share = $item->share) ? url('/share') . '/' . $share->code : null,
                'share_count' => $share ? $share->readCount : null,
            ];
        });
        return $links;
    }

    public function linkSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wd' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "errcode" => -1,
                "errmsg" => $validator->errors()->first(),
            ]);
        }
        $wd = $request->input('wd');

        $query = Auth::user()->links();
        $query = $query->whereRaw("MATCH(`title`,`tags`,`content`) AGAINST (? IN NATURAL LANGUAGE MODE)", $wd);

        $links = $query->get()->map(function ($item, $key) {
            return [
                'id' => $item->id,
                'url' => $item->url,
                'title' => $item->title,
                'cover' => $item->cover,
                'category_id' => $item->category_id,
                'tags' => $item->tags,
                'abstract' => $item->abstract,
                'share_url' => ($share = $item->share) ? url('/share') . '/' . $share->code : null,
                'share_count' => $share ? $share->readCount : null,
            ];
        });
        return $links;
    }

    public function linkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'title' => 'nullable',
            'category_id' => 'nullable|numeric|exists:categories,id,user_id,' . Auth::user()->id,
            'tags' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "errcode" => -1,
                "errmsg" => $validator->errors()->first(),
            ]);
        }

        $info = [];
        $info['url'] = $request->input('url');

        if ($ret = $this->getInfoFromUrl($info['url'])) {
            $info['title'] = $ret['title'];
            $info['cover'] = $ret['cover'];
            $info['abstract'] = $ret['abstract'];
            $info['content'] = $ret['content'];
        } else {
            $info['title'] = '无法打开网页';
            $info['cover'] = null;
            $info['abstract'] = '网页加载异常，请检查url';
            $info['content'] = '<p>网页加载异常，请检查url</p>';
        }

        if ($request->has('title')) {
            $info['title'] = $request->input('title');
        }
        $info['tags'] = $request->input('tags');
        $info['category_id'] = ($category_id = $request->input('category_id')) ? intval($category_id) : null;

        $link = Auth::user()->links()->create($info);

        return response()->json([
            'id' => $link->id,
            'url' => $link->url,
            'tags' => $link->tags,
            'category_id' => $link->category_id,
            'title' => $link->title,
            'cover' => $link->cover,
            'abstract' => $link->abstract,
            'content' => $link->content,
        ]);
    }


    public function linkDelete(Request $request, $id)
    {
        $link = Auth::user()->links()->findOrFail($id);
        $link->delete();

        return response()->json([
            'msg' => 'deleted.'
        ]);
    }

    public function linkModify(Request $request, $id)
    {
        $link = Auth::user()->links()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'title' => 'nullable',
            'category_id' => 'nullable|numeric|exists:categories,id,user_id,' . Auth::user()->id,
            'tags' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "errcode" => -1,
                "errmsg" => $validator->errors()->first(),
            ]);
        }

        $url = $request->input('url');
        $link->url = $url;
        if ($html = $this->getInfoFromUrl($url)) {
            $link->title = $html['title'];
            $link->cover = $html['cover'];
            $link->abstract = $html['abstract'];
            $link->content = $html['content'];
        } else {
            $link->title = '无法打开网页';
            $link->cover = null;
            $link->abstract = '网页加载异常，请检查url';
            $link->content = '<p>网页加载异常，请检查url</p>';
        }

        if ($request->has('title')) {
            $link->title = $request->input('title');
        }
        $link->tags = $request->input('tags');
        $link->category_id = ($category_id = $request->input('category_id')) ? intval($category_id) : null;

        $link->save();

        return response()->json([
            'id' => $link->id,
            'url' => $link->url,
            'tags' => $link->tags,
            'category_id' => $link->category_id,
            'title' => $link->title,
            'cover' => $link->cover,
            'abstract' => $link->abstract,
            'content' => $link->content,
        ]);
    }

    public function linkShow(Request $request, $id)
    {
        $link = Link::findOrFail($id);
        if (($share = $link->share) && Auth::user()->id !== $link->user->id) {
            $share->readCount++;
            $share->save();
        }

        return [
            'id' => $link->id,
            'url' => $link->url,
            'title' => $link->title,
            'cover' => $link->cover,
            'category_id' => $link->category_id,
            'tags' => $link->tags,
            'abstract' => $link->abstract,
            'content' => $link->content,
            'share_url' => ($share = $link->share) ? url('/share') . '/' . $share->code : null,
            'share_count' => $share ? $share->readCount : null,
        ];
    }

    public function linkShare(Request $request, $id)
    {
        $link = Auth::user()->links()->findOrFail($id);

        if (!$share = $link->share) {
            $share = $link->share()->create([
                'code' => $code = str_random(),
            ]);
        }

        return [
            'share_url' => url('/share') . '/' . $share->code,
        ];
    }


    private function getInfoFromUrl($url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 5,
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) {
            return false;
        }
        $readability = LaravelReadability::make($html);
        try {
            $ret = $readability->getContent();
        } catch (Exception $e) {
            return false;
        }
        $doc = $this->parseHTML((new Document(strip_tags($ret['content'], '<img><p><h1><h2><h3><h4>')))->first('html'));
        $content = null;
        $excerpt = null;
        foreach ($doc as $item) {
            if ($item['value']) {
                switch ($item['type']) {
                    case 'text':
                        if (!$excerpt) {
                            $excerpt = $item['value'];
                        }
                        $content .= '<p>' . $item['value'] . '</p>';
                        break;
                    case 'image':
                        $this->rel2abs($item['value'], $url);
                        $content .= '<img src="' . $this->rel2abs($item['value'], $url) . '">';
                        break;

                }
                $content .= "\n";
            }
        }

        return [
            'title' => $ret['title'],
            'cover' => $ret['lead_image_url'] ? $this->rel2abs($ret['lead_image_url'], $url) : null,
            'abstract' => $excerpt,
            'content' => $content,
        ];
    }

    /**
     * @param \DiDom\Element|\DOMElement|null $node
     * @return array
     */
    private function parseHTML($node)
    {
        $content = [];
        foreach ($node->children() as $child) {
            if ($child->isTextNode()) {
                if ($text = preg_replace('/^\s+/u', '', $child->text())) {
                    $content[] = [
                        'type' => 'text',
                        'value' => htmlentities(preg_replace('/[\n\s]+/u', ' ', $text)),
                    ];
                }
            } else if ($child->matches('img')) {
                $content[] = [
                    'type' => 'image',
                    'value' => $child->getAttribute('src') ?: $child->getAttribute('data-src'),
                ];
            } else {
                $content = array_merge($content, $this->parseHTML($child));
            }
        }
        return $content;
    }

    function rel2abs($rel, $base)
    {
        /* return if already absolute URL */
        if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

        /* queries and anchors */
        if ($rel[0] == '#' || $rel[0] == '?') return $base . $rel;

        /* parse base URL and convert to local variables:
           $scheme, $host, $path */
        extract(parse_url($base));

        /* remove non-directory element from path */
        $path = preg_replace('#/[^/]*$#', '', $path);

        /* destroy path if relative url points to root */
        if ($rel[0] == '/') $path = '';

        /* dirty absolute URL */
        $abs = "$host$path/$rel";

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
        }

        /* absolute URL is ready! */
        return $scheme . '://' . $abs;
    }
}