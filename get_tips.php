<?php
$use_select_user = true;
include("common.php");
if (isset($_REQUEST['clause'])) $clause = $_REQUEST['clause'];//if clause is specified, use it
else $clause = "1";
if (strstr($clause,';')) {//if there's a semicolon
  $clause = strstr($clause,';',true);//truncate at first semicolon
}
$query = "SELECT * FROM `tips` WHERE ".$clause;
$result = mysql_query($query)or die("e".mysql_error());
echo "s";
$first = true;
while ($assoc = mysql_fetch_assoc($result)) {
  if (!$first) echo "/";
  else $first = false;
  echo $assoc['id']."&";
  echo $assoc['from_id_address']."&";
  echo $assoc['to_id_address']."&";
  echo $assoc['amount']."&";
  echo $assoc['data_id']."&";
  echo $assoc['command_id']."&";
  echo strtotime($assoc['timestamp']);
}
?>