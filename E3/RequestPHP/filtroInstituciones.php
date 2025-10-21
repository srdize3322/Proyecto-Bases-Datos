#!/usr/bin/env php
<?php
mb_internal_encoding('UTF-8');

$base = realpath(__DIR__.'/..');
$in  = "$base/Old/Instituciones previsionales de salud.csv";
$ok  = "$base/Depurado/Instituciones previsionales de salud_OK.csv";
$err = "$base/Eliminado/Instituciones previsionales de salud_ERR.csv";
$log = "$base/Logs/Instituciones previsionales de salud_LOG.txt";
@mkdir("$base/Depurado",0777,true);
@mkdir("$base/Eliminado",0777,true);
@mkdir("$base/Logs",0777,true);
$I=fopen($in,'r') or die("No puedo leer $in\n");
$O=fopen($ok,'w'); $E=fopen($err,'w'); $L=fopen($log,'w');
$sep=';';

$hdr=fgetcsv($I,0,$sep);
fputcsv($O,$hdr,$sep); fputcsv($E,$hdr,$sep);

$ixNom=array_search('nombre',$hdr);
$ixRut=array_search('rut',$hdr);
$ixEnl=array_search('Enlace',$hdr);

while(($r=fgetcsv($I,0,$sep))!==false){
  if(!array_filter($r,fn($c)=>trim($c)!=='')){
    fputcsv($E,$r,$sep); fwrite($L,"Línea vacía\n"); continue;
  }

  // nombre
  if($ixNom!==false){
    $n=trim($r[$ixNom]);
    $n=mb_convert_case(mb_strtolower($n), MB_CASE_TITLE, 'UTF-8');
    $r[$ixNom]=$n;
  }

  // enlace
  if($ixEnl!==false){
    $r[$ixEnl]=preg_replace('#^https://#i','',$r[$ixEnl]);
  }

  // rut
  if($ixRut!==false){
    $o=$r[$ixRut];
    $s=strtoupper(str_replace('.','',$o));
    $s=preg_replace('/[\x{2010}-\x{2015}\x{2212}]/u','-',$s);
    if(strpos($s,'-')===false && strlen($s)>1)
      $s=substr($s,0,-1).'-'.substr($s,-1);
    if(!preg_match('/^(\d{6,8})-([0-9K])$/',$s,$m)){
      fputcsv($E,$r,$sep); fwrite($L,"RUT inválido: $o\n"); continue;
    }
    [$c,$d]=[$m[1],$m[2]];
    $sum=0;$m2=2;
    for($i=strlen($c)-1;$i>=0;$i--){
      $sum+=intval($c[$i])*$m2; $m2=$m2==7?2:$m2+1;
    }
    $r11=11-($sum%11);
    $dv=$r11==11?'0':($r11==10?'K':"$r11");
    if($d!==$dv) fwrite($L,"RUT corregido: $o -> $c-$dv\n");
    $r[$ixRut]="$c-$dv";
  }

  fputcsv($O,$r,$sep);
}

fclose($I); fclose($O); fclose($E); fclose($L);