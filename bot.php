<?php

// ================= CONFIG =================
$token = getenv("BOT_TOKEN");
$api = "https://api.telegram.org/bot$token/";

$menu_photo = "https://img.sanishtech.com/u/ae24e1175ddf7d3206536335d7ee414a.jpeg";

// =========================================

$update = json_decode(file_get_contents("php://input"), true);

// ================= FUNÇÃO BOT =================
function bot($method,$data=[]){
global $api;

$ch = curl_init($api.$method);

curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

$res = curl_exec($ch);

curl_close($ch);

return json_decode($res,true);
}

// ================= API CPF =================
function consultaCPF($cpf){

$url = "https://sara-api.xyz/api/consultas/cpf?cpf=$cpf&apikey=bigmouth";

$res = file_get_contents($url);

if(!$res){
return false;
}

return json_decode($res,true);

}

// ================= API NOME =================
function consultaNome($nome){

$nome = urlencode($nome);

$url = "https://sara-api.xyz/api/consultas/nome?nome=$nome&apikey=bigmouth";

$res = file_get_contents($url);

if(!$res){
return false;
}

return json_decode($res,true);

}

// ================= API TELEFONE =================
function consultaTelefone($tel){

$url = "https://sara-api.xyz/api/consultas/telefone?telefone=$tel&apikey=bigmouth";

$res = file_get_contents($url);

if(!$res){
return false;
}

return json_decode($res,true);

}

// ================= MENU =================
function mainMenu(){

return [
"inline_keyboard"=>[

[
["text"=>"🔎 Consultas","callback_data"=>"consultas"]
],

[
["text"=>"⚙️ OBT","callback_data"=>"obt"]
],

[
["text"=>"👑 Dono","callback_data"=>"dono"]
]

]

];

}

// ================= MESSAGE =================
if(isset($update["message"])){

$chat_id = $update["message"]["chat"]["id"];
$text = trim($update["message"]["text"] ?? "");

// ================= START =================
if($text=="/start" or $text=="/menu"){

bot("sendPhoto",[

"chat_id"=>$chat_id,
"photo"=>$menu_photo,
"caption"=>"🔴 <b>RED NOSE</b>\n\nEscolha uma opção abaixo.",
"parse_mode"=>"HTML",
"reply_markup"=>json_encode(mainMenu())

]);

}

// ================= CPF =================
if(preg_match('/^\/cpf\s+(.+)/',$text,$m)){

$cpf = preg_replace('/[^0-9]/','',$m[1]);

$msg = bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"🔎 Consultando CPF..."

]);

$msg_id = $msg["result"]["message_id"];

$res = consultaCPF($cpf);

bot("deleteMessage",[

"chat_id"=>$chat_id,
"message_id"=>$msg_id

]);

if(!$res || !isset($res["body"])){

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"❌ CPF não encontrado."

]);

exit;

}

$d = $res["body"];

$txt = "🪪 <b>CONSULTA CPF</b>\n\n";

$txt .= "👤 <b>Nome:</b> ".$d["name"]."\n";
$txt .= "📄 <b>CPF:</b> ".$d["cpf_masked"]."\n";
$txt .= "🎂 <b>Nascimento:</b> ".$d["birth_date"]."\n";
$txt .= "⚧ <b>Sexo:</b> ".$d["gender"]."\n";

$txt .= "\n👩 <b>Mãe:</b> ".$d["mother_name"]."\n";
$txt .= "👨 <b>Pai:</b> ".$d["father_name"]."\n";

$txt .= "\n📧 <b>Email:</b> ".$d["email"]."\n";

$txt .= "\n💰 <b>Renda:</b> ".$d["income"]."\n";

$txt .= "\n🏠 <b>Endereço</b>\n";

$txt .= $d["address"]["street"].", ".$d["address"]["number"]."\n";
$txt .= $d["address"]["neighborhood"]."\n";
$txt .= $d["address"]["city"]." - ".$d["address"]["state"]."\n";
$txt .= "CEP: ".$d["address"]["zip_code"]."\n";

if(isset($d["phones"])){

$txt .= "\n📞 <b>Telefones</b>\n";

foreach($d["phones"] as $tel){

$txt .= "📱 $tel\n";

}

}

