<?php
$use_select_user = true;
include("common.php");
if (isset($_REQUEST['clause']) && $_REQUEST['clause']!="") $clause = $_REQUEST['clause'];//if clause is specified, use it
else $clause = "1";
if (strstr($clause,';')) {//if there's a semicolon
  $clause = strstr($clause,';',true);//truncate at first semicolon
}
$query = "SELECT * FROM `tips` WHERE ".$clause;
$result = mysql_query($query)or die("e".mysql_error());
?>
<form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
Clause: <input type='text' name='clause' value="<?php echo $clause; ?>">
<input type='submit' name='submit'>
<table border=1>
<tr>
<td>id</td>
<td>from_id_address</td>
<td>to_id_address</td>
<td>amount (nsats)</td>
<td>data_id</td>
<td>command_id</td>
<td>timestamp (converted with php date())</td>
</tr>
<?php
while ($assoc = mysql_fetch_assoc($result)) {
  echo "<tr>";
  echo "<td>".$assoc['id']."</td>";
  echo "<td>".$assoc['from_id_address']."</td>";
  echo "<td>".$assoc['to_id_address']."</td>";
  echo "<td>".$assoc['amount']."</td>";
  echo "<td>".$assoc['data_id']."</td>";
  echo "<td>".$assoc['command_id']."</td>";
  echo "<td>".date('Y-m-d H:i:s',strtotime($assoc['timestamp']))."</td>";
  echo "</tr>";
}
echo "</table>";
?>