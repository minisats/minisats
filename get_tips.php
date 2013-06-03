<?php
include("common.php");
if (!isset($_REQUEST['clause'])) {
  die ("eClause not defined");
}
$clause = $_REQUEST['clause'];
$clause = explode(",",$clause)[0];
$query = "SELECT * FROM `tips` WHERE ".$clause;
$result = mysql_query($query)or die("emysql_error: ".mysql_error());

$to_replace = Array("%",",",";");
$replacements = Array("%25","%2C","%3B");

$first = true;
echo "s";
while ($assoc = mysql_fetch_assoc($result)) {
  if (!$first) echo ";";
  else $first=false;
  echo $assoc['id'].",";
  echo $assoc['from_id_address'].",";
  echo $assoc['to_id_address'].",";
  echo $assoc['amount'].",";
  echo str_replace($to_replace,$replacements,$assoc['memo']).",";
  echo strtotime($assoc['timestamp']);
}
?>