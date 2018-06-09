<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $link->title }}</title>

    <link rel="stylesheet" href="https://res.wx.qq.com/open/libs/weui/1.1.2/weui.min.css">

    <style>
        body {
            padding: 10px;
        }

        p {
            text-indent: 2em;
        }

        img {
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
        }

        .info {
            margin-right: 20px;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
<h2 class="title center">{{ $link->title }}</h2>
<section style="text-align: right">
    <span class="info">分享者ID：{{ $link->user->id }}</span>
    <span class="info">阅读次数：{{ $share->read_count }}</span>
</section>

<article class="weui-article">
    {!! $link->content !!}
</article>

<footer class="weui-footer">
    <p>Copyright &copy; 2017 - {{ date('Y') }} favorites.luozy.cn</p>
</footer>
</body>
</html>
