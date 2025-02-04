<?php
// Gabriel 26022024 criacao

//LOG
$LOG_CAMINHO = defineCaminhoLog();
if (isset($LOG_CAMINHO)) {
    $LOG_NIVEL = defineNivelLog();
    $identificacao = date("dmYHis") . "-PID" . getmypid() . "-" . "tempoatendimento";
    if (isset($LOG_NIVEL)) {
        if ($LOG_NIVEL >= 1) {
            $arquivo = fopen(defineCaminhoLog() . "tempoatendimento_" . date("dmY") . ".log", "a");
        }
    }
}
if (isset($LOG_NIVEL)) {
    if ($LOG_NIVEL == 1) {
        fwrite($arquivo, $identificacao . "\n");
    }
    if ($LOG_NIVEL >= 2) {
        fwrite($arquivo, $identificacao . "-ENTRADA->" . json_encode($jsonEntrada) . "\n");
    }
}
//LOG

$idEmpresa = null;
if (isset($jsonEntrada["idEmpresa"])) {
    $idEmpresa = $jsonEntrada["idEmpresa"];
}

$conexao = conectaMysql($idEmpresa);

$demandas = array();

$mes = isset($jsonEntrada["mes"])  ? $jsonEntrada["mes"]  : date('m');
$ano = isset($jsonEntrada["ano"])  ? $jsonEntrada["ano"]  : date('Y');
$dia = date("t", mktime(0,0,0,$mes,'01',$ano)); 
$sqldti = $ano."-".$mes."-"."01";
$mesprox = $mes + 1;
$anoprox = $ano;
if ($mesprox == 13) {
  $mesprox = 1;
  $anoprox = $ano + 1;
}
$sqldtf = $anoprox."-".$mesprox."-"."01";


$sql = "SELECT contratotipos.nomeContrato , contrato.tituloContrato,
        demanda.iddemanda, demanda.tituloDemanda, demanda.dataFechamento, dataReal, tarefa.horaInicioReal, tarefa.horaFinalReal,
        TIMEDIFF(tarefa.horaFinalReal, tarefa.horaInicioReal) as TEMPO
        from tarefa , demanda, contrato, contratotipos ";

$where = " where ";

$sql = $sql . $where . " demanda.idContratoTipo = contratotipos.idContratoTipo AND
                        demanda.idContrato = contrato.idContrato and
                        tarefa.idDemanda = demanda.idDemanda and
                        not tarefa.idDemanda is null ";
$where = " and ";
if (isset($jsonEntrada["idContratoTipo"])) {
    $sql = $sql . $where . " demanda.idContratoTipo = '" . $jsonEntrada["idContratoTipo"] . "'";
    $where = " and ";
} else {
    $sql = $sql . $where . " demanda.idContratoTipo = contratotipos.idContratoTipo ";
    $where = " and ";
}
if (isset($jsonEntrada["idCliente"])) {
    $sql = $sql . $where . " tarefa.idcliente = " . $jsonEntrada["idCliente"];
    $where = " and ";
}
$sql = $sql . " and dataReal >= '" . $sqldti . "' and dataReal < '" . $sqldtf . "' and not horaFinalReal is null ";
$sql = $sql . "group by demanda.idContratoTipo , demanda.idDemanda, dataReal, tarefa.horaInicioReal, tarefa.horaFinalReal
               order by contratotipos.nomeContrato, demanda.idDemanda, dataReal, tarefa.horaInicioReal";


//echo "-SQL->".$sql."\n"; 
//LOG
if (isset($LOG_NIVEL)) {
    if ($LOG_NIVEL >= 3) {
        fwrite($arquivo, $identificacao . "-SQL->" . $sql . "\n");
    }
}
//LOG

$rows = 0;
$buscar = mysqli_query($conexao, $sql);
$demandaArray = array();

while ($row = mysqli_fetch_array($buscar, MYSQLI_ASSOC)) {
    $idDemanda = $row['iddemanda'];
    if (!isset($demandaArray[$idDemanda])) {
        $demandaArray[$idDemanda] = $row;
        $demandaArray[$idDemanda]['TEMPO'] = strtotime($row['TEMPO']) - strtotime('TODAY');
    } else {
        $demandaArray[$idDemanda]['TEMPO'] += strtotime($row['TEMPO']) - strtotime('TODAY');
    }
    $rows++;
}

$demandas = array();
$totalTempo = 0;
$totalCobrado = 0;

foreach ($demandaArray as &$demanda) {
    $demanda['TEMPO'] = gmdate('H:i:s', $demanda['TEMPO']);
    $tempo = strtotime($demanda['TEMPO']) - strtotime('TODAY');
    
    if ($tempo < 1800) { 
        $demanda['tempoCobrado'] = "00:30:00";
    } else {
        $demanda['tempoCobrado'] = $demanda['TEMPO'];
    }

    $totalTempo += $tempo;
    $cobrado = strtotime($demanda['tempoCobrado']) - strtotime('TODAY');
    $totalCobrado += $cobrado;
}

$demandas = array_values($demandaArray);

$jsonSaida = [
    "demandas" => $demandas,
    "total" => [
        [
            "totalTempo" => gmdate('H:i:s', $totalTempo),
            "totalCobrado" => gmdate('H:i:s', $totalCobrado)
        ]
    ]
];

//echo "-SAIDA->".json_encode($jsonSaida)."\n";

//LOG
if (isset($LOG_NIVEL)) {
    if ($LOG_NIVEL >= 2) {
        fwrite($arquivo, $identificacao . "-SAIDA->" . json_encode($jsonSaida) . "\n\n");
    }
}
//LOG