<?php
declare(strict_types=1);

final class BudgetController
{
    public function index(): void
    {
        $user=require_auth();$uid=(int)$user['id'];$month=(string)($_GET['month']??date('Y-m'));if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))$month=date('Y-m');
        $budgets=BudgetService::forMonth($uid,$month);$stmt=Database::connection()->prepare("SELECT id,name FROM categories WHERE user_id=:user AND type='expense' AND is_active=1 ORDER BY name");$stmt->execute(['user'=>$uid]);$categories=$stmt->fetchAll();
        $edit=null;if(isset($_GET['edit'])){$db=Database::connection();$stmt=$db->prepare('SELECT * FROM budgets WHERE id=:id AND user_id=:user');$stmt->execute(['id'=>(int)$_GET['edit'],'user'=>$uid]);$edit=$stmt->fetch()?:null;if($edit){$stmt=$db->prepare('SELECT percentage FROM budget_alert_levels WHERE budget_id=:budget AND user_id=:user ORDER BY percentage');$stmt->execute(['budget'=>$edit['id'],'user'=>$uid]);$edit['alert_percentages']=implode(', ',array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN)));}}
        render('budgets/index',['title'=>'Orçamentos','budgets'=>$budgets,'categories'=>$categories,'month'=>$month,'edit'=>$edit,'errors'=>$_SESSION['_errors']??[]]);unset($_SESSION['_errors']);
    }
    public function save(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$id=(int)($_POST['id']??0);$name=trim((string)($_POST['name']??''));$category=(int)($_POST['category_id']??0);$limit=FinancialService::moneyToCents((string)($_POST['limit_amount']??''));$month=(string)($_POST['reference_month']??'');$raw=(string)($_POST['alert_percentages']??'50, 80, 100');$parts=preg_split('/[,;\s]+/',trim($raw),-1,PREG_SPLIT_NO_EMPTY)?:[];$levels=[];$errors=[];
        if(mb_strlen($name)<2||mb_strlen($name)>80)$errors[]='Informe um nome para o orçamento.';if($limit===null||$limit<=0)$errors[]='O limite precisa ser maior que zero.';if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))$errors[]='Selecione um mês de referência válido.';
        foreach($parts as $part){if(!ctype_digit($part)||(int)$part<1||(int)$part>1000){$errors[]='Os alertas devem ser percentuais entre 1 e 1000.';break;}$levels[]=(int)$part;}$levels=array_values(array_unique($levels));sort($levels);if(!$levels)$errors[]='Informe ao menos um percentual de alerta.';
        $stmt=$db->prepare("SELECT id FROM categories WHERE id=:id AND user_id=:user AND type='expense' AND is_active=1");$stmt->execute(['id'=>$category,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='Escolha uma categoria de despesa ativa.';
        $stmt=$db->prepare('SELECT id FROM budgets WHERE user_id=:user AND category_id=:category AND reference_month=:month AND id<>:id');$stmt->execute(['user'=>$uid,'category'=>$category,'month'=>$month,'id'=>$id]);if($stmt->fetchColumn())$errors[]='Já existe um orçamento para essa categoria neste mês.';
        if($errors){$_SESSION['_errors']=$errors;redirect('/budgets?month='.rawurlencode($month).($id?'&edit='.$id:''));}
        $db->beginTransaction();try{$now=gmdate('c');if($id){$stmt=$db->prepare('UPDATE budgets SET name=:name,category_id=:category,limit_amount=:limit,reference_month=:month,updated_at=:at WHERE id=:id AND user_id=:user');$stmt->execute(['name'=>$name,'category'=>$category,'limit'=>$limit,'month'=>$month,'at'=>$now,'id'=>$id,'user'=>$uid]);if($stmt->rowCount()===0){$check=$db->prepare('SELECT 1 FROM budgets WHERE id=:id AND user_id=:user');$check->execute(['id'=>$id,'user'=>$uid]);if(!$check->fetchColumn())throw new RuntimeException('Orçamento não encontrado.');}$budgetId=$id;}else{$stmt=$db->prepare('INSERT INTO budgets(user_id,category_id,name,limit_amount,reference_month,created_at,updated_at) VALUES(:user,:category,:name,:limit,:month,:created,:updated)');$stmt->execute(['user'=>$uid,'category'=>$category,'name'=>$name,'limit'=>$limit,'month'=>$month,'created'=>$now,'updated'=>$now]);$budgetId=(int)$db->lastInsertId();}
            $db->prepare('DELETE FROM budget_alert_levels WHERE budget_id=:budget AND user_id=:user')->execute(['budget'=>$budgetId,'user'=>$uid]);$levelStmt=$db->prepare('INSERT INTO budget_alert_levels(user_id,budget_id,percentage,created_at) VALUES(:user,:budget,:percentage,:created)');foreach($levels as $level)$levelStmt->execute(['user'=>$uid,'budget'=>$budgetId,'percentage'=>$level,'created'=>$now]);$db->commit();
        }catch(Throwable $error){$db->rollBack();throw $error;}redirect('/budgets?month='.rawurlencode($month));
    }
    public function toggle(): void
    {
        $user=require_auth();verify_csrf();$stmt=Database::connection()->prepare('UPDATE budgets SET is_active=1-is_active,updated_at=:at WHERE id=:id AND user_id=:user');$stmt->execute(['at'=>gmdate('c'),'id'=>(int)($_POST['id']??0),'user'=>$user['id']]);$month=(string)($_POST['month']??date('Y-m'));if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))$month=date('Y-m');redirect('/budgets?month='.rawurlencode($month));
    }
}