if(isset($d["parentes"])){

$txt .= "\n👥 <b>Parentes</b>\n";

foreach($d["parentes"] as $p){

$txt .= $p["vinculo"].": ".$p["nome"]."\n";

}

}

$keyboard = [

"inline_keyboard"=>[

[
["text"=>"🗑 Apagar","callback_data"=>"delmsg"]
]

]

];

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>json_encode($keyboard)

]);

}

// ================= NOME =================
if(preg_match('/^\/nome\s+(.+)/',$text,$m)){

$nome = $m[1];

$res = consultaNome($nome);

if(!$res || !isset($res["body"])){

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"❌ Nenhum resultado."

]);

exit;

}

$txt = "👤 <b>CONSULTA NOME</b>\n\n";

foreach($res["body"] as $r){

$txt .= "👤 ".$r["name"]."\n";
$txt .= "📄 CPF: ".$r["cpf"]."\n";
$txt .= "🎂 ".$r["birth_date"]."\n";
$txt .= "👩 ".$r["mother_name"]."\n\n";

}

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML"

]);

}

// ================= TELEFONE =================
if(preg_match('/^\/tel\s+(.+)/',$text,$m)){

$tel = preg_replace('/[^0-9]/','',$m[1]);

$res = consultaTelefone($tel);

if(!$res || !isset($res["body"])){

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"❌ Número não encontrado."

]);

exit;

}

$txt = "📞 <b>CONSULTA TELEFONE</b>\n\n";

foreach($res["body"] as $r){

$txt .= "👤 ".$r["name"]."\n";
$txt .= "📄 CPF: ".$r["cpf"]."\n";
$txt .= "🎂 ".$r["birth_date"]."\n";
$txt .= "📧 ".$r["email"]."\n";
$txt .= "📍 ".$r["city"]." - ".$r["state"]."\n\n";

}

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML"

]);

}

}

// ================= CALLBACK =================
if(isset($update["callback_query"])){

$data = $update["callback_query"]["data"];
$chat_id = $update["callback_query"]["message"]["chat"]["id"];
$message_id = $update["callback_query"]["message"]["message_id"];

bot("answerCallbackQuery",[

"callback_query_id"=>$update["callback_query"]["id"]

]);

// ================= CONSULTAS =================
if($data=="consultas"){

$keyboard=[

"inline_keyboard"=>[

[
["text"=>"🪪 CPF","callback_data"=>"cpf"]
],

[
["text"=>"👤 Nome","callback_data"=>"nome"]
],

[
["text"=>"📞 Telefone","callback_data"=>"tel"]
],

[
["text"=>"🔙 Voltar","callback_data"=>"menu"]
]

]

];

bot("editMessageCaption",[

"chat_id"=>$chat_id,
"message_id"=>$message_id,
"caption"=>"🔎 <b>CONSULTAS</b>\n\nUse:\n\n/cpf 00000000000\n/nome nome completo\n/tel 11900000000",
"parse_mode"=>"HTML",
"reply_markup"=>json_encode($keyboard)

]);

}

// ================= MENU =================
if($data=="menu"){

bot("editMessageCaption",[

"chat_id"=>$chat_id,
"message_id"=>$message_id,
"caption"=>"🔴 <b>RED NOSE</b>\n\nEscolha uma opção abaixo.",
"parse_mode"=>"HTML",
"reply_markup"=>json_encode(mainMenu())

]);

}

// ================= APAGAR =================
if($data=="delmsg"){

bot("deleteMessage",[

"chat_id"=>$chat_id,
"message_id"=>$message_id

]);

}

// ================= OBT =================
if($data=="obt"){

bot("editMessageCaption",[

"chat_id"=>$chat_id,
"message_id"=>$message_id,
"caption"=>"⚙️ <b>OBT</b>\n🚧 Em desenvolvimento...",
"parse_mode"=>"HTML"

]);

}

// ================= DONO =================
if($data=="dono"){

bot("editMessageCaption",[

"chat_id"=>$chat_id,
"message_id"=>$message_id,
"caption"=>"👑 <b>DONO</b>\nID: oculto",
"parse_mode"=>"HTML"

]);

}

}