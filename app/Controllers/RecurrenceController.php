<?php
declare(strict_types=1);

final class RecurrenceController
{
    public function index(): void
    {
        $user=require_auth();$uid=(int)$user['id'];RecurrenceService::generateForUser($uid);$db=Database::connection();
        $stmt=$db->prepare('SELECT r.*,a.name AS account_name,c.name AS category_name FROM recurring_transactions r JOIN accounts a ON a.id=r.account_id AND a.user_id=r.user_id LEFT JOIN categories c ON c.id=r.category_id AND c.user_id=r.user_id WHERE r.user_id=:user ORDER BY r.status,r.next_due_date');$stmt->execute(['user'=>$uid]);$rules=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT id,name FROM accounts WHERE user_id=:user AND is_active=1 ORDER BY name');$stmt->execute(['user'=>$uid]);$accounts=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT id,name,type FROM categories WHERE user_id=:user AND is_active=1 ORDER BY type,name');$stmt->execute(['user'=>$uid]);$categories=$stmt->fetchAll();
        render('recurrences/index',['title'=>'Recorrências','rules'=>$rules,'accounts'=>$accounts,'categories'=>$categories,'errors'=>$_SESSION['_errors']??[]]);unset($_SESSION['_errors']);
    }
    public function save(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$type=(string)($_POST['type']??'');$description=trim((string)($_POST['description']??''));$amount=FinancialService::moneyToCents((string)($_POST['amount']??''));$account=(int)($_POST['account_id']??0);$category=(int)($_POST['category_id']??0);$frequency=(string)($_POST['frequency']??'monthly');$interval=max(1,min(365,(int)($_POST['interval_value']??1)));$start=(string)($_POST['start_date']??'');$end=(string)($_POST['end_date']??'');$notes=trim((string)($_POST['notes']??''));$errors=[];
        if(!in_array($type,['income','expense'],true))$errors[]='Selecione receita ou despesa.';if(mb_strlen($description)<2||mb_strlen($description)>140)$errors[]='Informe uma descrição válida.';if($amount===null||$amount<=0)$errors[]='Informe um valor maior que zero.';if(!in_array($frequency,['daily','weekly','monthly','yearly','custom'],true))$errors[]='Frequência inválida.';if(!FinancialService::validDate($start))$errors[]='Informe uma data inicial válida.';if($end!==''&&!FinancialService::validDate($end))$errors[]='Informe uma data final válida.';if($end!==''&&$end<$start)$errors[]='A data final deve ser posterior à data inicial.';
        $stmt=$db->prepare('SELECT id FROM accounts WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$account,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='Selecione uma conta ativa.';
        if($category){$stmt=$db->prepare('SELECT id FROM categories WHERE id=:id AND user_id=:user AND type=:type AND is_active=1');$stmt->execute(['id'=>$category,'user'=>$uid,'type'=>$type]);if(!$stmt->fetchColumn())$errors[]='Selecione uma categoria válida para o tipo escolhido.';}else $category=null;
        if($errors){$_SESSION['_errors']=$errors;redirect('/recurrences');}
        $stmt=$db->prepare('INSERT INTO recurring_transactions(user_id,account_id,category_id,type,description,amount,frequency,interval_value,start_date,next_due_date,end_date,status,notes,created_at,updated_at) VALUES(:user,:account,:category,:type,:description,:amount,:frequency,:interval,:start,:next,:end,:status,:notes,:created,:updated)');$now=gmdate('c');$stmt->execute(['user'=>$uid,'account'=>$account,'category'=>$category,'type'=>$type,'description'=>$description,'amount'=>$amount,'frequency'=>$frequency,'interval'=>$interval,'start'=>$start,'next'=>$start,'end'=>$end?:null,'status'=>'active','notes'=>$notes,'created'=>$now,'updated'=>$now]);RecurrenceService::generateForUser($uid);redirect('/recurrences');
    }
    public function cancel(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$id=(int)($_POST['id']??0);$uid=(int)$user['id'];
        $db->beginTransaction();try{$stmt=$db->prepare("UPDATE recurring_transactions SET status='cancelled',updated_at=:at WHERE id=:id AND user_id=:user AND status='active'");$stmt->execute(['at'=>gmdate('c'),'id'=>$id,'user'=>$uid]);$stmt=$db->prepare("UPDATE transactions SET status='cancelled',updated_at=:at WHERE user_id=:user AND status='pending' AND id IN (SELECT ro.transaction_id FROM recurrence_occurrences ro WHERE ro.user_id=:occ_user AND ro.recurring_id=:rule AND ro.due_date>=:today)");$stmt->execute(['at'=>gmdate('c'),'user'=>$uid,'occ_user'=>$uid,'rule'=>$id,'today'=>date('Y-m-d')]);$db->commit();}catch(Throwable $error){$db->rollBack();throw $error;}redirect('/recurrences');
    }
}
