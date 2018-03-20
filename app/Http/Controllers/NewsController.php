<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Возвращает список новостей
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNews(Request $request)
    {

        $validatedData = $request->validate([
            'count' => 'nullable|numeric',
        ]);
        $newsCount = 50;
        if(!empty($validatedData['count'])) {
            $newsCount = $validatedData['count'];
        }

        $news = $this->getNewsList($newsCount);
        $parsedNews = $this->getParsedNews($news);

        return response()->json($parsedNews);
    }

    /**
     * Возвращает массив необработанных новостей с html-кодом
     *
     * @param $newsCount int нужное количество новостей
     * @return array html-код каждой новости
     */
    private function getNewsList($newsCount)
    {
        $regExp = '/<div class="content-section">((.|\n)+?)<\/div>/';
        $news = [];
        $i = 1;
        while (count($news) < $newsCount) {
            $html = file_get_contents('https://www.rbc.ua/ukr/worldnews/' . $i);
            preg_match_all($regExp, $html, $temp, PREG_PATTERN_ORDER, 0);
            $i++;
            $news = array_merge($news, $temp[1]);
        }
        return array_slice($news, 0, $newsCount);
    }

    /**
     * Возвращает масив структурированых данных по каждой новости
     *
     * @param array $news
     * @return array
     */
    private function getParsedNews(array $news)
    {
        $parsedNews = [];
        foreach ($news as $item) {
            array_push($parsedNews, $this->getOneParsedNews($item));
        }
        return $parsedNews;
    }

    /**
     * Возвращает ассоциативный массив с данными одной новости
     *
     * @param $item
     * @return array
     */
    private function getOneParsedNews($item)
    {
        $parsedItem = [];
        $parsedItem['title'] = $this->getTitle($item);
        $parsedItem['url'] = $this->getURL($item);
        $parsedItem['time'] = $this->getTime($item);
        $parsedItem['is_important'] = $this->getImportance($item);
        $parsedItem['markers'] = $this->getMarkers($item);
        return $parsedItem;
    }

    /**
     * Возвращает URL-адрес новости
     *
     * @param $item
     * @return string
     */
    private function getURL($item)
    {
        $regExp = '/href="((.)+?)"/';
        preg_match_all($regExp, $item, $url, PREG_PATTERN_ORDER, 0);
        return $url[1][0];
    }

    /**
     * Возвращает заголовок новости
     *
     * @param $item
     * @return string
     */
    private function getTitle($item)
    {
        $regExp = '/<\/span>((.|\n)+?)</';
        preg_match_all($regExp, $item, $title, PREG_PATTERN_ORDER, 0);
        return trim($title[1][0]);
    }

    /**
     * Возвращает время публикации новости
     *
     * @param $item
     * @return string
     */
    private function getTime($item)
    {
        $regExp = '/<span class="time">((.|\n)+?)<\/span>/';
        preg_match_all($regExp, $item, $time, PREG_PATTERN_ORDER, 0);
        return $time[1][0];
    }

    /**
     * Возвращает информацию, важна ли новость
     *
     * @param $item
     * @return bool
     */
    private function getImportance($item)
    {
        $regExp = '/" class="((.|\n)+?)"/';
        preg_match_all($regExp, $item, $is_important, PREG_PATTERN_ORDER, 0);
        if ($is_important[1][0] === "news-feed-item-bold-heading") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Возвращает список меток новости
     *
     * @param $item
     * @return array
     */
    private function getMarkers($item)
    {
        $regExp = '/<span class="news-feed-icon-((.|\n)+?)"><\/span>/';
        preg_match_all($regExp, $item, $markers, PREG_PATTERN_ORDER, 0);
        return $markers[1];
    }
}
