<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\User;
use App\Modules\HtmlParser;
use App\Modules\RichText;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LinkController extends Controller
{
    function __construct()
    {
        $this->middleware('login');
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $links = $user->links->map(function (Link $link) {
            return $link->summary;
        });

        return $links;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    public function search(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'wd' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errcode' => 1,
                'errmsg' => $validator->errors()->first(),
            ]);
        }

        $wd = $request->input('wd');

        $query = $user->links();
        $query = $query->whereRaw("MATCH(`title`, `tags`, `content`) AGAINST (? IN NATURAL LANGUAGE MODE)", $wd);

        $links = $query->get()
            ->map(function (Link $link) {
                return $link->summary;
            });
        return $links;
    }

    /**
     * @param Request $request
     * @return array|mixed
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:1024',
            'title' => 'nullable|max:256',
            'category_id' => 'nullable|exists:categories,id,user_id,' . $user->id,
            'tags' => 'nullable|max:256',
        ]);

        if ($validator->fails()) {
            return [
                'errcode' => 1,
                'errmsg' => $validator->errors()->first(),
            ];
        }

        try {
            $parser = HtmlParser::fromUrl($request->input('url'));
            $summary = $parser->getSummary();
        } catch (RequestException $requestException) {
            $content = $requestException->getResponse()->getBody()
                ->getContents();
            return [
                'errcode' => 1,
                'errmsg' => json_decode($content, true)['description'],
            ];
        }

        if ($request->has('title')) {
            $summary['title'] = $request->input('title');
        }
        if ($request->has('category_id')) {
            $summary['category_id'] = intval($request->input('category_id'));
        }

        $summary['tags'] = $request->input('tags');

        /** @var Link $link */
        $link = $user->links()->create($summary);

        return $link->summary;
    }


    /**
     * @param Request $request
     * @param $linkID
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $linkID)
    {
        /** @var User $user */
        $user = $request->user();

        $user->links()->where('id', $linkID)
            ->delete();

        return response()->json([
            'errcode' => 0,
            'msg' => 'deleted.'
        ]);
    }

    /**
     * @param Request $request
     * @param $linkID
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public function update(Request $request, $linkID)
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Link $link */
        $link = $user->links()->findOrFail($linkID);

        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:1024',
            'title' => 'nullable|max:256',
            'category_id' => 'nullable|exists:categories,id,user_id,' . $user->id,
            'tags' => 'nullable|max:256',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errcode' => 1,
                'errmsg' => $validator->errors()->first(),
            ]);
        }

        try {
            $parser = HtmlParser::fromUrl($request->input('url'));
            $summary = $parser->getSummary();
        } catch (RequestException $requestException) {
            $content = $requestException->getResponse()->getBody()
                ->getContents();
            return [
                'errcode' => 1,
                'errmsg' => json_decode($content, true)['description'],
            ];
        }

        if ($request->has('title')) {
            $summary['title'] = $request->input('title');
        }
        if ($request->has('category_id')) {
            $summary['category_id'] = intval($request->input('category_id'));
        }
        $summary['tags'] = $request->input('tags');

        $link->fill($summary);
        $link->save();

        return $link->summary;
    }

    /**
     * @param Request $request
     * @param $linkID
     * @return array
     */
    public function show(Request $request, $linkID)
    {
        /** @var User $user */
        $user = $request->user();
        $link = Link::findOrFail($linkID);

        if (($share = $link->share) && $user->id !== $link->user_id) {
            $share->increment('read_count');
        }

        return array_merge($link->summary, ['content' => RichText::load($link->content)]);
    }

    /**
     * @param Request $request
     * @param $linkID
     * @return array
     */
    public function share(Request $request, $linkID)
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Link $link */
        $link = $user->links()->findOrFail($linkID);

        if (!$share = $link->share) {
            $share = $link->share()->create([
                'code' => str_random(),
            ]);
        }

        return [
            'errcode' => 0,
            'share_url' => action('API\LinkController@share', $share->code),
        ];
    }
}