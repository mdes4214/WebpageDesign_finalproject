<!doctype html> 
<?php
session_start();
require_once("include/gpsvars.php");
require_once("include/configure.php");
require_once("include/db_func.php");
$db_conn = connect2db($dbhost, $dbuser, $dbpwd, $dbname);

function userauth($ID, $PWD, $db_conn) {
	$sqlcmd = "SELECT * FROM fpuser WHERE loginid='$ID' AND valid='Y'";
	$rs = querydb($sqlcmd, $db_conn);
	$retcode = 0;
	if (count($rs) > 0) {
		$Password = sha1($PWD);
		if ($Password == $rs[0]['password']) $retcode = 1;
	}
	return $retcode;
}
function logout(){
	session_destroy();
	return;
}
function judgegroup($gameid){
	$tmp = substr($gameid, 2, 1);
	$group = "";
	switch($tmp){
		case 0:
		case 5:
			$group = "A";
			break;
		case 1:
		case 6:
			$group = "B";
			break;
		case 2:
		case 7:
			$group = "C";
			break;
		case 3:
		case 8:
			$group = "D";
			break;
		case 4:
		case 9:
			$group = "E";
			break;
		default:
			$group = "FALSE";
			break;
	};
	return $group;
}
function makeid($gameid){
	$a = array();
	$a[0] = substr($gameid, 0, 3);
	$a[1] = substr($gameid, 3, 3);
	$a[2] = substr($gameid, 6, 3);
	return implode($a, ',');
}

if(isset($_POST['functionname']) && $_POST['functionname'] == 'logout'){
	logout();
}
if(isset($_POST['functionname']) && $_POST['functionname'] == 'totalsearch'){
	$tab2search = '3';
}

