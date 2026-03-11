<?php

class Worksheet{

public static function buildGrid($img,$grid){

$w=imagesx($img);
$h=imagesy($img);

$cellW=floor($w/$grid);
$cellH=floor($h/$grid);

$data=[];
$pixels=[];

for($y=0;$y<$grid;$y++){
for($x=0;$x<$grid;$x++){

$px=$x*$cellW;
$py=$y*$cellH;

$rgb=imagecolorat($img,$px,$py);

$r=($rgb>>16)&0xFF;
$g=($rgb>>8)&0xFF;
$b=$rgb&0xFF;

$pixels[]=[$r,$g,$b];

$data[$y][$x]=[$r,$g,$b];

}
}

return [$data,$pixels];

}

}