<?php
include '../common/config.php';

// 設定此程式可執行之功能 (將不允許執行的功能設為 false，或是加上註解)
$a_valid['HOME']            = true;  // 首頁
// $a_valid['CREATE_DATABASE'] = false;  // 新增資料庫
$a_valid['CREATE_TABLE']    = true;  // 新增資料表
$a_valid['DROP_TABLE']      = true;  // 刪除資料表
$a_valid['VIEW_DEFINE']     = true;  // 查看定義
$a_valid['ADD_DATA']        = true;  // 新增預設資料
$a_valid['LIST_DATA']       = true;  // 列出資料
$a_valid['EXPORT_TBL']      = true;  // 匯出 (單一資料表)
$a_valid['EXPORT_SQL']      = true;  // 匯出 (指定的SQL)
$a_valid['IMPORT']          = true;  // 資料匯入
$a_valid['IMPORT_SAVE']     = true;  // 資料匯入之上傳 (配含IMPORT)
$a_valid['IMPORT_EXEC']     = true;  // 資料匯入之執行 (配含IMPORT)
$a_valid['SQL_QUERY']       = true;  // 執行自定SQL


// ************ 以下為資料定義，依自行需要進行修改 ************
// 資料表之SQL語法 (採用陣列方式，可以設定多個。注意陣列的key即為資料表名稱)

$a_table['person'] = '
CREATE TABLE person (
    uid int NOT NULL auto_increment,
    usercode varchar(255) NULL,
    username varchar(255) NULL,
    address  varchar(255) NULL,
    birthday date default NULL,
    height   int default NULL,
    weight   int default NULL,
    remark   varchar(255) NULL,
    PRIMARY KEY  (uid)
)
';


// 如要預先新增記錄，定義於此
$a_record[] = "INSERT INTO person(usercode, username, address, birthday, height, weight, remark) VALUES
 ('P001', 'Allen', '台北', '2021-02-03', '170','75', ''),
 ('P002', 'Bruce', '台中', '2020-08-12', '180','85', 'OK'); ";


// 指定匯入匯出的預設檔名
$file_csv = 'output.csv';
$file_temp = '__temp__.csv';


// 指定匯入匯出的資料表及對應欄位名稱
$is_title = true;  // 是否第一列要包含欄位名稱
$table_import = 'person';
$a_mapping = array(
'usercode',
'username',
'address',
'birthday',
'height',
'weight',
'remark' );

// 指定匯出的 SQL
$is_title_sql = true;  // 是否第一列要包含欄位名稱
$sql_export = 'SELECT * FROM person';


// ************ 以下為此程式之功能執行，毋需修改 ************

// 修改過的 fgetcsv()，才可處理中文資料
function __fgetcsv(&$handle, $length = null, $d = ",", $e = '"') {
    $d = preg_quote($d);
    $e = preg_quote($e);
    $_line = "";
    $eof=false;
    while ($eof != true) {
        $_line .= (empty ($length) ? fgets($handle) : fgets($handle, $length));
        $itemcnt = preg_match_all('/' . $e . '/', $_line, $dummy);
        if ($itemcnt % 2 == 0)
            $eof = true;
    }
    $_csv_line = preg_replace('/(?: |[ ])?$/', $d, trim($_line));
    $_csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d . '/';
    preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
    $_csv_data = $_csv_matches[1];

    for ($_csv_i = 0; $_csv_i < count($_csv_data); $_csv_i++) {
        $_csv_data[$_csv_i] = preg_replace("/^" . $e . "(.*)" . $e . "$/s", "$1", $_csv_data[$_csv_i]);
        $_csv_data[$_csv_i] = str_replace($e . $e, $e, $_csv_data[$_csv_i]);
    }
    return empty ($_line) ? false : $_csv_data;
}


function build_fields_table($sth) {
    $ret = '';

    // 以各欄位名稱當表格標題
    $fields = array(); 
    for ($i=0; $i<$sth->columnCount(); $i++) {
        $col = $sth->getColumnMeta($i);
        $fields[] = $col['name'];
    }

    $ret .= '<table border="1" cellpadding="2" cellspaceing="0">';
    $ret .= '<tr>';
    foreach ($fields as $val) {
        $ret .= '<th>' . $val . '</th>';
    }
    $ret .= '</tr>';

    // 列出各筆記錄資料
    while($row=$sth->fetch(PDO::FETCH_ASSOC)) {
        $ret .= '<tr>';
        foreach($row as $one) {
            $ret .= '<td>' . $one . '</td>';
        }
        $ret .= '</tr>';
    }
    $ret .= '</table>';

    return $ret;
}