if(!isset($tab2search)){
	echo "<scrpit>alert('!!!');</script>";
	$tab2search = '3';
}
$ErrMsg = "";
if (isset($submit_i)) {
	if (strlen($ID_i) > 0 && strlen($ID_i)<=16 && $ID_i==addslashes($ID_i) && $ID_i==htmlspecialchars($ID_i) && strlen($PWD_i) > 0 && strlen($PWD_i)<=16 && $PWD_i==addslashes($PWD_i) && $PWD_i==htmlspecialchars($PWD_i)) {
		$Authorized = userauth($ID_i,$PWD_i,$db_conn);
		if ($Authorized) {
			$sqlcmd = "SELECT * FROM fpuser WHERE loginid='$ID_i' AND valid='Y'";
			$rs = querydb($sqlcmd, $db_conn);
			$LoginID = $rs[0]['loginid'];
			$_SESSION['LoginID'] = $LoginID;
			$_SESSION['Authority'] = $rs[0]['authority'];
			header ("Location:index.php");
			exit();
		}
		$ErrMsg = '您並非合法使用者或是使用權已被停止';
	} else {
		$ErrMsg = '帳號錯誤，您並非合法使用者或是使用權已被停止';
	}
	if (empty($ErrMsg)) $ErrMsg = '登入錯誤';
}
if (isset($submit_r)) {
	if (strlen($ID_r) > 0 && strlen($ID_r)<=16 && $ID_r==addslashes($ID_r) && $ID_r==htmlspecialchars($ID_r) && strlen($PWD_r) > 0 && strlen($PWD_r)<=16 && $PWD_r==addslashes($PWD_r) && $PWD_r==htmlspecialchars($PWD_r)) {
		$gamegroup = judgegroup($gameID_r);
		if($gamegroup == "FALSE" || strlen($gameID_r) != 9 || !is_numeric($gameID_r)){
			$ErrMsg = '格式錯誤，請依循正確格式輸入';
		}
		else{
			$sqlcmd = "SELECT * FROM fpuser WHERE loginid='$ID_r'";
			$rs1 = querydb($sqlcmd, $db_conn);
			$sqlcmd = "SELECT * FROM fprequest WHERE loginid='$ID_r'";
			$rs2 = querydb($sqlcmd, $db_conn);
			if(!empty($rs1) || !empty($rs2)){
				$ErrMsg = '使用者已存在，請重新輸入';
			}
			else{
				$gameid = makeid($gameID_r);
				$time = date('Y-m-d H:i:s', time('NOW'));
				$pwd = sha1($PWD_r);
				$sqlcmd = "INSERT INTO fprequest (loginid, password, gameid, gamegroup, registtime) VALUES('$ID_r', '$pwd', '$gameid', '$gamegroup', '$time')";
				$result = updatedb($sqlcmd, $db_conn);
				
				echo "<script language=javascript>"; 
				echo "window.alert('已送出申請，請等待管理者審核')"; 
				echo "</script>"; 
				echo "<script language=\"javascript\">"; 
				echo "location.href='index.php'"; 
				echo "</script>"; 
				exit();
			}
		}
	} else {
		$ErrMsg = '格式錯誤，請依循正確格式輸入';
	}
	if (empty($ErrMsg)) $ErrMsg = '註冊錯誤';
}
if (isset($submit_addnews)) {
	if(strlen($news_link) > 0 && $news_link == addslashes($news_link) && $news_link == htmlspecialchars($news_link)){
		if($_FILES["news_image"]["error"] > 0){
			$ErrMsg = '上傳檔案有誤，請重新上傳檔案';
		}
		else{
			$sqlcmd = "SELECT count(*) AS reccount FROM fpnews ";
			$rs = querydb($sqlcmd, $db_conn);
			$RecCount = $rs[0]['reccount'];
			$filename = "latest/".($RecCount+1).".jpg";
			move_uploaded_file($_FILES["news_image"]["tmp_name"], $filename);
			
			$sqlcmd = "INSERT INTO fpnews (image, link) VALUES('$filename', '$news_link')";
			$result = updatedb($sqlcmd, $db_conn);
			
			echo "<script language=javascript>"; 
			echo "window.alert('已成功新增')"; 
			echo "</script>"; 
			echo "<script language=\"javascript\">"; 
			echo "location.href='index.php'"; 
			echo "</script>"; 
			exit();
		}
	}
	else{
		$ErrMsg = '格式錯誤，請依循正確格式輸入';
	}
}
if(isset($submit_comment)){
	if(strlen($content) > 0 && strlen($content) < 3000 && $content == addslashes($content) && $content == htmlspecialchars($content)){
		$userid = $_SESSION['LoginID'];
		$sqlcmd = "SELECT * FROM fpuser WHERE loginid='$userid'";
		$rs = querydb($sqlcmd, $db_conn);
		
		$image_c = "";
		if($image_comment == 'Y'){
			$image_c = $rs[0]['monsterimage'];
		}
		$gameid_c = $rs[0]['gameid'];
		$gamegroup_c = $rs[0]['gamegroup'];
		$time_c = date('Y-m-d H:i:s', time('NOW'));
		
		$sqlcmd = "INSERT INTO fpcomment (content, image, time, user, userid, usergroup) VALUES('$content', '$image_c', '$time_c', '$userid', '$gameid_c', '$gamegroup_c')";
		$rs = updatedb($sqlcmd, $db_conn);
		
		echo "<script language=javascript>"; 
		echo "window.alert('已留言')"; 
		echo "</script>"; 
		echo "<script language=\"javascript\">"; 
		echo "location.href='index.php'"; 
		echo "</script>"; 
		exit();
	}
	else{
		$ErrMsg = '格式錯誤，請重新輸入';
	}
}
if($ErrMsg != ""){
	echo "<script language=javascript>"; 
	echo "window.alert('".$ErrMsg."')"; 
	echo "</script>"; 
	echo "<script language=\"javascript\">"; 
	echo "location.href='index.php'"; 
	echo "</script>"; 
	return; 
}

if(isset($action) && $action == 'pass' && isset($seqno)){
	$sqlcmd = "SELECT * FROM fprequest WHERE seqno='$seqno'";
    $rs = querydb($sqlcmd, $db_conn);
    if (count($rs) > 0) {
		$loginid_p = $rs[0]['loginid'];
		$password_p = $rs[0]['password'];
		$gameid_p = $rs[0]['gameid'];
		$gamegroup_p = $rs[0]['gamegroup'];
		$registtime_p = $rs[0]['registtime'];
		$sqlcmd = "INSERT INTO fpuser (loginid, password, gameid, gamegroup, registtime) VALUES('$loginid_p', '$password_p', '$gameid_p', '$gamegroup_p', '$registtime_p')";
        $result = updatedb($sqlcmd, $db_conn);
		$sqlcmd = "DELETE FROM fprequest WHERE seqno='$seqno'";
		$result = updatedb($sqlcmd, $db_conn);
	}
	header ("Location:index.php");
	exit();
}
if (isset($action) && $action=='recover' && isset($seqno)) {
    $sqlcmd = "SELECT * FROM fpuser WHERE seqno='$seqno' AND valid='N'";
    $rs = querydb($sqlcmd, $db_conn);
    if (count($rs) > 0) {
            $sqlcmd = "UPDATE fpuser SET valid='Y' WHERE seqno='$seqno'";
            $result = updatedb($sqlcmd, $db_conn);
    }
	header ("Location:index.php");
	exit();
}
if (isset($action) && $action=='delete' && isset($seqno)) {
    $sqlcmd = "SELECT * FROM fpuser WHERE seqno='$seqno' AND valid='Y'";
    $rs = querydb($sqlcmd, $db_conn);
    if (count($rs) > 0) {
            $sqlcmd = "UPDATE fpuser SET valid='N' WHERE seqno='$seqno'";
            $result = updatedb($sqlcmd, $db_conn);
    }
	header ("Location:index.php");
	exit();
}
if(isset($action) && $action=='commentD' && isset($seqno)){
	$sqlcmd = "SELECT * FROM fpcomment WHERE seqno='$seqno'";
    $rs = querydb($sqlcmd, $db_conn);
    if (count($rs) > 0) {
            $sqlcmd = "DELETE FROM fpcomment WHERE seqno='$seqno'";
            $result = updatedb($sqlcmd, $db_conn);
    }
	header ("Location:index.php");
	exit();
}

