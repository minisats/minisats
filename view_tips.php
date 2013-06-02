<?php
include("common.php");
$query = "SELECT * FROM `tips` WHERE 1";
$result = mysql_query($query);
echo "<table border=1>";
?>
<tr>
<td>Tip ID</td>
<td>From ID Address</td>
<td>To ID Address</td>
<td>Amount (nSats)</td>
<td>Memo</td>
<td>Timestamp</td>
</tr>
<?php
while ($assoc = mysql_fetch_assoc($result)) {
  echo "<tr>";
  echo "<td>".$assoc['id']."</td>";
  echo "<td>".$assoc['from_id_address']."</td>";
  echo "<td>".$assoc['to_id_address']."</td>";
  echo "<td>".$assoc['amount']."</td>";
  echo "<td>".$assoc['memo']."</td>";
  echo "<td>".date('Y-m-d H:i:s',strtotime($assoc['timestamp']))."</td>";
  echo "</tr>";
}
echo "</table>";
?>