function build_fields_title($sth) {
    $ret = '';

    // 以各欄位名稱當表格標題
    $fields = array(); 
    for ($i=0; $i<$sth->columnCount(); $i++) {
        $col = $sth->getColumnMeta($i);
        $fields[] = $col['name'];
    }

    $ret .= '<table border="1" cellpadding="2" cellspaceing="0">';
    $ret .= '<tr>';
    foreach ($fields as $val) {
        $ret .= '<th>' . $val . '</th>';
    }
    $ret .= '</tr>';

    // 列出各筆記錄資料
    while($row=$sth->fetch(PDO::FETCH_ASSOC)) {
        $ret .= '<tr>';
        foreach($row as $one) {
            $ret .= '<td>' . $one . '</td>';
        }
        $ret .= '</tr>';
    }
    $ret .= '</table>';

    return $ret;
}


// ***** 主程式 *****
$do = $_GET['do'] ?? '';

// 接收傳入變數 (供 SQL_INPUT 及 SQL_QUERY 使用)
$sql = $_POST['sql'] ?? '';
$sql = stripslashes($sql);  // 去除表單傳遞時產生的脫逸符號

$msg = '';

// 檢查功能是否允許
if(!isset($a_valid[$do]) || !$a_valid[$do]) {
    $msg .= '******無法執行此功能！******';
}
else {
switch($do) {
    case 'ADD_DATA' :
        $pdo = db_open();
        
        $msg .= '<h2>新增記錄</h2>';
        foreach($a_record as $key=>$sqlstr) {
            $sth = $pdo->query($sqlstr);
            if($sth===FALSE) {
                $msg .= '<p>無法新增！</p>';
                $msg .= print_r($pdo->errorInfo(),TRUE);
            }
            else {
                $new_uid = $pdo->lastInsertId();    // 傳回剛才新增記錄的 auto_increment 的欄位值
                $msg .= '<p>新增成功 (最後 uid=' . $new_uid .  ')</p>';
            }
        }
        break;
        
        
        
    case 'LIST_DATA' :
        $pdo = db_open();        
        $msg .= '<h2>記錄內容</h2>';
        foreach($a_table as $key=>$sqlstr) {
            $sqlstr = 'SELECT * FROM ' . $key;
            $sth = $pdo->query($sqlstr);
            $msg .= '<h3>資料表『' . $key . '』</h3>';
            if ($sth===FALSE) {
                $msg .= '<p>無法顯示</p>';
                $msg .= print_r($pdo->errorInfo(),TRUE);
            }
            else {
                $msg .= build_fields_table($sth);
            }
        }
        break;
        
        
        
    case 'CREATE_TABLE' : 
        $pdo = db_open();        
        $msg .= '<h2>資料表建立結果</h2>';        
        foreach($a_table as $key=>$sqlstr) {
            $msg .= '<h3>資料表『' . $key . '』</h3>';            
            $sth = $pdo->query($sqlstr);   
            if($sth===FALSE) {
                $msg .= '<p>無法建立！</p>';
                $msg .= print_r($pdo->errorInfo(),TRUE);
            }
            else {
                $msg .= '<p>建立完成</p>';
            }
        }
        break;


        
    case 'DROP_TABLE' : 
        $pdo = db_open();        
        // 執行SQL及處理結果
        $msg .= '<h2>資料表刪除結果</h2>';
        foreach($a_table as $key=>$sqlstr) {
            $msg .= '<h3>資料表『' . $key . '』</h3>';            
            $sqlstr = 'DROP TABLE ' . $key;
            $sth = $pdo->exec($sqlstr);
            if($sth===FALSE) {
                $msg .= '<p>無法刪除！</p>';
                $msg .= print_r($pdo->errorInfo(),TRUE);
            }
            else {
                $msg .= '<p>刪除成功</p>';
            }
        }
        break;



    case 'CREATE_DATABASE' : 
        try {
            $pdo = new PDO('mysql:host='.DB_SERVERIP, DB_USERNAME, DB_PASSWORD);
            if(defined('SET_CHARACTER')) $pdo->query(SET_CHARACTER);
            
            $sqlstr = 'CREATE DATABASE ' . DB_DATABASE;
            $sqlstr .= ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ';   // or utf8
            
            $pdo->exec($sqlstr);  // or die(print_r($pdo->errorInfo(), true));
        }
        catch (PDOException $e) {
            die("DB ERROR: ". $e->getMessage());
        }
        $msg .= '<h2>資料庫建立</h2>';
        $msg .= print_r($pdo->errorInfo(),TRUE);
        $msg .= '<p>資料庫『' . DB_DATABASE . '』</p>';
        $msg .= '<p>' . $sqlstr . '</p>';
        $msg .= '<p>如要刪除 DROP DATABASE ' . DB_DATABASE . '</p>';
        break;
        
        
        
    case 'SQL_QUERY' :
        $msg .= <<< HEREDOC
        <h2>請輸入SQL指令</h2>
        <form name="form1" method="post" action="?do=SQL_QUERY">
        <textarea name="sql" rows="4" cols="80">{$sql}</textarea><br />
        <input type="submit" value="送出查詢">
        </form>
        <hr />
HEREDOC;
        if(empty($sql)) {
            $msg .= '<h2>SQL 範例</h2>' ;
            $msg .= '<p>';
            $msg .= 'SHOW TABLES<br>';
            $msg .= 'SHOW TABLE STATUS<br>';
            $msg .= '=======================<br>';
            foreach($a_table as $key=>$sqlstr) {
                $msg .= 'DESC ' . $key . '<br>';
                // $msg .= '<p>SHOW COLUMNS FROM ' . $key . '</p>';
                $msg .= 'SELECT count(*) FROM ' . $key . '<br>';
                $msg .= 'SELECT * FROM ' . $key . '<br>';
                $msg .= '-----------------------------------<br>';
            }
            $msg .= '</p>';

        }
        else {
            $pdo = db_open();            
            $sqlstr = $sql;
            $sth = $pdo->query($sqlstr);            
            if($sth===FALSE) {
                $msg .= '<h3>執行結果失敗！</h3>';
                $msg .= print_r($pdo->errorInfo(),TRUE);
            }
            else {
                // SELECT 語法結果
                $msg .= '<h3>rowCount: ' . $sth->rowCount() . '</h3>';
                $msg .= build_fields_table($sth);
            }
        }
        break;
        
        
        
    case 'VIEW_DEFINE' :
        $msg .= '<table border="0"><tr><td>';
        $msg .= '<div align="left">';
        $msg .= '<h2>資料表 (程式內定義)</h2>';
        foreach($a_table as $key=>$sqlstr) {
            $msg .= '<h3>' . $key . '<h3>';
            $msg .= '<pre>' . $sqlstr . '</pre><hr />';
        }
        $msg .= '<h2>預設 SQL (程式內定義)</h2><hr />';
        foreach($a_record as $key=>$sqlstr) {
            $msg .= '<pre>' . $sqlstr . '</pre>';
        }
        $msg .= '</div>';
        $msg .= '</td></tr></table>';
        break;
    

        
    case 'EXPORT_SQL' :
        // 依 SQL 匯出
        // 開始匯出
        header('Content-Type: text/csv; charset=utf-8');  
        header('Content-Disposition: attachment; filename=' . $file_csv);
        $output = fopen("php://output", "w"); 
        if($is_title_sql) {
            // fputcsv($output, $ary);  // 匯出欄位名稱
        }
        // 連接資料庫
        $pdo = db_open();
        $sqlstr = $sql_export;
        $sth = $pdo->prepare($sqlstr);
        if($sth->execute()) {
            $total_rec = $sth->rowCount();
            $data = '';
            while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                unset($row['uid']);  // remove uid field
                fputcsv($output, $row);
            }
        }
        else {
            die('Error!');
        }
        fclose($output);
        die();
        break;
    


    case 'EXPORT_TBL' :
        // 資料表及匯入的各個欄位
        $ary = array();
        foreach($a_mapping as $k=>$value) {
            $ary[] = $value;
        }
        // 開始匯出
        header('Content-Type: text/csv; charset=utf-8');  
        header('Content-Disposition: attachment; filename=' . $file_csv);
        $output = fopen("php://output", "w"); 
        if($is_title) {
            fputcsv($output, $ary);  // 匯出欄位名稱
        }
        // 連接資料庫
        $pdo = db_open();
        $sqlstr = "SELECT * FROM " . $table_import;
        $sth = $pdo->prepare($sqlstr);
        if($sth->execute()) {
            $total_rec = $sth->rowCount();
            $data = '';
            while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                unset($row['uid']);  // remove uid field
                fputcsv($output, $row);
            }
        }
        else {
            die('Error!');
        }
        fclose($output);
        die();
        break;
    
    

    case 'IMPORT' :
        $msg .= <<< HEREDOC
        <h2>匯入檔案上傳</h2>
        <p>選擇要匯入的 .csv 檔案 (請自行確認格式及內容的正確)</p>
        <form name="form1" method="post" action="?do=IMPORT_SAVE" enctype="multipart/form-data">
        檔案：<input type="file" name="file">
        <input type="submit" value="上傳">
        </form>
