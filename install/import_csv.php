<?php

function __fgetcsv(&$handle, $length = null, $d = ",", $e = '"')
{
    $d = preg_quote($d);
    $e = preg_quote($e);
    $_line = "";
    $eof=false;
    while ($eof != true)
    {
        $_line .= (empty ($length) ? fgets($handle) : fgets($handle, $length));
        $itemcnt = preg_match_all('/' . $e . '/', $_line, $dummy);
        if ($itemcnt % 2 == 0)
            $eof = true;
    }
    $_csv_line = preg_replace('/(?: |[ ])?$/', $d, trim($_line));
    $_csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d . '/';
    preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
    $_csv_data = $_csv_matches[1];

    for ($_csv_i = 0; $_csv_i < count($_csv_data); $_csv_i++)
    {
        $_csv_data[$_csv_i] = preg_replace("/^" . $e . "(.*)" . $e . "$/s", "$1", $_csv_data[$_csv_i]);
        $_csv_data[$_csv_i] = str_replace($e . $e, $e, $_csv_data[$_csv_i]);
    }
    return empty ($_line) ? false : $_csv_data;
}

include '../common/config.php';
include '../common/utility.php';

// 參數
$csv_file = 'data.csv';

// 依序匯入匯出的欄位及對應欄位名稱
$a_mapping = array(
    'f00' =>'filmyear',
    'f01' =>'pub_date',
    'f02' =>'title_c',
    'f03' =>'title_e',
    'f04' =>'area',
    'f05' =>'rate',
    'f06' =>'key_wiki',
    'f07' =>'key_imdb',
    'f08' =>'key_tmdb',
    'f09' =>'key_dban',
    'f10' =>'key_note',
    'f16' =>'tag_cast',
    'f17' =>'tag_note',
    'f18' =>'tag_topic',
    'f19' =>'tag_genre',
    'f20' =>'poster',
    'f21' =>'remark' );


// 資料表及匯入的各個欄位
$sqlstr = "INSERT INTO film(";
foreach($a_mapping as $k=>$value)
{
    $sqlstr .= $value . ',';
}
$sqlstr = rtrim($sqlstr, ',');  // 移除最後一個逗號
$sqlstr .= ') VALUES ';

// 連接資料庫
$pdo = db_open();

$cnt = 0;
$msg = '';
// 讀入資料後逐筆新增
$time1 = microtime(TRUE);

if(($handle = fopen($csv_file, "r")) !== FALSE)
{
    $record_all = '';
    while(($row = __fgetcsv($handle)) !== FALSE)
    {
        $record_one = '(';
        $cnt = 0;
        foreach($a_mapping as $k=>$value)
        {
            $one = str_replace("'", "\'", $row[$cnt]);  // 處理單引號
            $cnt++;
            $record_one .= "'" . $one . "',";
        }
        $record_one = rtrim($record_one, ',');  // 移除最後一個逗號
        $record_one .= '),';

        $record_all .= $record_one;
        $cnt++;
    }
    fclose($handle);

    $record_all = rtrim($record_all, ',');  // 移除最後一個逗號
    $sqlstr .= $record_all;

    echo $sqlstr; exit;

    if($pdo->query($sqlstr))
    {
        $msg = '已新增 ' . $cnt . ' 筆記錄';
    }
    else
    {
        echo print_r($pdo->errorInfo()) . '<br />' . $sqlstr; exit;  // 此列供開發時期偵錯用        
    }
}

$time2 = microtime(TRUE);
$spend = $time2 - $time1;


$html = <<< HEREDOC
<p>{$msg}</p>
<p>共花費時間：{$spend} 秒</p>
HEREDOC;

echo $html;
?>