<?php
declare(strict_types=1);

final class TransactionController
{
    public function index(): void
    {
        $user=require_auth();$db=Database::connection();$uid=(int)$user['id'];
        $where=['t.user_id=:user','t.status<>\'cancelled\''];$params=['user'=>$uid];
        foreach(['type'=>'t.type','status'=>'t.status','account'=>'t.account_id','category'=>'t.category_id'] as $key=>$column){$value=(string)($_GET[$key]??'');if($value!==''){$where[]=$column.'=:'.$key;$params[$key]=$value;}}
        $from=(string)($_GET['from']??'');$to=(string)($_GET['to']??'');$search=trim((string)($_GET['q']??''));
        if(FinancialService::validDate($from)){$where[]='t.transaction_date>=:from';$params['from']=$from;}
        if(FinancialService::validDate($to)){$where[]='t.transaction_date<=:to';$params['to']=$to;}
        if($search!==''){$where[]='t.description LIKE :search';$params['search']='%'.$search.'%';}
        $sql='SELECT t.*,a.name AS account_name,c.name AS category_name FROM transactions t JOIN accounts a ON a.id=t.account_id AND a.user_id=t.user_id LEFT JOIN categories c ON c.id=t.category_id AND c.user_id=t.user_id WHERE '.implode(' AND ',$where).' ORDER BY t.transaction_date DESC,t.id DESC LIMIT 300';
        $stmt=$db->prepare($sql);$stmt->execute($params);$transactions=$stmt->fetchAll();
        $edit=null;if(isset($_GET['edit'])){$stmt=$db->prepare('SELECT * FROM transactions WHERE user_id=:user AND id=:id');$stmt->execute(['user'=>$uid,'id'=>(int)$_GET['edit']]);$edit=$stmt->fetch()?:null;}
        $accounts=$db->prepare('SELECT id,name FROM accounts WHERE user_id=:user AND (is_active=1 OR id=:edit_account) ORDER BY name');$accounts->execute(['user'=>$uid,'edit_account'=>$edit['account_id']??0]);$accounts=$accounts->fetchAll();
        $categories=$db->prepare('SELECT id,name,type FROM categories WHERE user_id=:user AND (is_active=1 OR id=:edit_category) ORDER BY type,name');$categories->execute(['user'=>$uid,'edit_category'=>$edit['category_id']??0]);$categories=$categories->fetchAll();
        render('transactions/index',['title'=>'Movimentações','transactions'=>$transactions,'accounts'=>$accounts,'categories'=>$categories,'edit'=>$edit,'filters'=>$_GET,'errors'=>$_SESSION['_errors']??[]]);unset($_SESSION['_errors']);
    }

    public function save(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$id=(int)($_POST['id']??0);
        $type=(string)($_POST['type']??'');$description=trim((string)($_POST['description']??''));$amount=FinancialService::moneyToCents((string)($_POST['amount']??''));$date=(string)($_POST['transaction_date']??'');$due=(string)($_POST['due_date']??'');$account=(int)($_POST['account_id']??0);$category=(int)($_POST['category_id']??0);$status=(string)($_POST['status']??'pending');$method=trim((string)($_POST['payment_method']??''));$notes=trim((string)($_POST['notes']??''));
        $errors=[];if(!in_array($type,['income','expense'],true))$errors[]='Selecione receita ou despesa.';if(mb_strlen($description)<2||mb_strlen($description)>140)$errors[]='A descrição precisa ter entre 2 e 140 caracteres.';if($amount===null||$amount<=0)$errors[]='Informe um valor maior que zero.';if(!FinancialService::validDate($date))$errors[]='Informe uma data válida.';if($due!==''&&!FinancialService::validDate($due))$errors[]='Informe um vencimento válido.';if(!in_array($status,['pending','paid','received'],true)||($type==='income'&&$status==='paid')||($type==='expense'&&$status==='received'))$errors[]='Status incompatível com o tipo de movimentação.';
        $stmt=$db->prepare('SELECT id FROM accounts WHERE id=:id AND user_id=:user AND (is_active=1 OR :editing=1)');$stmt->execute(['id'=>$account,'user'=>$uid,'editing'=>$id?1:0]);if(!$stmt->fetchColumn())$errors[]='Selecione uma conta ativa desta conta de usuário.';
        if($category){$stmt=$db->prepare('SELECT id FROM categories WHERE id=:id AND user_id=:user AND type=:type AND (is_active=1 OR :editing=1)');$stmt->execute(['id'=>$category,'user'=>$uid,'type'=>$type,'editing'=>$id?1:0]);if(!$stmt->fetchColumn())$errors[]='A categoria precisa pertencer ao usuário e corresponder ao tipo da movimentação.';}
        else $category=null;
        if($errors){$_SESSION['_errors']=$errors;redirect($id?'/transactions?edit='.$id:'/transactions');}
        $now=gmdate('c');$values=['user'=>$uid,'account'=>$account,'category'=>$category,'type'=>$type,'description'=>$description,'amount'=>$amount,'date'=>$date,'due'=>$due?:null,'status'=>$status,'method'=>$method,'notes'=>$notes,'now'=>$now];
        if($id){$stmt=$db->prepare("UPDATE transactions SET account_id=:account,category_id=:category,type=:type,description=:description,amount=:amount,transaction_date=:date,due_date=:due,status=:status,payment_method=:method,notes=:notes,updated_at=:now WHERE id=:id AND user_id=:user");$stmt->execute($values+['id'=>$id]);if(!$stmt->rowCount()){$_SESSION['_errors']=['Movimentação não encontrada.'];}}
        else{$insertValues=$values;$insertValues['created']=$now;$stmt=$db->prepare("INSERT INTO transactions(user_id,account_id,category_id,type,description,amount,transaction_date,due_date,status,payment_method,notes,created_at,updated_at) VALUES(:user,:account,:category,:type,:description,:amount,:date,:due,:status,:method,:notes,:created,:now)");$stmt->execute($insertValues);}
        redirect('/transactions');
    }

    public function cancel(): void
    {
        $user=require_auth();verify_csrf();$stmt=Database::connection()->prepare("UPDATE transactions SET status='cancelled',updated_at=:at WHERE id=:id AND user_id=:user AND status<>'cancelled'");$stmt->execute(['at'=>gmdate('c'),'id'=>(int)($_POST['id']??0),'user'=>$user['id']]);redirect('/transactions');
    }
}
