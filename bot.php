<?php

/*
██████╗ ███████╗██████╗     ███╗   ██╗ ██████╗ ███████╗███████╗
██╔══██╗██╔════╝██╔══██╗    ████╗  ██║██╔═══██╗██╔════╝██╔════╝
██████╔╝█████╗  ██║  ██║    ██╔██╗ ██║██║   ██║███████╗█████╗
██╔══██╗██╔══╝  ██║  ██║    ██║╚██╗██║██║   ██║╚════██║██╔══╝
██║  ██║███████╗██████╔╝    ██║ ╚████║╚██████╔╝███████║███████╗
╚═╝  ╚═╝╚══════╝╚═════╝     ╚═╝  ╚═══╝ ╚═════╝ ╚══════╝╚══════╝

Red Nose Intelligence
V2 ULTRA
*/

$TOKEN = getenv("BOT_TOKEN");
$API = "https://api.telegram.org/bot$TOKEN/";

$update = json_decode(file_get_contents("php://input"), true);

############################################################
# TELEGRAM ENGINE
############################################################

function bot($method,$data=[]){

global $API;

$ch = curl_init($API.$method);

curl_setopt_array($ch,[
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_POSTFIELDS=>$data
]);

$res = curl_exec($ch);
curl_close($ch);

return json_decode($res,true);

}

############################################################
# API REQUEST
############################################################

function api($url){

$ch = curl_init($url);

curl_setopt_array($ch,[
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_TIMEOUT=>25,
CURLOPT_SSL_VERIFYPEER=>false
]);

$res = curl_exec($ch);

curl_close($ch);

return json_decode($res,true);

}

############################################################
# HELPERS
############################################################

function v($v){
if(!$v || $v=="") return "Não informado";
return $v;
}

function delKeyboard(){

return json_encode([
"inline_keyboard"=>[
[
["text"=>"🗑 Apagar","callback_data"=>"delmsg"]
]
]
]);

}

############################################################
# FORMATADORES
############################################################

function headerBox($title){

return "━━━━━━━━━━━━━━━━━━\n<b>$title</b>\n━━━━━━━━━━━━━━━━━━\n\n";

}

############################################################
# MENU
############################################################

function menu($chat){

$txt="🧠 <b>RED NOSE INTELLIGENCE</b>\n";
$txt.="━━━━━━━━━━━━━━━━━━\n\n";

$txt.="🔎 <b>CONSULTAS DISPONÍVEIS</b>\n\n";

$txt.="🪪 /cpf CPF\n";
$txt.="👤 /nome NOME\n";
$txt.="📞 /tel TELEFONE\n";
$txt.="🪦 /obito CPF\n";
$txt.="📸 /foto CPF\n\n";

$txt.="━━━━━━━━━━━━━━━━━━\n";
$txt.="<i>Sistema OSINT Premium</i>";

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>$txt,
"parse_mode"=>"HTML"
]);

}

############################################################
# CPF CONSULTA
############################################################

function consultaCPF($chat,$cpf){

$cpf=preg_replace('/\D/','',$cpf);

if(strlen($cpf)!=11){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ CPF inválido.\nUse: <code>/cpf 00000000000</code>",
"parse_mode"=>"HTML"
]);

return;
}

$msg=bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"🧠 <b>Red Nose Engine</b>\n<i>Consultando bases nacionais...</i>",
"parse_mode"=>"HTML"
]);

$r=api("https://sara-api.xyz/consulta/cpf?cpf=$cpf");

bot("deleteMessage",[
"chat_id"=>$chat,
"message_id"=>$msg["result"]["message_id"]
]);

if(!$r || empty($r["resultado"]["body"])){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ CPF não encontrado."
]);

return;
}

$d=$r["resultado"]["body"];

#################################################
# GERAR RELATÓRIO TXT COMPLETO
#################################################

function v($v){
return ($v===null || $v=="" || $v=="NULL") ? "NÃO ENCONTRADO" : $v;
}

$txt="
╔══════════════════════════════╗
     RED NOSE INTELLIGENCE
╚══════════════════════════════╝

🧠 DADOS PRINCIPAIS
──────────────────────────────

CPF: ".v($d["cpf_masked"])."
Nome: ".v($d["name"])."
Primeiro nome: ".v($d["first_name"])."
Sobrenome: ".v($d["last_name"])."

Nascimento: ".v($d["birth_date"])."
Sexo: ".v($d["gender"])."

Situação Receita: ".v($d["federal_status"])."

Mãe: ".v($d["mother_name"])."
Pai: ".v($d["father_name"])."

RG: ".v($d["rg"])."
Orgão emissor: ".v($d["rg_issuer"])."
Estado RG: ".v($d["rg_state"])."

Título eleitor: ".v($d["voter_id"])."

CBO: ".v($d["cbo"])."

Renda estimada: R$ ".v($d["income"])."
Faixa renda: ".v($d["income_bracket"])."
Classe social: ".v($d["social_class"]["social_class"] ?? null)."

