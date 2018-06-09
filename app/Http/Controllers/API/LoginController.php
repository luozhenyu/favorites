<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    protected $jscode2session = 'https://api.weixin.qq.com/sns/jscode2session';

    private $appId;
    private $appSecret;

    public function __construct()
    {
        $this->appId = env('WECHAT_APPID');
        $this->appSecret = env('WECHAT_SECRET');
    }


    /**
     * @param Request $request
     * @return array|mixed
     */
    public function login(Request $request)
    {
        $js_code = $request->input('js_code');

        $response = (new Client)->post($this->jscode2session, [
            'form_params' => [
                'appid' => $this->appId,
                'secret' => $this->appSecret,
                'js_code' => $js_code,
                'grant_type' => 'authorization_code',
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        if (key_exists('errcode', $result)) {
            return $result;
        }

        User::updateOrCreate([
            'openid' => $result['openid'],
        ], [
            'session_key' => $result['session_key'],
            'third_session' => ($third_session = str_random('128')),
        ]);

        return [
            'errcode' => 0,
            'third_session' => $third_session,
        ];
    }
}