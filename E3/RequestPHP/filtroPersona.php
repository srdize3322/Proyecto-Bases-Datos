#!/usr/bin/env php
<?php
// E3/RequestPHP/filtroPersona.php  —  Persona.csv -> OK/ERR/LOG en Depurado/Eliminado/Logs
// Reglas: RUN 6–8 dígitos + DV (con/sin guion, DV numérico o K); corrige DV; dup=ERR;
// nombre/apellidos no nulos; email translitera tildes (diaz), repara y valida; teléfono últimos 9;
// tipo/titular coherentes (infiero si falta); rol listado (limpia prof/esp si no clínico);
// institución ∈ lista fija (abajo); si no coincide -> FONASA; firma ignorada.

mb_internal_encoding('UTF-8');
$R = realpath(__DIR__.'/..');
$IN  = "$R/Old/Persona.csv";
$OK  = "$R/Depurado/Persona_OK.csv";
$ERR = "$R/Eliminado/Persona_ERR.csv";
$LOG = "$R/Logs/Persona_LOG.txt";
@mkdir("$R/Depurado",0777,true); @mkdir("$R/Eliminado",0777,true); @mkdir("$R/Logs",0777,true);
if(!is_readable($IN)) die("No puedo leer $IN\n");

// ---------- helpers de internet normalizar rut sacado de internet se usa en otras partes tambien ----------
$log = function($h,$m){ fwrite($h,'['.date('Y-m-d H:i:s')."] $m\n"); };
$cap = function($s){ $s=trim(mb_strtolower((string)$s)); if($s==='')return ''; $s=preg_replace('/\s+/u',' ',$s);
  return preg_replace_callback('/\b(\p{L})(\p{L}*)/u',fn($m)=>mb_strtoupper($m[1]).$m[2],$s); };
$dv11 = function($n){ $s=0;$m=2; for($i=strlen($n)-1;$i>=0;$i--){$s+=intval($n[$i])*$m;$m=$m==7?2:$m+1;}
  $r=11-($s%11); return $r==11?'0':($r==10?'K':"$r"); };
$unaccent = function($s){ $x=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); if($x!==false&&$x!=='')return $x;
  return strtr($s,['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n']); };

$normRUN = function($raw,&$why=null,&$trace=null,$ln=null) use($dv11){
  $o=(string)$raw; $s=strtoupper(preg_replace('/[.\s]/','',trim($o))); if($s===''){ $why='RUN vacío'; return null; }
  if(strpos($s,'-')!==false){ [$c,$d]=explode('-',$s,2); } else { if(strlen($s)<2){$why='formato';return null;} $c=substr($s,0,-1); $d=substr($s,-1); }
  $c=preg_replace('/\D/','',$c); $d=strtoupper($d);
  if($c===''||strlen($c)<6){ $why='menos de 6'; return null; }
  if(strlen($c)>8){ $why='cuerpo>8'; return null; }
  if(!preg_match('/^[0-9K]$/',$d)){ $why='DV inválido'; return null; }
  $calc=$dv11($c); if($d!==$calc){ $d=$calc; }
  return "$c-$d";
};

$emailFix = function($s,&$trace,$ln) use($unaccent){
  $orig=trim((string)$s); if($orig===''){ $trace[]="L$ln email vacío -> mail@invalido.com"; return 'mail@invalido.com'; }
  $e=mb_strtolower($orig); $e=preg_replace('/\s+/','',$e); $e=preg_replace('/@+/', '@', $e);
  $parts=explode('@',$e,3); $local=$parts[0]??$e; $domain=$parts[1]??'';
  $local=$unaccent($local);  $domain=$unaccent($domain);
  $local=preg_replace('/\.\.+/','.', $local);   $local=preg_replace('/[^a-z0-9_\.\-\+]/','',$local); $local=trim($local,'.-');
  $domain=preg_replace('/\.\.+/','.', $domain); $domain=preg_replace('/[^a-z0-9\.\-]/','', $domain); $domain=trim($domain,'.-');
  $e = ($domain==='')? $local : "$local@$domain";
  if(!preg_match('/^[^@]+@[^@]+\.[a-z]{2,}$/',$e)){ $trace[]="L$ln email '$orig' -> mail@invalido.com"; return 'mail@invalido.com'; }
  if($e!==$orig) $trace[]="L$ln email '$orig'->'$e'"; return $e;
};

