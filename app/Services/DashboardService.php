<?php
declare(strict_types=1);

final class DashboardService
{
    private const PRESETS = ['current_month'=>'Este mês','previous_month'=>'Mês anterior','3m'=>'Últimos 3 meses','6m'=>'Últimos 6 meses','year'=>'Este ano','custom'=>'Personalizado'];

    public static function data(int $userId, array $input): array
    {
        $today=new DateTimeImmutable(date('Y-m-d'));
        $period=is_string($input['period']??null)?$input['period']:'current_month';
        if(!isset(self::PRESETS[$period]))$period='current_month';
        $start=match($period){
            'previous_month'=>$today->modify('first day of this month')->modify('-1 month'),
            '3m'=>$today->modify('first day of this month')->modify('-2 months'),
            '6m'=>$today->modify('first day of this month')->modify('-5 months'),
            'year'=>$today->setDate((int)$today->format('Y'),1,1),
            'custom'=>$today,
            default=>$today->modify('first day of this month'),
        };
        $end=$period==='previous_month'?$today->modify('first day of this month')->modify('-1 day'):$today;
        if($period==='custom'){
            $inputFrom=is_string($input['from']??null)?$input['from']:'';$inputTo=is_string($input['to']??null)?$input['to']:'';
            if(FinancialService::validDate($inputFrom)&&FinancialService::validDate($inputTo)){
                $candidateStart=new DateTimeImmutable($inputFrom);$candidateEnd=new DateTimeImmutable($inputTo);
                if($candidateStart<=$candidateEnd&&$candidateStart->diff($candidateEnd)->days<=1095){$start=$candidateStart;$end=$candidateEnd;}
            }
        }
        $effectiveEnd=$end>$today?$today:$end;
        $from=$start->format('Y-m-d');$to=$effectiveEnd->format('Y-m-d');$db=Database::connection();

        $totals=['income'=>0,'expense'=>0];
        if($start<=$effectiveEnd){
            $stmt=$db->prepare("SELECT type,SUM(amount) AS total FROM (SELECT type,amount,transaction_date AS event_date,status FROM transactions WHERE user_id=:user_tx UNION ALL SELECT 'expense',ii.amount,i.due_date,'paid' FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id AND i.user_id=ii.user_id WHERE ii.user_id=:user_card) events WHERE event_date>=:from_date AND event_date<=:to_date AND status IN ('paid','received') GROUP BY type");
            $stmt->execute(['user_tx'=>$userId,'user_card'=>$userId,'from_date'=>$from,'to_date'=>$to]);
            foreach($stmt->fetchAll() as $row)$totals[$row['type']]=(int)$row['total'];
        }

        $pending=['expense'=>0,'income'=>0];
        $stmt=$db->prepare("SELECT type,SUM(amount) AS total FROM transactions WHERE user_id=:user AND COALESCE(due_date,transaction_date)>=:from_date AND COALESCE(due_date,transaction_date)<=:to_date AND status='pending' GROUP BY type");
        $stmt->execute(['user'=>$userId,'from_date'=>$from,'to_date'=>$end->format('Y-m-d')]);
        foreach($stmt->fetchAll() as $row)$pending[$row['type']]=(int)$row['total'];
        $stmt=$db->prepare("SELECT COALESCE(SUM(amount),0) FROM payables WHERE user_id=:user AND due_date>=:from_date AND due_date<=:to_date AND status='pending'");$stmt->execute(['user'=>$userId,'from_date'=>$from,'to_date'=>$end->format('Y-m-d')]);$pending['expense']+=(int)$stmt->fetchColumn();
        $stmt=$db->prepare("SELECT COALESCE(SUM(amount),0) FROM receivables WHERE user_id=:user AND due_date>=:from_date AND due_date<=:to_date AND status='pending'");$stmt->execute(['user'=>$userId,'from_date'=>$from,'to_date'=>$end->format('Y-m-d')]);$pending['income']+=(int)$stmt->fetchColumn();
        $stmt=$db->prepare("SELECT COALESCE(SUM(ii.amount),0) FROM invoices i JOIN invoice_items ii ON ii.invoice_id=i.id AND ii.user_id=i.user_id WHERE i.user_id=:user AND i.due_date>=:from_date AND i.due_date<=:to_date AND i.status<>'paid'");$stmt->execute(['user'=>$userId,'from_date'=>$from,'to_date'=>$end->format('Y-m-d')]);$openInvoices=(int)$stmt->fetchColumn();

        $months=[];$monthStart=$start->modify('first day of this month');$monthEnd=$effectiveEnd->modify('first day of this month');
        if($start<=$effectiveEnd){
            for($month=$monthStart;$month<=$monthEnd;$month=$month->modify('+1 month')){
                $key=$month->format('Y-m');$months[$key]=['label'=>$month->format('m/Y'),'income'=>0,'expense'=>0];
            }
            $stmt=$db->prepare("SELECT substr(event_date,1,7) AS month,type,SUM(amount) AS total FROM (SELECT type,amount,transaction_date AS event_date,status FROM transactions WHERE user_id=:user_tx UNION ALL SELECT 'expense',ii.amount,i.due_date,'paid' FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id AND i.user_id=ii.user_id WHERE ii.user_id=:user_card) events WHERE event_date>=:from_date AND event_date<=:to_date AND status IN ('paid','received') GROUP BY month,type");
            $stmt->execute(['user_tx'=>$userId,'user_card'=>$userId,'from_date'=>$from,'to_date'=>$to]);
            foreach($stmt->fetchAll() as $row)if(isset($months[$row['month']]))$months[$row['month']][$row['type']]=(int)$row['total'];
        }

        $categoryRows=[];
        if($start<=$effectiveEnd){
            $stmt=$db->prepare("SELECT category_name AS name,SUM(amount) AS total FROM (SELECT COALESCE(c.name,'Sem categoria') AS category_name,t.amount,t.transaction_date AS event_date FROM transactions t LEFT JOIN categories c ON c.id=t.category_id AND c.user_id=t.user_id WHERE t.user_id=:user_tx AND t.type='expense' AND t.status='paid' UNION ALL SELECT COALESCE(c.name,'Sem categoria'),ii.amount,i.due_date FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id AND i.user_id=ii.user_id JOIN card_purchases p ON p.id=ii.purchase_id AND p.user_id=ii.user_id LEFT JOIN categories c ON c.id=p.category_id AND c.user_id=p.user_id WHERE ii.user_id=:user_card) expenses WHERE event_date>=:from_date AND event_date<=:to_date GROUP BY category_name ORDER BY total DESC LIMIT 7");
            $stmt->execute(['user_tx'=>$userId,'user_card'=>$userId,'from_date'=>$from,'to_date'=>$to]);$categoryRows=$stmt->fetchAll();
        }

        $weekly=[];
        if($start<=$effectiveEnd){
            $stmt=$db->prepare("SELECT CAST((julianday(event_date)-julianday(:week_start))/7 AS INTEGER)+1 AS week,SUM(amount) AS total FROM (SELECT amount,transaction_date AS event_date FROM transactions WHERE user_id=:user_tx AND type='expense' AND status='paid' UNION ALL SELECT ii.amount,i.due_date FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id AND i.user_id=ii.user_id WHERE ii.user_id=:user_card) expenses WHERE event_date>=:from_date AND event_date<=:to_date GROUP BY week ORDER BY week");
            $stmt->execute(['week_start'=>$from,'user_tx'=>$userId,'user_card'=>$userId,'from_date'=>$from,'to_date'=>$to]);foreach($stmt->fetchAll() as $row)$weekly[(int)$row['week']]=(int)$row['total'];
        }
        $weekCount=$start<=$effectiveEnd?(int)ceil(($start->diff($effectiveEnd)->days+1)/7):0;$weekLabels=[];$weekValues=[];for($week=1;$week<=max(1,$weekCount);$week++){$weekLabels[]='Semana '.$week;$weekValues[]=$weekly[$week]??0;}

        $balances=FinancialService::balances($userId);$totalBalance=array_sum(array_map(fn($a)=>(int)$a['balance'],$balances));
        $balanceLabels=[];$balanceValues=[];
        if($start<=$effectiveEnd){
            $baseDate=$monthStart->format('Y-m-d');$stmt=$db->prepare("SELECT COALESCE((SELECT SUM(a.opening_balance) FROM accounts a WHERE a.user_id=:user_open AND a.opening_date<:date_open),0) + COALESCE((SELECT SUM(CASE WHEN t.type='income' THEN t.amount ELSE -t.amount END) FROM transactions t JOIN accounts a ON a.id=t.account_id AND a.user_id=t.user_id WHERE t.user_id=:user_tx AND t.status IN ('paid','received') AND t.transaction_date>=a.opening_date AND t.transaction_date<:date_tx),0) - COALESCE((SELECT SUM(ip.amount) FROM invoice_payments ip JOIN accounts a ON a.id=ip.account_id AND a.user_id=ip.user_id WHERE ip.user_id=:user_invoice AND substr(ip.paid_at,1,10)>=a.opening_date AND substr(ip.paid_at,1,10)<:date_invoice),0)");
            $stmt->execute(['user_open'=>$userId,'date_open'=>$baseDate,'user_tx'=>$userId,'date_tx'=>$baseDate,'user_invoice'=>$userId,'date_invoice'=>$baseDate]);$runningBalance=(int)$stmt->fetchColumn();
            $stmt=$db->prepare("SELECT substr(event_date,1,7) AS month,SUM(amount) AS total FROM (SELECT a.opening_date AS event_date,a.opening_balance AS amount FROM accounts a WHERE a.user_id=:user_open AND a.opening_date>=:from_open AND a.opening_date<=:to_open UNION ALL SELECT t.transaction_date,CASE WHEN t.type='income' THEN t.amount ELSE -t.amount END FROM transactions t JOIN accounts a ON a.id=t.account_id AND a.user_id=t.user_id WHERE t.user_id=:user_tx AND t.status IN ('paid','received') AND t.transaction_date>=a.opening_date AND t.transaction_date>=:from_tx AND t.transaction_date<=:to_tx UNION ALL SELECT substr(ip.paid_at,1,10),-ip.amount FROM invoice_payments ip JOIN accounts a ON a.id=ip.account_id AND a.user_id=ip.user_id WHERE ip.user_id=:user_invoice AND substr(ip.paid_at,1,10)>=a.opening_date AND substr(ip.paid_at,1,10)>=:from_invoice AND substr(ip.paid_at,1,10)<=:to_invoice) events GROUP BY month");
            $stmt->execute(['user_open'=>$userId,'from_open'=>$baseDate,'to_open'=>$effectiveEnd->format('Y-m-d'),'user_tx'=>$userId,'from_tx'=>$baseDate,'to_tx'=>$effectiveEnd->format('Y-m-d'),'user_invoice'=>$userId,'from_invoice'=>$baseDate,'to_invoice'=>$effectiveEnd->format('Y-m-d')]);$monthlyChanges=[];foreach($stmt->fetchAll() as $event)$monthlyChanges[$event['month']]=(int)$event['total'];
            for($month=$monthStart;$month<=$monthEnd;$month=$month->modify('+1 month')){$runningBalance+=$monthlyChanges[$month->format('Y-m')]??0;$balanceLabels[]=$month->format('m/Y');$balanceValues[]=$runningBalance;}
        }

        $stmt=$db->prepare("SELECT * FROM (SELECT t.id,t.type,t.description,t.amount,t.transaction_date,t.status,a.name AS account_name,c.name AS category_name FROM transactions t JOIN accounts a ON a.id=t.account_id AND a.user_id=t.user_id LEFT JOIN categories c ON c.id=t.category_id AND c.user_id=t.user_id WHERE t.user_id=:user_tx AND t.status<>'cancelled' UNION ALL SELECT ii.id,'expense',p.description,ii.amount,i.due_date,i.status,'Cartão · '||cc.name,cat.name FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id AND i.user_id=ii.user_id JOIN card_purchases p ON p.id=ii.purchase_id AND p.user_id=ii.user_id JOIN credit_cards cc ON cc.id=i.card_id AND cc.user_id=i.user_id LEFT JOIN categories cat ON cat.id=p.category_id AND cat.user_id=p.user_id WHERE ii.user_id=:user_card) activity WHERE transaction_date>=:from_date AND transaction_date<=:to_date AND transaction_date<=:today ORDER BY transaction_date DESC,id DESC LIMIT 6");
        $stmt->execute(['user_tx'=>$userId,'user_card'=>$userId,'from_date'=>$from,'to_date'=>$to,'today'=>$today->format('Y-m-d')]);$recent=$stmt->fetchAll();

        $periodBalance=$balanceValues?end($balanceValues):($start>$effectiveEnd?0:$totalBalance);
        $formFrom=$period==='custom'?(is_string($input['from']??null)?$input['from']:$from):$from;
        $formTo=$period==='custom'?(is_string($input['to']??null)?$input['to']:$end->format('Y-m-d')):$end->format('Y-m-d');
        return ['period'=>$period,'periodLabel'=>self::PRESETS[$period],'from'=>$formFrom,'to'=>$formTo,'effectiveFrom'=>$from,'effectiveTo'=>$to,'totals'=>$totals,'pending'=>$pending,'openInvoices'=>$openInvoices,'balances'=>$balances,'totalBalance'=>$totalBalance,'periodBalance'=>$periodBalance,'months'=>array_values($months),'categoryLabels'=>array_column($categoryRows,'name'),'categoryValues'=>array_map('intval',array_column($categoryRows,'total')),'weekLabels'=>$weekLabels,'weekValues'=>$weekValues,'balanceLabels'=>$balanceLabels,'balanceValues'=>$balanceValues,'recent'=>$recent];
    }
}
