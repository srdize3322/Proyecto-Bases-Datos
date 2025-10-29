#!/usr/bin/env php
<?php
// E3/RequestPHP/filtroArancel_DCColita.php — depuración basada en selección “mejor fila” por código.
// Mantiene un único registro por código interno priorizando descripciones limpias y códigos Fonasa válidos.

mb_internal_encoding('UTF-8');

$BASE = realpath(__DIR__.'/..');
$IN   = "$BASE/Old/Arancel DCColita de rana.csv";
$OK   = "$BASE/Depurado/Arancel_DCColita_OK.csv";
$ERR  = "$BASE/Eliminado/Arancel_DCColita_ERR.csv";
$LOG  = "$BASE/Logs/Arancel_DCColita_LOG.txt";

@mkdir("$BASE/Depurado",0777,true);
@mkdir("$BASE/Eliminado",0777,true);
@mkdir("$BASE/Logs",0777,true);

if (!is_readable($IN)) {
    fwrite(STDERR, "No puedo leer $IN\n");
    exit(1);
}

$log = fn($h,$m)=>fwrite($h,'['.date('Y-m-d H:i:s')."] $m\n");
$trimRow = function (&$row) { foreach ($row as &$c) { $c = trim((string)$c); } };
$collapseSpaces = fn($s)=>preg_replace('/\s+/u',' ',trim((string)$s));
$onlyDigits = fn($s)=>preg_replace('/\D/','',(string)$s);
$stripBom = function($s){
    $s=(string)$s;
    if(strncmp($s,"\xEF\xBB\xBF",3)===0) return substr($s,3);
    return $s;
};
$normalizeKey = function($s) use ($collapseSpaces){
    $s=$collapseSpaces($s);
    $s=mb_strtolower($s,'UTF-8');
    $s=preg_replace('/[^a-z0-9áéíóúñü ]/u','',$s);
    $s=preg_replace('/\s+/u',' ',trim($s));
    return $s;
};

$in=fopen($IN,'r');
$sep=';'; $enc='"'; $esc='\\';
$header=fgetcsv($in,0,$sep,$enc,$esc);
if($header===false){
    fclose($in); exit(0);
}
foreach($header as &$h){
    $h=$stripBom($h);
    $h=preg_replace('/\s+/u',' ',trim($h));
}
unset($h);

$idxCod=0; $idxFonasa=1; $idxDesc=2; $idxValor=3;
$line=1;
$groups=[]; $errors=[];

while(($row=fgetcsv($in,0,$sep,$enc,$esc))!==false){
    $line++;
    $orig=$row;
    $trimRow($row);
    if(isset($row[$idxCod])) $row[$idxCod]=$stripBom($row[$idxCod]);
    if(!array_filter($row,fn($c)=>$c!=='')){
        $errors[]=['row'=>$orig,'reason'=>"L$line fila vacía"];
        continue;
    }

    $notes=[];
    // código interno
    $codRaw=$row[$idxCod]??'';
    $codDigits=$onlyDigits($codRaw);
    if($codDigits===''){
        $errors[]=['row'=>$orig,'reason'=>"L$line sin código interno"];
        continue;
    }
    $codigo=(int)$codDigits;
    if((string)$codigo!==$codRaw){
        $notes[]="codigo:'$codRaw'->$codigo";
    }

    // cod Fonasa
    $fonasaRaw=$row[$idxFonasa]??'';
    $fonasa=str_replace([' ','.'],'',$fonasaRaw);
    if($fonasa===''){
        $errors[]=['row'=>$orig,'reason'=>"L$line sin código FONASA"];
        continue;
    }
    if(!preg_match('/^\d+(?:-\d+)?$/',$fonasa)){
        $errors[]=['row'=>$orig,'reason'=>"L$line Código FONASA inválido '$fonasaRaw'"];
        continue;
    }
    if($fonasa!==$fonasaRaw){
        $notes[]="codFonasa:'$fonasaRaw'->$fonasa";
    }

    // descripción
    $descRaw=$row[$idxDesc]??'';
    $desc=$collapseSpaces($descRaw);
    if($desc===''){
        $errors[]=['row'=>$orig,'reason'=>"L$line atención vacía"];
        continue;
    }
    if($desc!==$descRaw){
        $notes[]="atencion:Normalizada";
    }
    if(mb_strlen($desc,'UTF-8')>100){
        $desc=mb_substr($desc,0,100,'UTF-8');
        $notes[]="atencion:>100 -> trunc";
    }

    // valor
    $valorRaw=$row[$idxValor]??'';
    $valorDigits=$onlyDigits($valorRaw);
    if($valorDigits===''){
        $errors[]=['row'=>$orig,'reason'=>"L$line valor vacío"];
        continue;
    }
    $valor=(int)$valorDigits;
    if((string)$valor!==$valorRaw){
        $notes[]="valor:'$valorRaw'->$valor";
    }

    $groups[$codigo][]=[
        'line'=>$line,
        'clean'=>[$codigo,$fonasa,$desc,$valor],
        'notes'=>$notes,
        'raw'=>$orig
    ];
}
fclose($in);

$scoreEntry=function($entry){
    [$codigo,$fonasa,$desc,$valor]=$entry['clean'];
    $score=0;
    if(strpos($desc,'**')===0) $score-=100;
    if(stripos($desc,'**')!==false) $score-=50;
    $score-=count($entry['notes'])*5;
    $score+=min($valor/1000000,10);
    return [$score,$entry['line']];
};

$okEntries=[];
foreach($groups as $code=>$list){
    usort($list,function($a,$b) use ($scoreEntry){
        [$sa,$la]=$scoreEntry($a);
        [$sb,$lb]=$scoreEntry($b);
        if($sa==$sb) return $la<=>$lb;
        return $sb<=>$sa;
    });
    $best=array_shift($list);
    $okEntries[]=$best;
    foreach($list as $dup){
        $errors[]=[
            'row'=>$dup['raw'],
            'reason'=>"L{$dup['line']} duplicado $code (se mantiene mejor versión)",
            'notes'=>$dup['notes']
        ];
    }
}

usort($okEntries,fn($a,$b)=>$a['line']<=>$b['line']);

$ok=fopen($OK,'w');
$er=fopen($ERR,'w');
$lg=fopen($LOG,'w');
fputcsv($ok,$header,$sep,$enc,$esc);
fputcsv($er,$header,$sep,$enc,$esc);

foreach($okEntries as $entry){
    fputcsv($ok,$entry['clean'],$sep,$enc,$esc);
    if($entry['notes']){
        $log($lg,"L{$entry['line']} ".implode(' | ',$entry['notes']));
    }
}

foreach($errors as $err){
    $row=$err['row'];
    if(is_array($row) && count($row)>=4){
        fputcsv($er,$row,$sep,$enc,$esc);
    } else {
        fputcsv($er,$header,$sep,$enc,$esc); // fallback
    }
    $msg=$err['reason'];
    if(isset($err['notes']) && $err['notes']){
        $msg.=" | ".implode(' | ',$err['notes']);
    }
    $log($lg,$msg);
}

fclose($ok); fclose($er); fclose($lg);
