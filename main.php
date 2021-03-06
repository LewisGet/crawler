<?php

$url = $argv[1];
$level = $argv[2];

class crawler
{
    public $baseUrl = "";
    public $allUrls = array();
    public $level = 0;
    public $nowLevel = 0;

    public function init ($url, $level)
    {
        if (! empty($level))
        {
            $this->level = $level;
        }

        $this->baseUrl = $url;

        $this->block($url);
    }

    public function block ($url)
    {
        $this->nowLevel += 1;

        if ($this->level != 0 and $this->nowLevel > $this->level)
        {
            return true;
        }

        $page = $this->getContent($url);

        $links = $this->getDomLinks($page);

        foreach ($links as $link)
        {
            if (! in_array($link, $this->allUrls))
            {
                $this->allUrls[] = $link;

                $this->block($link);
            }
        }

        return true;
    }

    public function getContent ($url)
    {
        $dom = new DOMDocument();

        $content = file_get_contents($url);

        $content = $this->preLoadHtml($content);

        $dom->loadHTML($content);

        return $dom;
    }

    public function preLoadHtml ($content)
    {
        $content = $this->clearUpHtml($content);

        $this->createTextFile($content);

        return $content;
    }

    public function getDomLinks (DOMDocument $dom)
    {
        $links = $dom->documentElement->getElementsByTagName('a');

        foreach ($links as $link)
        {
            $url = $link->getAttribute('href');
            $url = $this->urlDebug($url);

            if (! empty($url))
            {
                $linksUrl[] = $url;
            }
        }

        return $linksUrl;
    }

    public function urlDebug ($url)
    {
        // 開頭不是 http, https
        if (strpos($url, "http") !== 0)
        {
            $url = $this->baseUrl . "/" . $url;
        }
        elseif (strpos($url, $this->baseUrl) !== 0)
        {
            // 跨站網址不收入
            return "";
        }

        if ($this->isPdfFile($url))
        {
            $this->pdfFile($url);

            // 存取完 pdf 後離開。
            return "";
        }

        return $url;
    }

    public function isPdfFile ($url)
    {
        return (pathinfo($url))['extension'] == "pdf";
    }

    public function pdfFile ($url)
    {
        $content = file_get_contents($url);

        $filename = md5($content);

        file_put_contents ("data/{$filename}.pdf", $content);
    }

    public function clearUpHtml ($content)
    {
        $tidy = new tidy();

        // 防止大寫的 html tag
        $content = strtolower($content);

        $content = strip_tags($content, "<a>");

        // fixed error html
        $content = $tidy->repairString($content);

        return $content;
    }

    public function createTextFile ($content)
    {
        // remove html
        $content = strip_tags($content);

        $fileName = md5($content);

        file_put_contents ("data/{$fileName}.txt", $content);
    }
}

$c = new crawler();

$c->init($url, $level);
