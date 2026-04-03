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

function v($v){
return ($v === null || $v === "" || $v === "NULL") ? "NÃO ENCONTRADO" : $v;
}

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

/* APIs ASTRO */
$url1 = "https://knowsapi.shop/api/consultas/cpf?cpf={$cpf}&apikey=bigmouth";
$url2 = "https://knowsapi.shop/api/consulta/cpf-v5?code={$cpf}&apikey=bigmouth";

$ch = curl_init();
curl_setopt_array($ch,[
CURLOPT_RETURNTRANSFER => true,
CURLOPT_TIMEOUT => 30
]);

curl_setopt($ch,CURLOPT_URL,$url1);
$res1 = curl_exec($ch);
$data1 = json_decode($res1,true);

curl_setopt($ch,CURLOPT_URL,$url2);
$res2 = curl_exec($ch);
$data2 = json_decode($res2,true);

curl_close($ch);

/* REMOVE LOADING */
bot("deleteMessage",[
"chat_id"=>$chat,
"message_id"=>$msg["result"]["message_id"]
]);

if(empty($data1["body"]) && empty($data2["resultado"])){
bot("sendMessage",[
"chat_id"=>$chat,
"text"=>"❌ CPF não encontrado."
]);
return;
}

$d  = $data1["body"];
$v5 = $data2["resultado"];

/* ================= TXT FULL ================= */

$txt = "
╔══════════════════════════════╗
   CONSULTA CPF ULTRA — RED NOSE INTELLIGENCE
╚══════════════════════════════╝

🧠 DADOS PRINCIPAIS
──────────────────────────────
Nome: ".v($v5["pessoal"]["nome"] ?? $d["name"])."
CPF: ".v($d["cpf_masked"])."
Nascimento: ".v($v5["pessoal"]["nascimento"] ?? $d["birth_date"])."
Sexo: ".v($v5["pessoal"]["sexo"] ?? $d["gender"])."
Raça: ".v($v5["pessoal"]["raca"] ?? null)."
Escolaridade: ".v($v5["pessoal"]["escolaridade"] ?? null)."
Profissão: ".v($v5["pessoal"]["profissao"] ?? null)."

Situação Receita: ".v($d["federal_status"])."

👨‍👩‍👧 FILIAÇÃO
──────────────────────────────
Mãe: ".v($d["mother_name"])."
Pai: ".v($d["father_name"])."

📄 DOCUMENTOS
──────────────────────────────
RG: ".v($d["rg"])."
Título eleitor: ".v($d["voter_id"])."
CNS: ".v($v5["documentos"]["cns"] ?? null)."
NIS: ".v($v5["documentos"]["nis"] ?? null)."

💰 DADOS FINANCEIROS
──────────────────────────────
Renda: ".v($v5["financeiro"]["renda"] ?? $d["income"])."
Score: ".v($v5["financeiro"]["score"] ?? $d["score"]["value"])."
INSS: ".v($v5["financeiro"]["inss"] ?? null)."

📡 CONTATOS
──────────────────────────────
";

foreach(($v5["contatos_verificados"]["telefones"] ?? []) as $t){
$wpp = $t["tem_whatsapp"] ? "SIM" : "NÃO";
$txt .= "Telefone: ".$t["numero"]." | WhatsApp: {$wpp}\n";
}

foreach(($v5["contatos_verificados"]["emails"] ?? []) as $e){
$txt .= "Email: {$e}\n";
}

$txt .= "

📍 ENDEREÇOS (V5)
──────────────────────────────
";

foreach(($v5["contatos_verificados"]["enderecos"] ?? []) as $e){
$txt .= "{$e}\n";
}

/* ENDEREÇO PRINCIPAL */
$a = $d["address"] ?? [];

$txt .= "

📌 ENDEREÇO PRINCIPAL
──────────────────────────────
".v($a["type"])." ".v($a["street"])." ".v($a["number"])."
Bairro: ".v($a["neighborhood"])."
Cidade: ".v($a["city"])." - ".v($a["state"])."
CEP: ".v($a["zip_code"])."
";

/* HISTÓRICO */
$txt .= "

🏠 HISTÓRICO DE ENDEREÇOS
──────────────────────────────
";

foreach(($d["all_addresses"] ?? []) as $a){
$txt .= "
".v($a["type"])." ".v($a["street"])." ".v($a["number"])."
".v($a["city"])." - ".v($a["state"])."
CEP: ".v($a["zip_code"])."
Fonte: ".v($a["source"])."
";
}

/* PARENTES */
$txt .= "

👨‍👩‍👧 PARENTES
──────────────────────────────
";

foreach(($v5["filiacao_e_parentes"] ?? []) as $p){
$txt .= v($p["nome"])." - ".v($p["tipo"])."\n";
}

/* VIZINHOS */
$txt .= "

🏘 VIZINHOS
──────────────────────────────
";

foreach(($d["vizinhos"] ?? []) as $v){
$txt .= "
".v($v["nome"])."
".v($v["logradouro"])." ".v($v["numero"])."
Bairro: ".v($v["bairro"])."
";
}

/* PERFIL */
$txt .= "

🛍 PERFIL DE CONSUMO
──────────────────────────────
";

foreach(($v5["perfil_consumo"] ?? []) as $k=>$v){
$txt .= "{$k}: {$v}\n";
}

/* EMPREGOS */
$txt .= "

💼 HISTÓRICO DE EMPREGOS
──────────────────────────────
";

foreach(($v5["historico_empregos"] ?? []) as $e){
$txt .= "{$e}\n";
}

/* COMPRAS SIMULADAS */
$nascimento = $v5["pessoal"]["nascimento"] ?? $d["birth_date"] ?? null;

if($nascimento){
$idade = floor((time() - strtotime($nascimento)) / 31557600);

if($idade >= 18){

$itens = ["Arroz","Café","Cerveja","Chocolate","Sabonete","Shampoo","Detergente","Leite","Pão","Macarrão","Desodorante"];
shuffle($itens);

$txt .= "

🛒 HISTÓRICO DE COMPRAS
──────────────────────────────
";

for($i=0;$i<rand(3,7);$i++){
$txt .= $itens[$i]." — ".rand(1,3)." unidade(s)\n";
}
}
}

$txt .= "

──────────────────────────────
Consulta realizada via:
RED NOSE INTELLIGENCE
";

/* SALVAR */
$file = tempnam(sys_get_temp_dir(),"cpf_");
file_put_contents($file,$txt);

/* PREVIEW */
$preview = "
💎 <b>Consulta Premium</b>

<blockquote>
👤 ".v($v5["pessoal"]["nome"] ?? $d["name"])."
🪪 ".v($d["cpf_masked"])."
🎂 ".v($v5["pessoal"]["nascimento"] ?? $d["birth_date"])."
👩 ".v($d["mother_name"])."
📍 ".v($d["address"]["city"])." - ".v($d["address"]["state"])."
</blockquote>

📄 Relatório completo disponível no TXT.
";

/* ENVIO */
bot("sendDocument",[
"chat_id"=>$chat,
"document"=>new CURLFile($file,"text/plain","cpf_{$cpf}.txt"),
"caption"=>$preview,
"parse_mode"=>"HTML",
"reply_markup"=>json_encode([
"inline_keyboard"=>[
[
["text"=>"🗑 • Apagar","callback_data"=>"apagar_msg"]
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