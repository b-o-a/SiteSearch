<?php

class SiteSearch {
    // файл со списком файлов, в которых производить поиск, каждый с
    // новой строки
    private $search_db;
    // текст начала поиска в файле, будет отрезан
    private $start_phrase;
    // текст конца поиска в файле, будет отрезан
    private $end_phrase;
    // добавляем к найденной фразе в результатах поиска
    // текст такой длины слева и справа
    private $snippet_wide;
    // список файлов для поиска
    private $search_urls;

    function __construct($search_db = 'classes/searchdb.txt',
                         $start = '<body>',
                         $end = '</body>',
                         $snippet = 300) {
        // инициализация переменных
        $this->search_db = $search_db;
        $this->start_phrase = $start;
        $this->end_phrase = $end;
        $this->snippet_wide = $snippet;
        
        // получаем все файлы для поиска из базы
        $this->get_search_urls();
    }

    function get_search_urls() {
        // получаем все файлы для поиска из базы
        $raw_urls = file_get_contents($this->search_db);
        $this->search_urls = split("\n", $raw_urls);
        foreach ($this->search_urls as $key=>$search_url) {
            // обрезаем пробелы
            $this->search_urls[$key] = trim($search_url);
            // пустые строки игнорируем
            if ($this->search_urls[$key] == '') {
                unset($this->search_urls[$key]);
            }
        }
    }

    function remove_HTML($string) {
        // удаляем html код
        $string = mb_eregi_replace('<script.+?script>', '', $string);
        $string = mb_eregi_replace('<noscript.+?noscript>', '', $string);
        $string = mb_eregi_replace('<style.+?style>', '', $string);
        $string = mb_eregi_replace('<noframes.+?noframes>', '', $string);
        $string = mb_eregi_replace('<select.+?select>', '', $string);
        $string = mb_eregi_replace('<option.+?option>', '', $string);
        $string = mb_eregi_replace('<!--.+?--!>', '', $string);
        $string = mb_eregi_replace('<.+?>', '', $string);
        // удаляем двойные пробелы
        $string = mb_eregi_replace(' +', ' ', $string);
        // удаляем пробелы по краям
        return trim($string);
    }

    function find_phrase($search_url, $search_phrase) {
        // читаем файл для поиска
        $file_data = file_get_contents($search_url);
        
        // вырезаем часть, в которой будем искать
        // если не нашли начало или конец - ищем по всему файлу
        if (mb_eregi('('.$this->start_phrase.'.+'.$this->end_phrase.')',
                     $file_data,
                     $res)) {
            $file_data = $res[0];
            $file_data = mb_substr($file_data,
                                   mb_strlen($this->start_phrase)-1,
                                   mb_stripos($file_data,
                                   $this->end_phrase));
        }
        // удаляем html код
        $file_data = $this->remove_HTML($file_data);
        // удаляем переводы строк
        $file_data=mb_eregi_replace("\r",'',$file_data);
        $file_data=mb_eregi_replace("\n",'',$file_data);
        // ищем фразу
        $snippet_begin = mb_strpos(mb_strtoupper($file_data, 'utf-8'),
                                   mb_strtoupper($search_phrase, 'utf-8'));
        // если нашли
        if ($snippet_begin) {
            // определяем начало и конец результата
            $snippet_begin = $snippet_begin - $this->snippet_wide;
            if($snippet_begin < 0) $snippet_begin = 0;
            $snippet_end = $snippet_begin + strlen($search_phrase) + $this->snippet_wide*2;
            if ($snippet_end > mb_strlen($file_data)) $snippet_end = mb_strlen($file_data);
            $snippet = mb_substr($file_data, $snippet_begin, $snippet_end-$snippet_begin);
            // сокращаем результат до полного слова слева и справа
            $snippet_begin = mb_strpos($snippet, ' ');
            $snippet = mb_substr($snippet, $snippet_begin, mb_strlen($snippet) - $snippet_begin);
            $snippet_end = mb_strrpos($snippet, ' ');
            $snippet = mb_substr($snippet, 0, $snippet_end);
            // украшательство
            $snippet = '...'.htmlspecialchars($snippet).'...';

//          эта строчка не работает по непонятным причинам на РаЗнЫх регистрах
//          $snippet = mb_eregi_replace("($search_phrase)", "<strong>\\1</strong>", $snippet);
//          вместо неё следующий костыль:
            $find_start = mb_strpos(mb_strtoupper($snippet, 'utf-8'),
                                    mb_strtoupper($search_phrase, 'utf-8'));
            $snippet = mb_substr($snippet, 0, $find_start).
                       '<strong>'.
                       $search_phrase.
                       '</strong>'.
                       mb_substr($snippet, $find_start+mb_strlen($search_phrase));

            return $snippet;
        } else {
            return false;
        }
    }

    function get_search_result($search_phrase) {
        // собственно поиск
        if ($search_phrase <> '') {
            // для каждого файла
            foreach ($this->search_urls as $search_url) {
                // найти фразу
                $snippet = $this->find_phrase($search_url, $search_phrase);
                if ($snippet) {
                    // и если нашли, добавить к результату
                    $result[$search_url] = $snippet;
                }
            }
            return $result;
        } else {
            return false;
        }
    }
}
?>
