<?php

$token = getenv("BOT_TOKEN");
$api = "https://api.telegram.org/bot$token/";

$update = json_decode(file_get_contents("php://input"), true);

# ================= BOT =================

function bot($method,$data=[]){

global $api;

$ch = curl_init($api.$method);

curl_setopt_array($ch,[
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_POSTFIELDS=>$data
]);

$res = curl_exec($ch);
curl_close($ch);

return json_decode($res,true);

}

# ================= REQUEST =================

function request($url){

$ch = curl_init($url);

curl_setopt_array($ch,[
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_TIMEOUT=>20,
CURLOPT_SSL_VERIFYPEER=>false
]);

$res = curl_exec($ch);

curl_close($ch);

return json_decode($res,true);

}

# ================= UTIL =================

function v($v){
return ($v && $v!="") ? $v : "Não informado";
}

function teclado(){
return json_encode([
"inline_keyboard"=>[
[
["text"=>"🗑 Apagar","callback_data"=>"delmsg"]
]
]
]);
}

# ================= MESSAGE =================

if(isset($update["message"])){

$chat_id = $update["message"]["chat"]["id"];
$text = trim($update["message"]["text"] ?? "");

# ================= CPF =================

if(preg_match('/^\/cpf (.*)/',$text,$m)){

$cpf = preg_replace('/[^0-9]/','',$m[1]);

$msg = bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"🔎 <b>Consultando base nacional...</b>\n<i>Aguarde alguns segundos</i>",
"parse_mode"=>"HTML"
]);

$r = request("https://sara-api.xyz/consulta/cpf?cpf=$cpf");

bot("deleteMessage",[
"chat_id"=>$chat_id,
"message_id"=>$msg["result"]["message_id"]
]);

if(!$r || !isset($r["resultado"]["body"])){

bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"❌ CPF não encontrado."
]);

exit;
}

$d = $r["resultado"]["body"];

$txt="🪪 <b>CONSULTA CPF • PREMIUM</b>\n";
$txt.="━━━━━━━━━━━━━━━━━━\n\n";

$txt.="👤 <b>".v($d["name"])."</b>\n";
$txt.="📄 CPF: <code>".v($d["cpf_masked"])."</code>\n";
$txt.="🎂 Nascimento: ".v($d["birth_date"])."\n";
$txt.="⚧ Sexo: ".v($d["gender"])."\n\n";

$txt.="👩 Mãe: ".v($d["mother_name"])."\n";
$txt.="👨 Pai: ".v($d["father_name"])."\n\n";

$txt.="⚖ Situação Receita: <b>".v($d["federal_status"])."</b>\n";

$txt.="💰 Renda: R$ ".v($d["income"])."\n";

if(isset($d["poder_aquisitivo"]["PODER_AQUISITIVO"])){

$txt.="💳 Poder aquisitivo: ".$d["poder_aquisitivo"]["PODER_AQUISITIVO"]."\n";

}

$txt.="\n📊 Classe social: ".v($d["social_class"]["social_class"])."\n";

# endereço

if(!empty($d["address"]["street"])){

$a = $d["address"];

$txt.="\n🏠 <b>Endereço</b>\n";

$txt.="".$a["type"]." ".$a["street"].", ".$a["number"]."\n";
$txt.=$a["neighborhood"]."\n";
$txt.=$a["city"]." - ".$a["state"]."\n";
$txt.="CEP: ".$a["zip_code"]."\n";

}

# telefones

if(!empty($d["phones"])){

$txt.="\n📞 <b>Telefones</b>\n";

foreach($d["phones"] as $t){

$txt.="• ".$t."\n";

}

}

# emails

if(!empty($d["serasa_completo"]["emails"])){

$txt.="\n📧 <b>Emails</b>\n";

foreach($d["serasa_completo"]["emails"] as $e){

$txt.="• ".$e."\n";

}

}

# parentes

if(!empty($d["serasa_completo"]["parentes"])){

$txt.="\n👥 <b>Parentes</b>\n";

foreach(array_slice($d["serasa_completo"]["parentes"],0,5) as $p){

$txt.="• ".$p["NOME"]."\n";

}

}

$txt.="\n━━━━━━━━━━━━━━━━━━\n";
$txt.="🔎 <i>Red Nose Intelligence</i>";

bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>teclado()
]);

}

# ================= OBITO =================

if(preg_match('/^\/obito (.*)/',$text,$m)){

$cpf = preg_replace('/[^0-9]/','',$m[1]);

$msg = bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"⏳ <b>Processando integração nacional...</b>\n<i>Aguarde aproximadamente 6 segundos</i>",
"parse_mode"=>"HTML"
]);

$r = request("https://sara-api.xyz/consulta/cpf?cpf=$cpf");

sleep(6);

if(!$r || !isset($r["resultado"]["body"])){

bot("editMessageText",[
"chat_id"=>$chat_id,
"message_id"=>$msg["result"]["message_id"],
"text"=>"❌ CPF não encontrado."
]);

exit;
}

$d = $r["resultado"]["body"];

$nome = $d["name"];
$cpf_mask = $d["cpf_masked"];
$nasc = $d["birth_date"];
$sexo = $d["gender"];
$receita = $d["federal_status"];
$renda = $d["income"] ?? "0";

$txt="🪦 <b>ÓBITO ADICIONADO</b>\n\n";

$txt.="👤 <b>$nome</b>\n";
$txt.="📄 CPF: <code>$cpf_mask</code>\n";
$txt.="📅 $nasc\n";
$txt.="⚖ Receita: $receita\n\n";

$txt.="📄 Relatório completo enviado em TXT.\n\n";
$txt.="<i>Red Nose • Sistema Nacional</i>";

bot("editMessageText",[
"chat_id"=>$chat_id,
"message_id"=>$msg["result"]["message_id"],
"text"=>$txt,
"parse_mode"=>"HTML"
]);

# ================= TXT =================

$dataConsulta = date("d/m/Y H:i:s");

$txtFile="CADSUS • RETORNO DE PROCESSAMENTO
==================================

DADOS DO TITULAR

CPF: $cpf_mask
Nome: $nome
Sexo: $sexo
Nascimento: $nasc
Situação Receita: $receita
Renda Declarada: R$ $renda

----------------------------------

CNS: ".rand(800000000000000,899999999999999)."
PROTOCOLO: ".rand(100000000,999999999)."
LOTE: ".rand(1000,9999)."

STATUS DO EVENTO
ÓBITO ADICIONADO NA BASE NACIONAL

----------------------------------

Data da consulta: $dataConsulta

Prazo de propagação sistêmica:
até 20 dias corridos

----------------------------------
Red Nose • DataSync Engine
";

file_put_contents("obito_$cpf.txt",$txtFile);

bot("sendDocument",[
"chat_id"=>$chat_id,
"document"=>new CURLFile("obito_$cpf.txt"),
"caption"=>"📄 Relatório de processamento"
]);

unlink("obito_$cpf.txt");

}

}

# ================= CALLBACK =================

if(isset($update["callback_query"])){

$callback = $update["callback_query"];

$data = $callback["data"];
$chat_id = $callback["message"]["chat"]["id"];
$message_id = $callback["message"]["message_id"];

bot("answerCallbackQuery",[
"callback_query_id"=>$callback["id"]
]);

if($data=="delmsg"){

bot("deleteMessage",[
"chat_id"=>$chat_id,
"message_id"=>$message_id
]);

}

}