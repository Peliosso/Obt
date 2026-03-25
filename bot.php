<?php

// ================= CONFIG =================
$token = getenv("BOT_TOKEN");
$api = "https://api.telegram.org/bot$token/";

$menu_photo = "https://kommodo.ai/i/Ee5pfcnMEvTwfYgNBx4B";

// =========================================

$update = json_decode(file_get_contents("php://input"), true);

// ================= FUNÇÃO BOT =================
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

// ================= CONSULTA CPF =================
function consultaCPF($cpf)
{
    $url = "https://astrosearch.rf.gd/api/cpf.php?token=MJJ&cpf=$cpf";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if (!$response) return false;

    return json_decode($response, true);
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
    $text = trim($update["message"]["text"] ?? "");

    // ===== MENU =====
    if ($text == "/start" || $text == "/menu") {

        bot("sendPhoto", [
            "chat_id" => $chat_id,
            "photo" => $menu_photo,
            "caption" => "🔴 <b>RED NOSE</b>\n\nEscolha uma opção abaixo.",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode(mainMenu())
        ]);
    }

    // ===== COMANDO CPF =====
    if (preg_match('/^\/cpf\s+(.+)/', $text, $m)) {

        $cpf = preg_replace('/[^0-9]/', '', $m[1]);

        if (strlen($cpf) != 11) {
            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "❌ CPF inválido."
            ]);
            exit;
        }

        // 1️⃣ STICKER LOADING
        $msg = bot("sendSticker", [
            "chat_id" => $chat_id,
            "sticker" => "CAACAgIAAxkBAAEC5bppw3KJMTyzWRSHYN3lP1wLT7fn7wACiBEAAjlK-Evax1yQ_ij4FDoE"
        ]);

        $sticker_id = $msg["result"]["message_id"];

        // 2️⃣ CONSULTA
        $res = consultaCPF($cpf);

        // 3️⃣ APAGA STICKER
        bot("deleteMessage", [
            "chat_id" => $chat_id,
            "message_id" => $sticker_id
        ]);

        // 4️⃣ ERROS
        if (!$res) {
            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "❌ API offline."
            ]);
            exit;
        }

        if (!isset($res["status"]) || !$res["status"]) {
            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "❌ CPF não encontrado."
            ]);
            exit;
        }

        $p = $res["dados"]["pessoal"];
        $f = $res["dados"]["financeiro"];

        // 5️⃣ RESPOSTA
        $txt = "🪪 <b>CONSULTA CPF</b>\n\n";
        $txt .= "👤 <b>Nome:</b> {$p["nome"]}\n";
        $txt .= "📄 <b>CPF:</b> {$p["cpf"]}\n";
        $txt .= "🎂 <b>Nascimento:</b> {$p["nascimento"]}\n";
        $txt .= "⚧ <b>Sexo:</b> {$p["sexo"]}\n";
        $txt .= "📊 <b>Situação:</b> {$p["situacao"]}\n\n";
        $txt .= "💰 <b>Renda:</b> {$f["renda"]}\n";
        $txt .= "📈 <b>Score:</b> {$f["score"]}";

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🗑 Apagar", "callback_data" => "delmsg"]
                ]
            ]
        ];

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => $txt,
            "parse_mode" => "HTML",
            "reply_markup" => json_encode($keyboard)
        ]);
    }
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

    // ===== CONSULTAS =====
    if ($data == "consultas") {

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🪪 CPF", "callback_data" => "cpf"]
                ],
                [
                    ["text" => "🔙 Voltar", "callback_data" => "menu"]
                ]
            ]
        ];

        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "🔎 <b>CONSULTAS</b>\n\nUse:\n<code>/cpf 00000000000</code>",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode($keyboard)
        ]);
    }

    // ===== CPF INFO =====
    if ($data == "cpf") {

        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "🪪 <b>CONSULTA CPF</b>\n\nDigite:\n<code>/cpf 00000000000</code>",
            "parse_mode" => "HTML"
        ]);
    }

    // ===== APAGAR =====
    if ($data == "delmsg") {
        bot("deleteMessage", [
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ]);
    }

    // ===== OBT =====
    if ($data == "obt") {
        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "⚙️ <b>OBT</b>\n\n🚧 Em desenvolvimento...",
            "parse_mode" => "HTML"
        ]);
    }

    // ===== DONO =====
    if ($data == "dono") {
        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "👑 <b>DONO</b>\n\nID: 7909126***",
            "parse_mode" => "HTML"
        ]);
    }
}