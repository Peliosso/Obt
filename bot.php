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

        // ===== STICKER =====
        bot("sendSticker", [
            "chat_id" => $chat_id,
            "sticker" => "CAACAgIAAxkBAAEC5bppw3KJMTyzWRSHYN3lP1wLT7fn7wACiBEAAjlK-Evax1yQ_ij4FDoE"
        ]);

        // ===== LOADING =====
        $msg = bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "🔎 Consultando CPF...\n⏳ Aguarde..."
        ]);

        $message_id = $msg["result"]["message_id"];

        // ===== CONSULTA API =====
        $res_api = consultarCPF($cpf);

        if (!$res_api || !$res_api["status"]) {
            bot("editMessageText", [
                "chat_id" => $chat_id,
                "message_id" => $message_id,
                "text" => "❌ CPF não encontrado ou erro na API."
            ]);
            return;
        }

        $d = $res_api["dados"];

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

        $txt .= "📞 <b>Telefones</b>\n$telefones\n";
        $txt .= "📧 <b>Emails</b>\n$emails\n\n";

        $txt .= "📍 <b>Endereços</b>\n$enderecos\n";

        $txt .= "👨‍👩‍👧 <b>Parentes</b>\n$parentes\n";

        $txt .= "📊 <b>Perfil de Consumo</b>\n$perfil\n";

        $txt .= "📄 <b>Documentos</b>\n";
        $txt .= "NIS: {$d["documentos"]["nis"]}\n";
        $txt .= "CNS: {$d["documentos"]["cns"]}\n\n";

        $txt .= "💼 <b>Histórico de Empregos</b>\n$empregos";

        // ===== BOTÃO APAGAR =====
        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🗑 Apagar", "callback_data" => "del"]
                ]
            ]
        ];

        // ===== EDITA MENSAGEM =====
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