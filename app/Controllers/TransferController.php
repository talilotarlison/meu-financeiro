<?php
declare(strict_types=1);

final class TransferController
{
    public function index(): void
    {
        $user=require_auth();$db=Database::connection();$uid=(int)$user['id'];
        $stmt=$db->prepare('SELECT t.*,s.name AS source_name,d.name AS destination_name FROM transfers t JOIN accounts s ON s.id=t.source_account_id AND s.user_id=t.user_id JOIN accounts d ON d.id=t.destination_account_id AND d.user_id=t.user_id WHERE t.user_id=:user ORDER BY t.transfer_date DESC,t.id DESC LIMIT 300');$stmt->execute(['user'=>$uid]);$transfers=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT id,name FROM accounts WHERE user_id=:user AND is_active=1 ORDER BY name');$stmt->execute(['user'=>$uid]);$accounts=$stmt->fetchAll();
        render('transfers/index',['title'=>'Transferências','transfers'=>$transfers,'accounts'=>$accounts,'errors'=>$_SESSION['_errors']??[]]);unset($_SESSION['_errors']);
    }
    public function save(): void
    {
        $user=require_auth();verify_csrf();$uid=(int)$user['id'];$from=(int)($_POST['source_account_id']??0);$to=(int)($_POST['destination_account_id']??0);$description=trim((string)($_POST['description']??'Transferência entre contas'));$amount=FinancialService::moneyToCents((string)($_POST['amount']??''));$date=(string)($_POST['transfer_date']??'');$notes=trim((string)($_POST['notes']??''));$errors=[];
        if($from===$to)$errors[]='A conta de origem e destino devem ser diferentes.';if($amount===null||$amount<=0)$errors[]='Informe um valor maior que zero.';if(!FinancialService::validDate($date))$errors[]='Informe uma data válida.';if(mb_strlen($description)<2||mb_strlen($description)>140)$errors[]='Informe uma descrição entre 2 e 140 caracteres.';
        $db=Database::connection();$stmt=$db->prepare('SELECT COUNT(*) FROM accounts WHERE user_id=:user AND is_active=1 AND id IN (:from,:to)');$stmt->execute(['user'=>$uid,'from'=>$from,'to'=>$to]);if((int)$stmt->fetchColumn()!==2)$errors[]='Selecione duas contas ativas pertencentes ao seu usuário.';
        if($errors){$_SESSION['_errors']=$errors;redirect('/transfers');}
        $db->beginTransaction();try{$stmt=$db->prepare('INSERT INTO transfers(user_id,source_account_id,destination_account_id,description,amount,transfer_date,notes,created_at) VALUES(:user,:source,:destination,:description,:amount,:date,:notes,:now)');$stmt->execute(['user'=>$uid,'source'=>$from,'destination'=>$to,'description'=>$description,'amount'=>$amount,'date'=>$date,'notes'=>$notes,'now'=>gmdate('c')]);$db->commit();}catch(Throwable $error){$db->rollBack();throw $error;}
        redirect('/transfers');
    }
}
