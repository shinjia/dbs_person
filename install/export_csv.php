<?php
include '../common/config.php';

$file_csv = 'output.csv';

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
$ary = array();
foreach($a_mapping as $k=>$value)
{
    $ary[] = $value;
}

header('Content-Type: text/csv; charset=utf-8');  
header('Content-Disposition: attachment; filename=' . $file_csv);  
$output = fopen("php://output", "w"); 


fputcsv($output, $ary);  


// 連接資料庫
$pdo = db_open();

// 寫出 SQL 語法
$sqlstr = "SELECT * FROM film ORDER BY pub_date DESC ";

$sth = $pdo->prepare($sqlstr);

// 執行SQL及處理結果
if($sth->execute())
{
    // 成功執行 query 指令
    $total_rec = $sth->rowCount();
    $data = '';
    while($row = $sth->fetch(PDO::FETCH_ASSOC))
    {
        unset($row['uid']);  // remove uid field
        
        fputcsv($output, $row);  
    }
}
else
{
    $msg = 'Error!';
}

fclose($output);  

?>