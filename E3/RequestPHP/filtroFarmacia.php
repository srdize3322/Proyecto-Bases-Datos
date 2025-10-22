#!/usr/bin/env php
<?php
mb_internal_encoding('UTF-8');

/* === 0) Configuración de rutas === */
$BASE = realpath(__DIR__.'/..');
$IN   = "$BASE/Old/Farmacia.csv";
$OK   = "$BASE/Depurado/Farmacia_OK.csv";
$ERR  = "$BASE/Eliminado/Farmacia_ERR.csv";
$LOG  = "$BASE/Logs/Farmacia_LOG.txt";
@mkdir("$BASE/Depurado",0777,true);
@mkdir("$BASE/Eliminado",0777,true);
@mkdir("$BASE/Logs",0777,true);
if(!is_readable($IN)) die("No puedo leer $IN\n");

/* === 1) Funciones auxiliares === */
$log = fn($h,$m)=>fwrite($h,'['.date('Y-m-d H:i:s')."] $m\n");
$digits = fn($s)=>preg_replace('/\D/','',(string)$s);
$cut = fn($s,$n)=>mb_strlen((string)$s,'UTF-8')>$n?mb_substr((string)$s,0,$n,'UTF-8'):(string)$s;
$trimRow = function(&$row){ foreach($row as $i=>$c){ $row[$i]=trim((string)$c); } };
$stripInvisible = function(&$x){
  $x=(string)$x;
  // Elimina BOM/FEFF/ZWSP/LRM/RLM y caracteres de control ASCII
  $x=preg_replace('/^\xEF\xBB\xBF/u','',$x);
  $x=preg_replace('/^\x{FEFF}|\x{200B}|\x{200E}|\x{200F}/u','',$x);
  $x=preg_replace('/^[\x00-\x1F\x7F]+/','',$x);
};

