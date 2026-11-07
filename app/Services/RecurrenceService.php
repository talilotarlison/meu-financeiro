<?php
declare(strict_types=1);

final class RecurrenceService
{
    public static function generateForUser(int $userId): void
    {
        $db=Database::connection();$today=new DateTimeImmutable(date('Y-m-d'));$horizon=$today->modify('+12 months')->format('Y-m-d');
        $stmt=$db->prepare("SELECT * FROM recurring_transactions WHERE user_id=:user AND status='active' AND next_due_date<=:horizon ORDER BY next_due_date LIMIT 100");
        $stmt->execute(['user'=>$userId,'horizon'=>$horizon]);$rules=$stmt->fetchAll();
        foreach($rules as $rule){
            $db->beginTransaction();
            try{
                $due=new DateTimeImmutable($rule['next_due_date']);$iterations=0;
                while($due->format('Y-m-d')<=$horizon&&(!$rule['end_date']||$due->format('Y-m-d')<=$rule['end_date'])&&$iterations<500){
                    $dueDate=$due->format('Y-m-d');
                    $exists=$db->prepare('SELECT 1 FROM recurrence_occurrences WHERE recurring_id=:rule AND due_date=:due');
                    $exists->execute(['rule'=>$rule['id'],'due'=>$dueDate]);
                    if(!$exists->fetchColumn()){
                        $tx=$db->prepare('INSERT INTO transactions(user_id,account_id,category_id,type,description,amount,transaction_date,due_date,status,payment_method,notes,created_at,updated_at) VALUES(:user,:account,:category,:type,:description,:amount,:date,:due,:status,:method,:notes,:created,:updated)');
                        $now=gmdate('c');$tx->execute(['user'=>$userId,'account'=>$rule['account_id'],'category'=>$rule['category_id'],'type'=>$rule['type'],'description'=>$rule['description'],'amount'=>$rule['amount'],'date'=>$dueDate,'due'=>$dueDate,'status'=>'pending','method'=>'','notes'=>$rule['notes'],'created'=>$now,'updated'=>$now]);
                        $occurrence=$db->prepare('INSERT INTO recurrence_occurrences(user_id,recurring_id,transaction_id,due_date,created_at) VALUES(:user,:rule,:tx,:due,:created)');
                        $occurrence->execute(['user'=>$userId,'rule'=>$rule['id'],'tx'=>$db->lastInsertId(),'due'=>$dueDate,'created'=>$now]);
                    }
                    $due=self::next($due,$rule['frequency'],(int)$rule['interval_value'],(int)substr($rule['start_date'],8,2),(int)substr($rule['start_date'],5,2));$iterations++;
                }
                $next=$due->format('Y-m-d');$state=($rule['end_date']&&$next>$rule['end_date'])?'cancelled':'active';
                $update=$db->prepare('UPDATE recurring_transactions SET next_due_date=:next,status=:status,updated_at=:at WHERE id=:id AND user_id=:user');
                $update->execute(['next'=>$next,'status'=>$state,'at'=>gmdate('c'),'id'=>$rule['id'],'user'=>$userId]);$db->commit();
            }catch(Throwable $error){$db->rollBack();throw $error;}
        }
    }

    public static function next(DateTimeImmutable $date,string $frequency,int $interval,?int $anchorDay=null,?int $anchorMonth=null): DateTimeImmutable
    {
        return match($frequency){
            'daily'=>$date->modify('+'.$interval.' days'),
            'weekly'=>$date->modify('+'.($interval*7).' days'),
            'monthly'=>self::moveMonth($date,$interval,$anchorDay??(int)$date->format('d')),
            'yearly'=>self::moveYear($date,$interval,$anchorDay??(int)$date->format('d'),$anchorMonth??(int)$date->format('m')),
            default=>$date->modify('+'.$interval.' days'),
        };
    }
    private static function moveMonth(DateTimeImmutable $date,int $interval,int $anchorDay): DateTimeImmutable
    {
        $target=$date->modify('first day of this month')->modify('+'.$interval.' months');
        return $target->setDate((int)$target->format('Y'),(int)$target->format('m'),min($anchorDay,(int)$target->format('t')));
    }
    private static function moveYear(DateTimeImmutable $date,int $interval,int $anchorDay,int $anchorMonth): DateTimeImmutable
    {
        $year=(int)$date->format('Y')+$interval;$month=$anchorMonth;$first=new DateTimeImmutable(sprintf('%04d-%02d-01',$year,$month));$day=min($anchorDay,(int)$first->format('t'));
        return $date->setDate($year,$month,$day);
    }
}
