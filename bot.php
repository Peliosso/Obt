<?php

// ================= CONFIG =================
$token = getenv("BOT_TOKEN");
$api = "https://api.telegram.org/bot$token/";

// imagem do menu (troca se quiser)
$menu_photo = "https://i.imgur.com/8Km9tLL.jpg";

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

    if ($text == "/menu") {

        bot("sendPhoto", [
            "chat_id" => $chat_id,
            "photo" => $menu_photo,
            "caption" => "🔴 <b>RED NOSE</b>\n\nEscolha uma opção abaixo.",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode(mainMenu())
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
                    ["text" => "📄 Nome", "callback_data" => "nome"],
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
            "caption" => "🔎 <b>CONSULTAS</b>\n\nSelecione o tipo:",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode($keyboard)
        ]);
    }

    // ===== NOME =====
    if ($data == "nome") {

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🔙 Voltar", "callback_data" => "consultas"]
                ]
            ]
        ];

        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "📄 <b>CONSULTA NOME</b>\n\nModo de uso:\nEnvie o nome completo após ativação da API.",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode($keyboard)
        ]);
    }

    // ===== CPF =====
    if ($data == "cpf") {

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🔙 Voltar", "callback_data" => "consultas"]
                ]
            ]
        ];

        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "🪪 <b>CONSULTA CPF</b>\n\nModo de uso:\nEnvie o CPF após ativação da API.",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode($keyboard)
        ]);
    }

    // ===== OBT =====
    if ($data == "obt") {

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🔙 Voltar", "callback_data" => "menu"]
                ]
            ]
        ];

        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "⚙️ <b>OBT</b>\n\n🚧 Em desenvolvimento...",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode($keyboard)
        ]);
    }

    // ===== DONO =====
    if ($data == "dono") {

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🔙 Voltar", "callback_data" => "menu"]
                ]
            ]
        ];

        bot("editMessageCaption", [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "caption" => "👑 <b>DONO</b>\n\nID: 7909126***\nName: ,✨",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode($keyboard)
        ]);
    }
}
