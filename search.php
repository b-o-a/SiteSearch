<?php
require 'classes/SiteSearch.php';

if ($_POST['searchtext'] <> '') {
    $search_text = $_POST['searchtext'];
    $s = new SiteSearch('classes/searchdb.txt',
                        '<section id="content">',
                        '<footer>');
    $res = $s->get_search_result($search_text);
    if ($res) {
        $i = 0;
        $msg = '<h2>Результаты поиска:</h2><hr size="1">';
        foreach ($res as $key=>$value) {
            $i++;
            $msg = $msg."<p>$i. <a href = \"$key\">".$value."</a></p>";
        }
    } else {
        $msg = '<h2>Результатов не найдено.</h2>';
    }
} else {
    $msg = '<h2>Задана пустая строка поиска.</h2>';
}

include('pattern.php');

?>