HEREDOC;
        break;
        


    case 'IMPORT_SAVE' :        
        $a_file = $_FILES["file"];  // 上傳的檔案內容
        // 上傳檔案處理
        if($a_file["size"]>0) {
            $save_filename = $file_temp;
            move_uploaded_file($a_file["tmp_name"], $save_filename);
        }
        header('Location: ?do=IMPORT_EXEC');
        break;



    case 'IMPORT_EXEC' :
        // 資料表及匯入的各個欄位
        $sqlstr = "INSERT INTO $table_import(";
        foreach($a_mapping as $k=>$value) {
            $sqlstr .= $value . ',';
        }
        $sqlstr = rtrim($sqlstr, ',');  // 移除最後一個逗號
        $sqlstr .= ') VALUES ';

        $pdo = db_open();
        $time1 = microtime(TRUE);
        $cnt_record = 0;
        // 讀入資料後逐筆新增
        if((@$handle = fopen($file_temp, "r")) !== FALSE) {
            $record_all = '';
            while(($row = __fgetcsv($handle)) !== FALSE) {
                $cnt_record++;
                $cnt = 0;
                $record_one = '(';
                foreach($a_mapping as $value) {
                    $one = str_replace("'", "\'", $row[$cnt]);  // 處理單引號
                    $cnt++;
                    $record_one .= "'" . $one . "',";
                }
                $record_one = rtrim($record_one, ',');  // 移除最後一個逗號
                $record_one .= '),';

                $record_all .= $record_one;
            }
            fclose($handle);
            $record_all = rtrim($record_all, ',');  // 移除最後一個逗號
            $sqlstr .= $record_all;

            if($pdo->query($sqlstr)) {
                $msg .= '已新增 ' . $cnt_record . ' 筆記錄';
            }
        }
        @unlink($file_temp);
        $time2 = microtime(TRUE);
        $spend = $time2 - $time1;
        $msg .= '<p>共花費時間：' . $spend . '</p>';
        break;
} /* end of switch */
} /* end of if..else */


