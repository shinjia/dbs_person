<?php

$html = <<< HEREDOC
<h2>資料管理系統 dbs_person v1.1</h2>

<p><a href="list_page.php">列表 (分頁) list_page</a></p>
<p><a href="list_all.php">列表 (全部) list_all</a></p>
<hr/>
<p><a href="find.php">查詢姓名 (全部顯示版本)</a></p>
<p><a href="findp.php">查詢姓名 (分頁顯示版本)</a></p>
<hr>
HEREDOC;


include 'pagemake.php';
pagemake($html);
?>