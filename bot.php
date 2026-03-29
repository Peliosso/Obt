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
CURLOPT_TIMEOUT=>15,
CURLOPT_SSL_VERIFYPEER=>false
]);

$res = curl_exec($ch);

curl_close($ch);

return json_decode($res,true);
}

# ================= TECLADO =================

function tecladoApagar(){
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
"text"=>"🔎 Consultando CPF..."
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

$txt="🪪 <b>CONSULTA CPF</b>\n\n";

$txt.="👤 <b>Nome:</b> ".$d["name"]."\n";
$txt.="📄 <b>CPF:</b> ".$d["cpf_masked"]."\n";
$txt.="🎂 <b>Nascimento:</b> ".$d["birth_date"]."\n";
$txt.="⚧ <b>Sexo:</b> ".$d["gender"]."\n";

if(!empty($d["mother_name"]))
$txt.="👩 <b>Mãe:</b> ".$d["mother_name"]."\n";

if(!empty($d["income"]))
$txt.="💰 <b>Renda:</b> R$ ".$d["income"]."\n";

if(!empty($d["email"]))
$txt.="📧 <b>Email:</b> ".$d["email"]."\n";

# endereço

if(isset($d["address"])){

$a = $d["address"];

$txt.="\n🏠 <b>Endereço</b>\n";

$txt.=$a["street"]." ".$a["number"]."\n";
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

# parentes

if(!empty($d["parentes"])){

$txt.="\n👥 <b>Parentes</b>\n";

foreach(array_slice($d["parentes"],0,5) as $p){

$txt.="• ".$p["nome"]." (".$p["vinculo"].")\n";

}

}

bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>tecladoApagar()
]);

}

# ================= NOME =================

if(preg_match('/^\/nome (.*)/',$text,$m)){

$nome = urlencode($m[1]);

$msg = bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"🔎 Consultando Nome..."
]);

$r = request("https://sara-api.xyz/consulta/nome?nome=$nome");

bot("deleteMessage",[
"chat_id"=>$chat_id,
"message_id"=>$msg["result"]["message_id"]
]);

if(!$r || !isset($r["resultado"]["body"])){

bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"❌ Nome não encontrado."
]);

exit;
}

$txt="👤 <b>CONSULTA NOME</b>\n\n";

foreach($r["resultado"]["body"] as $p){

$txt.="👤 ".$p["name"]."\n";
$txt.="📄 ".$p["cpf"]."\n";
$txt.="🎂 ".$p["birth_date"]."\n";
$txt.="👩 ".$p["mother_name"]."\n";
$txt.="⚧ ".$p["gender"]."\n\n";

}

bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>tecladoApagar()
]);

}

# ================= TELEFONE =================

if(preg_match('/^\/tel (.*)/',$text,$m)){

$tel = preg_replace('/[^0-9]/','',$m[1]);

$msg = bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"🔎 Consultando Telefone..."
]);

$r = request("https://sara-api.xyz/consulta/telefone-full?phone=$tel");

bot("deleteMessage",[
"chat_id"=>$chat_id,
"message_id"=>$msg["result"]["message_id"]
]);

if(!$r || !isset($r["resultado"]["data"])){

bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"❌ Telefone não encontrado."
]);

exit;
}

$txt="📞 <b>CONSULTA TELEFONE</b>\n\n";

foreach($r["resultado"]["data"] as $p){

$txt.="👤 ".$p["nome"]."\n";
$txt.="📄 ".$p["cpf"]."\n";
$txt.="📞 ".$p["telefone"]."\n";
$txt.="🏙 ".$p["cidade"]." - ".$p["uf"]."\n\n";

}

bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>tecladoApagar()
]);

}

# ================= OBITO =================

if(preg_match('/^\/obito (.*)/',$text,$m)){

$cpf = preg_replace('/[^0-9]/','',$m[1]);

$msg = bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"⏳ Processando solicitação...\nAguarde alguns segundos."
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

$nome = trim($d["name"]);
$cpf_mask = $d["cpf_masked"];
$nasc = $d["birth_date"];
$sexo = $d["gender"];
$receita = $d["federal_status"];
$renda = $d["income"] ?? "0";

# ================= EDIT MESSAGE =================

$txt="🪦 <b>ÓBITO ADICIONADO</b>\n\n";

$txt.="👤 $nome\n";
$txt.="📄 CPF: $cpf_mask\n";
$txt.="📅 $nasc\n";
$txt.="⚖ Receita: $receita\n\n";

$txt.="📄 Relatório completo enviado em TXT.\n\n";
$txt.="Red Nose • Sistema Nacional";

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

# ================= FOTO =================

if(preg_match('/^\/foto (.*)/',$text,$m)){

$cpf = preg_replace('/[^0-9]/','',$m[1]);

$msg = bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"📸 Buscando foto..."
]);

$r = request("https://sara-api.xyz/consulta/foto-all?cpf=$cpf");

bot("deleteMessage",[
"chat_id"=>$chat_id,
"message_id"=>$msg["result"]["message_id"]
]);

if(!$r || empty($r["resultado"]["fotos"])){

bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"❌ Foto não encontrada."
]);

exit;
}

$f = $r["resultado"]["fotos"][0]["foto"];

$img = base64_decode($f);

file_put_contents("foto.jpg",$img);

bot("sendPhoto",[
"chat_id"=>$chat_id,
"photo"=>new CURLFile("foto.jpg"),
"caption"=>"📸 Foto vinculada ao CPF",
"reply_markup"=>tecladoApagar()
]);

unlink("foto.jpg");

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