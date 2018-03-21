<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Galanix</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    </head>
    <body>
        <div class="container">
            <h1>Парсер новин сайту РБК (<a href="https://www.rbc.ua/ukr/">rbc.ua</a>)</h1>
            <div class="form-group">
                <label for="count">Кількість новин</label>
                <input type="number" class="form-control" id="count"
                       aria-describedby="countHelp" name="count" placeholder="">
                <small id="countHelp" class="form-text text-muted">Не обов'язково для заповнення</small>
            </div>
            <button onclick="getNews($('#count').val())" class="btn btn-primary">Старт</button>
            <button onclick="resetTable()" class="btn btn-primary">Очистити</button>
            <table style="display: none;" class="table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Час новини</th>
                        <th>Заголовок</th>
                        <th>Мітки</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <button id="csv" style="display: none;" onclick="saveToCSV()" class="btn btn-success">Зберегти в CSV</button>
            <a id="save_csv" style="display: none;" href="" class="btn btn-primary">Зберегти</a>
            <br>
        </div>
        <!-- Scripts -->
        <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script>
        var newsJSON;

        /**
         * Получает с сервера список новостей и выводит их в таблицу
         *
         * @param count
         */
        function getNews(count) {
            $.getJSON('get_news?count='+count, function(data){
                newsJSON = data;
                $.each(data, function(key, val){
                    key += 1;
                    trClass = '';
                    if (val.is_important) {
                        trClass = 'info';
                    }
                    markers = markersToString(val.markers);
                    $('<tr class="' + trClass + '"><td>' + key + '</td><td>' + val.time + '</td><td><a href=\"' + val.url + '">' + val.title + '</a></td><td>' + markers + '</td></tr>').appendTo('tbody');
                });
                $('table').slideDown('slow');
                $('#csv').slideDown('slow');
            });
        }

        /**
         * преобразует массив маркеров в строку
         *
         * @param markersArray
         * @returns string
         */
        function markersToString(markersArray) {
            markers = '';
            $.each(markersArray, function(key, val){
                markers += val + ', ';
            });
            markers = markers.substring(0, markers.length - 2);
            return markers;
        }

        /**
         * Возвращает страницу к первоначальному виду
         */
        function resetTable() {
            $('#save_csv').slideUp('slow');
            $('#csv').slideUp('slow');
            $('table').slideUp('slow');
            $("tbody").empty();
        }

        /**
         * Генерирует CSV
         */
        function saveToCSV() {
            const rows = [["name1", "city1", "some other info"], ["name2", "city2", "more info"]];
            csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "№,Час публікації,Заголовок,Посилання,Важлива,Мітки\r\n";
            $.each(newsJSON, function(key, val){
                key += 1;
                row = key + ',';
                row += val.time + ',';
                title = val.title.replace(new RegExp('"','g'),'""');
                title = '"' + title + '"';
                row += title + ',';
                row += val.url + ',';
                row += val.is_important + ',';
                markers = markersToString(val.markers);
                if (markers.length > 0){
                    markers = '"' + markers + '"';
                }
                row += markers;
                csvContent += row + "\r\n";
            });
            var encodedUri = encodeURI(csvContent);
            $("#save_csv").attr("href", encodedUri);
            $('#save_csv').slideDown('slow');
        }
    </script>
    </body>
</html>
