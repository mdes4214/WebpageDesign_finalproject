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
		$ErrMsg = '�z�ëD�X�k�ϥΪ̩άO�ϥ��v�w�Q����';
	} else {
		$ErrMsg = '�b�����~�A�z�ëD�X�k�ϥΪ̩άO�ϥ��v�w�Q����';
	}
	if (empty($ErrMsg)) $ErrMsg = '�n�J���~';
}
if (isset($submit_r)) {
	if (strlen($ID_r) > 0 && strlen($ID_r)<=16 && $ID_r==addslashes($ID_r) && $ID_r==htmlspecialchars($ID_r) && strlen($PWD_r) > 0 && strlen($PWD_r)<=16 && $PWD_r==addslashes($PWD_r) && $PWD_r==htmlspecialchars($PWD_r)) {
		$gamegroup = judgegroup($gameID_r);
		if($gamegroup == "FALSE" || strlen($gameID_r) != 9 || !is_numeric($gameID_r)){
			$ErrMsg = '�榡���~�A�Ш̴`���T�榡��J';
		}
		else{
			$sqlcmd = "SELECT * FROM fpuser WHERE loginid='$ID_r'";
			$rs1 = querydb($sqlcmd, $db_conn);
			$sqlcmd = "SELECT * FROM fprequest WHERE loginid='$ID_r'";
			$rs2 = querydb($sqlcmd, $db_conn);
			if(!empty($rs1) || !empty($rs2)){
				$ErrMsg = '�ϥΪ̤w�s�b�A�Э��s��J';
			}
			else{
				$gameid = makeid($gameID_r);
				$time = date('Y-m-d H:i:s', time('NOW'));
				$pwd = sha1($PWD_r);
				$sqlcmd = "INSERT INTO fprequest (loginid, password, gameid, gamegroup, registtime) VALUES('$ID_r', '$pwd', '$gameid', '$gamegroup', '$time')";
				$result = updatedb($sqlcmd, $db_conn);
				
				echo "<script language=javascript>"; 
				echo "window.alert('�w�e�X�ӽСA�е��ݺ޲z�̼f��')"; 
				echo "</script>"; 
				echo "<script language=\"javascript\">"; 
				echo "location.href='index.php'"; 
				echo "</script>"; 
				exit();
			}
		}
	} else {
		$ErrMsg = '�榡���~�A�Ш̴`���T�榡��J';
	}
	if (empty($ErrMsg)) $ErrMsg = '���U���~';
}
if (isset($submit_addnews)) {
	if(strlen($news_link) > 0 && $news_link == addslashes($news_link) && $news_link == htmlspecialchars($news_link)){
		if($_FILES["news_image"]["error"] > 0){
			$ErrMsg = '�W���ɮצ��~�A�Э��s�W���ɮ�';
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
			echo "window.alert('�w���\�s�W')"; 
			echo "</script>"; 
			echo "<script language=\"javascript\">"; 
			echo "location.href='index.php'"; 
			echo "</script>"; 
			exit();
		}
	}
	else{
		$ErrMsg = '�榡���~�A�Ш̴`���T�榡��J';
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
		echo "window.alert('�w�d��')"; 
		echo "</script>"; 
		echo "<script language=\"javascript\">"; 
		echo "location.href='index.php'"; 
		echo "</script>"; 
		exit();
	}
	else{
		$ErrMsg = '�榡���~�A�Э��s��J';
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
	<title>�C����Ų��T�O���[���ɥ�y���x</title>
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
		$( "#button_login" ).click(function() {
			$( "#dialog_login" ).dialog( "open" );
			return false;
		});
		$( "#button_regist" ).click(function() {
			$( "#dialog_regist" ).dialog( "open" );
			return false;
		});
		$( "#button_addnews" ).click(function() {
			$( "#dialog_addnews" ).dialog( "open" );
			return false;
		});
		});
		function callLogout(){
			jQuery.ajax({
				type: "POST",
				url: 'index.php',
				data: {functionname: 'logout'},
				success: function(data){
					alert('�w�n�X');
					location.href='index.php';
				}
			});
		}
	</script>
</head>
<body background="bg.jpg">
<div id="dialog_login" title="�n�J">
	<form method="POST" name="LoginForm" action="">
	
	<table border="0" height="105" align="center">
		<tr height="35">
			<td align="center">�b���G
				<input type="text" name="ID_i" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">�K�X�G
				<input type="password" name="PWD_i" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input type="submit" name="submit_i" value="�n�J">
			</td>
		</tr>
	</table>
	</form>
</div>
<div id="dialog_regist" title="���U">
	<form method="POST" name="RegistForm" action="">
	<table border="0" height="140" align="center">
		<tr height="35">
			<td align="center">�b��(16��^�Ʀr)�G
				<input type="text" name="ID_r" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">�K�X(16��^�Ʀr)�G
				<input type="password" name="PWD_r" size="16" maxlength="16">
			</td>
		</tr>
		<tr height="35">
			<td align="center">�C��ID(9��Ʀr)�G
				<input type="text" name="gameID_r" size="9" maxlength="9">
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input type="submit" name="submit_r" value="���U">
			</td>
		</tr>
	</table>
	</form>
</div>
<div id="dialog_addnews" title="�s�W����">
	<form method="POST" name="NewsForm" action="" enctype="multipart/form-data" >
	<table border="0" height="140" align="center">
		<tr height="35">
			<td align="center">
			<?php
				$sqlcmd = "SELECT count(*) AS reccount FROM fpnews ";
				$rs = querydb($sqlcmd, $db_conn);
				$RecCount = $rs[0]['reccount'];
			?>
				�ثe�� <?php echo $RecCount; ?> �Ӯ���
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input type="file" name="news_image" id="news_image">
			</td>
		</tr>
		<tr height="35">
			<td align="center">�s���G
				<input type="text" name="news_link" size="16" maxlength="500">
			</td>
		</tr>
		<tr height="35">
			<td align="center">
				<input type="submit" name="submit_addnews" value="�s�W">
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
		<button id="button_login">�n�J</button>
		<button id="button_regist">���U</button>
	<?php
	}
	else{
	?>
		<button onclick="callLogout()">�n�X</button>
	<?php
	}
	?>
