<?php

$token = getenv("BOT_TOKEN");
$api = "https://api.telegram.org/bot$token/";

$update = json_decode(file_get_contents("php://input"), true);

function bot($method,$data=[]){
global $api;

$ch = curl_init($api.$method);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

$res = curl_exec($ch);
curl_close($ch);

return json_decode($res,true);
}

function tecladoApagar(){
return json_encode([
"inline_keyboard"=>[
[
["text"=>"🗑 Apagar","callback_data"=>"delmsg"]
]
]
]);
}

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

$txt="🪪 <b>CONSULTA CPF</b>\n\n";

$txt.="👤 <b>Nome:</b> ".$d["name"]."\n";
$txt.="📄 <b>CPF:</b> ".$d["cpf_masked"]."\n";
$txt.="🎂 <b>Nascimento:</b> ".$d["birth_date"]."\n";
$txt.="⚧ <b>Sexo:</b> ".$d["gender"]."\n\n";

$txt.="👩 <b>Mãe:</b> ".$d["mother_name"]."\n";
$txt.="📧 <b>Email:</b> ".$d["email"]."\n";
$txt.="💰 <b>Renda:</b> ".$d["income"]."\n";

bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>tecladoApagar()
]);

}

# ================= CPF2 =================

if(preg_match('/^\/cpf2 (.*)/',$text,$m)){

$cpf = preg_replace('/[^0-9]/','',$m[1]);

$msg = bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"🔎 Consultando CPF2..."
]);

$res = file_get_contents("https://sara-api.xyz/api/consultas/cpf2?cpf=$cpf&apikey=bigmouth");

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

$txt="🪪 <b>CONSULTA CPF2</b>\n\n";

foreach($d as $p){

$txt.="👤 <b>Nome:</b> ".$p["name"]."\n";
$txt.="📄 <b>CPF:</b> ".$p["cpf"]."\n";
$txt.="🎂 <b>Nascimento:</b> ".$p["birth_date"]."\n";
$txt.="👩 <b>Mãe:</b> ".$p["mother_name"]."\n";
$txt.="⚧ <b>Sexo:</b> ".$p["gender"]."\n\n";

}

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>tecladoApagar()
]);

}

# ================= CPF3 =================

if(preg_match('/^\/cpf3 (.*)/',$text,$m)){

$cpf = preg_replace('/[^0-9]/','',$m[1]);

$msg = bot("sendMessage",[
"chat_id"=>$chat_id,
"text"=>"🔎 Consultando CPF3..."
]);

$res = file_get_contents("https://sara-api.xyz/api/consultas/cpf3?cpf=$cpf&apikey=bigmouth");

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

$txt="🪪 <b>CONSULTA CPF3</b>\n\n";

foreach($d as $p){

$txt.="👤 <b>Nome:</b> ".$p["name"]."\n";
$txt.="📄 <b>CPF:</b> ".$p["cpf"]."\n";
$txt.="🎂 <b>Nascimento:</b> ".$p["birth_date"]."\n";
$txt.="👩 <b>Mãe:</b> ".$p["mother_name"]."\n\n";

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

$res = file_get_contents("https://sara-api.xyz/api/consultas/nome?nome=$nome&apikey=bigmouth");

bot("deleteMessage",[

"chat_id"=>$chat_id,
"message_id"=>$msg["result"]["message_id"]

]);

$data = json_decode($res,true);

if(!$data || !isset($data["body"])){

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"❌ Nome não encontrado."

]);

exit;
}

$txt="👤 <b>CONSULTA NOME</b>\n\n";

foreach($data["body"] as $p){

$txt.="👤 <b>Nome:</b> ".$p["name"]."\n";
$txt.="📄 <b>CPF:</b> ".$p["cpf"]."\n";
$txt.="🎂 <b>Nascimento:</b> ".$p["birth_date"]."\n";
$txt.="👩 <b>Mãe:</b> ".$p["mother_name"]."\n";
$txt.="⚧ <b>Sexo:</b> ".$p["gender"]."\n\n";

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

$res = file_get_contents("https://sara-api.xyz/api/consultas/telefone?telefone=$tel&apikey=bigmouth");

bot("deleteMessage",[

"chat_id"=>$chat_id,
"message_id"=>$msg["result"]["message_id"]

]);

$data = json_decode($res,true);

if(!$data || !isset($data["body"])){

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>"❌ Telefone não encontrado."

]);

exit;
}

$txt="📞 <b>CONSULTA TELEFONE</b>\n\n";

foreach($data["body"] as $p){

$txt.="👤 <b>Nome:</b> ".$p["name"]."\n";
$txt.="📄 <b>CPF:</b> ".$p["cpf"]."\n";
$txt.="🎂 <b>Nascimento:</b> ".$p["birth_date"]."\n";
$txt.="📧 <b>Email:</b> ".$p["email"]."\n";
$txt.="🏙 <b>Cidade:</b> ".$p["city"]." - ".$p["state"]."\n\n";

}

bot("sendMessage",[

"chat_id"=>$chat_id,
"text"=>$txt,
"parse_mode"=>"HTML",
"reply_markup"=>tecladoApagar()

]);

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