<?php
/**
 * Created by PhpStorm.
 * User: Dylan
 * Date: 23/09/2015
 * Time: 19:47
 */

include 'simple_html_dom.php';

$html = file_get_html('test.html');
$tableStops = [];

foreach($html->find('div[id=hour]') as $div) {
    $spanList = $div->find('span');
    $i = 0;

    foreach($spanList as $span) {
        //echo '<br>' . $span->outertext;

        if($i > 1) {
            $text = $span->plaintext;

            if(intval($text) > 1000) {
                //echo '<br>STOP : ' . $spanList[$i - 1] . ' ( n°' . $text . ')';

                $tableStops[] = [$spanList[$i - 1]->plaintext, $text];
            }
            else {
                //echo '<br>Nothing to say : ' . $span->plaintext;
            }
        }

        $i++;
    }

    $tableList = $div->find('table');
    $i = 0;

    foreach($tableList as $table) {
        $z = 0;

        echo '<br>STOP : ' . $tableStops[$i][0] . '( n°' . $tableStops[$i][1] . ')';

        foreach($table->find('tr')[0]->find('td') as $td) {
            if($z > 0) {
                $textTd = $td->innertext;

                $textSplit = preg_replace('/<[^>]*>/', '', $textTd);
                $textSplit = str_replace(' ', '|', $textSplit);
                $textSplit = preg_replace('/[|]+/', ' ', $textSplit);
                $textSplit = preg_replace('/([A-Z]{2}) ([A-Z]{2})/', '$1_$2', $textSplit);
                $textSplit = str_replace(' direction ', '', $textSplit);
                $textSplit = str_replace('Prochain passage dans ', '', $textSplit);
                $textSplit = str_replace('Passage suivant dans ', '', $textSplit);
                $textSplit = str_replace('Passage suivant prévu à ', '', $textSplit);
                $textSplit = str_replace(' minutes', '', $textSplit);

                //echo '<br>textSplit : "' . $textSplit . '"';

                $busInfo = explode(' ', $textSplit);

                echo '<br>DIRECTION : ' . $busInfo[0];
            }

            $z++;
        }

        $i++;
    }
}

/*
foreach($table->find('tr') as $tr) {
    echo '<br>' . $tr->plaintext;
}
*/

/*
foreach($html->find('table') as $table) {
    //echo '<br>' . $table->plaintext;

    foreach($table->find('tr') as $b) {
        echo '<br>' . $b->plaintext;
    }
}
*/