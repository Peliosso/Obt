<?php

// ================= CONFIG =================
$token = getenv("BOT_TOKEN");
$api = "https://api.telegram.org/bot$token/";

$menu_photo = "https://img.sanishtech.com/u/ae24e1175ddf7d3206536335d7ee414a.jpeg";

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

// ================= CONSULTA CPF (NOVA API) =================
function consultaCPF($cpf)
{
    $url = "https://sara-api.xyz/api/consulta/cpf-v5?code=$cpf&apikey=bigmouth";

    $response = file_get_contents($url);

    if (!$response) {
        return ["erro" => "api_off"];
    }

    $json = json_decode($response, true);

    if (!$json || !isset($json["status"])) {
        return ["erro" => "json"];
    }

    return $json;
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

        // LOADING
        $msg = bot("sendSticker", [
            "chat_id" => $chat_id,
            "sticker" => "CAACAgIAAxkBAAEC5bppw3KJMTyzWRSHYN3lP1wLT7fn7wACiBEAAjlK-Evax1yQ_ij4FDoE"
        ]);

        $sticker_id = $msg["result"]["message_id"];

        $res = consultaCPF($cpf);

        bot("deleteMessage", [
            "chat_id" => $chat_id,
            "message_id" => $sticker_id
        ]);

        if (isset($res["erro"])) {
            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "❌ Erro na API"
            ]);
            exit;
        }

        if (!$res["status"]) {
            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "❌ CPF não encontrado."
            ]);
            exit;
        }

        $r = $res["resultado"];
        $p = $r["pessoal"];
        $f = $r["financeiro"];
        $c = $r["contatos_verificados"];

        // ===== TELEFONES =====
        $telefones = "";
        foreach ($c["telefones"] as $t) {
            $telefones .= "📞 {$t["numero"]} (" . ($t["tem_whatsapp"] ? "WhatsApp" : "Normal") . ")\n";
        }

        // ===== EMAILS =====
        $emails = implode("\n📧 ", $c["emails"]);
        $emails = "📧 " . $emails;

        // ===== ENDEREÇOS =====
        $enderecos = "";
        foreach ($c["enderecos"] as $e) {
            $enderecos .= "🏠 $e\n";
        }

        // ===== PARENTES =====
        $parentes = "";
        foreach ($r["filiacao_e_parentes"] as $par) {
            $parentes .= "👥 {$par["tipo"]}: {$par["nome"]}\n";
        }

        // ===== TEXTO =====
        $txt = "🪪 <b>CONSULTA CPF COMPLETA</b>\n\n";

        $txt .= "👤 <b>Nome:</b> {$p["nome"]}\n";
        $txt .= "📄 <b>CPF:</b> {$p["cpf"]}\n";
        $txt .= "🎂 <b>Nascimento:</b> {$p["nascimento"]}\n";
        $txt .= "⚧ <b>Sexo:</b> {$p["sexo"]}\n";
        $txt .= "📊 <b>Situação:</b> {$p["situacao"]}\n";
        $txt .= "🎓 <b>Escolaridade:</b> {$p["escolaridade"]}\n";
        $txt .= "💼 <b>Profissão:</b> {$p["profissao"]}\n\n";

        $txt .= "💰 <b>Financeiro</b>\n";
        $txt .= "Renda: {$f["renda"]}\n";
        $txt .= "Score: {$f["score"]}\n";
        $txt .= "INSS: {$f["inss"]}\n\n";

        $txt .= "📞 <b>Telefones</b>\n$telefones\n";
        $txt .= "📧 <b>Emails</b>\n$emails\n\n";
        $txt .= "🏠 <b>Endereços</b>\n$enderecos\n";
        $txt .= "👨‍👩‍👧 <b>Parentes</b>\n$parentes";

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

    if ($data == "menu") {
        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "🔴 <b>RED NOSE</b>\n\nEscolha uma opção abaixo.",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode(mainMenu())
        ]);
    }

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

    if ($data == "cpf") {

        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "🪪 <b>CONSULTA CPF</b>\n\nDigite:\n<code>/cpf 00000000000</code>",
            "parse_mode" => "HTML"
        ]);
    }

    if ($data == "delmsg") {
        bot("deleteMessage", [
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ]);
    }

    if ($data == "obt") {
        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "⚙️ <b>OBT</b>\n🚧 Em desenvolvimento...",
            "parse_mode" => "HTML"
        ]);
    }

    if ($data == "dono") {
        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "👑 <b>DONO</b>\nID: oculto",
            "parse_mode" => "HTML"
        ]);
    }
}