</p>
<div id="tabs" style="background: none repeat scroll 0% 0% rgb(253, 245, 230);">
	<ul>
		<li><a href="#tabs-1">�̷s����</a></li>
		<li><a href="#tabs-2">�d����Ų</a></li>
		<li><a href="#tabs-3">�֦��v�ƦW</a></li>
		<li><a href="#tabs-4">�d���O</a></li>
		<?php
		if(isset($_SESSION['Authority']) && $_SESSION['Authority'] == 'Y'){
			echo "<li><a href=\"#tabs-5\">�޲z�� - �f��</a></li>";
			echo "<li><a href=\"#tabs-6\">�޲z�� - �޲z�v��</a></li>";
		}
		?>
	</ul>
	<div id="tabs-1">
		<?php
		if(isset($_SESSION['Authority']) && $_SESSION['Authority'] == 'Y'){
		?>
			<center><button id="button_addnews"><span style="font-family:Microsoft JhengHei;">�s�W����</span></button></center>
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
			<center>��<select name="Page1" onchange="submit();">
		<?php 
		for ($p=1; $p<=$TotalPage1; $p++) { 
			echo '  <option value="' . $p . '"';
			if ($p == $Page1) echo ' selected';
			echo ">$p</option>\n";
		}
		?>
		  </select>�� �@<?php echo $TotalPage1; ?>��</center>
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
		<a><img src="monster/001.png" border="0" ></a>
		<a><img src="monster/002.png" border="0" ></a>
		<a><img src="monster/003.png" border="0" ></a>
		<a><img src="monster/004.png" border="0" ></a>
		<a><img src="monster/005.png" border="0" ></a>
		<a><img src="monster/006.png" border="0" ></a>
		<a><img src="monster/007.png" border="0" ></a>
		<a><img src="monster/008.png" border="0" ></a>
		<a><img src="monster/009.png" border="0" ></a>
		<a><img src="monster/010.png" border="0" ></a>
		<a><img src="monster/011.png" border="0" ></a>
		<a><img src="monster/012.png" border="0" ></a>
		<a><img src="monster/013.png" border="0" ></a>
		<a><img src="monster/014.png" border="0" ></a>
		<a><img src="monster/015.png" border="0" ></a>
		<a><img src="monster/016.png" border="0" ></a>
		<a><img src="monster/017.png" border="0" ></a>
		<a><img src="monster/018.png" border="0" ></a>
		<a><img src="monster/019.png" border="0" ></a>
		<a><img src="monster/020.png" border="0" ></a>
		<a><img src="monster/021.png" border="0" ></a>
		<a><img src="monster/022.png" border="0" ></a>
		<a><img src="monster/023.png" border="0" ></a>
		<a><img src="monster/024.png" border="0" ></a>
		<a><img src="monster/025.png" border="0" ></a>
		<a><img src="monster/026.png" border="0" ></a>
		<a><img src="monster/027.png" border="0" ></a>
		<a><img src="monster/028.png" border="0" ></a>
		<a><img src="monster/029.png" border="0" ></a>
		<a><img src="monster/030.png" border="0" ></a>
		<a><img src="monster/031.png" border="0" ></a>
		<a><img src="monster/032.png" border="0" ></a>
		<a><img src="monster/033.png" border="0" ></a>
		<a><img src="monster/034.png" border="0" ></a>
		<a><img src="monster/035.png" border="0" ></a>
		<a><img src="monster/036.png" border="0" ></a>
		<a><img src="monster/037.png" border="0" ></a>
		<a><img src="monster/038.png" border="0" ></a>
		<a><img src="monster/039.png" border="0" ></a>
		<a><img src="monster/040.png" border="0" ></a>
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
		<form name="SelPage1" method="POST" action="">
			<center>��<select name="Page4" onchange="submit();">
		<?php 
		for ($p=1; $p<=$TotalPage4; $p++) { 
			echo '  <option value="' . $p . '"';
			if ($p == $Page4) echo ' selected';
			echo ">$p</option>\n";
		}
		?>
		  </select>�� �@<?php echo $TotalPage4; ?>��</center>
		</form>
		<?php } ?>
		<table class="mistab" width="75%" align="center">
		<?php
		foreach ($Contacts AS $item) {
			$seqno = $item['seqno'];
			$content = $item['content'];
			$image = $item['image'];
			$time = $item['time'];
			$user = $item['user'];
			$userid = $item['userid'];
			$usergroup = $item['usergroup'];
			echo '<tr align="center"><td><a><img src="line2.gif" border="0" ><img src="line1.gif" border="0" ><img src="line2.gif" border="0" ></a></td></tr>';
			echo '<tr align="center"><td>';
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
					<tr style="text-align:right"><td><a href="index.php?action=commentD&seqno=<?php echo $seqno; ?>">�R���d��</a></td></tr>
			<?php }	?>
			<tr align="center"><td><a><img src="line1.gif" border="0" ><img src="line2.gif" border="0" ><img src="line1.gif" border="0" ></a></td></tr>
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
					�n���W��Ų�ܡH&nbsp;
					<input type="radio" name="image_comment" value="Y">�O&nbsp;
					<input type="radio" name="image_comment" value="N" checked>�_&nbsp;
				</td>
			</tr>
			<tr height="35">
				<td style="text-align:right">
					<input type="submit" name="submit_comment" value="�d��">
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
			<center>��<select name="Page5" onchange="submit();">
		<?php 
		for ($p=1; $p<=$TotalPage5; $p++) { 
			echo '  <option value="' . $p . '"';
			if ($p == $Page5) echo ' selected';
			echo ">$p</option>\n";
		}
		?>
		  </select>�� �@<?php echo $TotalPage5; ?>��</center>
		</form>
		<?php } ?>
		<table class="mistab" width="90%" align="center" border="1">
		<tr>
			<th width="10%">�B�z</th>
			<th width="10%">�b��</th>
			<th width="35%">�K�X</th>
			<th width="15%">�C��ID</th>
			<th width="15%">�էO</th>
			<th width="15%">�ӽЮɶ�</th>
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
			<a href="index.php?action=pass&seqno=<?php echo $seqno; ?>">�q�L�f��</a>
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
			<center>��<select name="Page6" onchange="submit();">
		<?php 
		for ($p=1; $p<=$TotalPage6; $p++) { 
			echo '  <option value="' . $p . '"';
			if ($p == $Page6) echo ' selected';
			echo ">$p</option>\n";
		}
		?>
		  </select>�� �@<?php echo $TotalPage6; ?>��</center>
		</form>
		<?php } ?>
		<table class="mistab" width="90%" align="center" border="1">
		<tr>
			<th width="10%">�B�z</th>
			<th width="10%">�b��</th>
			<th width="35%">�K�X</th>
			<th width="15%">�C��ID</th>
			<th width="15%">�էO</th>
			<th width="15%">�ӽЮɶ�</th>
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
			<a href="index.php?action=recover&seqno=<?php echo $seqno; ?>">��_</a>
			</td>
			<td><STRIKE><?php echo $loginid; ?></STRIKE></td>  
			<?php }else{ ?>
			<a href="index.php?action=delete&seqno=<?php echo $seqno; ?>">���v</a>
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
</div>
 
 
</body>
</html>