$telFix = function($s,&$trace,$ln){
  $d=preg_replace('/\D/','',(string)$s); $d=preg_replace('/^56/','',$d);
  if(strlen($d)>=9) return substr($d,-9);
  $trace[]="L$ln tel '$s' -> 111111111"; return '111111111';
};

$instSet = array_flip(array_map(fn($x)=>mb_strtoupper($x,'UTF-8'),[
  'Salud Ltda.','Colmena de avispas S.A.','Fundación e imperio','Cruz de Malta S.A.','Vida uno S.A.',
  'Menos vida S.A.','Cruz pal cielo Ltda.','Medibanc S.A.','sinsalud S.A.','especial S.A.','FONASA'
]));
$rolesOK   = array_flip(['Paciente','Staff Médico','Administrativo','Enfermero','Enfermera','Médico','Medico','Tens','Paramédico','Tecnólogo Médico','Kinesiólogo']);
$rolesClin = array_flip(['Staff Médico','Enfermero','Enfermera','Médico','Medico','Tens','Paramédico','Tecnólogo Médico','Kinesiólogo']);
$aliasRol  = ['Staff Medico'=>'Staff Médico','Medico'=>'Médico','Paramedico'=>'Paramédico','Tecnologo Medico'=>'Tecnólogo Médico','Kinesiologo'=>'Kinesiólogo'];
$profCanon = function($profRaw) use ($cap){
  $prof = $cap($profRaw);
  $keyMaker = function($txt){
    $up = mb_strtoupper((string)$txt,'UTF-8');
    $up = strtr($up,['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
    return preg_replace('/[^A-Z]/u','',$up);
  };
  $map = [
    'TENS'        => 'Tens',
    'TENSS'       => 'Tens',
    'MEDICO'      => 'Médico',
    'MEDICA'      => 'Médico',
    'MEDICOA'     => 'Médico',
    'ENFERMERO'   => 'Enfermero',
    'ENFERMERA'   => 'Enfermera',
    'ENFERMEROA'  => 'Enfermero/a',
    'KINESIOLOGO' => 'Kinesiólogo',
    'KINESIOLOGOA'=> 'Kinesiólogo/a'
  ];
  $key = $keyMaker($prof);
  return $map[$key] ?? $prof;
};
$stripBom = function($s){
  $s=(string)$s;
  if(strncmp($s,"\xEF\xBB\xBF",3)===0) return substr($s,3);
  return $s;
};

// ---------- IO ----------
$in=fopen($IN,'r'); $ok=fopen($OK,'w'); $er=fopen($ERR,'w'); $lg=fopen($LOG,'w');
$del=';'; $hdr=fgetcsv($in, 0, $del, '"', '\\'); if($hdr===false) exit(0);
foreach($hdr as &$h){ $h=$stripBom($h); } unset($h);
fputcsv($ok, $hdr, $del, '"', '\\'); fputcsv($er, $hdr, $del, '"', '\\');

// índice flexible
$idx = [
  'ID'=>0,'RUN'=>1,'Nom'=>2,'Ape'=>3,'Dir'=>4,'Mail'=>5,'Tel'=>6,
  'Tipo'=>7,'Tit'=>8,'Rol'=>9,'Prof'=>10,'Esp'=>11,'Fir'=>12,'Inst'=>13
];

// ---------- proceso ----------
$seen=[]; $ln=1;
while(($row=fgetcsv($in, 0, $del, '"', '\\'))!==false){ $ln++; $t=[];
  $get=function($k)use($row,$idx){ return $idx[$k]!==null && isset($row[$idx[$k]])? $row[$idx[$k]]:''; };
  $set=function($k,$v)use(&$row,$idx){ if($idx[$k]!==null) $row[$idx[$k]]=$v; };

  // RUN (descarta sólo irrecuperables) + duplicados
  $why=null; $run=$normRUN($get('RUN'),$why,$t,$ln);
  if($run===null){ $t[]="L$ln RUN '{$get('RUN')}' -> ERR ($why)"; fputcsv($er, $row, $del, '"', '\\'); $log($lg,implode(' | ',$t)); continue; }
  if(isset($seen[$run])){ $t[]="L$ln Duplicado $run -> ERR"; fputcsv($er, $row, $del, '"', '\\'); $log($lg,implode(' | ',$t)); continue; }
  $seen[$run]=1; if($get('RUN')!==$run){ $t[]="L$ln RUN '{$get('RUN')}'->'$run'"; $set('RUN',$run); }

  // Nombre/Apellidos
  $nom=$cap($get('Nom')) ?: 'Sin Nombre';     if($nom!==$get('Nom')) $set('Nom',$nom);
  $ape=$cap($get('Ape')) ?: 'Sin Apellidos';  if($ape!==$get('Ape')) $set('Ape',$ape);

  // Dirección
  if($idx['Dir']!==null){ $dir=preg_replace('/\s+/u',' ',trim((string)$get('Dir'))); if($dir!==$get('Dir')) $set('Dir',$dir); }

  // Email / Tel
  if($idx['Mail']!==null){ $m=$emailFix($get('Mail'),$t,$ln); if($m!==$get('Mail')) $set('Mail',$m); }
  if($idx['Tel']!==null){  $p=$telFix($get('Tel'),$t,$ln);   if($p!==$get('Tel'))  $set('Tel',$p); }

  // Tipo / Titular
  $titWhy=null; $tit = $idx['Tit']!==null ? $normRUN($get('Tit'),$titWhy,$t,$ln) : null;
  $tipoRaw=strtolower(trim((string)$get('Tipo')));
  if(!in_array($tipoRaw,['titular','beneficiario'])){
    $tipo = ($tit===null||$tit===$run)? 'titular':'beneficiario';
  } else {
    $tipo = $tipoRaw;
  }
  if($tipo==='titular'){
    if($tit!==null && $tit!==$run){ $t[]="L$ln titular '$tit' -> '$run'"; }
    $set('Tit',$run);
  } else {
    if($tit===null){
      $t[]="L$ln beneficiario sin titular -> ERR";
      fputcsv($er, $row, $del, '"', '\\'); $log($lg,implode(' | ',$t));
      continue;
    }
    if($tit===$run){
      $t[]="L$ln beneficiario con titular propio $run -> ERR";
      fputcsv($er, $row, $del, '"', '\\'); $log($lg,implode(' | ',$t));
      continue;
    }
    if($idx['Tit']!==null && $get('Tit')!==$tit) $set('Tit',$tit);
  }
  if($idx['Tipo']!==null && $get('Tipo')!==$tipo) $set('Tipo',$tipo);

  // Rol / Prof / Esp
  if($idx['Rol']!==null){
    $rol=$cap($get('Rol')); if(isset($aliasRol[$rol])) $rol=$aliasRol[$rol];
    if(!isset($rolesOK[$rol])) $rol='Paciente';
    if($rol!==$get('Rol')) $set('Rol',$rol);
    $clin=isset($rolesClin[$rol]);
    if($idx['Prof']!==null){
      $pr = $clin ? $profCanon($get('Prof')) : '';
      if($pr!==$get('Prof')) $set('Prof',$pr);
    }
    if($idx['Esp']!==null){  $es=$cap($get('Esp'));  if(!$clin) $es=''; if($es!==$get('Esp'))  $set('Esp',$es); }
  }

  // Firma (sólo trim)
  if($idx['Fir']!==null){ $f=trim((string)$get('Fir')); if($f!==$get('Fir')) $set('Fir',$f); }

  // Institución
  if($idx['Inst']!==null){
    $ir=trim((string)$get('Inst')); $key=mb_strtoupper($ir,'UTF-8');
    if($ir==='' || !isset($instSet[$key])) $set('Inst','FONASA');
  }

  fputcsv($ok, $row, $del, '"', '\\');
  if($t) $log($lg,implode(' | ',$t));
}

fclose($in); fclose($ok); fclose($er); fclose($lg);
