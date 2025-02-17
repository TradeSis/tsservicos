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


$sql = "SELECT contratotipos.nomeContrato, contrato.tituloContrato,demanda.idDemanda, 
        demanda.tituloDemanda, demanda.dataFechamento, tipoocorrencia.nomeTipoOcorrencia, 
        usuario.nomeUsuario AS nomeAtendente, tarefa.dataReal, tarefa.horaInicioReal, 
        tarefa.horaFinalReal,TIMEDIFF(tarefa.horaFinalReal, tarefa.horaInicioReal) AS tempo FROM tarefa
        INNER JOIN demanda ON tarefa.idDemanda = demanda.idDemanda
        INNER JOIN contrato ON demanda.idContrato = contrato.idContrato
        INNER JOIN contratotipos ON demanda.idContratoTipo = contratotipos.idContratoTipo
        INNER JOIN tipoocorrencia ON tarefa.idTipoOcorrencia = tipoocorrencia.idTipoOcorrencia
        INNER JOIN usuario ON tarefa.idAtendente = usuario.idUsuario
        WHERE   tarefa.dataReal >= '" . $sqldti . "'
                AND tarefa.dataReal < '" . $sqldtf . "'
                AND tarefa.horaFinalReal IS NOT NULL";

$where = " AND ";
if (isset($jsonEntrada["idContratoTipo"])) {
    $sql .= $where . " demanda.idContratoTipo = '" . $jsonEntrada["idContratoTipo"] . "'";
    $where = " AND ";
} 
if (isset($jsonEntrada["idCliente"])) {
    $sql .= $where . " tarefa.idCliente = " . $jsonEntrada["idCliente"];
    $where = " AND ";
}

$sql .= " GROUP BY demanda.idContratoTipo, demanda.idDemanda, tarefa.dataReal, tarefa.horaInicioReal, tarefa.horaFinalReal
          ORDER BY contratotipos.nomeContrato, demanda.idDemanda, tarefa.dataReal, tarefa.horaInicioReal";


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
    $idDemanda = $row['idDemanda'];
    if (!isset($demandaArray[$idDemanda])) {
        $demandaArray[$idDemanda] = array();
    }
    $row['tempo'] = strtotime($row['tempo']) - strtotime('TODAY');
    $demandaArray[$idDemanda][] = $row;
    $rows++;
}

$demandas = array();
$totalTempo = 0;
$totalCobrado = 0;

foreach ($demandaArray as $idDemanda => &$demandasPorId) {
    $cobrado = 0;
    $count = count($demandasPorId);

    for ($i = 0; $i < $count; $i++) {
        $demanda = &$demandasPorId[$i];
        
        $tempo = $demanda['tempo'];
        $horas = floor($tempo / 3600);
        $minutos = floor(($tempo - ($horas*3600)) / 60);
        $segundos = $tempo % 60;
        $demanda['tempo'] = sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
        
        $cobrado += $tempo;

        $totalTempo += $tempo;

        if ($i < $count - 1) {  
            $demanda['tempoCobrado'] = sprintf('%02d:%02d:%02d', floor($tempo / 3600), floor(($tempo % 3600) / 60), $tempo % 60);
        } else {  
            if ($cobrado < 1800) { 
                $tempoRestante = 1800 - $cobrado; 
                $totalCobradoSegundos = $tempo + $tempoRestante;
                $demanda['tempoCobrado'] = sprintf('%02d:%02d:%02d', floor($totalCobradoSegundos / 3600), floor(($totalCobradoSegundos % 3600) / 60), $totalCobradoSegundos % 60);
            } else {
                $demanda['tempoCobrado'] = sprintf('%02d:%02d:%02d', floor($tempo / 3600), floor(($tempo % 3600) / 60), $tempo % 60);
            }
        }
        $totalCobrado += strtotime($demanda['tempoCobrado']) - strtotime('TODAY');
    }
    unset($demanda); 
}

foreach ($demandaArray as $demandasPorIdDemanda) {
    $demandas = array_merge($demandas, $demandasPorIdDemanda);
}

$totalTempoHoras = floor($totalTempo / 3600);
$totalTempoMinutos = floor(($totalTempo % 3600) / 60);
$totalTempoSegundos = $totalTempo % 60;

$totalCobradoHoras = floor($totalCobrado / 3600);
$totalCobradoMinutos = floor(($totalCobrado % 3600) / 60);
$totalCobradoSegundos = $totalCobrado % 60;

$jsonSaida = [
    "demandas" => $demandas,
    "total" => [
        [
            "totalTempo" => sprintf('%02d:%02d:%02d', $totalTempoHoras, $totalTempoMinutos, $totalTempoSegundos),
            "totalCobrado" => sprintf('%02d:%02d:%02d', $totalCobradoHoras, $totalCobradoMinutos, $totalCobradoSegundos)
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