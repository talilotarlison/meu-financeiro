<?php
declare(strict_types=1);

final class CommitmentController
{
    public function index(): void
    {
        $user=require_auth();$uid=(int)$user['id'];RecurrenceService::generateForUser($uid);$db=Database::connection();
        $stmt=$db->prepare('SELECT p.*,a.name AS account_name,c.name AS category_name FROM payables p JOIN accounts a ON a.id=p.account_id AND a.user_id=p.user_id LEFT JOIN categories c ON c.id=p.category_id AND c.user_id=p.user_id WHERE p.user_id=:user ORDER BY CASE p.status WHEN \'pending\' THEN 0 ELSE 1 END,p.due_date');$stmt->execute(['user'=>$uid]);$payables=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT r.*,a.name AS account_name,c.name AS category_name FROM receivables r JOIN accounts a ON a.id=r.account_id AND a.user_id=r.user_id LEFT JOIN categories c ON c.id=r.category_id AND c.user_id=r.user_id WHERE r.user_id=:user ORDER BY CASE r.status WHEN \'pending\' THEN 0 ELSE 1 END,r.due_date');$stmt->execute(['user'=>$uid]);$receivables=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT id,name FROM accounts WHERE user_id=:user AND is_active=1 ORDER BY name');$stmt->execute(['user'=>$uid]);$accounts=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT id,name,type FROM categories WHERE user_id=:user AND is_active=1 ORDER BY type,name');$stmt->execute(['user'=>$uid]);$categories=$stmt->fetchAll();
        render('commitments/index',['title'=>'Contas a pagar e receber','payables'=>$payables,'receivables'=>$receivables,'accounts'=>$accounts,'categories'=>$categories,'errors'=>$_SESSION['_errors']??[]]);unset($_SESSION['_errors']);
    }
    public function save(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$type=(string)($_POST['type']??'expense');$description=trim((string)($_POST['description']??''));$amount=FinancialService::moneyToCents((string)($_POST['amount']??''));$due=(string)($_POST['due_date']??'');$account=(int)($_POST['account_id']??0);$category=(int)($_POST['category_id']??0);$notes=trim((string)($_POST['notes']??''));$errors=[];
        if(!in_array($type,['income','expense'],true))$errors[]='Tipo de compromisso inválido.';if(mb_strlen($description)<2||mb_strlen($description)>140)$errors[]='Informe uma descrição válida.';if($amount===null||$amount<=0)$errors[]='Informe um valor maior que zero.';if(!FinancialService::validDate($due))$errors[]='Informe uma data de vencimento válida.';
        $stmt=$db->prepare('SELECT id FROM accounts WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$account,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='Selecione uma conta ativa.';
        if($category){$stmt=$db->prepare('SELECT id FROM categories WHERE id=:id AND user_id=:user AND type=:type AND is_active=1');$stmt->execute(['id'=>$category,'user'=>$uid,'type'=>$type]);if(!$stmt->fetchColumn())$errors[]='Selecione uma categoria compatível com o compromisso.';}else $category=null;
        if($errors){$_SESSION['_errors']=$errors;redirect('/commitments');}$now=gmdate('c');
        if($type==='expense'){$stmt=$db->prepare('INSERT INTO payables(user_id,account_id,category_id,description,amount,due_date,status,notes,created_at,updated_at) VALUES(:user,:account,:category,:description,:amount,:due,\'pending\',:notes,:created,:updated)');}
        else{$stmt=$db->prepare('INSERT INTO receivables(user_id,account_id,category_id,description,amount,due_date,status,notes,created_at,updated_at) VALUES(:user,:account,:category,:description,:amount,:due,\'pending\',:notes,:created,:updated)');}
        $stmt->execute(['user'=>$uid,'account'=>$account,'category'=>$category,'description'=>$description,'amount'=>$amount,'due'=>$due,'notes'=>$notes,'created'=>$now,'updated'=>$now]);redirect('/commitments');
    }
    public function settle(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$type=(string)($_POST['type']??'');$id=(int)($_POST['id']??0);
        if(!in_array($type,['expense','income'],true))redirect('/commitments');
        $table=$type==='expense'?'payables':'receivables';$complete=$type==='expense'?'paid':'received';$doneAt=$type==='expense'?'paid_at':'received_at';$txStatus=$type==='expense'?'paid':'received';
        $db->beginTransaction();try{
            $stmt=$db->prepare("SELECT * FROM {$table} WHERE id=:id AND user_id=:user AND status='pending'");$stmt->execute(['id'=>$id,'user'=>$uid]);$item=$stmt->fetch();if(!$item){$db->rollBack();redirect('/commitments');}
            $date=date('Y-m-d');$now=gmdate('c');$tx=$db->prepare('INSERT INTO transactions(user_id,account_id,category_id,type,description,amount,transaction_date,due_date,status,payment_method,notes,created_at,updated_at) VALUES(:user,:account,:category,:type,:description,:amount,:date,:due,:status,:method,:notes,:created,:updated)');
            $tx->execute(['user'=>$uid,'account'=>$item['account_id'],'category'=>$item['category_id'],'type'=>$type,'description'=>$item['description'],'amount'=>$item['amount'],'date'=>$date,'due'=>$item['due_date'],'status'=>$txStatus,'method'=>'','notes'=>$item['notes'],'created'=>$now,'updated'=>$now]);
            $update=$db->prepare("UPDATE {$table} SET status=:status,{$doneAt}=:date,updated_at=:at WHERE id=:id AND user_id=:user AND status='pending'");$update->execute(['status'=>$complete,'date'=>$now,'at'=>$now,'id'=>$id,'user'=>$uid]);if($update->rowCount()!==1)throw new RuntimeException('Compromisso já liquidado.');$db->commit();
        }catch(Throwable $error){$db->rollBack();throw $error;}redirect('/commitments');
    }
    public function postpone(): void
    {
        $user=require_auth();verify_csrf();$type=(string)($_POST['type']??'');$id=(int)($_POST['id']??0);$due=(string)($_POST['due_date']??'');if(!FinancialService::validDate($due)||!in_array($type,['expense','income'],true)){$_SESSION['_errors']=['Informe uma nova data válida.'];redirect('/commitments');}
        $table=$type==='expense'?'payables':'receivables';$stmt=Database::connection()->prepare("UPDATE {$table} SET due_date=:due,updated_at=:at WHERE id=:id AND user_id=:user AND status='pending'");$stmt->execute(['due'=>$due,'at'=>gmdate('c'),'id'=>$id,'user'=>$user['id']]);redirect('/commitments');
    }
    public function cancel(): void
    {
        $user=require_auth();verify_csrf();$type=(string)($_POST['type']??'');$id=(int)($_POST['id']??0);if(!in_array($type,['expense','income'],true))redirect('/commitments');$table=$type==='expense'?'payables':'receivables';$stmt=Database::connection()->prepare("UPDATE {$table} SET status='cancelled',updated_at=:at WHERE id=:id AND user_id=:user AND status='pending'");$stmt->execute(['at'=>gmdate('c'),'id'=>$id,'user'=>$user['id']]);redirect('/commitments');
    }
}
