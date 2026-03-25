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
    $res = file_get_contents($url);
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

        $msg = bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "🔎 Consultando CPF...\n⏳ Aguarde...",
            "parse_mode" => "HTML"
        ]);

        $message_id = $msg["result"]["message_id"];

        $api = consultarCPF($cpf);

        if (!$api || !$api["status"]) {
            bot("editMessageText", [
                "chat_id" => $chat_id,
                "message_id" => $message_id,
                "text" => "❌ CPF não encontrado."
            ]);
            return;
        }

        $d = $api["dados"];

        // ===== FORMATAR TELEFONES =====
        $telefones = "";
        foreach ($d["contatos_verificados"]["telefones"] as $t) {
            $telefones .= "📞 {$t["numero"]}";
            $telefones .= $t["tem_whatsapp"] ? " (WhatsApp)\n" : "\n";
        }

        // ===== EMAILS =====
        $emails = implode("\n", $d["contatos_verificados"]["emails"]);

        // ===== ENDEREÇOS =====
        $enderecos = "";
        foreach ($d["contatos_verificados"]["enderecos"] as $e) {
            $enderecos .= "📍 $e\n";
        }

        // ===== PARENTES =====
        $parentes = "";
        foreach ($d["filiacao_e_parentes"] as $p) {
            $parentes .= "{$p["tipo"]}: {$p["nome"]}\n";
        }

        // ===== PERFIL =====
        $perfil = "";
        foreach ($d["perfil_consumo"] as $k => $v) {
            $perfil .= "$k: $v\n";
        }

        // ===== EMPREGOS =====
        $empregos = implode("\n", $d["historico_empregos"]);

        // ===== TEXTO FINAL =====
        $txt = "🪪 <b>CPF COMPLETO</b>\n\n";

        $txt .= "👤 <b>Pessoal</b>\n";
        $txt .= "Nome: {$d["pessoal"]["nome"]}\n";
        $txt .= "CPF: {$d["pessoal"]["cpf"]}\n";
        $txt .= "Nascimento: {$d["pessoal"]["nascimento"]}\n";
        $txt .= "Sexo: {$d["pessoal"]["sexo"]}\n";
        $txt .= "Raça: {$d["pessoal"]["raca"]}\n";
        $txt .= "Escolaridade: {$d["pessoal"]["escolaridade"]}\n";
        $txt .= "Profissão: {$d["pessoal"]["profissao"]}\n";
        $txt .= "Situação: {$d["pessoal"]["situacao"]}\n\n";

        $txt .= "💰 <b>Financeiro</b>\n";
        $txt .= "Renda: {$d["financeiro"]["renda"]}\n";
        $txt .= "Score: {$d["financeiro"]["score"]}\n";
        $txt .= "INSS: {$d["financeiro"]["inss"]}\n\n";

        $txt .= "📞 <b>Contatos</b>\n$telefones\n";
        $txt .= "📧 Emails:\n$emails\n\n";

        $txt .= "📍 <b>Endereços</b>\n$enderecos\n";

        $txt .= "👨‍👩‍👧 <b>Parentes</b>\n$parentes\n";

        $txt .= "📊 <b>Perfil</b>\n$perfil\n";

        $txt .= "📄 <b>Documentos</b>\n";
        $txt .= "NIS: {$d["documentos"]["nis"]}\n";
        $txt .= "CNS: {$d["documentos"]["cns"]}\n\n";

        $txt .= "💼 <b>Empregos</b>\n$empregos";

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