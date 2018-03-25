<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

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
            <button id="start" onclick="getNews($('#count').val())" class="btn btn-primary">Старт</button>
            <button id="clear" disabled="disabled" onclick="resetTable()" class="btn btn-primary">Очистити</button>
            <table style="display: none;" class="table">
                <caption>Дата і час парсінгу: <span id="datetime"></span></caption>
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
            <input type="email" id="send_to" placeholder="Email" style="display: none;">
            <button id="send" data-loading-text="Відправка..."  style="display: none;" onclick="sendEmail($('#send_to').val())" class="btn btn-warning">Надіслати на e-mail</button>
            <button id="save_to_db" data-loading-text="Відправка..."  style="display: none;" onclick="saveToDataBase()" class="btn btn-danger">Зберегти в БД</button>
            <br>
        </div>
        <!-- Scripts -->
        <script src="{{ asset('js/jquery.min.js') }}"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script>
        /**
         * Глобальная переменная, в которой сохраняется список новостей, принятый от сервера
         */
        var newsJSON;

        /**
         * Добавляет токен в заголовок, что бы сервер принимал запросы
         */
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        /**
         * Получает с сервера список новостей и выводит их в таблицу
         *
         * @param count
         */
        function getNews(count) {
            $.getJSON('get_news?count='+count, function(data){
                newsJSON = data;
                $('#datetime').text(data.datetime);
                $.each(data.news, function(key, val){
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
                $('#save_to_db').slideDown('slow');
                $("#start").prop("disabled", true);
                $("#clear").prop("disabled", false);
            });
        }

        /**
         * Преобразует массив маркеров в строку
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
            $('#send_to').slideUp('slow');
            $('#send').slideUp('slow');
            $('#save_to_db').slideUp('slow');
            $('table').slideUp('slow');
            $("tbody").empty();
            $("#start").prop("disabled", false);
            $("#clear").prop("disabled", true);
        }

        /**
         * Генерирует CSV
         */
        function saveToCSV() {
            csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "№,Час публікації,Заголовок,Посилання,Важлива,Мітки\r\n";
            $.each(newsJSON.news, function(key, val){
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
            $("#save_csv").attr("download", 'news' + newsJSON.datetime + '.csv');
            $('#save_csv').slideDown('slow');
            $('#send_to').slideDown('slow');
            $('#send').slideDown('slow');
        }

        /**
         * Отправляет запрос на отправку письма по заданному адресу
         *
         * @param email
         */
        function sendEmail(email) {
            var $btn = $('#send').button('loading');
            $.post( "send_email", {email:email, news:newsJSON})
                .done(function() {
                    alert( "Email відправлено!");
                })
                .fail(function(data) {
                    alert(data.responseJSON.message);
                })
                .always(function() {
                    $btn.button('reset');
                });

        }

        /**
         * Отправляет новости для сохранения в БД
         */
        function saveToDataBase() {
            var $btn = $('#save_to_db').button('loading');
            $.post( "save", {news:newsJSON})
                .done(function() {
                    alert( "Збережено в базу даних.");
                })
                .fail(function() {
                    alert('Під час збереження в БД сталася помилка');
                })
                .always(function() {
                    $btn.button('reset');
                });
        }
    </script>
    </body>
</html>
