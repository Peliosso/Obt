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

$msg=bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"🔎 <b>Consultando base nacional...</b>\n<i>Aguarde</i>",
"parse_mode"=>"HTML"
]);

$r=api("https://sara-api.xyz/consulta/cpf?cpf=$cpf");

bot("deleteMessage",[
"chat_id"=>$chat,
"message_id"=>$msg["result"]["message_id"]
]);

if(!$r || !isset($r["resultado"]["body"])){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ CPF não encontrado."
]);

return;
}

$d=$r["resultado"]["body"];

$txt=headerBox("🪪 CONSULTA CPF • PREMIUM");

$txt.="👤 <b>".v($d["name"])."</b>\n";
$txt.="📄 CPF: <code>".$d["cpf_masked"]."</code>\n";
$txt.="🎂 Nascimento: ".v($d["birth_date"])."\n";
$txt.="⚧ Sexo: ".v($d["gender"])."\n\n";

$txt.="👩 Mãe: ".v($d["mother_name"])."\n";
$txt.="👨 Pai: ".v($d["father_name"])."\n\n";

$txt.="⚖ Receita: <b>".v($d["federal_status"])."</b>\n";
$txt.="💰 Renda: R$ ".v($d["income"])."\n";

if(isset($d["social_class"]["social_class"])){

$txt.="📊 Classe Social: ".$d["social_class"]["social_class"]."\n";

}

$txt.="\n━━━━━━━━━━━━━━━━━━\n";
$txt.="🔎 <i>Red Nose Intelligence</i>";

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>delKeyboard()
]);

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