?>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>遊戲圖鑑資訊記錄暨分享交流平台</title>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	<script src="jquery.cookie.js"></script>
	<link rel="stylesheet" href="/resources/demos/style.css">
	<style type="text/css">
		*{
			font-family: Helvetica, Arial, 'Heiti TC', 'Microsoft JhengHei', sans-serif;
		}
	</style>
	<script>
		$(function() {
		$( "#tabs" ).tabs({
			active: ($.cookie("tabs") || 0),  
			activate: function (event, ui) {   
				var newIndex = ui.newTab.parent().children().index(ui.newTab); 
				$.cookie("tabs", newIndex, { expires: 1 });
				$( "#dialog_login" ).dialog( "close" );
				$( "#dialog_regist" ).dialog( "close" );
				$( "#dialog_addnews" ).dialog( "close" );
				$( "#dialog_attribute_search" ).dialog( "close" );
				$( "#dialog_id_search" ).dialog( "close" );
			}
		});
		$( "#dialog_login" ).dialog({
			autoOpen: false,
			height:200,
			width:400,
			position:{my:"center", at:"center", of: window}
		});
		$( "#dialog_regist" ).dialog({
			autoOpen: false,
			height:250,
			width:400,
			position:{my:"center", at:"center", of: window}
		});
		$( "#dialog_addnews" ).dialog({
			autoOpen: false,
			height:250,
			width:500,
			position:{my:"center", at:"center", of: window}
		});
		$( "#dialog_attribute_search" ).dialog({
			autoOpen: false,
			height:200,
			width:600,
			position:{my:"center", at:"center", of: window}
		});
		$( "#dialog_id_search" ).dialog({
			autoOpen: false,
			height:250,
			width:500,
			position:{my:"center", at:"center", of: window}
		});
		$( "#button_login" ).click(function() {
			$( "#dialog_login" ).dialog( "open" );
			$( "#dialog_regist" ).dialog( "close" );
			$( "#dialog_addnews" ).dialog( "close" );
			$( "#dialog_attribute_search" ).dialog( "close" );
			$( "#dialog_id_search" ).dialog( "close" );
			return false;
		});
		$( "#button_regist" ).click(function() {
			$( "#dialog_regist" ).dialog( "open" );
			$( "#dialog_login" ).dialog( "close" );
			$( "#dialog_addnews" ).dialog( "close" );
			$( "#dialog_attribute_search" ).dialog( "close" );
			$( "#dialog_id_search" ).dialog( "close" );
			return false;
		});
		$( "#button_addnews" ).click(function() {
			$( "#dialog_addnews" ).dialog( "open" );
			$( "#dialog_login" ).dialog( "close" );
			$( "#dialog_regist" ).dialog( "close" );
			$( "#dialog_attribute_search" ).dialog( "close" );
			$( "#dialog_id_search" ).dialog( "close" );
			return false;
		});
		$( "#attribute_search" ).click(function() {
			$( "#dialog_attribute_search" ).dialog( "open" );
			$( "#dialog_login" ).dialog( "close" );
			$( "#dialog_regist" ).dialog( "close" );
			$( "#dialog_addnews" ).dialog( "close" );
			$( "#dialog_id_search" ).dialog( "close" );
			return false;
		});
		$( "#id_search" ).click(function() {
			$( "#dialog_id_search" ).dialog( "open" );
			$( "#dialog_login" ).dialog( "close" );
			$( "#dialog_regist" ).dialog( "close" );
			$( "#dialog_addnews" ).dialog( "close" );
			$( "#dialog_attribute_search" ).dialog( "close" );
			return false;
		});
		});
		function callLogout(){
			jQuery.ajax({
				type: "POST",
				url: 'index.php',
				data: {functionname: 'logout'},
				success: function(data){
					alert('已登出');
					location.href='index.php';
				}
			});
		}
		function callTotalSearch(){
			jQuery.ajax({
				type: "POST",
				url: 'index.php',
				data: {functionname: 'totalsearch'},
				success: function(data){
					location.href='index.php';
				}
			});
		}
	</script>
