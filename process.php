<?php

require "lib/ColorReducer.php";
require "lib/Worksheet.php";

$grid=intval($_POST['grid']);
$colors=intval($_POST['colors']);

$tmp=$_FILES['image']['tmp_name'];

$name=uniqid().".jpg";

move_uploaded_file($tmp,"uploads/".$name);

$img=imagecreatefromstring(file_get_contents("uploads/".$name));

list($gridData,$pixels)=Worksheet::buildGrid($img,$grid);

$palette=ColorReducer::reduce($pixels,$colors);

file_put_contents("output/".$name.".json",json_encode([
"grid"=>$gridData,
"palette"=>$palette,
"size"=>$grid
]));

header("Location: preview.php?id=".$name);