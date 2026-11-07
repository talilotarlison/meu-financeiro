<?php
declare(strict_types=1);

final class GoalController
{
    public function index(): void
    {
        $user=require_auth();$uid=(int)$user['id'];$goals=GoalService::all($uid);$db=Database::connection();
        $stmt=$db->prepare('SELECT id,name FROM accounts WHERE user_id=:user AND is_active=1 ORDER BY name');$stmt->execute(['user'=>$uid]);$accounts=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT id,name,type FROM categories WHERE user_id=:user AND is_active=1 ORDER BY type,name');$stmt->execute(['user'=>$uid]);$categories=$stmt->fetchAll();
        $history=[];$edit=null;if(isset($_GET['history'])){$stmt=$db->prepare('SELECT gc.*,g.name AS goal_name FROM goal_contributions gc JOIN goals g ON g.id=gc.goal_id AND g.user_id=gc.user_id WHERE gc.user_id=:user AND gc.goal_id=:goal ORDER BY gc.movement_date DESC,gc.id DESC');$stmt->execute(['user'=>$uid,'goal'=>(int)$_GET['history']]);$history=$stmt->fetchAll();}
        if(isset($_GET['edit'])){$stmt=$db->prepare('SELECT * FROM goals WHERE id=:id AND user_id=:user');$stmt->execute(['id'=>(int)$_GET['edit'],'user'=>$uid]);$edit=$stmt->fetch()?:null;}
        render('goals/index',['title'=>'Metas financeiras','goals'=>$goals,'accounts'=>$accounts,'categories'=>$categories,'history'=>$history,'edit'=>$edit,'errors'=>$_SESSION['_errors']??[]]);unset($_SESSION['_errors']);
    }
    public function save(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$id=(int)($_POST['id']??0);$name=trim((string)($_POST['name']??''));$description=trim((string)($_POST['description']??''));$target=FinancialService::moneyToCents((string)($_POST['target_amount']??''));$start=(string)($_POST['start_date']??'');$due=(string)($_POST['due_date']??'');$account=(int)($_POST['account_id']??0);$category=(int)($_POST['category_id']??0);$color=(string)($_POST['color']??'#168b68');$errors=[];
        if(mb_strlen($name)<2||mb_strlen($name)>100)$errors[]='Informe um nome de meta entre 2 e 100 caracteres.';if(mb_strlen($description)>500)$errors[]='A descrição deve ter até 500 caracteres.';if($target===null||$target<=0)$errors[]='O valor desejado precisa ser maior que zero.';if(!FinancialService::validDate($start))$errors[]='Informe uma data inicial válida.';if($due!==''&&(!FinancialService::validDate($due)||$due<$start))$errors[]='A data prevista precisa ser válida e posterior ao início.';if(!preg_match('/^#[0-9a-fA-F]{6}$/',$color))$color='#168b68';
        if($account){$stmt=$db->prepare('SELECT id FROM accounts WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$account,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='A conta vinculada precisa pertencer ao usuário e estar ativa.';}else $account=null;
        if($category){$stmt=$db->prepare('SELECT id FROM categories WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$category,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='Selecione uma categoria válida.';}else $errors[]='Selecione uma categoria para a meta.';
        if($errors){$_SESSION['_errors']=$errors;redirect('/goals'.($id?'?edit='.$id:''));}$now=gmdate('c');
        if($id){$stmt=$db->prepare("UPDATE goals SET name=:name,description=:description,target_amount=:target,start_date=:start,due_date=:due,account_id=:account,category_id=:category,color=:color,updated_at=:at WHERE id=:id AND user_id=:user");$stmt->execute(['name'=>$name,'description'=>$description,'target'=>$target,'start'=>$start,'due'=>$due?:null,'account'=>$account,'category'=>$category,'color'=>$color,'at'=>$now,'id'=>$id,'user'=>$uid]);}
        else{$stmt=$db->prepare('INSERT INTO goals(user_id,name,description,target_amount,start_date,due_date,account_id,category_id,color,status,created_at,updated_at) VALUES(:user,:name,:description,:target,:start,:due,:account,:category,:color,\'active\',:created,:updated)');$stmt->execute(['user'=>$uid,'name'=>$name,'description'=>$description,'target'=>$target,'start'=>$start,'due'=>$due?:null,'account'=>$account,'category'=>$category,'color'=>$color,'created'=>$now,'updated'=>$now]);}
        redirect('/goals');
    }
    public function contribution(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$goalId=(int)($_POST['goal_id']??0);$direction=(string)($_POST['direction']??'contribution');$amount=FinancialService::moneyToCents((string)($_POST['amount']??''));$date=(string)($_POST['movement_date']??'');$accountId=(int)($_POST['account_id']??0);$notes=trim((string)($_POST['notes']??''));$errors=[];
        if(!in_array($direction,['contribution','withdrawal'],true))$errors[]='Tipo de movimentação inválido.';if($amount===null||$amount<=0)$errors[]='Informe um valor maior que zero.';if(!FinancialService::validDate($date))$errors[]='Informe uma data válida.';
        $stmt=$db->prepare('SELECT * FROM goals WHERE id=:id AND user_id=:user');$stmt->execute(['id'=>$goalId,'user'=>$uid]);$goal=$stmt->fetch();if(!$goal||$goal['status']==='cancelled')$errors[]='Meta não encontrada ou cancelada.';
        $current=0;if($goal){$stmt=$db->prepare("SELECT COALESCE(SUM(CASE WHEN direction='contribution' THEN amount ELSE -amount END),0) FROM goal_contributions WHERE goal_id=:goal AND user_id=:user");$stmt->execute(['goal'=>$goalId,'user'=>$uid]);$current=(int)$stmt->fetchColumn();if($direction==='withdrawal'&&($amount??0)>$current)$errors[]='O resgate não pode ultrapassar o valor acumulado.';}
        if($goal&&$goal['account_id']){if(!$accountId)$errors[]='Selecione a conta de origem/destino da movimentação.';if($accountId===(int)$goal['account_id'])$errors[]='Selecione outra conta para a movimentação.';$stmt=$db->prepare('SELECT id FROM accounts WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$accountId,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='Selecione uma conta ativa de sua propriedade.';}
        elseif($accountId){$stmt=$db->prepare('SELECT id FROM accounts WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$accountId,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='Selecione uma conta ativa de sua propriedade.';}
        if($errors){$_SESSION['_errors']=$errors;redirect('/goals');}
        $db->beginTransaction();try{$now=gmdate('c');$transferId=null;
            if($goal['account_id']){$source=$direction==='contribution'?$accountId:(int)$goal['account_id'];$destination=$direction==='contribution'?(int)$goal['account_id']:$accountId;$transfer=$db->prepare('INSERT INTO transfers(user_id,source_account_id,destination_account_id,description,amount,transfer_date,notes,created_at) VALUES(:user,:source,:destination,:description,:amount,:date,:notes,:created)');$transfer->execute(['user'=>$uid,'source'=>$source,'destination'=>$destination,'description'=>($direction==='contribution'?'Aporte na meta: ':'Resgate da meta: ').$goal['name'],'amount'=>$amount,'date'=>$date,'notes'=>$notes,'created'=>$now]);$transferId=(int)$db->lastInsertId();}
            $stmt=$db->prepare('INSERT INTO goal_contributions(user_id,goal_id,amount,direction,movement_date,notes,transfer_id,created_at) VALUES(:user,:goal,:amount,:direction,:date,:notes,:transfer,:created)');$stmt->execute(['user'=>$uid,'goal'=>$goalId,'amount'=>$amount,'direction'=>$direction,'date'=>$date,'notes'=>$notes,'transfer'=>$transferId,'created'=>$now]);$newAmount=$current+($direction==='contribution'?$amount:-$amount);$status=$newAmount>=(int)$goal['target_amount']?'completed':'active';$stmt=$db->prepare('UPDATE goals SET status=:status,updated_at=:at WHERE id=:id AND user_id=:user');$stmt->execute(['status'=>$status,'at'=>$now,'id'=>$goalId,'user'=>$uid]);$db->commit();
        }catch(Throwable $error){$db->rollBack();throw $error;}redirect('/goals?history='.$goalId);
    }
    public function cancel(): void
    {
        $user=require_auth();verify_csrf();$stmt=Database::connection()->prepare("UPDATE goals SET status='cancelled',updated_at=:at WHERE id=:id AND user_id=:user AND status='active'");$stmt->execute(['at'=>gmdate('c'),'id'=>(int)($_POST['id']??0),'user'=>$user['id']]);redirect('/goals');
    }
}
