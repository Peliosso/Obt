<?php

// ================= CONFIG =================
$token = getenv("BOT_TOKEN");
$api = "https://api.telegram.org/bot$token/";
$menu_photo = "https://kommodo.ai/i/Ee5pfcnMEvTwfYgNBx4B";

// =========================================

$update = json_decode(file_get_contents("php://input"), true);

function bot($method, $data = [])
{
    global $api;
    $ch = curl_init($api . $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// ================= API =================
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

// ================= MENU =================
function mainMenu()
{
    return [
        "inline_keyboard" => [
            [
                ["text" => "🔎 Consultas", "callback_data" => "consultas"]
            ],
            [
                ["text" => "⚙️ OBT", "callback_data" => "obt"]
            ],
            [
                ["text" => "👑 Dono", "callback_data" => "dono"]
            ]
        ]
    ];
}

// ================= MESSAGE =================
if (isset($update["message"])) {

    $chat_id = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"];

    // ===== MENU =====
    if ($text == "/menu") {

        bot("sendPhoto", [
            "chat_id" => $chat_id,
            "photo" => $menu_photo,
            "caption" => "🔴 <b>RED NOSE</b>\n\nEscolha uma opção abaixo.",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode(mainMenu())
        ]);
    }

    // ===== CPF =====
    if (preg_match('/^\/cpf (.+)/', $text, $match)) {

    $cpf = preg_replace('/[^0-9]/', '', $match[1]);

    // envia sticker
    bot("sendSticker", [
        "chat_id" => $chat_id,
        "sticker" => "CAACAgIAAxkBAAEC5bppw3KJMTyzWRSHYN3lP1wLT7fn7wACiBEAAjlK-Evax1yQ_ij4FDoE"
    ]);

    // mensagem loading
    $msg = bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🔎 Consultando CPF...\n⏳ Aguarde..."
    ]);

    $message_id = $msg["result"]["message_id"];

    $api = consultarCPF($cpf);

    // DEBUG (IMPORTANTE)
    if (!$api) {
        bot("editMessageText", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "text" => "❌ ERRO NA API (sem resposta)"
        ]);
        return;
    }

    if (!$api["status"]) {
        bot("editMessageText", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "text" => "❌ CPF não encontrado"
        ]);
        return;
    }

    $d = $api["dados"];

    $txt = "🪪 <b>CPF</b>\n\n";
    $txt .= "👤 {$d["pessoal"]["nome"]}\n";
    $txt .= "📄 {$d["pessoal"]["cpf"]}\n";
    $txt .= "🎂 {$d["pessoal"]["nascimento"]}\n\n";

    // botão apagar
    $keyboard = [
        "inline_keyboard" => [
            [
                ["text" => "🗑 Apagar", "callback_data" => "del"]
            ]
        ]
    ];

    bot("editMessageText", [
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "text" => $txt,
        "parse_mode" => "HTML",
        "reply_markup" => json_encode($keyboard)
    ]);
}

// ================= CALLBACK =================
if (isset($update["callback_query"])) {

    $callback = $update["callback_query"];
    $data = $callback["data"];
    $chat_id = $callback["message"]["chat"]["id"];
    $message_id = $callback["message"]["message_id"];

    bot("answerCallbackQuery", [
        "callback_query_id" => $callback["id"]
    ]);

    // ===== APAGAR =====
    if ($data == "del") {
        bot("deleteMessage", [
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ]);
    }

    // ===== MENU =====
    if ($data == "menu") {
        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "🔴 <b>RED NOSE</b>\n\nEscolha uma opção abaixo.",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode(mainMenu())
        ]);
    }
}