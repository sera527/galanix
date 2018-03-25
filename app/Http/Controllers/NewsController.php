<?php

namespace App\Http\Controllers;

use App\Mail\YourNewsList;
use App\Article;
use App\Marker;
use App\Parsing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
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
        $current = Carbon::now();
        $parsedNews = $this->getParsedNews($news);
        $response['datetime'] = $current->toDateTimeString();
        $response['news'] = $parsedNews;

        return response()->json($response);
    }

    /**
     * Отправляет список новостей на указанный Email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmail(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'news' => 'required|array',
        ]);
        $csv = $this->makeCSV($validatedData['news']['news']);

        Mail::to($validatedData['email'])->send(new YourNewsList($csv, $validatedData['news']['datetime']));
        return response()->json([], 200);
    }

    /**
     * Сохраняет новости в БД
     *
     * @param Request $request
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'news' => 'required|array',
        ]);

        $datetime = new Carbon($validatedData['news']['datetime']);
        $parsing = new Parsing();
        $parsing->datetime = $datetime;
        $parsing->save();

        foreach ($validatedData['news']['news'] as $item) {
            $article = new Article();
            $article->time = $item['time'];
            $article->title = $item['title'];
            $article->url = $item['url'];
            $isImportant = $item['is_important'] === 'true'? true: false;
            $article->is_important = $isImportant;
            $parsing->articles()->save($article);

            if (isset($item['markers'])) {
                foreach ($item['markers'] as $m) {
                    $marker = Marker::firstOrCreate(['name' => $m]);
                    $article->markers()->attach($marker);
                }
            }
        }
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
        $parsedItem['time'] = $this->getTime($item);
        $parsedItem['title'] = $this->getTitle($item);
        $parsedItem['url'] = $this->getURL($item);
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

    /**
     * Генерирует строку в формате CSV
     *
     * @param array $newsArray
     * @return bool|string
     */
    private function makeCSV(array $newsArray)
    {
        $fp = fopen('php://temp', 'rw');

        fputcsv($fp, ['№', 'Час публікації', 'Заголовок', 'Посилання', 'Важлива', 'Мітки']);
        $i = 1;
        foreach ($newsArray as $item) {
            if (isset($item['markers'])) {
                $markers = '';
                foreach ($item['markers'] as $marker) {
                    $markers .= $marker . ', ';
                }
                $markers = substr($markers, 0, -2);
                $item['markers'] = $markers;
            } else {
                $item['markers'] = '';
            }
            array_unshift($item, $i);

            fputcsv($fp, $item);
            $i++;
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }
}