</head>
<body background="bg.jpg">
<div id="dialog_login" title="登入">
	<form method="POST" name="LoginForm" action="">
	
	<table border="0" height="105" align="center">
		<tr height="35">
			<td align="center">帳號：
				<input type="text" name="ID_i" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">密碼：
				<input type="password" name="PWD_i" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input style="font-family:Microsoft JhengHei" type="submit" name="submit_i" value="登入">
			</td>
		</tr>
	</table>
	</form>
</div>
<div id="dialog_regist" title="註冊">
	<form method="POST" name="RegistForm" action="">
	<table border="0" height="140" align="center">
		<tr height="35">
			<td align="center">帳號(16位英數字)：
				<input type="text" name="ID_r" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">密碼(16位英數字)：
				<input type="password" name="PWD_r" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">遊戲ID(9位數字)：
				<input type="text" name="gameID_r" size="9" maxlength="9">
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input style="font-family:Microsoft JhengHei" type="submit" name="submit_r" value="註冊">
			</td>
		</tr>
	</table>
	</form>
</div>
<div id="dialog_addnews" title="新增消息">
	<form method="POST" name="NewsForm" action="" enctype="multipart/form-data" >
	<table border="0" height="140" align="center">
		<tr height="35">
			<td align="center">
			<?php
				$sqlcmd = "SELECT count(*) AS reccount FROM fpnews ";
				$rs = querydb($sqlcmd, $db_conn);
				$RecCount = $rs[0]['reccount'];
			?>
				目前有 <?php echo $RecCount; ?> 個消息
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input type="file" name="news_image" id="news_image">
			</td>
		</tr>
		<tr height="35">
			<td align="center">連結：
				<input type="text" name="news_link" size="16" maxlength="500">
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input style="font-family:Microsoft JhengHei" type="submit" name="submit_addnews" value="新增">
			</td>
		</tr>
	</table>
	</form>
</div>
<div id="dialog_attribute_search" title="屬性檢索">
	<form method="POST" name="AttributeSearchForm" action="">
	<table border="0" height="105" align="center">
		<tr height="35">
			<td align="center">主屬性：
				<input type="radio" name="attribute_first" value='-1' checked>不指定&nbsp;
				<input type="radio" name="attribute_first" value='1'><img src="gem/Gem1.png">&nbsp;
				<input type="radio" name="attribute_first" value='2'><img src="gem/Gem2.png">&nbsp;
				<input type="radio" name="attribute_first" value='3'><img src="gem/Gem3.png">&nbsp;
				<input type="radio" name="attribute_first" value='4'><img src="gem/Gem4.png">&nbsp;
				<input type="radio" name="attribute_first" value='5'><img src="gem/Gem5.png">
			</td>
		</tr>
		<tr height="35">
			<td align="center">副屬性：
				<input type="radio" name="attribute_second" value='-1' checked>不指定&nbsp;
				<input type="radio" name="attribute_second" value='0'>無&nbsp
				<input type="radio" name="attribute_second" value='1'><img src="gem/Gem1.png">&nbsp;
				<input type="radio" name="attribute_second" value='2'><img src="gem/Gem2.png">&nbsp;
				<input type="radio" name="attribute_second" value='3'><img src="gem/Gem3.png">&nbsp;
				<input type="radio" name="attribute_second" value='4'><img src="gem/Gem4.png">&nbsp;
				<input type="radio" name="attribute_second" value='5'><img src="gem/Gem5.png">
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input style="font-family:Microsoft JhengHei" type="submit" name="tab2search" value="搜尋屬性">
			</td>
		</tr>
	</table>
	</form>
</div>
<div id="dialog_id_search" title="編號檢索">
	<form method="POST" name="IDSearchForm" action="">
	<table border="0" height="140" align="center">
		<tr height="35">
			<td align="center">帳號(16位英數字)：
				<input type="text" name="ID_r" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">密碼(16位英數字)：
				<input type="password" name="PWD_r" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">遊戲ID(9位數字)：
				<input type="text" name="gameID_r" size="9" maxlength="9">
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input style="font-family:Microsoft JhengHei" type="submit" name="tab2search" value="搜尋編號">
			</td>
		</tr>
	</table>
	</form>
</div>

<a href="index.php"><img src="title.jpg"></a>
<p style="direction:rtl;">
	<?php
	if(!isset($_SESSION['LoginID'])){
	?>
		<button id="button_login">登入</button>
		<button id="button_regist">註冊</button>
	<?php
	}
	else{
	?>
		<button onclick="callLogout()">登出</button>
	<?php
	}
	?>
