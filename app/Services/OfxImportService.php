<?php

namespace App\Services;

/**
 * OfxImportService
 * Faz o parsing de arquivos OFX/OFC/QFX e importa as transações
 * para a tabela contas_movimentacoes com deduplicação por hash.
 */
class OfxImportService
{
    /**
     * Processa um arquivo OFX/OFC e retorna as transações parseadas.
     *
     * @param string $filePath Caminho absoluto do arquivo
     * @return array ['transacoes' => [...], 'conta_info' => [...], 'erros' => [...]]
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['transacoes' => [], 'conta_info' => [], 'erros' => ['Arquivo não encontrado.']];
        }

        $conteudo = file_get_contents($filePath);
        if ($conteudo === false) {
            return ['transacoes' => [], 'conta_info' => [], 'erros' => ['Não foi possível ler o arquivo.']];
        }

        // Detecta se é XML (OFX 2.x) ou SGML (OFX 1.x)
        $conteudo = $this->normalizar($conteudo);

        if (strpos($conteudo, '<?xml') !== false || strpos($conteudo, '<OFX>') !== false) {
            return $this->parseXml($conteudo);
        }

        return $this->parseSgml($conteudo);
    }

    /**
     * Normaliza o conteúdo do arquivo (encoding, quebras de linha).
     */
    private function normalizar(string $conteudo): string
    {
        // Remove BOM UTF-8
        $conteudo = ltrim($conteudo, "\xEF\xBB\xBF");

        // Tenta converter de ISO-8859-1 para UTF-8 se necessário
        if (!mb_detect_encoding($conteudo, 'UTF-8', true)) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'ISO-8859-1');
        }

        // Normaliza quebras de linha
        $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);

        return $conteudo;
    }

    /**
     * Parse OFX 1.x (formato SGML).
     */
    private function parseSgml(string $conteudo): array
    {
        $transacoes = [];
        $contaInfo  = [];
        $erros      = [];

        // Extrai informações da conta
        if (preg_match('/<BANKID>(.*?)\n/', $conteudo, $m))  $contaInfo['banco']  = trim($m[1]);
        if (preg_match('/<ACCTID>(.*?)\n/', $conteudo, $m))  $contaInfo['conta']  = trim($m[1]);
        if (preg_match('/<ACCTTYPE>(.*?)\n/', $conteudo, $m)) $contaInfo['tipo']  = trim($m[1]);
        if (preg_match('/<LEDGERBAL>.*?<BALAMT>(.*?)\n/s', $conteudo, $m)) $contaInfo['saldo'] = (float)str_replace(',', '.', trim($m[1]));

        // Extrai bloco de transações
        if (!preg_match('/<BANKTRANLIST>(.*?)<\/BANKTRANLIST>/s', $conteudo, $blocoMatch)) {
            // Tenta sem fechamento explícito
            if (!preg_match('/<BANKTRANLIST>(.*)/s', $conteudo, $blocoMatch)) {
                return ['transacoes' => [], 'conta_info' => $contaInfo, 'erros' => ['Nenhuma transação encontrada no arquivo.']];
            }
        }

        $bloco = $blocoMatch[1];

        // Extrai cada transação STMTTRN
        preg_match_all('/<STMTTRN>(.*?)(?:<\/STMTTRN>|(?=<STMTTRN>)|$)/s', $bloco, $matches);

        foreach ($matches[1] as $trn) {
            $t = $this->parseSgmlTransacao($trn);
            if ($t) $transacoes[] = $t;
        }

        return ['transacoes' => $transacoes, 'conta_info' => $contaInfo, 'erros' => $erros];
    }

    /**
     * Parse de uma transação SGML individual.
     */
    private function parseSgmlTransacao(string $trn): ?array
    {
        $get = function(string $tag) use ($trn): string {
            if (preg_match("/<{$tag}>(.*?)(?:\n|<\/|$)/s", $trn, $m)) {
                return trim(strip_tags($m[1]));
            }
            return '';
        };

        $trntype = strtoupper($get('TRNTYPE'));
        $dtposted = $get('DTPOSTED');
        $trnamt   = $get('TRNAMT');
        $fitid    = $get('FITID');
        $memo     = $get('MEMO') ?: $get('NAME');

        if (empty($fitid) || empty($trnamt)) return null;

        $valor = (float)str_replace(',', '.', $trnamt);
        $tipo  = $valor >= 0 ? 'credito' : 'debito';

        // Tipos OFX que indicam débito independente do sinal
        $tiposDebito = ['DEBIT', 'CHECK', 'PAYMENT', 'CASH', 'DIRECTDEBIT', 'FEE', 'SRVCHG', 'ATM'];
        if (in_array($trntype, $tiposDebito) && $valor > 0) {
            $tipo  = 'debito';
            $valor = -abs($valor);
        }

        return [
            'fitid'             => $fitid,
            'tipo'              => $tipo,
            'valor'             => abs($valor),
            'data_movimentacao' => $this->parsarData($dtposted),
            'descricao'         => $this->limparDescricao($memo),
            'numero_documento'  => $get('CHECKNUM') ?: $get('REFNUM'),
            'categoria'         => $this->inferirCategoria($memo, $trntype),
            'origem'            => 'ofx',
            'hash_transacao'    => md5($fitid . $trnamt . $dtposted),
        ];
    }

    /**
     * Parse OFX 2.x (formato XML).
     */
    private function parseXml(string $conteudo): array
    {
        $transacoes = [];
        $contaInfo  = [];
        $erros      = [];

        // Remove header OFX antes do XML
        $conteudo = preg_replace('/^.*?<OFX>/s', '<OFX>', $conteudo);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($conteudo);

        if (!$xml) {
            $erros[] = 'Arquivo XML inválido.';
            return ['transacoes' => [], 'conta_info' => [], 'erros' => $erros];
        }

        // Navega para BANKMSGSRSV1 > STMTTRNRS > STMTRS
        $stmtrs = $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS ?? null;
        if (!$stmtrs) {
            $erros[] = 'Estrutura OFX XML não reconhecida.';
            return ['transacoes' => [], 'conta_info' => [], 'erros' => $erros];
        }

        $contaInfo['banco'] = (string)($stmtrs->BANKACCTFROM->BANKID ?? '');
        $contaInfo['conta'] = (string)($stmtrs->BANKACCTFROM->ACCTID ?? '');
        $contaInfo['tipo']  = (string)($stmtrs->BANKACCTFROM->ACCTTYPE ?? '');
        $contaInfo['saldo'] = (float)(string)($stmtrs->LEDGERBAL->BALAMT ?? 0);

        foreach ($stmtrs->BANKTRANLIST->STMTTRN ?? [] as $trn) {
            $trntype  = strtoupper((string)$trn->TRNTYPE);
            $dtposted = (string)$trn->DTPOSTED;
            $trnamt   = (string)$trn->TRNAMT;
            $fitid    = (string)$trn->FITID;
            $memo     = (string)($trn->MEMO ?: $trn->NAME);

            if (empty($fitid)) continue;

            $valor = (float)str_replace(',', '.', $trnamt);
            $tipo  = $valor >= 0 ? 'credito' : 'debito';

            $tiposDebito = ['DEBIT', 'CHECK', 'PAYMENT', 'CASH', 'DIRECTDEBIT', 'FEE', 'SRVCHG', 'ATM'];
            if (in_array($trntype, $tiposDebito) && $valor > 0) {
                $tipo  = 'debito';
                $valor = -abs($valor);
            }

            $transacoes[] = [
                'fitid'             => $fitid,
                'tipo'              => $tipo,
                'valor'             => abs($valor),
                'data_movimentacao' => $this->parsarData($dtposted),
                'descricao'         => $this->limparDescricao($memo),
                'numero_documento'  => (string)($trn->CHECKNUM ?? $trn->REFNUM ?? ''),
                'categoria'         => $this->inferirCategoria($memo, $trntype),
                'origem'            => 'ofx',
                'hash_transacao'    => md5($fitid . $trnamt . $dtposted),
            ];
        }

        return ['transacoes' => $transacoes, 'conta_info' => $contaInfo, 'erros' => $erros];
    }

    /**
     * Parseia data no formato OFX (YYYYMMDDHHMMSS ou YYYYMMDD).
     */
    private function parsarData(string $data): string
    {
        $data = preg_replace('/[^0-9]/', '', substr($data, 0, 8));
        if (strlen($data) === 8) {
            return substr($data, 0, 4) . '-' . substr($data, 4, 2) . '-' . substr($data, 6, 2);
        }
        return date('Y-m-d');
    }

    /**
     * Limpa a descrição da transação.
     */
    private function limparDescricao(string $desc): string
    {
        $desc = preg_replace('/\s+/', ' ', trim($desc));
        $desc = preg_replace('/[^\w\s\-\/\.,;:@#\(\)\[\]áéíóúâêîôûãõàèìòùäëïöüçÁÉÍÓÚÂÊÎÔÛÃÕÀÈÌÒÙÄËÏÖÜÇ]/u', '', $desc);
        return substr($desc, 0, 255);
    }

    /**
     * Infere uma categoria com base na descrição e tipo OFX.
     */
    private function inferirCategoria(string $desc, string $trntype): string
    {
        $desc = strtolower($desc);

        $mapeamentos = [
            'salario'      => ['salario', 'salário', 'folha', 'pagamento rh'],
            'aluguel'      => ['aluguel', 'locacao', 'locação', 'imovel', 'imóvel'],
            'energia'      => ['energia', 'eletricidade', 'cemig', 'copel', 'celpe', 'coelba', 'light'],
            'agua'         => ['agua', 'água', 'saneamento', 'sabesp', 'copasa', 'embasa'],
            'telefone'     => ['telefone', 'celular', 'internet', 'vivo', 'tim', 'claro', 'oi ', 'net '],
            'impostos'     => ['imposto', 'tributo', 'darf', 'das ', 'gps ', 'inss', 'fgts', 'iss', 'icms'],
            'fornecedores' => ['fornecedor', 'compra', 'nf ', 'nota fiscal'],
            'tarifas'      => ['tarifa', 'taxa', 'fee', 'iof', 'cpmf', 'ted ', 'doc ', 'pix '],
            'investimentos'=> ['aplicacao', 'aplicação', 'resgate', 'cdb', 'lci', 'lca', 'fundo'],
        ];

        foreach ($mapeamentos as $categoria => $palavras) {
            foreach ($palavras as $palavra) {
                if (strpos($desc, $palavra) !== false) return ucfirst($categoria);
            }
        }

        // Por tipo OFX
        $tipoMap = [
            'CREDIT'      => 'Receita',
            'DEBIT'       => 'Despesa',
            'INT'         => 'Juros',
            'DIV'         => 'Dividendos',
            'FEE'         => 'Tarifas',
            'SRVCHG'      => 'Tarifas',
            'DEP'         => 'Depósito',
            'ATM'         => 'Saque',
            'POS'         => 'Compra',
            'XFER'        => 'Transferência',
            'CHECK'       => 'Cheque',
            'PAYMENT'     => 'Pagamento',
            'CASH'        => 'Dinheiro',
            'DIRECTDEP'   => 'Depósito Direto',
            'DIRECTDEBIT' => 'Débito Automático',
            'REPEATPMT'   => 'Pagamento Recorrente',
            'OTHER'       => 'Outros',
        ];

        return $tipoMap[$trntype] ?? 'Outros';
    }
}
