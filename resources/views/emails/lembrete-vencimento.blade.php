<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembrete de vencimento</title>
    <style>
        body {font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; color: #333;}
        .container {max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px 24px;}
        .header {text-align: center; margin-bottom: 28px;}
        .header h1 {font-size: 22px; color: #111; margin: 0;}
        .info-box {background-color: #f9f9f9; border-left: 4px solid #111; border-radius: 4px; padding: 16px 20px; margin: 20px 0;}
        .info-box p {margin: 6px 0; font-size: 15px;}
        .info-box .label {color: #666; font-size: 13px;}
        .info-box .value {font-weight: bold; font-size: 16px;}
        .pix-box {
            background-color: #111;
            color: #fff;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 24px 0;
            text-align: center;
        }
        .pix-box .pix-label {
            font-size: 12px;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .pix-box .pix-key {
            font-size: 18px;
            font-weight: bold;
            margin-top: 6px;
            word-break: break-all;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 28px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Fight House Club</h1>
        </div>

        <p>Olá, <strong>{{ $mensalidade->aluno->nome }}</strong>!</p>

        @if($atrasada)
          <p>Passando para lembrar que sua mensalidade está <strong>em atraso</strong>:</p>
        @else
          <p>Passando para lembrar que sua mensalidade está próxima do vencimento:</p>
        @endif

        <div class="info-box">
            <p>
                <span class="label">Referência</span><br>
                <span class="value">{{ \Carbon\Carbon::parse($mensalidade->mes_referencia)->locale('pt_BR')->isoFormat('MMMM [de] YYYY') }}</span>
            </p>
            <p>
                <span class="label">Vencimento</span><br>
                <span class="value">{{ \Carbon\Carbon::parse($mensalidade->data_vencimento)->locale('pt_BR')->isoFormat('DD [de] MMMM') }}</span>
            </p>
            <p>
                <span class="label">Valor</span><br>
                <span class="value">R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}</span>
            </p>
        </div>

        @if($pixChave)
        <div class="pix-box">
            <div class="pix-label">Chave Pix</div>
            <div class="pix-key">{{ $pixChave }}</div>
        </div>
        @endif

        <p>Qualquer dúvida, fale com a gente!</p>

        <div class="footer">
            Fight House Club &mdash; Este é um email automático.
        </div>
    </div>
</body>
</html>