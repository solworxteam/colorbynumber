<?php

$id=$_GET['id'];

$data=json_decode(file_get_contents("output/".$id.".json"),true);

$grid=$data["grid"];
$palette=$data["palette"];
$size=$data["size"];

?>

<html>
<head>

<style>

table{
border-collapse:collapse;
margin:auto;
}

td{
width:15px;
height:15px;
border:1px solid #ccc;
text-align:center;
font-size:9px;
}

</style>

</head>

<body>

<h2 style="text-align:center">Worksheet Preview</h2>

<table>

<?php

foreach($grid as $row){

echo "<tr>";

foreach($row as $pixel){

$num=1;

echo "<td>".$num."</td>";

}

echo "</tr>";

}

?>

</table>

<br>

<div style="text-align:center">

<a href="download.php?id=<?=$id?>">
<button>Download PDF</button>
</a>

</div>

</body>
</html>