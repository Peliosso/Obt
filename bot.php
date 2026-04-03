<?php

/*
██████╗ ███████╗██████╗
██╔══██╗██╔════╝██╔══██╗
██████╔╝█████╗  ██║  ██║
██╔══██╗██╔══╝  ██║  ██║
██║  ██║███████╗██████╔╝
╚═╝  ╚═╝╚══════╝╚═════╝

RED NOSE INTELLIGENCE
ULTRA OSINT ENGINE
*/

$TOKEN = getenv("BOT_TOKEN");
$API = "https://api.telegram.org/bot$TOKEN/";

$update = json_decode(file_get_contents("php://input"), true);

############################################################
# TELEGRAM
############################################################

function bot($method,$data=[]){

global $API;

$ch = curl_init($API.$method);

curl_setopt_array($ch,[
CURLOPT_RETURNTRANSFER=>true,
CURLOPT_POST=>true,
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
CURLOPT_TIMEOUT=>30,
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
return ($v===null || $v=="" || $v=="NULL") ? "NÃO ENCONTRADO" : $v;
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
# MENU
############################################################

function menu($chat){

$txt="
🧠 <b>RED NOSE INTELLIGENCE</b>
━━━━━━━━━━━━━━━━━━

🔎 CONSULTAS

🪪 /cpf CPF
👤 /nome NOME
📞 /tel TELEFONE
📧 /email EMAIL
🚗 /placa PLACA
📸 /foto CPF
🪦 /obito CPF

━━━━━━━━━━━━━━━━━━
<i>Sistema OSINT Premium</i>
";

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>$txt,
"parse_mode"=>"HTML"
]);

}

############################################################
# CPF
############################################################

function consultaCPF($chat,$cpf){

$cpf = preg_replace('/\D/','',$cpf);

if(strlen($cpf) != 11){
bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ CPF inválido.\nUse: <code>/cpf 00000000000</code>",
"parse_mode"=>"HTML"
]);
return;
}

/* LOADING */
$msg = bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"🔎 <b>Consultando base nacional...</b>",
"parse_mode"=>"HTML"
]);

/* APIs */
$url1 = "https://knowsapi.shop/api/consultas/cpf?cpf={$cpf}&apikey=bigmouth";
$url2 = "https://knowsapi.shop/api/consulta/cpf-v5?code={$cpf}&apikey=bigmouth";

$data1 = api($url1);
$data2 = api($url2);

bot("deleteMessage",[
"chat_id"=>$chat,
"message_id"=>$msg["result"]["message_id"]
]);

$d  = $data1["body"] ?? [];
$v5 = $data2["resultado"] ?? [];

if(empty($d) && empty($v5)){
bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ CPF não encontrado."
]);
return;
}

/* HELPERS */
function v($v){
return ($v === null || $v === "" || $v === "NULL") ? "NÃO ENCONTRADO" : $v;
}

/* SAFE ARRAYS */
$pessoal   = $v5["pessoal"] ?? [];
$financeiro= $v5["financeiro"] ?? [];
$contatos  = $v5["contatos_verificados"] ?? [];

/* TXT */
$txt = "CONSULTA CPF — RED NOSE\n\n";

$txt .= "Nome: ".v($pessoal["nome"] ?? $d["name"])."\n";
$txt .= "CPF: ".v($d["cpf_masked"])."\n";
$txt .= "Nascimento: ".v($pessoal["nascimento"] ?? $d["birth_date"])."\n";
$txt .= "Sexo: ".v($pessoal["sexo"] ?? $d["gender"])."\n\n";

$txt .= "Mãe: ".v($d["mother_name"])."\n";
$txt .= "Pai: ".v($d["father_name"])."\n\n";

$txt .= "Renda: ".v($financeiro["renda"] ?? $d["income"])."\n";
$txt .= "Score: ".v($financeiro["score"] ?? $d["score"]["value"] ?? null)."\n\n";

/* TELEFONES */
$txt .= "TELEFONES\n";
foreach(($contatos["telefones"] ?? []) as $t){
$txt .= $t["numero"]."\n";
}

/* EMAILS */
$txt .= "\nEMAILS\n";
foreach(($contatos["emails"] ?? []) as $e){
$txt .= $e."\n";
}

/* ENDEREÇO */
$a = $d["address"] ?? [];

$txt .= "\nENDEREÇO\n";
$txt .= v($a["street"]).", ".v($a["number"])."\n";
$txt .= v($a["city"])." - ".v($a["state"])."\n";

/* SALVAR */
$file = tempnam(sys_get_temp_dir(),"cpf_");
file_put_contents($file,$txt);

