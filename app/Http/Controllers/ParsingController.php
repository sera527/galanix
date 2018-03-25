<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Parsing;

class ParsingController extends Controller
{
    /**
     * Выводит список парсингов
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $parsings = Parsing::all();

        return view('parsing_index', ['parsings' => $parsings]);
    }

    /**
     * Выводит новости парсинга, соответстующего id
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($id)
    {
        $parsing = Parsing::find($id);
        $articles = $parsing->articles()->with('markers')->get();

        return view('parsing', ['parsing' => $parsing, 'articles' => $articles]);
    }
}
