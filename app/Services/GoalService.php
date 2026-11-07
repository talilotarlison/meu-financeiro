<?php
declare(strict_types=1);

final class GoalService
{
    public static function all(int $userId): array
    {
        $stmt=Database::connection()->prepare("SELECT g.*,a.name AS account_name,c.name AS category_name,COALESCE(SUM(CASE WHEN gc.direction='contribution' THEN gc.amount ELSE -gc.amount END),0) AS saved FROM goals g LEFT JOIN accounts a ON a.id=g.account_id AND a.user_id=g.user_id LEFT JOIN categories c ON c.id=g.category_id AND c.user_id=g.user_id LEFT JOIN goal_contributions gc ON gc.goal_id=g.id AND gc.user_id=g.user_id WHERE g.user_id=:user GROUP BY g.id ORDER BY CASE g.status WHEN 'active' THEN 0 WHEN 'completed' THEN 1 ELSE 2 END,g.due_date,g.name");$stmt->execute(['user'=>$userId]);$goals=$stmt->fetchAll();
        $today=new DateTimeImmutable('today');foreach($goals as &$goal){$target=(int)$goal['target_amount'];$saved=(int)$goal['saved'];$goal['remaining']=max(0,$target-$saved);$goal['progress']=$target>0?min(100,(int)floor(max(0,$saved)*100/$target)):0;$goal['days_remaining']=$goal['due_date']?(int)$today->diff(new DateTimeImmutable($goal['due_date']))->format('%r%a'):null;}
        unset($goal);return $goals;
    }
}
