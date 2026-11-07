<?php
declare(strict_types=1);

final class CardController
{
    public function index(): void
    {
        $user=require_auth();$uid=(int)$user['id'];$db=Database::connection();
        $db->prepare("UPDATE invoices SET status='closed',updated_at=:at WHERE user_id=:user AND status='open' AND closing_date<:today")->execute(['at'=>gmdate('c'),'user'=>$uid,'today'=>date('Y-m-d')]);
        $stmt=$db->prepare('SELECT c.*,a.name AS payment_account,(SELECT COALESCE(SUM(ii.amount),0) FROM invoices i JOIN invoice_items ii ON ii.invoice_id=i.id AND ii.user_id=i.user_id WHERE i.card_id=c.id AND i.user_id=c.user_id AND i.status<>\'paid\') AS used_limit FROM credit_cards c JOIN accounts a ON a.id=c.payment_account_id AND a.user_id=c.user_id WHERE c.user_id=:user ORDER BY c.is_active DESC,c.name');$stmt->execute(['user'=>$uid]);$cards=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT p.*,c.name AS card_name,cat.name AS category_name FROM card_purchases p JOIN credit_cards c ON c.id=p.card_id AND c.user_id=p.user_id LEFT JOIN categories cat ON cat.id=p.category_id AND cat.user_id=p.user_id WHERE p.user_id=:user ORDER BY p.purchase_date DESC,p.id DESC LIMIT 100');$stmt->execute(['user'=>$uid]);$purchases=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT id,name FROM accounts WHERE user_id=:user AND is_active=1 ORDER BY name');$stmt->execute(['user'=>$uid]);$accounts=$stmt->fetchAll();
        $stmt=$db->prepare("SELECT id,name FROM categories WHERE user_id=:user AND type='expense' AND is_active=1 ORDER BY name");$stmt->execute(['user'=>$uid]);$categories=$stmt->fetchAll();
        $edit=null;if(isset($_GET['edit'])){$stmt=$db->prepare('SELECT * FROM credit_cards WHERE id=:id AND user_id=:user');$stmt->execute(['id'=>(int)$_GET['edit'],'user'=>$uid]);$edit=$stmt->fetch()?:null;}
        render('cards/index',['title'=>'Cartões','cards'=>$cards,'purchases'=>$purchases,'accounts'=>$accounts,'categories'=>$categories,'edit'=>$edit,'errors'=>$_SESSION['_errors']??[]]);unset($_SESSION['_errors']);
    }

    public function invoices(): void
    {
        $user=require_auth();$uid=(int)$user['id'];$db=Database::connection();
        $db->prepare("UPDATE invoices SET status='closed',updated_at=:at WHERE user_id=:user AND status='open' AND closing_date<:today")->execute(['at'=>gmdate('c'),'user'=>$uid,'today'=>date('Y-m-d')]);
        $stmt=$db->prepare('SELECT i.*,c.name AS card_name,c.color,c.payment_account_id,a.name AS payment_account,(SELECT COALESCE(SUM(ii.amount),0) FROM invoice_items ii WHERE ii.invoice_id=i.id AND ii.user_id=i.user_id) AS total,(SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_id=i.id AND ii.user_id=i.user_id) AS item_count FROM invoices i JOIN credit_cards c ON c.id=i.card_id AND c.user_id=i.user_id JOIN accounts a ON a.id=c.payment_account_id AND a.user_id=c.user_id WHERE i.user_id=:user ORDER BY i.due_date DESC,i.id DESC LIMIT 120');$stmt->execute(['user'=>$uid]);$invoices=$stmt->fetchAll();
        $stmt=$db->prepare('SELECT id,name FROM accounts WHERE user_id=:user AND is_active=1 ORDER BY name');$stmt->execute(['user'=>$uid]);$accounts=$stmt->fetchAll();
        $focus=null;$items=[];if(isset($_GET['id'])){$stmt=$db->prepare('SELECT i.*,c.name AS card_name,c.payment_account_id,ip.amount AS paid_amount,ip.paid_at,payacct.name AS paid_account FROM invoices i JOIN credit_cards c ON c.id=i.card_id AND c.user_id=i.user_id LEFT JOIN invoice_payments ip ON ip.invoice_id=i.id AND ip.user_id=i.user_id LEFT JOIN accounts payacct ON payacct.id=ip.account_id AND payacct.user_id=ip.user_id WHERE i.id=:id AND i.user_id=:user');$stmt->execute(['id'=>(int)$_GET['id'],'user'=>$uid]);$focus=$stmt->fetch()?:null;if($focus){$stmt=$db->prepare('SELECT ii.*,p.description,p.purchase_date,cat.name AS category_name FROM invoice_items ii JOIN card_purchases p ON p.id=ii.purchase_id AND p.user_id=ii.user_id LEFT JOIN categories cat ON cat.id=p.category_id AND cat.user_id=p.user_id WHERE ii.invoice_id=:invoice AND ii.user_id=:user ORDER BY p.purchase_date,ii.installment_number');$stmt->execute(['invoice'=>$focus['id'],'user'=>$uid]);$items=$stmt->fetchAll();}}
        render('cards/invoices',['title'=>'Faturas','invoices'=>$invoices,'accounts'=>$accounts,'focus'=>$focus,'items'=>$items,'errors'=>$_SESSION['_errors']??[]]);unset($_SESSION['_errors']);
    }

    public function save(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$id=(int)($_POST['id']??0);$name=trim((string)($_POST['name']??''));$institution=trim((string)($_POST['institution']??''));$brand=trim((string)($_POST['brand']??''));$limit=FinancialService::moneyToCents((string)($_POST['credit_limit']??''));$closing=(int)($_POST['closing_day']??0);$due=(int)($_POST['due_day']??0);$account=(int)($_POST['payment_account_id']??0);$color=(string)($_POST['color']??'#5478d4');$errors=[];
        if(mb_strlen($name)<2||mb_strlen($name)>80)$errors[]='Informe um nome de cartão entre 2 e 80 caracteres.';if($limit===null||$limit<0)$errors[]='Informe um limite válido.';if($closing<1||$closing>28||$due<1||$due>28)$errors[]='Dias de fechamento e vencimento precisam ficar entre 1 e 28.';if(!preg_match('/^#[0-9a-fA-F]{6}$/',$color))$color='#5478d4';
        $stmt=$db->prepare('SELECT id FROM accounts WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$account,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='Selecione a conta usada para pagar a fatura.';
        if($errors){$_SESSION['_errors']=$errors;redirect($id?'/cards?edit='.$id:'/cards');}$now=gmdate('c');
        if($id){$stmt=$db->prepare('UPDATE credit_cards SET name=:name,institution=:institution,brand=:brand,credit_limit=:limit,closing_day=:closing,due_day=:due,payment_account_id=:account,color=:color,updated_at=:at WHERE id=:id AND user_id=:user');$stmt->execute(['name'=>$name,'institution'=>$institution,'brand'=>$brand,'limit'=>$limit,'closing'=>$closing,'due'=>$due,'account'=>$account,'color'=>$color,'at'=>$now,'id'=>$id,'user'=>$uid]);}
        else{$stmt=$db->prepare('INSERT INTO credit_cards(user_id,name,institution,brand,credit_limit,closing_day,due_day,payment_account_id,color,created_at,updated_at) VALUES(:user,:name,:institution,:brand,:limit,:closing,:due,:account,:color,:created,:updated)');$stmt->execute(['user'=>$uid,'name'=>$name,'institution'=>$institution,'brand'=>$brand,'limit'=>$limit,'closing'=>$closing,'due'=>$due,'account'=>$account,'color'=>$color,'created'=>$now,'updated'=>$now]);}
        redirect('/cards');
    }

    public function toggle(): void
    {
        $user=require_auth();verify_csrf();$stmt=Database::connection()->prepare('UPDATE credit_cards SET is_active=1-is_active,updated_at=:at WHERE id=:id AND user_id=:user');$stmt->execute(['at'=>gmdate('c'),'id'=>(int)($_POST['id']??0),'user'=>$user['id']]);redirect('/cards');
    }

    public function purchase(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$cardId=(int)($_POST['card_id']??0);$category=(int)($_POST['category_id']??0);$description=trim((string)($_POST['description']??''));$amount=FinancialService::moneyToCents((string)($_POST['total_amount']??''));$date=(string)($_POST['purchase_date']??'');$count=(int)($_POST['installment_count']??1);$notes=trim((string)($_POST['notes']??''));$errors=[];
        if(mb_strlen($description)<2||mb_strlen($description)>140)$errors[]='Informe uma descrição válida.';if($amount===null||$amount<=0)$errors[]='Informe um valor maior que zero.';if(!FinancialService::validDate($date))$errors[]='Informe uma data válida.';if($count<1||$count>60||($amount!==null&&$amount<$count))$errors[]='Número de parcelas inválido para o valor informado.';
        $stmt=$db->prepare('SELECT * FROM credit_cards WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$cardId,'user'=>$uid]);$card=$stmt->fetch();if(!$card)$errors[]='Selecione um cartão ativo.';
        if($category){$stmt=$db->prepare("SELECT id FROM categories WHERE id=:id AND user_id=:user AND type='expense' AND is_active=1");$stmt->execute(['id'=>$category,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='Selecione uma categoria de despesa ativa.';}else $category=null;
        if($card&&$amount!==null){$stmt=$db->prepare("SELECT COALESCE(SUM(ii.amount),0) FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id AND i.user_id=ii.user_id WHERE i.user_id=:user AND i.card_id=:card AND i.status<>'paid'");$stmt->execute(['user'=>$uid,'card'=>$cardId]);$used=(int)$stmt->fetchColumn();if($used+$amount>(int)$card['credit_limit'])$errors[]='Esta compra ultrapassa o limite disponível do cartão.';}
        if($errors){$_SESSION['_errors']=$errors;redirect('/cards');}
        $db->beginTransaction();try{
            $now=gmdate('c');$purchase=$db->prepare('INSERT INTO card_purchases(user_id,card_id,category_id,description,total_amount,purchase_date,installment_count,notes,created_at) VALUES(:user,:card,:category,:description,:amount,:date,:count,:notes,:created)');$purchase->execute(['user'=>$uid,'card'=>$cardId,'category'=>$category,'description'=>$description,'amount'=>$amount,'date'=>$date,'count'=>$count,'notes'=>$notes,'created'=>$now]);$purchaseId=(int)$db->lastInsertId();
            $base=intdiv($amount,$count);$remainder=$amount%$count;$purchaseDate=new DateTimeImmutable($date);$ref=$purchaseDate->modify('first day of this month');if((int)$purchaseDate->format('d')>(int)$card['closing_day'])$ref=$ref->modify('+1 month');
            for($part=1;$part<=$count;$part++){
                $refMonth=$ref->format('Y-m');$closingDate=$ref->setDate((int)$ref->format('Y'),(int)$ref->format('m'),(int)$card['closing_day']);$dueMonth=(int)$card['due_day']>(int)$card['closing_day']?$ref:$ref->modify('+1 month');$dueDate=$dueMonth->setDate((int)$dueMonth->format('Y'),(int)$dueMonth->format('m'),(int)$card['due_day']);$status=$closingDate->format('Y-m-d')<date('Y-m-d')?'closed':'open';
                $make=$db->prepare("INSERT INTO invoices(user_id,card_id,reference_month,closing_date,due_date,status,created_at,updated_at) VALUES(:user,:card,:month,:closing,:due,:status,:created,:updated) ON CONFLICT(user_id,card_id,reference_month) DO UPDATE SET status=CASE WHEN invoices.status='open' AND excluded.status='closed' THEN 'closed' ELSE invoices.status END,updated_at=excluded.updated_at");$make->execute(['user'=>$uid,'card'=>$cardId,'month'=>$refMonth,'closing'=>$closingDate->format('Y-m-d'),'due'=>$dueDate->format('Y-m-d'),'status'=>$status,'created'=>$now,'updated'=>$now]);
                $find=$db->prepare('SELECT id,status FROM invoices WHERE user_id=:user AND card_id=:card AND reference_month=:month');$find->execute(['user'=>$uid,'card'=>$cardId,'month'=>$refMonth]);$invoice=$find->fetch();if($invoice['status']==='paid')throw new RuntimeException('Não é possível inserir uma compra em fatura já paga.');
                $line=$db->prepare('INSERT INTO invoice_items(user_id,invoice_id,purchase_id,installment_number,total_installments,amount) VALUES(:user,:invoice,:purchase,:number,:total,:amount)');$line->execute(['user'=>$uid,'invoice'=>$invoice['id'],'purchase'=>$purchaseId,'number'=>$part,'total'=>$count,'amount'=>$base+($part<=$remainder?1:0)]);$ref=$ref->modify('+1 month');
            }
            $db->commit();
        }catch(Throwable $error){$db->rollBack();if($error instanceof RuntimeException){$_SESSION['_errors']=[$error->getMessage()];redirect('/cards');}throw $error;}
        redirect('/cards');
    }

    public function payInvoice(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$invoiceId=(int)($_POST['invoice_id']??0);$accountId=(int)($_POST['account_id']??0);$errors=[];
        $stmt=$db->prepare('SELECT i.*,COALESCE((SELECT SUM(ii.amount) FROM invoice_items ii WHERE ii.invoice_id=i.id AND ii.user_id=i.user_id),0) AS total FROM invoices i WHERE i.id=:id AND i.user_id=:user');$stmt->execute(['id'=>$invoiceId,'user'=>$uid]);$invoice=$stmt->fetch();if(!$invoice||$invoice['status']==='paid')$errors[]='Fatura não encontrada ou já paga.';if($invoice&&(int)$invoice['total']<1)$errors[]='Esta fatura não possui lançamentos.';
        $stmt=$db->prepare('SELECT id FROM accounts WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$accountId,'user'=>$uid]);if(!$stmt->fetchColumn())$errors[]='Selecione uma conta ativa para o pagamento.';
        if($errors){$_SESSION['_errors']=$errors;redirect('/invoices');}
        $db->beginTransaction();try{$now=gmdate('c');$payment=$db->prepare('INSERT INTO invoice_payments(user_id,invoice_id,account_id,amount,paid_at,notes,created_at) VALUES(:user,:invoice,:account,:amount,:date,:notes,:created)');$payment->execute(['user'=>$uid,'invoice'=>$invoiceId,'account'=>$accountId,'amount'=>$invoice['total'],'date'=>date('Y-m-d'),'notes'=>'Pagamento da fatura','created'=>$now]);$update=$db->prepare("UPDATE invoices SET status='paid',paid_at=:at,updated_at=:at2 WHERE id=:id AND user_id=:user AND status<>'paid'");$update->execute(['at'=>$now,'at2'=>$now,'id'=>$invoiceId,'user'=>$uid]);if($update->rowCount()!==1)throw new RuntimeException('Fatura já paga.');$db->commit();}catch(Throwable $error){$db->rollBack();throw $error;}redirect('/invoices');
    }

    public function closeInvoice(): void
    {
        $user=require_auth();verify_csrf();$stmt=Database::connection()->prepare("UPDATE invoices SET status='closed',updated_at=:at WHERE id=:id AND user_id=:user AND status='open' AND closing_date<=:today");$stmt->execute(['at'=>gmdate('c'),'id'=>(int)($_POST['invoice_id']??0),'user'=>$user['id'],'today'=>date('Y-m-d')]);redirect('/invoices');
    }
}
