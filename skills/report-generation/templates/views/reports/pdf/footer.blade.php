<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rodapé do Relatório</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #6c757d;
            margin: 0;
            padding: 0;
        }

        .footer {
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            margin-top: 20px;
            text-align: center;
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .footer-left {
            text-align: left;
        }

        .footer-center {
            text-align: center;
        }

        .footer-right {
            text-align: right;
        }

        .page-number {
            font-weight: bold;
            font-size: 11px;
        }

        .company-info {
            font-size: 9px;
            color: #999;
        }

        .disclaimer {
            font-size: 8px;
            color: #999;
            margin-top: 10px;
            line-height: 1.2;
            text-align: justify;
        }

        .footer-border {
            border-top: 1px solid #dee2e6;
            margin-top: 10px;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="footer">
        <div class="footer-info">
            <div class="footer-left">
                <span class="company-info">
                    {{ $company_name ?? 'Easy Budget' }}<br>
                    CNPJ: {{ $company_cnpj ?? '00.000.000/0000-00' }}<br>
                    {{ $company_address ?? 'Endereço não informado' }}
                </span>
            </div>

            <div class="footer-center">
                <span class="page-number">
                    Página {PAGENO} de {nbpg}
                </span>
            </div>

            <div class="footer-right">
                <span class="company-info">
                    Sistema: Easy Budget Laravel<br>
                    Versão: {{ $system_version ?? '1.0.0' }}<br>
                    Gerado em: {{ now()->format('d/m/Y H:i:s') }}
                </span>
            </div>
        </div>

        <div class="footer-border"></div>

        <div class="disclaimer">
            <strong>Aviso Legal:</strong> Este relatório foi gerado automaticamente pelo sistema Easy Budget Laravel.
            As informações aqui contidas são confidenciais e destinam-se exclusivamente ao uso interno da empresa.
            É proibida a reprodução, distribuição ou divulgação total ou parcial deste documento sem autorização prévia.
            O sistema não se responsabiliza por decisões tomadas com base nestas informações.
            Para dúvidas ou esclarecimentos, entre em contato com o suporte técnico.
        </div>
    </div>
</body>
</html>
