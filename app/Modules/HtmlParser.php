<?php

namespace App\Modules;

use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;
use andreskrey\Readability\Readability;
use DiDom\Document;
use DiDom\Query;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class HtmlParser
{
    protected $html;

    protected $document;


    protected $targetUrl;

    /**
     * HtmlParser constructor.
     * @param string $html
     * @param null $targetUrl
     */
    public function __construct(string $html, $targetUrl = null)
    {
        $this->html = preg_replace('/<script[\s\S]*?<\/script>/i', '', $html);
        $this->document = new Document($this->html);
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

        $defaultConfig = [
            'lua_source' => static::getLuaSource(),
            'url' => $url,
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:60.0) Gecko/20100101 Firefox/60.0',
            'wait' => 0.5,
        ];

        $customConfig = [
            'mp.weixin.qq.com' => [
                'wait' => 2,
            ],
        ];

        foreach ($customConfig as $key => $config) {
            if (str_contains($url, $key)) {
                $defaultConfig = array_merge($defaultConfig, $config);
            }
        }

        $response = $client->get($splashUrl, ['query' => $defaultConfig]);
        $content = $response->getBody()->getContents();
        return json_decode($content, true);
    }

    /**
     * @return array
     */
    public function getSummary()
    {
        if (strlen($this->html) < 512) {
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

        $title = trim($readability->getTitle());
        $cover = optional($readability->getImages())[0];
        $abstract = trim($readability->getExcerpt());
        $content = trim(clean($readability->getContent()));

        $customConfig = [
            'mp.weixin.qq.com' => function () use (&$title, &$cover, &$content) {
                $title = null;
                if ($cover) {
                    $cover = str_replace('tp=webp', '', $cover);
                    $cover = str_replace('http://', 'https://', $cover);
                }
                $content = str_replace('tp=webp', '', $content);
            },
        ];

        foreach ($customConfig as $key => $callback) {
            if (str_contains($this->targetUrl, $key)) {
                $callback();
            }
        }

        if (empty($abstract)) {
            $contentDocument = new Document($content);
            $firstTextNode = $contentDocument->first('//p[string-length() > 6]', Query::TYPE_XPATH);
            $abstract = trim($firstTextNode->text());
        }

        if (empty($title)) {
            if ($firstHeaderNode = $this->document->first('(//h1|//h2|//h3|//h4|//h5|//p)[string-length() > 3]', Query::TYPE_XPATH)) {
                $title = trim($firstHeaderNode->text());
            } else {
                $title = substr($abstract, 0, 10);
            }
        }

        return [
            'title' => $title,
            'cover' => $cover,
            'abstract' => $abstract,
            'content' => $content,
        ];
    }

    /**
     * @return string
     */
    protected static function getLuaSource()
    {
        return <<<LUA
function main(splash)
    splash:set_user_agent(splash.args.user_agent)
    splash:set_viewport_size(640, 10000)
    
    assert(splash:go(splash.args.url))
    assert(splash:wait(1))
    
    splash:set_viewport_size(640, 1)
    assert(splash:wait(0.1))
    assert(splash:set_viewport_full())
    assert(splash:wait(splash.args.wait))
    
    return {
        html = splash:html(),
        --png = splash:png(),
    }
end
LUA;
    }

}