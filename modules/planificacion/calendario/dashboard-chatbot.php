1<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>if(window.top!==window.self){window.top.location.href="../../../login.php";}else{window.location.href="../../../login.php";}</script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente - Planificación</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <style>
        :root {
            --chat-bg: #f0f4f8;
            --bot-bubble: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            --user-bubble: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            --sidebar-bg: #fff;
            --input-border: #cbd5e1;
            --faq-hover: #e0e7ff;
        }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: var(--chat-bg); min-height: 100vh; }
        .chat-layout { display: grid; grid-template-columns: 280px 1fr; gap: 0; min-height: 100vh; }
        @media (max-width: 768px) {
            .chat-layout { grid-template-columns: 1fr; }
            .faq-sidebar { display: none; }
            .faq-toggle { display: block !important; }
        }
        .faq-sidebar {
            background: var(--sidebar-bg);
            border-right: 1px solid #e2e8f0;
            padding: 1.25rem;
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0,0,0,0.04);
        }
        .faq-sidebar h2 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .faq-sidebar h2 i { color: #2563eb; }
        .faq-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .faq-item {
            padding: 0.65rem 0.85rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
            text-align: left;
        }
        .faq-item:hover {
            background: var(--faq-hover);
            border-color: #a5b4fc;
            color: #3730a3;
        }
        .faq-item i { margin-right: 0.4rem; color: #6366f1; font-size: 0.75rem; }
        .chat-zone { display: flex; flex-direction: column; background: var(--chat-bg); }
        .chat-header {
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #fff;
            border-radius: 0 0 1rem 1rem;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.25);
        }
        .chat-header h1 { font-size: 1.1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
        .chat-header p { font-size: 0.8rem; opacity: 0.9; margin: 0.35rem 0 0 0; }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .chat-msg { display: flex; gap: 0.75rem; max-width: 85%; animation: msgIn 0.3s ease; }
        @keyframes msgIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .chat-msg.user { align-self: flex-end; flex-direction: row-reverse; }
        .chat-msg .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #fff;
            font-size: 0.9rem;
        }
        .chat-msg.bot .avatar { background: linear-gradient(135deg, #1e3a8a, #2563eb); }
        .chat-msg.user .avatar { background: linear-gradient(135deg, #0f766e, #14b8a6); }
        .chat-msg .bubble {
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            font-size: 0.9rem;
            line-height: 1.45;
        }
        .chat-msg.bot .bubble {
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #334155;
            border-bottom-left-radius: 0.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .chat-msg.user .bubble {
            background: var(--user-bubble);
            color: #fff;
            border-bottom-right-radius: 0.25rem;
        }
        .chat-msg .bubble .link-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            background: #2563eb;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }
        .chat-msg .bubble .link-btn:hover { background: #1d4ed8; transform: translateY(-1px); }
        .chat-msg .bubble .link-btn i { font-size: 0.8rem; }
        .chat-input-wrap {
            padding: 1rem 1.25rem;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            border-radius: 1rem 1rem 0 0;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
        }
        .chat-input-inner { display: flex; gap: 0.5rem; align-items: center; }
        .chat-input-inner input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid var(--input-border);
            border-radius: 1rem;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .chat-input-inner input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .chat-input-inner button {
            padding: 0.75rem 1.25rem;
            border-radius: 1rem;
            border: none;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: transform 0.1s, box-shadow 0.2s;
        }
        .chat-input-inner button:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35); }
        .chat-input-inner button:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        .typing { color: #64748b; font-size: 0.85rem; font-style: italic; }
        .faq-toggle { display: none; position: fixed; bottom: 5rem; left: 1rem; z-index: 10; width: 44px; height: 44px; border-radius: 50%; background: #2563eb; color: #fff; border: none; cursor: pointer; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4); }
    </style>
</head>
<body>
    <div class="chat-layout">
        <aside class="faq-sidebar">
            <h2><i class="fas fa-question-circle"></i> Preguntas frecuentes</h2>
            <div class="faq-list" id="faqList">
                <button type="button" class="faq-item" data-q="¿Qué puedo preguntar?"><i class="fas fa-comment-dots"></i> ¿Qué puedo preguntar?</button>
                <button type="button" class="faq-item" data-q="¿Cómo veo el calendario?"><i class="fas fa-calendar-alt"></i> ¿Cómo veo el calendario?</button>
                <button type="button" class="faq-item" data-q="¿Dónde veo necropsias?"><i class="fas fa-feather-pointed"></i> ¿Dónde veo necropsias?</button>
                <button type="button" class="faq-item" data-q="Última semana"><i class="fas fa-calendar-week"></i> Última semana</button>
                <button type="button" class="faq-item" data-q="Comparativo"><i class="fas fa-balance-scale"></i> Comparativo</button>
                <button type="button" class="faq-item" data-q="Ayuda"><i class="fas fa-info-circle"></i> Ayuda</button>
            </div>
        </aside>
        <div class="chat-zone">
            <header class="chat-header">
                <h1><i class="fas fa-robot"></i> Asistente de planificación</h1>
                <p>Escribe en lenguaje natural: fechas, última semana, último mes o entre dos fechas. Te dirijo al calendario o al reporte de necropsias.</p>
            </header>
            <div class="chat-messages" id="chatMessages">
                <div class="chat-msg bot">
                    <div class="avatar"><i class="fas fa-robot"></i></div>
                    <div class="bubble">
                        Hola. Puedes pedirme el <strong>calendario</strong> o el <strong>comparativo de necropsias</strong> para un periodo. Por ejemplo: "calendario última semana", "necropsias último mes" o "entre el 1 y el 15 de enero". También usa las preguntas frecuentes a la izquierda.
                    </div>
                </div>
            </div>
            <div class="chat-input-wrap">
                <div class="chat-input-inner">
                    <input type="text" id="inputMensaje" placeholder="Escribe tu consulta (ej: calendario última semana)..." autocomplete="off">
                    <button type="button" id="btnEnviar"><i class="fas fa-paper-plane"></i> Enviar</button>
                </div>
            </div>
        </div>
    </div>
    <button type="button" class="faq-toggle" id="faqToggle" title="Ver preguntas frecuentes"><i class="fas fa-question"></i></button>

    <script>
    (function() {
        var apiUrl = 'api/chatbot_endpoint.php';
        var messagesEl = document.getElementById('chatMessages');
        var inputEl = document.getElementById('inputMensaje');
        var btnEnviar = document.getElementById('btnEnviar');

        function addMessage(text, isUser, url, label) {
            var wrap = document.createElement('div');
            wrap.className = 'chat-msg ' + (isUser ? 'user' : 'bot');
            var avatar = document.createElement('div');
            avatar.className = 'avatar';
            avatar.innerHTML = isUser ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
            var bubble = document.createElement('div');
            bubble.className = 'bubble';
            bubble.textContent = text;
            if (url && label) {
                var link = document.createElement('a');
                link.href = url;
                link.className = 'link-btn';
                link.target = '_blank';
                link.rel = 'noopener';
                link.innerHTML = '<i class="fas fa-external-link-alt"></i> ' + label;
                bubble.appendChild(document.createElement('br'));
                bubble.appendChild(link);
            }
            wrap.appendChild(avatar);
            wrap.appendChild(bubble);
            messagesEl.appendChild(wrap);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function setTyping(show) {
            var existing = document.getElementById('typingIndicator');
            if (show && !existing) {
                var div = document.createElement('div');
                div.id = 'typingIndicator';
                div.className = 'chat-msg bot';
                div.innerHTML = '<div class="avatar"><i class="fas fa-robot"></i></div><div class="bubble typing">Pensando...</div>';
                messagesEl.appendChild(div);
                messagesEl.scrollTop = messagesEl.scrollHeight;
            } else if (!show && existing) {
                existing.remove();
            }
        }

        function send() {
            var text = (inputEl.value || '').trim();
            if (!text) return;
            inputEl.value = '';
            btnEnviar.disabled = true;
            addMessage(text, true);
            setTyping(true);

            fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mensaje: text })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setTyping(false);
                if (data.ok) {
                    addMessage(data.mensaje, false, data.url || null, data.label || null);
                } else {
                    addMessage(data.error || 'Error al procesar la consulta.', false);
                }
            })
            .catch(function() {
                setTyping(false);
                addMessage('No se pudo conectar con el asistente. Comprueba que Ollama esté en ejecución.', false);
            })
            .finally(function() {
                btnEnviar.disabled = false;
                inputEl.focus();
            });
        }

        btnEnviar.addEventListener('click', send);
        inputEl.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                send();
            }
        });

        document.querySelectorAll('.faq-item').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var q = this.getAttribute('data-q');
                if (q) {
                    inputEl.value = q;
                    send();
                }
            });
        });
    })();
    </script>
</body>
</html>
