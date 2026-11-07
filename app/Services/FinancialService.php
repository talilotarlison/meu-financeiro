<?php
declare(strict_types=1);

final class FinancialService
{
    public static function balances(int $userId): array
    {
        $today=date('Y-m-d');
        $sql = <<<'SQL'
SELECT a.id,a.name,a.type,a.institution,a.opening_balance,a.opening_date,a.color,a.is_active,
       CASE WHEN a.opening_date<=:today_open THEN a.opening_balance ELSE 0 END
       + COALESCE((SELECT SUM(CASE WHEN t.type='income' THEN t.amount ELSE -t.amount END)
                   FROM transactions t WHERE t.user_id=a.user_id AND t.account_id=a.id
                     AND t.status IN ('paid','received') AND t.transaction_date >= a.opening_date AND t.transaction_date <= :today_tx),0)
       + COALESCE((SELECT SUM(tr.amount) FROM transfers tr WHERE tr.user_id=a.user_id
                   AND tr.destination_account_id=a.id AND tr.transfer_date >= a.opening_date AND tr.transfer_date <= :today_in),0)
       - COALESCE((SELECT SUM(tr.amount) FROM transfers tr WHERE tr.user_id=a.user_id
                   AND tr.source_account_id=a.id AND tr.transfer_date >= a.opening_date AND tr.transfer_date <= :today_out),0)
       - COALESCE((SELECT SUM(ip.amount) FROM invoice_payments ip WHERE ip.user_id=a.user_id
                   AND ip.account_id=a.id AND substr(ip.paid_at,1,10) >= a.opening_date AND substr(ip.paid_at,1,10) <= :today_invoice),0) AS balance
FROM accounts a WHERE a.user_id=:user_id
SQL;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['user_id'=>$userId,'today_open'=>$today,'today_tx'=>$today,'today_in'=>$today,'today_out'=>$today,'today_invoice'=>$today]);
        return $stmt->fetchAll();
    }

    public static function moneyToCents(string $input): ?int
    {
        $value = trim(str_replace(['R$', "\u{00a0}", ' '], '', $input));
        if ($value === '' || !preg_match('/^(?:\d{1,3}(?:\.\d{3})*|\d+)?(?:,\d{1,2})?$/', $value)) return null;
        $value = str_replace('.', '', $value);
        $parts = explode(',', $value, 2);
        $whole = $parts[0] === '' ? 0 : (int) $parts[0];
        $fraction = str_pad($parts[1] ?? '', 2, '0');
        if ($whole > intdiv(PHP_INT_MAX - (int) $fraction, 100)) return null;
        return $whole * 100 + (int) $fraction;
    }

    public static function formatMoney(int $cents): string
    {
        return 'R$ ' . number_format($cents / 100, 2, ',', '.');
    }

    public static function validDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