// 顯示功能表列
$menu  = '';
$menu .= '| <a href="?do=HOME">安裝首頁</a> ';
$menu .= (!isset($a_valid['VIEW_DEFINE']) || !$a_valid['VIEW_DEFINE']) ? '' : '| <a href="?do=VIEW_DEFINE">程式內SQL定義</a> ';
$menu .= '| --- ';
$menu .= (!isset($a_valid['CREATE_DATABASE']) || !$a_valid['CREATE_DATABASE']) ? '' : '| <a href="?do=CREATE_DATABASE">建立資料庫</a> ';
$menu .= '| --- ';
$menu .= (!isset($a_valid['CREATE_TABLE']) || !$a_valid['CREATE_TABLE']) ? '' : '| <a href="?do=CREATE_TABLE">建立資料表</a> ';
$menu .= (!isset($a_valid['DROP_TABLE']) || !$a_valid['DROP_TABLE']) ? '' : '| <a href="?do=DROP_TABLE" onClick="return confirm(\'確定要刪除嗎？\');">刪除資料表</a> ';
$menu .= '| --- ';
$menu .= (!isset($a_valid['EXPORT_SQL']) || !$a_valid['EXPORT_SQL']) ? '' : '| <a href="?do=EXPORT_SQL">匯出(SQL)</a> ';
$menu .= (!isset($a_valid['EXPORT_TBL']) || !$a_valid['EXPORT_TBL']) ? '' : '| <a href="?do=EXPORT_TBL">匯出(資料表)</a> ';
$menu .= (!isset($a_valid['IMPORT']) || !$a_valid['IMPORT']) ? '' : '| <a href="?do=IMPORT">匯入</a> ';
$menu .= '| --- ';
$menu .= (!isset($a_valid['ADD_DATA']) || !$a_valid['ADD_DATA']) ? '' : '| <a href="?do=ADD_DATA">新增預設記錄</a> ';
$menu .= (!isset($a_valid['LIST_DATA']) || !$a_valid['LIST_DATA']) ? '' : '| <a href="?do=LIST_DATA">查看記錄內容</a> ';
$menu .= '| --- ';
$menu .= (!isset($a_valid['SQL_QUERY']) || !$a_valid['SQL_QUERY']) ? '' : '| <a href="?do=SQL_QUERY">SQL測試</a> ';
$menu .= '|';



$html = <<< HEREDOC
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>基本資料庫系統 - 安裝程式</title>
</head>
<body>
    <h2>初始安裝工具程式</h2>
    <p>{$menu}</p>
    <hr>
    <div align="center">
    {$msg}
    </div>
</body>
</html>
HEREDOC;

echo $html;
?>