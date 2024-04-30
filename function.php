<?php

/**
 * 返却履歴と返却予定日から貸出状況を文字列で返す
 * 
 * @param string $returned 返却履歴
 * @param string $return　返却予定日
 * @return string 条件によって貸出状況を返す
 */
function checkStatus($returned, $return)
{
    if ($returned == NULL) {
        $today = strtotime("today");
        $return_on = strtotime($return);
        $diff = $return_on - $today;
        if ($diff >= 0) {
            return "レンタル中";
        } else {
            return "延滞中";
        }
    } else {
        return "返却済み";
    }
}

/**
 * レンタル期間の番号に対応する文字列で返す
 * 
 * @param int $span レンタル期間
 * @return string 条件によってレンタル期間を返す
 */

function checkSpan($span)
{
    switch ($span) {
        case 0:
            return '当日';
        case 1:
            return '１泊２日';

        case 2:
            return '２泊３日';

        case 7:
            return '７泊８日';
        default:
            return '';
    }
}

/**
 * 発刊日の日付から新作、旧作判定をして文字列で返す
 * 
 * @param string $publication_on 発刊日
 * @return string １年以内なら'新作'、違えば'旧作'を返す
 */
function checkPublication($publication_on)
{
    $today = new DateTime('today');
    $today->format('Y-m-d');
    $pub_date = new DateTime($publication_on);
    $pub_date->format('Y-m-d');
    $diff = $today->diff($pub_date);
    if ($diff->days > '365') {
        return '旧作';
    } else {
        return '新作';
    }
}

?>