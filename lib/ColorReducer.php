<?php

class ColorReducer{

public static function reduce($pixels,$k=8,$iterations=8){

$centroids=[];

for($i=0;$i<$k;$i++)
$centroids[]=$pixels[array_rand($pixels)];

for($it=0;$it<$iterations;$it++){

$groups=array_fill(0,$k,[]);

foreach($pixels as $p){

$best=0;
$bestDist=PHP_INT_MAX;

foreach($centroids as $i=>$c){

$d=pow($p[0]-$c[0],2)+pow($p[1]-$c[1],2)+pow($p[2]-$c[2],2);

if($d<$bestDist){
$bestDist=$d;
$best=$i;
}

}

$groups[$best][]=$p;

}

foreach($groups as $i=>$group){

if(count($group)==0) continue;

$r=$g=$b=0;

foreach($group as $p){
$r+=$p[0];
$g+=$p[1];
$b+=$p[2];
}

$n=count($group);

$centroids[$i]=[
$r/$n,
$g/$n,
$b/$n
];

}

}

return $centroids;

}

}