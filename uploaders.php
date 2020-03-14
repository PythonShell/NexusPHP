<?php
require "include/bittorrent.php";
dbconn();
require_once(get_langfile_path());
loggedinorreturn();

if (get_user_class() < UC_UPLOADER)
    permissiondenied();

$year=0+$_GET['year'];
if (!$year || $year < 2000)
$year=date('Y');
$month=0+$_GET['month'];
if (!$month || $month<=0 || $month>12)
$month=date('m');
$order=$_GET['order'];
if (!in_array($order, array('username', 'torrent_size', 'torrent_count')))
	$order='username';
if ($order=='username')
	$order .=' ASC';
else $order .= ' DESC';

$wage=0+$_GET['wage'];
stdhead($lang_uploaders['head_uploaders']);
begin_main_frame();
?>
<div style="width: 940px">
<?php
$year2 = substr($datefounded, 0, 4);
$yearfounded = ($year2 ? $year2 : 2007);
$yearnow=date("Y");

$timestart=strtotime($year."-".$month."-01 00:00:00");
$sqlstarttime=date("Y-m-d H:i:s", $timestart);
$timeend=strtotime("+1 month", $timestart);
$sqlendtime=date("Y-m-d H:i:s", $timeend);

print("<h1 align=\"center\">".$lang_uploaders['text_uploaders']." - ".date("Y-m",$timestart)."</h1>");

$yearselection="<select name=\"year\">";
for($i=$yearfounded; $i<=$yearnow; $i++)
	$yearselection .= "<option value=\"".$i."\"".($i==$year ? " selected=\"selected\"" : "").">".$i."</option>";
$yearselection.="</select>";

$monthselection="<select name=\"month\">";
for($i=1; $i<=12; $i++)
	$monthselection .= "<option value=\"".$i."\"".($i==$month ? " selected=\"selected\"" : "").">".$i."</option>";
$monthselection.="</select>";

?>
<div>
<form method="get" action="?">
<span>
<?php echo $lang_uploaders['text_select_month']?><?php echo $yearselection?>&nbsp;&nbsp;<?php echo $monthselection?>&nbsp;&nbsp;<input type="submit" value="<?php echo $lang_uploaders['submit_go']?>" />
</span>
</form>
</div>

<?php
$numres = sql_query("SELECT COUNT(users.id) FROM users WHERE class >= ".UC_UPLOADER) or sqlerr(__FILE__, __LINE__);
$numrow = mysql_fetch_array($numres);
$num=$numrow[0];
if (!$num)
	print("<p align=\"center\">".$lang_uploaders['text_no_uploaders_yet']."</p>");
