<?php

namespace App\Modules;

use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;
use andreskrey\Readability\Readability;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class HtmlParser
{
    protected $html;

    protected $targetUrl;

    /**
     * HtmlParser constructor.
     * @param string $html
     * @param null $targetUrl
     */
    public function __construct(string $html, $targetUrl = null)
    {
        $this->html = $html;
        $this->targetUrl = $targetUrl;
    }

    /**
     * @param string $targetUrl
     * @return HtmlParser
     */
    public static function fromUrl(string $targetUrl)
    {
        $parts = static::requestHtml($targetUrl);
        return new static($parts['html'], $targetUrl);
    }

    /**
     * @param string $url
     * @throws RequestException
     * @return array
     */
    protected static function requestHtml(string $url)
    {
        $client = new Client;

        $splashUrl = rtrim(env('SPLASH_URL'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'execute';
        $response = $client->get($splashUrl, [
            'query' => [
                'lua_source' => static::getLuaSource(),

                'url' => $url,
                'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                'wait' => 0.5,
            ],
        ]);

        $content = $response->getBody()->getContents();
        return json_decode($content, true);
    }

    /**
     * @return string
     */
    protected static function getLuaSource()
    {
        return <<<LUA
function main(splash)
    splash:set_user_agent(splash.args.user_agent)
    assert(splash:go(splash.args.url))
    assert(splash:wait(splash.args.wait))
    return {
        html = splash:html(),
        png = splash:png(),
    }
end
LUA;
    }

    /**
     * @return array
     */
    public function getSummary()
    {
        if (strlen($this->html) < 200) {
            $summary = [
                'title' => '网页长度过短',
                'cover' => null,
                'abstract' => '网页长度过短，请直接访问',
                'content' => '<p>网页长度过短，请直接访问</p>',
            ];
        } else {
            try {
                $summary = $this->parseHtml();
            } catch (ParseException $e) {
                $summary = [
                    'title' => '网页解析异常',
                    'cover' => null,
                    'abstract' => '网页解析异常，请检查网页是否允许访问',
                    'content' => '<p>网页解析异常，请检查网页是否允许访问</p>',
                ];
            }
        }

        $summary['url'] = $this->targetUrl;
        return $summary;
    }

    /**
     * @return array
     * @throws ParseException
     */
    protected function parseHtml()
    {
        $configuration = new Configuration();
        $configuration->setFixRelativeURLs(true)
            ->setOriginalURL($this->targetUrl);

        $readability = new Readability($configuration);
        $readability->parse($this->html);

        return [
            'title' => trim($readability->getTitle()),
            'cover' => optional($readability->getImages())[0],
            'abstract' => $readability->getExcerpt(),
            'content' => clean($readability->getContent()),
        ];
    }
}