</p>
<div id="tabs" style="background: none repeat scroll 0% 0% rgb(253, 245, 230);">
	<ul>
		<li><a href="#tabs-1">最新消息</a></li>
		<li><a href="#tabs-2">寵物圖鑑</a></li>
		<?php
		if(isset($_SESSION['LoginID'])){
			echo "<li><a href=\"#tabs-7\">紀錄表</a></li>";
		}
		?>
		<li><a href="#tabs-3">擁有率排名</a></li>
		<li><a href="#tabs-4">留言板</a></li>
		<?php
		if(isset($_SESSION['Authority']) && $_SESSION['Authority'] == 'Y'){
			echo "<li><a href=\"#tabs-5\">管理者 - 審核</a></li>";
			echo "<li><a href=\"#tabs-6\">管理者 - 管理權限</a></li>";
		}
		?>
	</ul>
	<div id="tabs-1">
		<?php
		if(isset($_SESSION['Authority']) && $_SESSION['Authority'] == 'Y'){
		?>
			<center><button id="button_addnews"><span style="font-family:Microsoft JhengHei;">新增消息</span></button></center>
		<?php } ?>
		<?php
		$sqlcmd = "SELECT count(*) AS reccount FROM fpnews ";
		$rs = querydb($sqlcmd, $db_conn);
		$RecCount1 = $rs[0]['reccount'];
		$TotalPage1 = (int) ceil($RecCount1/$ItemPerPage);
		if (!isset($Page1)) {
			if (isset($_SESSION['CurPage1'])) $Page1 = $_SESSION['CurPage1'];
			else $Page1 = 1;
		}
		if ($Page1 > $TotalPage1) $Page1 = $TotalPage1;
		$_SESSION['CurPage1'] = $Page1;
		$EndRec1 = $RecCount1 - ($Page1-1) * $ItemPerPage;
		$StartRec1 = $EndRec1 - $ItemPerPage;
		if($StartRec1 < 0){
			$ItemPerPage += $StartRec1;
			$StartRec1 = 0;
		}
		$sqlcmd = "SELECT * FROM fpnews " . "LIMIT $StartRec1,$ItemPerPage";
		$Contacts = querydb($sqlcmd, $db_conn);
		?>
		<?php if ($TotalPage1 > 1) { ?>
		<form name="SelPage1" method="POST" action="">
			<center>第<select name="Page1" onchange="submit();">
		<?php 
		for ($p=1; $p<=$TotalPage1; $p++) { 
			echo '  <option value="' . $p . '"';
			if ($p == $Page1) echo ' selected';
			echo ">$p</option>\n";
		}
		?>
		  </select>頁 共<?php echo $TotalPage1; ?>頁</center><br/>
		</form>
		<?php } ?>
		<table class="mistab" width="90%" align="center">
		<?php

		foreach (array_reverse($Contacts) AS $item) {
			$seqno = $item['seqno'];
			$image = $item['image'];
			$link = $item['link'];
			echo '<tr align="center"><td>';
		?>
			<a href="<?php echo $link; ?>" target="_blank"><img src="<?php echo $image; ?>" border="0" ></a>
			</td></tr>
		<?php } ?>
		</table>	
	</div>
	<div id="tabs-2">
		<p style="text-align:center">
			<button id="attribute_search"><span style="font-family:Microsoft JhengHei;">屬性檢索</span></button>&nbsp;
			<button id="id_search"><span style="font-family:Microsoft JhengHei;">編號檢索</span></button>&nbsp;
			<button onclick="callTotalSearch()"><span style="font-family:Microsoft JhengHei;">總覽</span></button>
		</p>
		<?php 
		if($tab2search == '搜尋屬性'){		
			$ItemPerPage21 = 10;
			if($attribute_first == '-1' && $attribute_second == '-1'){
				$sqlcmd = "SELECT count(*) AS reccount FROM fpmonster ";
			}
			else if($attribute_first == '-1'){
				$sqlcmd = "SELECT count(*) AS reccount FROM fpmonster WHERE attributesecond=$attribute_second";
			}
			else if($attribute_second == '-1'){
				$sqlcmd = "SELECT count(*) AS reccount FROM fpmonster WHERE attributefirst=$attribute_first";
			}
			else{
				$sqlcmd = "SELECT count(*) AS reccount FROM fpmonster WHERE attributefirst=$attribute_first AND attributesecond=$attribute_second";
			}
			$rs = querydb($sqlcmd, $db_conn);
			$RecCount21 = $rs[0]['reccount'];
			$TotalPage21 = (int) ceil($RecCount21/$ItemPerPage21);
			if (!isset($Page21)) {
				if (isset($_SESSION['CurPage21'])) $Page21 = $_SESSION['CurPage21'];
				else $Page21 = 1;
			}
			if ($Page21 > $TotalPage21) $Page21 = $TotalPage21;
			$_SESSION['CurPage21'] = $Page21;
			$StartRec21 = ($Page21-1) * $ItemPerPage21;
			if($attribute_first == '-1' && $attribute_second == '-1'){
				$sqlcmd = "SELECT * FROM fpmonster ORDER BY id ASC LIMIT $StartRec21,$ItemPerPage21";
			}
			else if($attribute_first == '-1'){
				echo 'YAAAAA';
				$sqlcmd = "SELECT * FROM fpmonster WHERE attributesecond=$attribute_second ORDER BY id ASC LIMIT $StartRec21,$ItemPerPage21";
			}
			else if($attribute_second == '-1'){
	
				$sqlcmd = "SELECT * FROM fpmonster WHERE attributefirst=$attribute_first ORDER BY id ASC LIMIT $StartRec21,$ItemPerPage21";
			}
			else{
				$sqlcmd = "SELECT * FROM fpmonster WHERE attributefirst=$attribute_first AND attributesecond=$attribute_second ORDER BY id ASC LIMIT $StartRec21,$ItemPerPage21";
			}
			$Contacts = querydb($sqlcmd, $db_conn);
			echo count($Contacts);
			?>
			<?php if ($TotalPage21 > 1) { ?>
			<form name="SelPage21" method="POST" action="">
				<center>第<select name="Page21" onchange="submit();">
			<?php 
			for ($p=1; $p<=$TotalPage21; $p++) { 
				echo '  <option value="' . $p . '"';
				if ($p == $Page21) echo ' selected';
				echo ">$p</option>\n";
			}
			?>
			  </select>頁 共<?php echo $TotalPage21; ?>頁</center><br/>
			</form>
			<?php } ?>
			<table class="mistab" width="40%" align="center" border="0">
			<?php
			foreach ($Contacts AS $item) {
				$image21 = $item['image'];
				$name21 = $item['name'];
				$first21 = $item['attributefirst'];
				$second21 = $item['attributesecond'];
				
				echo '<tr height="70"><td align="right" width="45%"><img src="'.$image21.'"></td>';
				echo '<td align="left" >&nbsp;屬性：<img src="gem/Gem'.$first21.'.png">/';
				if($second21 == '0'){
					echo '無<br/>';
				}
				else{
					echo '<img src="gem/Gem'.$second21.'.png"><br/>';
				}
				echo '&nbsp;'.$name21;
				echo '</td>';
			}
			?>
			</table>
		<?php
		}
		else if($tab2search == '搜尋編號'){
		?>
		<?php
		}
		else if($tab2search == '3'){
			$ItemPerPage23 = 50;
			$sqlcmd = "SELECT count(*) AS reccount FROM fpmonster ";
			$rs = querydb($sqlcmd, $db_conn);
			$RecCount23 = $rs[0]['reccount'];
			$TotalPage23 = (int) ceil($RecCount23/$ItemPerPage23);
			if (!isset($Page23)) {
				if (isset($_SESSION['CurPage23'])) $Page23 = $_SESSION['CurPage23'];
				else $Page23 = 1;
			}
			if ($Page23 > $TotalPage23) $Page23 = $TotalPage23;
			$_SESSION['CurPage23'] = $Page23;
			$StartRec23 = ($Page23-1) * $ItemPerPage23;
			$sqlcmd = "SELECT * FROM fpmonster " . "ORDER BY id ASC LIMIT $StartRec23,$ItemPerPage23";
			$Contacts = querydb($sqlcmd, $db_conn);
			?>
			<?php if ($TotalPage23 > 1) { ?>
			<form name="SelPage23" method="POST" action="">
				<center>第<select name="Page23" onchange="submit();">
			<?php 
			for ($p=1; $p<=$TotalPage23; $p++) { 
				echo '  <option value="' . $p . '"';
				if ($p == $Page23) echo ' selected';
				echo ">$p</option>\n";
			}
			?>
			  </select>頁 共<?php echo $TotalPage23; ?>頁</center><br/>
			</form>
			<?php } ?>
			<table class="mistab" width="80%" align="center" border="0">
			<tr>
			<td style="word-break: break-all;">
			<?php
			for($i = 0; $i < count($Contacts); $i++){
				$image2 = $Contacts[$i]['image'];
				echo '<img src="'.$image2.'">';
			}
			?>
			</td>
			</tr>
			</table>
		<?php
		}
		?>
	</div>
	<div id="tabs-3">

	</div>
	<div id="tabs-4">
		<?php
		$sqlcmd = "SELECT count(*) AS reccount FROM fpcomment ";
		$rs = querydb($sqlcmd, $db_conn);
		$RecCount4 = $rs[0]['reccount'];
		$TotalPage4 = (int) ceil($RecCount4/$ItemPerPage);
		if (!isset($Page4)) {
			if (isset($_SESSION['CurPage4'])) $Page4 = $_SESSION['CurPage4'];
			else $Page4 = 1;
		}
		if ($Page4 > $TotalPage4) $Page4 = $TotalPage4;
		$_SESSION['CurPage4'] = $Page4;
		$StartRec4 = ($Page4-1) * $ItemPerPage;
		$sqlcmd = "SELECT * FROM fpcomment " . "LIMIT $StartRec4,$ItemPerPage";
		$Contacts = querydb($sqlcmd, $db_conn);
		?>
		<?php if ($TotalPage4 > 1) { ?>
		<form name="SelPage4" method="POST" action="">
			<center>第<select name="Page4" onchange="submit();">
		<?php 
		for ($p=1; $p<=$TotalPage4; $p++) { 
			echo '  <option value="' . $p . '"';
			if ($p == $Page4) echo ' selected';
			echo ">$p</option>\n";
		}
		?>
		  </select>頁 共<?php echo $TotalPage4; ?>頁</center><br/>
		</form>
		<?php } ?>
		<table class="mistab" style="table-layout:fixed;" width="75%" align="center">
		<?php
		foreach ($Contacts AS $item) {
			$seqno = $item['seqno'];
			$content = $item['content'];
			$image = $item['image'];
			$time = $item['time'];
			$user = $item['user'];
			$userid = $item['userid'];
			$usergroup = $item['usergroup'];
			?>
			<tr style="text-align:left"><td>
			<?php echo $content; ?>
			</td></tr>
			<tr style="text-align:right"><td>
			<?php echo "by ".$user; ?>
			</td></tr>
			<tr style="text-align:right"><td>
			<?php 
				echo $userid." "; 
				$groupImage = "group/".$usergroup.".png";
				echo "<img src=".$groupImage." border=\"0\">";
			?>
			</td></tr>
			<tr style="text-align:right"><td>
			<?php echo "at ".$time; ?>
			</td></tr>
			<?php
				if(isset($_SESSION['Authority']) && $_SESSION['Authority'] == 'Y'){
			?>
					<tr style="text-align:right"><td><a href="index.php?action=commentD&seqno=<?php echo $seqno; ?>">刪除留言</a></td></tr>
			<?php }	?>
			<tr align="center"><td height="30px" background="line9.png"></td></tr>
		<?php } ?>
		</table>
		
		<?php 
		if(isset($_SESSION['LoginID'])){
		?>
		<form method="POST" name="CommentForm" action="">
		<table class="mistab" width="60%" align="center">
			<tr height="200">
				<td style="text-align:left">
					<textarea rows=7 cols=100 name="content" required></textarea>
				</td>
			</tr>
			<tr height="35">
				<td style="text-align:right">
					要附上圖鑑嗎？&nbsp;
					<input type="radio" name="image_comment" value="Y">是&nbsp;
					<input type="radio" name="image_comment" value="N" checked>否&nbsp;
				</td>
			</tr>
			<tr height="35">
				<td style="text-align:right">
					<input style="font-family:Microsoft JhengHei" type="submit" name="submit_comment" value="留言">
				</td>
			</tr>
		</table>
		</form>
		<?php } ?>
	</div>
	<div id="tabs-5">
		<?php
		if(isset($_SESSION['Authority']) && $_SESSION['Authority'] == 'Y'){
		$sqlcmd = "SELECT count(*) AS reccount FROM fprequest ";
		$rs = querydb($sqlcmd, $db_conn);
		$RecCount5 = $rs[0]['reccount'];
		$TotalPage5 = (int) ceil($RecCount5/$ItemPerPage);
		if (!isset($Page5)) {
			if (isset($_SESSION['CurPage5'])) $Page5 = $_SESSION['CurPage5'];
			else $Page5 = 1;
		}
		if ($Page5 > $TotalPage5) $Page5 = $TotalPage5;
		$_SESSION['CurPage5'] = $Page5;
		$StartRec5 = ($Page5-1) * $ItemPerPage;
		$sqlcmd = "SELECT * FROM fprequest " . "LIMIT $StartRec5,$ItemPerPage";
		$Contacts = querydb($sqlcmd, $db_conn);
		?>
		<?php if ($TotalPage5 > 1) { ?>
		<form name="SelPage5" method="POST" action="">
			<center>第<select name="Page5" onchange="submit();">
		<?php 
		for ($p=1; $p<=$TotalPage5; $p++) { 
			echo '  <option value="' . $p . '"';
			if ($p == $Page5) echo ' selected';
			echo ">$p</option>\n";
		}
		?>
		  </select>頁 共<?php echo $TotalPage5; ?>頁</center><br/>
		</form>
		<?php } ?>
		<table class="mistab" width="90%" align="center" border="1">
		<tr>
			<th width="10%">處理</th>
			<th width="10%">帳號</th>
			<th width="35%">密碼</th>
			<th width="15%">遊戲ID</th>
			<th width="15%">組別</th>
			<th width="15%">申請時間</th>
		</tr>
		<?php
		foreach ($Contacts AS $item) {
			$seqno = $item['seqno'];
			$loginid = $item['loginid'];
			$password = $item['password'];
			$gameid = $item['gameid'];
			$gamegroup = $item['gamegroup'];
			$registtime = $item['registtime'];
			echo '<tr align="center"><td>';
		?>
			<a href="index.php?action=pass&seqno=<?php echo $seqno; ?>">通過審核</a>
			</td>
			<td><?php echo $loginid; ?></td>   
			<td><?php echo $password; ?></td>  
			<td><?php echo $gameid; ?></td>
			<td><?php echo $gamegroup; ?></td>    
			<td><?php echo $registtime; ?></td>  			
			</tr>
		<?php } ?>
		</table>
		<?php
		}
		?>
	</div>
	<div id="tabs-6">
		<?php
		if(isset($_SESSION['Authority']) && $_SESSION['Authority'] == 'Y'){
		$sqlcmd = "SELECT count(*) AS reccount FROM fpuser WHERE authority='N'";
		$rs = querydb($sqlcmd, $db_conn);
		$RecCount6 = $rs[0]['reccount'];
		$TotalPage6 = (int) ceil($RecCount6/$ItemPerPage);
		if (!isset($Page6)) {
			if (isset($_SESSION['CurPage6'])) $Page6 = $_SESSION['CurPage6'];
			else $Page6 = 1;
		}
		if ($Page6 > $TotalPage6) $Page6 = $TotalPage6;
		$_SESSION['CurPage6'] = $Page6;
		$StartRec6 = ($Page6-1) * $ItemPerPage;
		$sqlcmd = "SELECT * FROM fpuser " . "WHERE authority='N' LIMIT $StartRec6,$ItemPerPage";
		$Contacts = querydb($sqlcmd, $db_conn);
		?>
		<?php if ($TotalPage6 > 1) { ?>
		<form name="SelPage6" method="POST" action="">
			<center>第<select name="Page6" onchange="submit();">
		<?php 
		for ($p=1; $p<=$TotalPage6; $p++) { 
			echo '  <option value="' . $p . '"';
			if ($p == $Page6) echo ' selected';
			echo ">$p</option>\n";
		}
		?>
		  </select>頁 共<?php echo $TotalPage6; ?>頁</center><br/>
		</form>
		<?php } ?>
		<table class="mistab" width="90%" align="center" border="1">
		<tr>
			<th width="10%">處理</th>
			<th width="10%">帳號</th>
			<th width="35%">密碼</th>
			<th width="15%">遊戲ID</th>
			<th width="15%">組別</th>
			<th width="15%">申請時間</th>
		</tr>
		<?php
		foreach ($Contacts AS $item) {
			$seqno = $item['seqno'];
			$loginid = $item['loginid'];
			$password = $item['password'];
			$gameid = $item['gameid'];
			$gamegroup = $item['gamegroup'];
			$registtime = $item['registtime'];
			$valid = $item['valid'];
			echo '<tr align="center"><td>';
			if($valid == 'N'){
		?>
			<a href="index.php?action=recover&seqno=<?php echo $seqno; ?>">恢復</a>
			</td>
			<td><STRIKE><?php echo $loginid; ?></STRIKE></td>  
			<?php }else{ ?>
			<a href="index.php?action=delete&seqno=<?php echo $seqno; ?>">停權</a>
			</td>
			<td><?php echo $loginid; ?></td>   
			<?php } ?>
			<td><?php echo $password; ?></td>  
			<td><?php echo $gameid; ?></td>
			<td><?php echo $gamegroup; ?></td>    
			<td><?php echo $registtime; ?></td>  			
			</tr>
		<?php } ?>
		</table>
		<?php
		}
		?>
	</div>
	<div id="tabs-7">
	</div>
</div>
 
 
</body>
</html>