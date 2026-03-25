<?php

// ================= CONFIG =================
$token = getenv("BOT_TOKEN");
$api_url = "https://api.telegram.org/bot$token/";

// =========================================

$update = json_decode(file_get_contents("php://input"), true);

// ================= BOT =================
function bot($method, $data = [])
{
    global $api_url;

    $ch = curl_init($api_url . $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    curl_close($ch);

    return json_decode($res, true);
}

// ================= API CPF =================
function consultarCPF($cpf)
{
    $url = "https://astrosearch.rf.gd/api/cpf.php?token=MJJ&cpf=" . urlencode($cpf);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $res = curl_exec($ch);

    if (curl_error($ch)) {
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    return json_decode($res, true);
}

// ================= MESSAGE =================
if (isset($update["message"])) {

    $chat_id = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"];

    // ===== COMANDO CPF =====
if (preg_match('/^\/cpf (.+)/', $text, $match)) {

    $cpf = preg_replace('/[^0-9]/', '', $match[1]);

    // ===== FIGURINHA (LOADING) =====
    bot("sendSticker", [
        "chat_id" => $chat_id,
        "sticker" => "CAACAgIAAxkBAAEC5bppw3KJMTyzWRSHYN3lP1wLT7fn7wACiBEAAjlK-Evax1yQ_ij4FDoE"
    ]);

    // ===== MENSAGEM PEQUENA (EDITÁVEL) =====
    $msg = bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🔎 Consultando CPF..."
    ]);

    $message_id = $msg["result"]["message_id"];

    // ===== CONSULTA =====
    $res_api = consultarCPF($cpf);

    if (!$res_api || !$res_api["status"]) {
        bot("editMessageText", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "text" => "❌ CPF não encontrado"
        ]);
        return;
    }

    $d = $res_api["dados"];

    // ===== RESUMO (NÃO ESTOURA LIMITE) =====
    $txt = "🪪 <b>CPF RESULTADO</b>\n\n";
    $txt .= "👤 {$d["pessoal"]["nome"]}\n";
    $txt .= "📄 {$d["pessoal"]["cpf"]}\n";
    $txt .= "🎂 {$d["pessoal"]["nascimento"]}\n";
    $txt .= "🚻 {$d["pessoal"]["sexo"]}\n";
    $txt .= "📊 {$d["pessoal"]["situacao"]}\n\n";
    $txt .= "💰 Renda: {$d["financeiro"]["renda"]}\n";
    $txt .= "📈 Score: {$d["financeiro"]["score"]}";

    // botão
    $keyboard = [
        "inline_keyboard" => [
            [
                ["text" => "📄 Completo", "callback_data" => "full_$cpf"],
                ["text" => "🗑 Apagar", "callback_data" => "del"]
            ]
        ]
    ];

    // ===== EDITA RESUMO =====
    bot("editMessageText", [
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "text" => $txt,
        "parse_mode" => "HTML",
        "reply_markup" => json_encode($keyboard)
    ]);
}

// ===== VER COMPLETO =====
if (strpos($data, "full_") === 0) {

    $cpf = str_replace("full_", "", $data);

    $res_api = consultarCPF($cpf);

    if (!$res_api || !$res_api["status"]) return;

    $d = $res_api["dados"];

    $txt = print_r($d, true);

    $file = "cpf_$cpf.txt";
    file_put_contents($file, $txt);

    bot("sendDocument", [
        "chat_id" => $chat_id,
        "document" => new CURLFile(realpath($file)),
        "caption" => "📄 Dados completos"
    ]);
}

// ================= CALLBACK =================
if (isset($update["callback_query"])) {

    $callback = $update["callback_query"];
    $chat_id = $callback["message"]["chat"]["id"];
    $message_id = $callback["message"]["message_id"];
    $data = $callback["data"];

    bot("answerCallbackQuery", [
        "callback_query_id" => $callback["id"]
    ]);

    if ($data == "del") {
        bot("deleteMessage", [
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ]);
    }
}