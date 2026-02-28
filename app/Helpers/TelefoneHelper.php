<?php
namespace App\Helpers;

/**
 * TelefoneHelper
 *
 * Normalização de telefones brasileiros para integração (ex: WhatsApp).
 * - Remove caracteres não numéricos
 * - Remove DDI 55 (Brasil), quando presente
 * - Gera variações com e sem o dígito 9 após o DDD
 *
 * A função é propositalmente "burra e segura": não tenta inferir DDD quando não existe.
 */
final class TelefoneHelper
{
    /**
     * normalizarTelefone
     *
     * Entrada comum: "+5531992746755"
     * Saída: ["31992746755", "3192746755"]
     *
     * @return string[] Variações únicas (10/11 dígitos), sem DDI
     */
    public static function normalizarTelefone(string $telefone): array
    {
        $digits = preg_replace('/\D/', '', $telefone);
        if ($digits === null) {
            return [];
        }

        // Remove DDI 55 se presente (somente quando fizer sentido existir DDI)
        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        // Celulares brasileiros (DDD + número): 10 (sem 9) ou 11 (com 9) dígitos
        if (strlen($digits) !== 10 && strlen($digits) !== 11) {
            return [];
        }

        $ddd   = substr($digits, 0, 2);
        $local = substr($digits, 2); // 8 ou 9 dígitos

        $variants = [];

        // Sempre inclui a versão "como está"
        $variants[] = $digits;

        // Gera a variação com/sem o 9 após o DDD
        if (strlen($digits) === 11) {
            // Ex: 31 + 9XXXXXXXX -> remove o primeiro 9 para buscar registros antigos (10 dígitos)
            if (strlen($local) === 9 && str_starts_with($local, '9')) {
                $variants[] = $ddd . substr($local, 1);
            }
        } else { // 10 dígitos
            // Ex: 31 + XXXXXXXX -> adiciona 9 para buscar registros novos (11 dígitos)
            $variants[] = $ddd . '9' . $local;
        }

        // Remove duplicatas preservando a ordem
        $unique = [];
        foreach ($variants as $v) {
            if (!in_array($v, $unique, true)) {
                $unique[] = $v;
            }
        }

        return $unique;
    }
}

