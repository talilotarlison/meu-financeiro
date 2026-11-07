<?php
declare(strict_types=1);

final class InvestmentService
{
    public const TYPES=['tesouro'=>'Tesouro Direto','cdb'=>'CDB','lci_lca'=>'LCI e LCA','savings'=>'Poupança','stock'=>'Ações','reit'=>'Fundos imobiliários','etf'=>'ETFs','fund'=>'Fundos de investimento','crypto'=>'Criptomoedas','pension'=>'Previdência privada','other'=>'Outros'];
    public static function all(int $userId): array
    {
        $stmt=Database::connection()->prepare('SELECT i.*,i.invested_amount AS cost_basis FROM investments i WHERE i.user_id=:user ORDER BY i.is_active DESC,i.name');$stmt->execute(['user'=>$userId]);$items=$stmt->fetchAll();foreach($items as &$item){$item['gain']=(int)$item['market_value']-(int)$item['cost_basis'];$item['return_percent']=(int)$item['cost_basis']>0?round($item['gain']*100/(int)$item['cost_basis'],2):0;}unset($item);return $items;
    }
}