else{
?>
<div style="margin-top: 8px">
<?php
	print("<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\" width=\"940\"><tr>");
	print("<td class=\"colhead\">".$lang_uploaders['col_username']."</td>");
	if($wage){
		print("<td class=\"colhead\">发布种子数量</td>");
		print("<td class=\"colhead\">EDU资源资源1G以上数量</td>");
		print("<td class=\"colhead\">EDU资源资源1G以下数量</td>");
		print("<td class=\"colhead\">其他资源数量</td>");
		print("<td class=\"colhead\">合计魔力工资</td>");
		print("<td class=\"colhead\">奖励邀请数量</td>");
		print("<td class=\"colhead\">是否通过考核</td>");
	}
	else {
	print("<td class=\"colhead\">".$lang_uploaders['col_torrents_size']."</td>");
	print("<td class=\"colhead\">".$lang_uploaders['col_torrents_num']."</td>");
	print("<td class=\"colhead\">".$lang_uploaders['col_last_upload_time']."</td>");
	print("<td class=\"colhead\">".$lang_uploaders['col_last_upload']."</td>");
	}
	print("</tr>");
	if ($wage) {
		/* add by pythonshell@TCCF begin at 20200315 */
		$time_period_condition = "t.added >= ".sqlesc($sqlstarttime)." AND t.added < ".sqlesc($sqlendtime);
		$user_class_condition = "u.class >= ".UC_UPLOADER;
		$edu_category = "(604, 605)";

		$base_q = "SELECT u.id, u.username FROM users u WHERE $user_class_condition";
		$main_q = "SELECT u.id, count(1) AS tc FROM users u LEFT JOIN torrents t ON t.owner=u.id WHERE $user_class_condition AND $time_period_condition GROUP BY u.id";
		$edu_big_q = "SELECT u.id, count(1) AS edu_big FROM users u LEFT JOIN torrents t ON t.owner=u.id WHERE t.size >= 1024*1024*1024 AND t.category IN $edu_category AND $user_class_condition AND $time_period_condition GROUP BY u.id";
		$edu_little_q = "SELECT u.id, count(t.id) AS edu_little FROM users u LEFT JOIN torrents t ON t.owner=u.id WHERE t.size < 1024*1024*1024 AND t.category IN $edu_category AND $user_class_condition AND $time_period_condition GROUP BY u.id";
		$non_edu_q = "SELECT u.id, count(t.id) AS non_edu FROM users u LEFT JOIN torrents t ON t.owner=u.id WHERE t.category NOT IN $edu_category AND $user_class_condition AND $time_period_condition GROUP BY u.id";
		$full_q = "SELECT base.id, base.username, IFNULL(a.tc,0) AS torrent_total, IFNULL(b.edu_big,0) AS edu_big, IFNULL(c.edu_little,0) AS edu_little, IFNULL(d.non_edu,0) AS non_edu, IFNULL(b.edu_big,0)*2000+IFNULL(c.edu_little,0)*1000+IFNULL(d.non_edu,0)*800 AS magic_value, (CASE WHEN a.tc>=80 THEN 3 WHEN a.tc >= 50 THEN 2 WHEN a.tc >= 30 THEN 1 ELSE 0 END) AS invite, (CASE WHEN a.tc>=20 THEN 1 ELSE 0 END) AS passed FROM ($base_q) base LEFT JOIN ($main_q) a ON base.id=a.id LEFT JOIN ($edu_big_q) b ON base.id=b.id LEFT JOIN ($edu_little_q) c ON base.id=c.id LEFT JOIN ($non_edu_q) d ON base.id=d.id";

		#var_dump($full_q);
		$res = sql_query($full_q);
		/* add by pythonshell@TCCF end at 20200315 */
	}
	else {
	$res = sql_query("SELECT users.id AS userid, users.username AS username, COUNT(torrents.id) AS torrent_count, SUM(torrents.size) AS torrent_size FROM torrents LEFT JOIN users ON torrents.owner=users.id WHERE users.class >= ".UC_UPLOADER." AND torrents.added > ".sqlesc($sqlstarttime)." AND torrents.added < ".sqlesc($sqlendtime)." GROUP BY userid ORDER BY ".$order);
	}


	$hasupuserid=array();
	while($row = mysql_fetch_array($res))
	{
		if($wage) {
		print("<tr>");
		print("<td class=\"colfollow\">".get_username($row['id'], false, true, true, false, false, false)."</td>");
		print("<td class=\"colfollow\">".$row['torrent_total']."</td>");
		print("<td class=\"colfollow\">".$row['edu_big']."</td>");
		print("<td class=\"colfollow\">".$row['edu_little']."</td>");
		print("<td class=\"colfollow\">".$row['non_edu']."</td>");
		print("<td class=\"colfollow\">".$row['magic_value']."</td>");
		print("<td class=\"colfollow\">".$row['invite']."</td>");
		print("<td class=\"colfollow\">".$row['passed']."</td>");
		}
		else {
		$res2 = sql_query("SELECT torrents.id, torrents.name, torrents.added FROM torrents WHERE owner=".$row['userid']." ORDER BY id DESC LIMIT 1");
		$row2 = mysql_fetch_array($res2);
		print("<tr>");
		print("<td class=\"colfollow\">".get_username($row['userid'], false, true, true, false, false, true)."</td>");
		print("<td class=\"colfollow\">".($row['torrent_size'] ? mksize($row['torrent_size']) : "0")."</td>");
		print("<td class=\"colfollow\">".$row['torrent_count']."</td>");
		print("<td class=\"colfollow\">".($row2['added'] ? gettime($row2['added']) : $lang_uploaders['text_not_available'])."</td>");
		print("<td class=\"colfollow\">".($row2['name'] ? "<a href=\"details.php?id=".$row2['id']."\">".htmlspecialchars($row2['name'])."</a>" : $lang_uploaders['text_not_available'])."</td>");
		print("</tr>");
		$hasupuserid[]=$row['userid'];
		unset($row2);
		}
	}
	if(!$wage)
	$res3=sql_query("SELECT users.id AS userid, users.username AS username, 0 AS torrent_count, 0 AS torrent_size FROM users WHERE class >= ".UC_UPLOADER.(count($hasupuserid) ? " AND users.id NOT IN (".implode(",",$hasupuserid).")" : "")." ORDER BY username ASC") or sqlerr(__FILE__, __LINE__);
	while($row = mysql_fetch_array($res3))
	{
		$res2 = sql_query("SELECT torrents.id, torrents.name, torrents.added FROM torrents WHERE owner=".$row['userid']." ORDER BY id DESC LIMIT 1");
		$row2 = mysql_fetch_array($res2);
		print("<tr>");
		print("<td class=\"colfollow\">".get_username($row['userid'], false, true, true, false, false, true)."</td>");
		print("<td class=\"colfollow\">".($row['torrent_size'] ? mksize($row['torrent_size']) : "0")."</td>");
		print("<td class=\"colfollow\">".$row['torrent_count']."</td>");
		print("<td class=\"colfollow\">".($row2['added'] ? gettime($row2['added']) : $lang_uploaders['text_not_available'])."</td>");
		print("<td class=\"colfollow\">".($row2['name'] ? "<a href=\"details.php?id=".$row2['id']."\">".htmlspecialchars($row2['name'])."</a>" : $lang_uploaders['text_not_available'])."</td>");
		print("</tr>");
		$count++;
		unset($row2);
	}
	print("</table>");
	}
?>
</div>
<?php
if (!$wage) {
?>
<div style="margin-top: 8px; margin-bottom: 8px;">
<span id="order" onclick="dropmenu(this);"><span style="cursor: pointer;" class="big"><b><?php echo $lang_uploaders['text_order_by']?></b></span>
<span id="orderlist" class="dropmenu" style="display: none"><ul>
<li><a href="?year=<?php echo $year?>&amp;month=<?php echo $month?>&amp;order=username"><?php echo $lang_uploaders['text_username']?></a></li>
<li><a href="?year=<?php echo $year?>&amp;month=<?php echo $month?>&amp;order=torrent_size"><?php echo $lang_uploaders['text_torrent_size']?></a></li>
<li><a href="?year=<?php echo $year?>&amp;month=<?php echo $month?>&amp;order=torrent_count"><?php echo $lang_uploaders['text_torrent_num']?></a></li>
</ul>
</span>
</span>
</div>
<?php
}
?>

</div>
<?php
end_main_frame();
stdfoot();
?>
