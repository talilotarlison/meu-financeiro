<?php
declare(strict_types=1);

final class AccountController
{
    private const TYPES = ['checking'=>'Conta corrente','savings'=>'Poupança','cash'=>'Dinheiro','digital'=>'Conta digital','payment'=>'Conta de pagamento','investment'=>'Investimentos','other'=>'Outra'];

    public function index(): void
    {
        $user = require_auth(); $db = Database::connection(); $uid = (int)$user['id'];
        $balances = FinancialService::balances($uid);
        $edit = null;
        if (isset($_GET['edit'])) {
            $stmt=$db->prepare('SELECT * FROM accounts WHERE id=:id AND user_id=:user'); $stmt->execute(['id'=>(int)$_GET['edit'],'user'=>$uid]); $edit=$stmt->fetch() ?: null;
        }
        $statement = null; $statementRows=[];
        if (isset($_GET['statement'])) {
            foreach($balances as $row) if((int)$row['id']===(int)$_GET['statement']) $statement=$row;
            if($statement) {
                $stmt=$db->prepare("SELECT t.id,t.type,t.description,t.amount,t.transaction_date AS date,t.status,c.name AS category,'transaction' AS entry_kind FROM transactions t LEFT JOIN categories c ON c.id=t.category_id AND c.user_id=t.user_id WHERE t.user_id=:user_tx AND t.account_id=:account_tx AND t.status<>'cancelled' UNION ALL SELECT tr.id,'transfer_out',tr.description,tr.amount,tr.transfer_date,'completed','Transferência enviada','transfer' FROM transfers tr WHERE tr.user_id=:user_out AND tr.source_account_id=:account_out UNION ALL SELECT tr.id,'transfer_in',tr.description,tr.amount,tr.transfer_date,'completed','Transferência recebida','transfer' FROM transfers tr WHERE tr.user_id=:user_in AND tr.destination_account_id=:account_in UNION ALL SELECT ip.id,'invoice_payment',cc.name,ip.amount,substr(ip.paid_at,1,10),'paid','Pagamento de fatura','invoice_payment' FROM invoice_payments ip JOIN invoices i ON i.id=ip.invoice_id AND i.user_id=ip.user_id JOIN credit_cards cc ON cc.id=i.card_id AND cc.user_id=i.user_id WHERE ip.user_id=:user_pay AND ip.account_id=:account_pay ORDER BY date DESC,id DESC");
                $stmt->execute(['user_tx'=>$uid,'account_tx'=>$statement['id'],'user_out'=>$uid,'account_out'=>$statement['id'],'user_in'=>$uid,'account_in'=>$statement['id'],'user_pay'=>$uid,'account_pay'=>$statement['id']]); $statementRows=$stmt->fetchAll();
            }
        }
        render('accounts/index',['title'=>'Contas','accounts'=>$balances,'types'=>self::TYPES,'edit'=>$edit,'statement'=>$statement,'statementRows'=>$statementRows,'errors'=>$_SESSION['_errors']??[]]); unset($_SESSION['_errors']);
    }

    public function save(): void
    {
        $user=require_auth(); verify_csrf(); $db=Database::connection(); $uid=(int)$user['id'];
        $id=(int)($_POST['id']??0); $name=trim((string)($_POST['name']??'')); $type=(string)($_POST['type']??'');
        $institution=trim((string)($_POST['institution']??'')); $openingInput=(string)($_POST['opening_balance']??'');$negative=str_starts_with(trim($openingInput),'-');$opening=FinancialService::moneyToCents($negative?substr(trim($openingInput),1):$openingInput);if($opening!==null&&$negative)$opening=-$opening;
        $date=(string)($_POST['opening_date']??''); $color=(string)($_POST['color']??'#168b68'); $description=trim((string)($_POST['description']??''));
        $errors=[]; if(mb_strlen($name)<2||mb_strlen($name)>80)$errors[]='Informe um nome de conta entre 2 e 80 caracteres.';
        if(!isset(self::TYPES[$type]))$errors[]='Selecione um tipo de conta válido.';
        if($opening===null)$errors[]='Informe um saldo inicial válido.';
        if(!FinancialService::validDate($date))$errors[]='Informe uma data inicial válida.';
        if(!preg_match('/^#[0-9a-fA-F]{6}$/',$color))$color='#168b68';
        if($errors){$_SESSION['_errors']=$errors;redirect($id?'/accounts?edit='.$id:'/accounts');}
        $now=gmdate('c');
        if($id){$stmt=$db->prepare('UPDATE accounts SET name=:name,type=:type,institution=:institution,opening_balance=:opening,opening_date=:date,color=:color,description=:description,updated_at=:now WHERE id=:id AND user_id=:user');$stmt->execute(['name'=>$name,'type'=>$type,'institution'=>$institution,'opening'=>$opening,'date'=>$date,'color'=>$color,'description'=>$description,'now'=>$now,'id'=>$id,'user'=>$uid]);}
        else{$stmt=$db->prepare('INSERT INTO accounts(user_id,name,type,institution,opening_balance,opening_date,color,description,created_at,updated_at) VALUES(:user,:name,:type,:institution,:opening,:date,:color,:description,:created,:updated)');$stmt->execute(['user'=>$uid,'name'=>$name,'type'=>$type,'institution'=>$institution,'opening'=>$opening,'date'=>$date,'color'=>$color,'description'=>$description,'created'=>$now,'updated'=>$now]);}
        redirect('/accounts');
    }

    public function toggle(): void
    {
        $user=require_auth();verify_csrf();$stmt=Database::connection()->prepare('UPDATE accounts SET is_active=1-is_active,updated_at=:at WHERE id=:id AND user_id=:user');
        $stmt->execute(['at'=>gmdate('c'),'id'=>(int)($_POST['id']??0),'user'=>$user['id']]);redirect('/accounts');
    }
}