/* === 2) Catálogos === */
$TIPOS = [
  'alimentos'=>'Alimentos',
  'equipamiento'=>'Equipamiento',
  'fármacos'=>'Fármacos', 'farmacos'=>'Fármacos',
  'insumos'=>'insumos',
  'psicotrópicos'=>'psicotrópicos', 'psicotropicos'=>'psicotrópicos',
  'refrigerados'=>'Refrigerados',
  'sueros'=>'Sueros'
];
$ESTADOS = ['activo'=>'activo','inactivo'=>'inactivo'];
$normTipo = function($s) use ($TIPOS){
  $k=mb_strtolower((string)$s,'UTF-8');
  $k=strtr($k,['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
  return $TIPOS[$k] ?? 'insumos';
};
$normEstado = fn($s)=>($ESTADOS[mb_strtolower((string)$s,'UTF-8')] ?? 'inactivo');

/* === 3) Lectura y preparación de archivos CSV === */
$I=fopen($IN,'r'); $O=fopen($OK,'w'); $E=fopen($ERR,'w'); $L=fopen($LOG,'w');
$SEP=';'; $ENC='"'; $ESC='\\';

// Limpiar encabezado
$hdr=fgetcsv($I,0,$SEP,$ENC,$ESC);
if($hdr===false){ foreach([$I,$O,$E,$L] as $h) fclose($h); exit(0); }
foreach($hdr as &$h){ $stripInvisible($h); } unset($h);
fputcsv($O,$hdr,$SEP,$ENC,$ESC);
fputcsv($E,$hdr,$SEP,$ENC,$ESC);

// Índices fijos de columnas según la pauta
[$C_COD,$C_NOM,$C_DES,$C_TIPO,$C_ONU,$C_CLAONU,$C_CLASF,$C_EST,$C_ESEN,$C_PREC] = [0,1,2,3,4,5,6,7,8,9];

/* === 4) Procesamiento fila a fila === */
$ln=1;
$seenCod = []; // Nuevo: set para detectar códigos duplicados

while(($r=fgetcsv($I,0,$SEP,$ENC,$ESC))!==false){
  $ln++;
  $trimRow($r);
  if(isset($r[0])) $stripInvisible($r[0]);

  // Si toda la fila está vacía → ERR
  if(!array_filter($r,fn($c)=>$c!=='')){
    fputcsv($E,$r,$SEP,$ENC,$ESC);
    $log($L,"L$ln vacía -> ERR");
    continue;
  }

  $t=[]; // Log de cambios por fila

  /* 4.1) Código genérico */
  $cod = $digits($r[$C_COD]);
  if($cod===''){
    fputcsv($E,$r,$SEP,$ENC,$ESC);
    $log($L,"L$ln cod vacío -> ERR");
    continue;
  }
  if($cod!==$r[$C_COD]){ $t[]="cod:'{$r[$C_COD]}'->$cod"; $r[$C_COD]=$cod; }

  // Detección de duplicados
  if (isset($seenCod[$cod])) {
    fputcsv($E,$r,$SEP,$ENC,$ESC);
    $log($L,"L$ln cod duplicado $cod -> ERR");
    continue;
  }
  $seenCod[$cod] = true;

  /* 4.2) Nombre y descripción */
  $nom = $cut($r[$C_NOM]!==''?$r[$C_NOM]:'Sin nombre',100);
  $des = $cut($r[$C_DES]!==''?$r[$C_DES]:'Sin descripción',256);
  if($nom!==$r[$C_NOM]){ $t[]="nombre:'{$r[$C_NOM]}'->$nom"; $r[$C_NOM]=$nom; }
  if($des!==$r[$C_DES]){ $t[]="desc:'{$r[$C_DES]}'->$des"; $r[$C_DES]=$des; }

  /* 4.3) Tipo */
  $tipo = $normTipo($r[$C_TIPO]);
  if($tipo!==$r[$C_TIPO]){ $t[]="tipo:'{$r[$C_TIPO]}'->$tipo"; $r[$C_TIPO]=$tipo; }

  /* 4.4) Código ONU y clasificación ONU */
  $onu = $digits($r[$C_ONU]);
  if($onu!==$r[$C_ONU]){ $t[]="codONU:'{$r[$C_ONU]}'->$onu"; $r[$C_ONU]=$onu; }
  $clonu = $onu==='' ? '' : $cut($r[$C_CLAONU],30);
  if($clonu!==$r[$C_CLAONU]){ $t[]="clasONU:'{$r[$C_CLAONU]}'->$clonu"; $r[$C_CLAONU]=$clonu; }

  /* 4.5) Clasificación interna */
  $clas = $cut($r[$C_CLASF]!==''?$r[$C_CLASF]:'Sin clasificación',50);
  if($clas!==$r[$C_CLASF]){ $t[]="clasif:'{$r[$C_CLASF]}'->$clas"; $r[$C_CLASF]=$clas; }

  /* 4.6) Estado y esencial */
  $est = $normEstado($r[$C_EST]);
  if($est!==$r[$C_EST]){ $t[]="estado:'{$r[$C_EST]}'->$est"; $r[$C_EST]=$est; }
  $esencial = ($r[$C_ESEN]==='1') ? '1' : '0';
  if($esencial!==$r[$C_ESEN]){ $t[]="esencial:'{$r[$C_ESEN]}'->$esencial"; $r[$C_ESEN]=$esencial; }

  /* 4.7) Precio */
  $precio = $digits($r[$C_PREC]);
  if($precio!==$r[$C_PREC]){ $t[]="precio:'{$r[$C_PREC]}'->$precio"; $r[$C_PREC]=$precio; }

  /* 4.8) Guardar resultado */
  fputcsv($O,$r,$SEP,$ENC,$ESC);
  if($t) $log($L,'L'.$ln.' '.implode(' | ',$t));
}

/* === 5) Cierre === */
foreach([$I,$O,$E,$L] as $h) fclose($h);