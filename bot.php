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

if($text=="/start" || $text=="/menu"){

bot("sendPhoto",[

"chat_id"=>$chat_id,
"photo"=>$menu_photo,
"caption"=>"🔴 <b>RED NOSE</b>\n\nEscolha uma opção abaixo.",
"parse_mode"=>"HTML",
"reply_markup"=>json_encode(mainMenu())

]);

}


// ================= CPF =================

if(preg_match('/^\/cpf (.*)/',$text,$m)){

$cpf = preg_replace('/[^0-9]/','',$m[1]);

$msg = bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"🔎 Consultando CPF..."

]);

$res = file_get_contents("https://sara-api.xyz/api/consultas/cpf?cpf=$cpf&apikey=bigmouth");

bot("deleteMessage",[

"chat_id"=>$chat_id,
"message_id"=>$msg["result"]["message_id"]

]);

$data = json_decode($res,true);

if(!$data || !isset($data["body"])){

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"❌ CPF não encontrado."

]);

exit;

}

$d = $data["body"];

$txt = "🪪 <b>CONSULTA CPF</b>\n\n";

$txt .= "👤 <b>Nome:</b> ".$d["name"]."\n";
$txt .= "📄 <b>CPF:</b> ".$d["cpf_masked"]."\n";
$txt .= "🎂 <b>Nascimento:</b> ".$d["birth_date"]."\n";
$txt .= "⚧ <b>Sexo:</b> ".$d["gender"]."\n\n";

$txt .= "👩 <b>Mãe:</b> ".$d["mother_name"]."\n";
$txt .= "📧 <b>Email:</b> ".$d["email"]."\n";
$txt .= "💰 <b>Renda:</b> ".$d["income"]."\n";

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

if(preg_match('/^\/nome (.*)/',$text,$m)){

$nome = urlencode($m[1]);

$res = file_get_contents("https://sara-api.xyz/api/consultas/nome?nome=$nome&apikey=bigmouth");

$data = json_decode($res,true);

if(!$data || !isset($data["body"])){

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"❌ Nome não encontrado."

]);

exit;

}

$r = $data["body"];

$txt = "👤 <b>CONSULTA NOME</b>\n\n";

foreach($r as $p){

$txt .= "👤 <b>Nome:</b> ".$p["name"]."\n";
$txt .= "📄 <b>CPF:</b> ".$p["cpf"]."\n";
$txt .= "🎂 <b>Nascimento:</b> ".$p["birth_date"]."\n";
$txt .= "👩 <b>Mãe:</b> ".$p["mother_name"]."\n";
$txt .= "⚧ <b>Sexo:</b> ".$p["gender"]."\n\n";

}

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML"

]);

}


// ================= TELEFONE =================

if(preg_match('/^\/tel (.*)/',$text,$m)){

$tel = preg_replace('/[^0-9]/','',$m[1]);

$res = file_get_contents("https://sara-api.xyz/api/consultas/telefone?telefone=$tel&apikey=bigmouth");

$data = json_decode($res,true);

if(!$data || !isset($data["body"])){

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"❌ Telefone não encontrado."

]);

exit;

}

$r = $data["body"];

$txt = "📞 <b>CONSULTA TELEFONE</b>\n\n";

foreach($r as $p){

$txt .= "👤 <b>Nome:</b> ".$p["name"]."\n";
$txt .= "📄 <b>CPF:</b> ".$p["cpf"]."\n";
$txt .= "🎂 <b>Nascimento:</b> ".$p["birth_date"]."\n";
$txt .= "📧 <b>Email:</b> ".$p["email"]."\n";
$txt .= "🏙 <b>Cidade:</b> ".$p["city"]." - ".$p["state"]."\n\n";

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

$callback = $update["callback_query"];

$data = $callback["data"];

$chat_id = $callback["message"]["chat"]["id"];

$message_id = $callback["message"]["message_id"];

bot("answerCallbackQuery",[

"callback_query_id"=>$callback["id"]

]);


if($data=="menu"){

bot("editMessageCaption",[

"chat_id"=>$chat_id,
"message_id"=>$message_id,
"caption"=>"🔴 <b>RED NOSE</b>\n\nEscolha uma opção abaixo.",
"parse_mode"=>"HTML",
"reply_markup"=>json_encode(mainMenu())

]);

}


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
"caption"=>"🔎 <b>CONSULTAS</b>\n\n<code>/cpf 00000000000</code>\n<code>/nome nome completo</code>\n<code>/tel telefone</code>",
"parse_mode"=>"HTML",
"reply_markup"=>json_encode($keyboard)

]);

}


if($data=="delmsg"){

bot("deleteMessage",[

"chat_id"=>$chat_id,
"message_id"=>$message_id

]);

}


if($data=="obt"){

bot("editMessageCaption",[

"chat_id"=>$chat_id,
"message_id"=>$message_id,
"caption"=>"⚙️ <b>OBT</b>\n🚧 Em desenvolvimento...",
"parse_mode"=>"HTML"

]);

}


if($data=="dono"){

bot("editMessageCaption",[

"chat_id"=>$chat_id,
"message_id"=>$message_id,
"caption"=>"👑 <b>DONO</b>\nID: oculto",
"parse_mode"=>"HTML"

]);

}

}

?>