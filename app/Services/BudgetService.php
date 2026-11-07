<?php
declare(strict_types=1);

final class BudgetService
{
    public static function forMonth(int $userId,string $month): array
    {
        $start=$month.'-01';$date=DateTimeImmutable::createFromFormat('!Y-m-d',$start);if(!$date)return [];$end=$date->modify('last day of this month')->format('Y-m-d');$db=Database::connection();
        $stmt=$db->prepare('SELECT b.*,c.name AS category_name FROM budgets b JOIN categories c ON c.id=b.category_id AND c.user_id=b.user_id WHERE b.user_id=:user AND b.reference_month=:month ORDER BY b.is_active DESC,c.name');$stmt->execute(['user'=>$userId,'month'=>$month]);$budgets=$stmt->fetchAll();
        foreach($budgets as &$budget){
            $stmt=$db->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=:user AND category_id=:category AND type='expense' AND status='paid' AND transaction_date>=:start AND transaction_date<=:end");$stmt->execute(['user'=>$userId,'category'=>$budget['category_id'],'start'=>$start,'end'=>$end]);$cash=(int)$stmt->fetchColumn();
            $stmt=$db->prepare('SELECT COALESCE(SUM(ii.amount),0) FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id AND i.user_id=ii.user_id JOIN card_purchases p ON p.id=ii.purchase_id AND p.user_id=ii.user_id WHERE ii.user_id=:user AND p.category_id=:category AND i.due_date>=:start AND i.due_date<=:end');$stmt->execute(['user'=>$userId,'category'=>$budget['category_id'],'start'=>$start,'end'=>$end]);$spent=$cash+(int)$stmt->fetchColumn();
            $stmt=$db->prepare('SELECT percentage FROM budget_alert_levels WHERE user_id=:user AND budget_id=:budget ORDER BY percentage');$stmt->execute(['user'=>$userId,'budget'=>$budget['id']]);$levels=array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN));
            $limit=(int)$budget['limit_amount'];$budget['spent']=$spent;$budget['remaining']=$limit-$spent;$budget['used_percent']=$limit>0?(int)floor($spent*100/$limit):0;$budget['over_amount']=max(0,$spent-$limit);$budget['alerts']=$levels;$budget['reached_alerts']=array_values(array_filter($levels,fn($level)=>$spent*100 >= $limit*$level));$budget['is_over']=$spent>$limit;
        }
        unset($budget);return $budgets;
    }
}