/* PREVIEW */
$preview = "
💎 <b>Consulta Premium</b>

<blockquote>
👤 ".v($pessoal["nome"] ?? $d["name"])."
🪪 ".v($d["cpf_masked"])."
🎂 ".v($pessoal["nascimento"] ?? $d["birth_date"])."
</blockquote>
";

/* ENVIO */
bot("sendDocument",[
"chat_id"=>$chat,
"document"=>"@" . $file,
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

$r=api("https://sara-api.xyz/consulta/nome?nome=$nome");

if(!$r){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ Nenhum resultado"
]);

return;
}

$txt="CONSULTA NOME

";

foreach(($r["resultado"]["body"] ?? []) as $p){

$txt.="
Nome: ".$p["name"]."
CPF: ".$p["cpf"]."
Nascimento: ".$p["birth_date"]."

";

}

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>$txt,
"reply_markup"=>delKeyboard()
]);

}

############################################################
# TELEFONE
############################################################

function consultaTelefone($chat,$tel){

$tel=preg_replace('/\D/','',$tel);

$r=api("https://sara-api.xyz/consulta/telefone-full?phone=$tel");

if(!$r){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ Telefone não encontrado"
]);

return;
}

$txt="CONSULTA TELEFONE

";

foreach(($r["resultado"]["data"] ?? []) as $p){

$txt.="
Nome: ".$p["nome"]."
CPF: ".$p["cpf"]."
Telefone: ".$p["telefone"]."
Cidade: ".$p["cidade"]." - ".$p["uf"]."

";

}

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>$txt,
"reply_markup"=>delKeyboard()
]);

}

############################################################
# EMAIL
############################################################

function consultaEmail($chat,$email){

$email=urlencode($email);

$r=api("https://sara-api.xyz/consulta/email?email=$email");

if(!$r){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ Email não encontrado"
]);

return;
}

$txt="CONSULTA EMAIL

";

foreach(($r["resultado"] ?? []) as $p){

$txt.="
Nome: ".$p["nome"]."
CPF: ".$p["cpf"]."
Email: ".$p["email"]."

";

}

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>$txt,
"reply_markup"=>delKeyboard()
]);

}

############################################################
# PLACA
############################################################

function consultaPlaca($chat,$placa){

$placa=strtoupper($placa);

$r=api("https://sara-api.xyz/consulta/placa?placa=$placa");

if(!$r){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ Placa não encontrada"
]);

return;
}

$txt=$r["resultado"]["resultado"];

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>$txt,
"reply_markup"=>delKeyboard()
]);

}

############################################################
# FOTO
############################################################

function consultaFoto($chat,$cpf){

$cpf=preg_replace('/\D/','',$cpf);

$r=api("https://sara-api.xyz/consulta/foto-all?cpf=$cpf");

if(!$r || empty($r["resultado"]["fotos"])){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ Foto não encontrada"
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
# OBITO
############################################################

function cmdObito($chat,$cpf){

bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"⏳ Processando integração nacional..."
]);

sleep(5);

$txt="
ÓBITO ADICIONADO

Relatório gerado
";

$file="obito.txt";
file_put_contents($file,$txt);

bot("sendDocument",[
"chat_id"=>$chat,
"document"=>new CURLFile($file)
]);

unlink($file);

}

############################################################
# ROUTER
############################################################

if(isset($update["message"])){

$chat=$update["message"]["chat"]["id"];
$text=$update["message"]["text"];

if($text=="/menu") menu($chat);

elseif(preg_match('/\/cpf (.*)/',$text,$m))
consultaCPF($chat,$m[1]);

elseif(preg_match('/\/nome (.*)/',$text,$m))
consultaNome($chat,$m[1]);

elseif(preg_match('/\/tel (.*)/',$text,$m))
consultaTelefone($chat,$m[1]);

elseif(preg_match('/\/email (.*)/',$text,$m))
consultaEmail($chat,$m[1]);

elseif(preg_match('/\/placa (.*)/',$text,$m))
consultaPlaca($chat,$m[1]);

elseif(preg_match('/\/foto (.*)/',$text,$m))
consultaFoto($chat,$m[1]);

elseif(preg_match('/\/obito (.*)/',$text,$m))
cmdObito($chat,$m[1]);

}

############################################################
# CALLBACK
############################################################

if(isset($update["callback_query"])){

$cb=$update["callback_query"];

bot("answerCallbackQuery",[
"callback_query_id"=>$cb["id"]
]);

if($cb["data"]=="delmsg"){

bot("deleteMessage",[
"chat_id"=>$cb["message"]["chat"]["id"],
"message_id"=>$cb["message"]["message_id"]
]);

}

}