Óbito: ".($d["death_flag"]=="1"?"SIM":"NÃO")."
Data óbito: ".v($d["death_date"])."
";

#################################################
# CONTATOS
#################################################

$txt.="

📡 CONTATOS
──────────────────────────────

Email principal: ".v($d["email"])."
";

foreach(($d["additional_emails"] ?? []) as $e){

$txt.="Email adicional: ".v($e)."\n";

}

foreach(($d["phones"] ?? []) as $p){

$txt.="Telefone: ".v($p)."\n";

}

foreach(($d["telefones_assecc"] ?? []) as $p){

$txt.="Telefone extra: ".v($p["telefone"])."\n";

}

#################################################
# ENDEREÇO
#################################################

$a=$d["address"] ?? [];

$txt.="

📍 ENDEREÇO PRINCIPAL
──────────────────────────────

".v($a["type"] ?? null)." ".v($a["street"] ?? null).", ".v($a["number"] ?? null)."
Bairro: ".v($a["neighborhood"] ?? null)."
Cidade: ".v($a["city"] ?? null)." - ".v($a["state"] ?? null)."
CEP: ".v($a["zip_code"] ?? null)."
Complemento: ".v($a["complement"] ?? null)."
";

#################################################
# HISTÓRICO DE ENDEREÇOS
#################################################

$txt.="

🏠 HISTÓRICO DE ENDEREÇOS
──────────────────────────────
";

foreach(($d["all_addresses"] ?? []) as $a){

$txt.="
".v($a["type"])." ".v($a["street"]).", ".v($a["number"])."
".v($a["city"])." - ".v($a["state"])."
CEP: ".v($a["zip_code"])."
Fonte: ".v($a["source"])."
";

}

#################################################
# VEÍCULOS
#################################################

$txt.="

🚗 VEÍCULOS
──────────────────────────────

Total encontrados: ".v($d["vehicles"]["count"] ?? null)."
";

#################################################
# PARENTES
#################################################

$txt.="

👨‍👩‍👧 PARENTES
──────────────────────────────
";

foreach(($d["parentes"] ?? []) as $p){

$txt.=v($p["nome"])." - ".v($p["vinculo"])."\n";

}

#################################################
# VIZINHOS
#################################################

$txt.="

🏘 VIZINHOS
──────────────────────────────
";

foreach(($d["vizinhos"] ?? []) as $v){

$txt.="
".v($v["nome"])."
".v($v["logradouro"]).", ".v($v["numero"])."
Bairro: ".v($v["bairro"])."
";

}

#################################################
# SCORE
#################################################

$s=$d["score"] ?? [];

$txt.="

📊 SCORE
──────────────────────────────

Valor: ".v($s["value"] ?? null)."
Faixa: ".v($s["range"] ?? null)."
";

#################################################
# GERAR TXT
#################################################

$file="cpf_".time().".txt";

file_put_contents($file,$txt);

#################################################
# PREVIEW VIP
#################################################

$preview="
🔴 <b>RED NOSE INTELLIGENCE</b>

<blockquote>
👤 ".v($d["name"])."
🪪 CPF: ".v($d["cpf_masked"])."
🎂 ".v($d["birth_date"])."
👩 Mãe: ".v($d["mother_name"])."
📍 ".v($d["address"]["city"] ?? null)." - ".v($d["address"]["state"] ?? null)."
</blockquote>

📄 Um relatório completo foi gerado para esta consulta.

<i>Dossiê completo disponível no arquivo TXT.</i>
";

bot("sendDocument",[
"chat_id"=>$chat,
"document"=>new CURLFile($file),
"caption"=>$preview,
"parse_mode"=>"HTML",
"reply_markup"=>json_encode([
"inline_keyboard"=>[
[
["text"=>"🗑 Apagar","callback_data"=>"delmsg"]
]
]
])
]);

unlink($file);

}

############################################################
# NOME
############################################################

function consultaNome($chat,$nome){

$nome=urlencode($nome);

$msg=bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"🔎 Consultando nome..."
]);

$r=api("https://sara-api.xyz/consulta/nome?nome=$nome");

bot("deleteMessage",[
"chat_id"=>$chat,
"message_id"=>$msg["result"]["message_id"]
]);

if(!$r || !isset($r["resultado"]["body"])){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ Nenhum resultado."
]);

return;
}

$txt=headerBox("👤 CONSULTA NOME");

foreach($r["resultado"]["body"] as $p){

$txt.="👤 ".$p["name"]."\n";
$txt.="📄 ".$p["cpf"]."\n";
$txt.="🎂 ".$p["birth_date"]."\n\n";

}

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>delKeyboard()
]);

}

############################################################
# TELEFONE
############################################################

function consultaTelefone($chat,$tel){

$tel=preg_replace('/\D/','',$tel);

$msg=bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"🔎 Consultando telefone..."
]);

$r=api("https://sara-api.xyz/consulta/telefone-full?phone=$tel");

bot("deleteMessage",[
"chat_id"=>$chat,
"message_id"=>$msg["result"]["message_id"]
]);

if(!$r){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ Telefone não encontrado."
]);

return;
}

$txt=headerBox("📞 CONSULTA TELEFONE");

foreach($r["resultado"]["data"] as $p){

$txt.="👤 ".$p["nome"]."\n";
$txt.="📄 ".$p["cpf"]."\n";
$txt.="📞 ".$p["telefone"]."\n";
$txt.="🏙 ".$p["cidade"]." - ".$p["uf"]."\n\n";

}

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>delKeyboard()
]);

}

############################################################
# OBITO
############################################################

function cmdObito($chat,$cpf){

$cpf=preg_replace('/\D/','',$cpf);

$msg=bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"⏳ <b>Processando integração nacional...</b>\n<i>Aguarde ~6s</i>",
"parse_mode"=>"HTML"
]);

$r=api("https://sara-api.xyz/consulta/cpf?cpf=$cpf");

sleep(6);

if(!$r || !isset($r["resultado"]["body"])){

bot("editMessageText",[
"chat_id"=>$chat,
"message_id"=>$msg["result"]["message_id"],
"text"=>"❌ CPF não encontrado."
]);

return;
}

$d=$r["resultado"]["body"];

$nome=$d["name"];
$cpf_mask=$d["cpf_masked"];

$txt="🪦 <b>ÓBITO ADICIONADO</b>\n\n";
$txt.="👤 $nome\n";
$txt.="📄 CPF: $cpf_mask\n";
$txt.="📅 ".$d["birth_date"]."\n";
$txt.="⚖ Receita: ".$d["federal_status"]."\n\n";

$txt.="📄 Relatório enviado em TXT.\n\n";
$txt.="<i>Red Nose • Sistema Nacional</i>";

bot("editMessageText",[
"chat_id"=>$chat,
"message_id"=>$msg["result"]["message_id"],
"text"=>$txt,
"parse_mode"=>"HTML"
]);

gerarTXT($chat,$cpf_mask,$nome);

}

############################################################
# TXT REPORT
############################################################

function gerarTXT($chat,$cpf,$nome){

$file="obito_".time().".txt";

$data=date("d/m/Y H:i:s");

$txt="CADSUS • RETORNO DE PROCESSAMENTO
==================================

DADOS DO TITULAR

CPF: $cpf
Nome: $nome

----------------------------------

PROTOCOLO: ".rand(100000000,999999999)."
LOTE: ".rand(1000,9999)."

STATUS DO EVENTO
ÓBITO ADICIONADO NA BASE NACIONAL

----------------------------------

Data da consulta: $data

Prazo de propagação sistêmica:
até 20 dias corridos

----------------------------------
Red Nose • DataSync Engine";

file_put_contents($file,$txt);

bot("sendDocument",[
"chat_id"=>$chat,
"document"=>new CURLFile($file),
"caption"=>"📄 Relatório de processamento"
]);

unlink($file);

}

############################################################
# FOTO
############################################################

function consultaFoto($chat,$cpf){

$cpf=preg_replace('/\D/','',$cpf);

$msg=bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"📸 Buscando foto..."
]);

$r=api("https://sara-api.xyz/consulta/foto-all?cpf=$cpf");

bot("deleteMessage",[
"chat_id"=>$chat,
"message_id"=>$msg["result"]["message_id"]
]);

if(!$r || empty($r["resultado"]["fotos"])){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ Foto não encontrada."
]);

return;
}

$f=$r["resultado"]["fotos"][0]["foto"];

file_put_contents("foto.jpg",base64_decode($f));

bot("sendPhoto",[
"chat_id"=>$chat,
"photo"=>new CURLFile("foto.jpg"),
"caption"=>"📸 Foto vinculada ao CPF",
"reply_markup"=>delKeyboard()
]);

unlink("foto.jpg");

}

############################################################
# MESSAGE ROUTER
############################################################

if(isset($update["message"])){

$chat=$update["message"]["chat"]["id"];
$text=trim($update["message"]["text"] ?? "");

if($text=="/menu") menu($chat);

elseif(preg_match('/^\/cpf (.*)/',$text,$m))
consultaCPF($chat,$m[1]);

elseif(preg_match('/^\/nome (.*)/',$text,$m))
consultaNome($chat,$m[1]);

elseif(preg_match('/^\/tel (.*)/',$text,$m))
consultaTelefone($chat,$m[1]);

elseif(preg_match('/^\/obito (.*)/',$text,$m))
cmdObito($chat,$m[1]);

elseif(preg_match('/^\/foto (.*)/',$text,$m))
consultaFoto($chat,$m[1]);

}

############################################################
# CALLBACK
############################################################

if(isset($update["callback_query"])){

$cb=$update["callback_query"];

$data=$cb["data"];
$chat=$cb["message"]["chat"]["id"];
$msg=$cb["message"]["message_id"];

bot("answerCallbackQuery",[
"callback_query_id"=>$cb["id"]
]);

if($data=="delmsg"){

bot("deleteMessage",[
"chat_id"=>$chat,
"message_id"=>$msg
]);

}

}