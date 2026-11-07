<?php
declare(strict_types=1);

if (getenv('APP_ENV') !== 'test' || getenv('DATABASE_PATH') !== ':memory:') {
    fwrite(STDERR, "Execute com APP_ENV=test e DATABASE_PATH=:memory: para proteger o banco real.\n");
    exit(2);
}

require dirname(__DIR__).'/app/Database.php';
require dirname(__DIR__).'/app/Migrator.php';
require dirname(__DIR__).'/app/Services/FinancialService.php';
require dirname(__DIR__).'/app/Services/DashboardService.php';
require dirname(__DIR__).'/app/Services/RecurrenceService.php';
require dirname(__DIR__).'/app/Services/BudgetService.php';
require dirname(__DIR__).'/app/Services/GoalService.php';
require dirname(__DIR__).'/app/Services/InvestmentService.php';
require dirname(__DIR__).'/app/Services/PatrimonyService.php';
require dirname(__DIR__).'/app/Services/ImportService.php';
require dirname(__DIR__).'/app/Services/BackupService.php';
require dirname(__DIR__).'/app/Services/ReportService.php';

$checks=0;
$check=static function(bool $condition,string $message)use(&$checks):void{if(!$condition)throw new RuntimeException('Falhou: '.$message);$checks++;};
$backupPaths=[];
try {
    $check(FinancialService::moneyToCents('1.234,56')===123456,'conversão de moeda pt-BR');
    $check(FinancialService::moneyToCents('12,3')===1230,'conversão de centavos');
    $check(FinancialService::moneyToCents('1,234')===null,'rejeição de valor ambíguo');
    $check(FinancialService::validDate('2026-11-07')&&!FinancialService::validDate('2026-02-30'),'validação de datas');

    $db=Database::connection();Migrator::run($db);
    $check((bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='net_worth_snapshots'")->fetchColumn(),'migration de patrimônio');
    $check((bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='index' AND name='idx_transactions_external_id'")->fetchColumn(),'índice antduplicidade de importação');

    $now=gmdate('c');$userInsert=$db->prepare('INSERT INTO users(id,name,email,password_hash,created_at,updated_at) VALUES(:id,:name,:email,:hash,:at,:at)');
    $userA=910000001;$userB=910000002;
    foreach([[$userA,'Teste A','phase8-a@example.test'],[$userB,'Teste B','phase8-b@example.test']] as [$id,$name,$email])$userInsert->execute(['id'=>$id,'name'=>$name,'email'=>$email,'hash'=>'test-hash','at'=>$now]);
    $profile=$db->prepare('SELECT status,last_login_at FROM users WHERE id=:id');$profile->execute(['id'=>$userA]);$profileState=$profile->fetch();$check($profileState['status']==='active'&&$profileState['last_login_at']===null,'migração de status e último login');
    $db->prepare('UPDATE users SET last_login_at=:at WHERE id=:id')->execute(['at'=>$now,'id'=>$userA]);$profile->execute(['id'=>$userA]);$check($profile->fetch()['last_login_at']===$now,'registro do último login');
    $setting=$db->prepare('INSERT INTO user_settings(user_id,created_at,updated_at) VALUES(:user,:at,:at)');$setting->execute(['user'=>$userA,'at'=>$now]);$setting->execute(['user'=>$userB,'at'=>$now]);
    $category=$db->prepare('INSERT INTO categories(user_id,name,type,created_at,updated_at) VALUES(:user,:name,:type,:at,:at)');
    $category->execute(['user'=>$userA,'name'=>'Salário','type'=>'income','at'=>$now]);$incomeCategory=(int)$db->lastInsertId();$category->execute(['user'=>$userA,'name'=>'Alimentação','type'=>'expense','at'=>$now]);$expenseCategory=(int)$db->lastInsertId();
    $account=$db->prepare('INSERT INTO accounts(user_id,name,type,opening_balance,opening_date,created_at,updated_at) VALUES(:user,:name,:type,0,:date,:at,:at)');$account->execute(['user'=>$userA,'name'=>'Conta A','type'=>'checking','date'=>date('Y-m-d'),'at'=>$now]);$accountA=(int)$db->lastInsertId();$account->execute(['user'=>$userB,'name'=>'Conta B','type'=>'checking','date'=>date('Y-m-d'),'at'=>$now]);$accountB=(int)$db->lastInsertId();
    try{$db->prepare('INSERT INTO transfers(user_id,source_account_id,destination_account_id,description,amount,transfer_date,created_at) VALUES(:user,:source,:destination,\'Transferência inválida\',100,:date,:at)')->execute(['user'=>$userA,'source'=>$accountA,'destination'=>$accountB,'date'=>date('Y-m-d'),'at'=>$now]);throw new RuntimeException('A chave estrangeira aceitou conta de outro usuário.');}catch(PDOException){$check(true,'isolamento de contas por chave estrangeira');}
    $tx=$db->prepare('INSERT INTO transactions(user_id,account_id,category_id,type,description,amount,transaction_date,status,external_id,created_at,updated_at) VALUES(:user,:account,:category,:type,:description,:amount,:date,:status,:external,:at,:at)');
    $today=date('Y-m-d');$tx->execute(['user'=>$userA,'account'=>$accountA,'category'=>$incomeCategory,'type'=>'income','description'=>'Receita de teste','amount'=>100000,'date'=>$today,'status'=>'received','external'=>'','at'=>$now]);$tx->execute(['user'=>$userA,'account'=>$accountA,'category'=>$expenseCategory,'type'=>'expense','description'=>'Despesa de teste','amount'=>25000,'date'=>$today,'status'=>'paid','external'=>'','at'=>$now]);
    $dashboard=DashboardService::data($userA,['period'=>'current_month']);$check($dashboard['totals']['income']===100000&&$dashboard['totals']['expense']===25000,'totais do dashboard');$check(end($dashboard['balanceValues'])===75000,'série mensal otimizada de saldo');
    $dupes=ImportService::markDuplicates($userA,$accountA,[['date'=>$today,'type'=>'expense','amount'=>25000,'description'=>'Despesa de teste','source_id'=>'csv:test']]);$check($dupes[0]['duplicate'],'detecção de lançamento duplicado');

    $db->prepare("INSERT INTO credit_cards(user_id,name,credit_limit,closing_day,due_day,payment_account_id,created_at,updated_at) VALUES(:user,'Cartão teste',50000,28,5,:account,:at,:at)")->execute(['user'=>$userA,'account'=>$accountA,'at'=>$now]);$cardId=(int)$db->lastInsertId();$db->prepare('INSERT INTO invoices(user_id,card_id,reference_month,closing_date,due_date,status,created_at,updated_at) VALUES(:user,:card,:month,:date,:date,\'paid\',:at,:at)')->execute(['user'=>$userA,'card'=>$cardId,'month'=>date('Y-m'),'date'=>$today,'at'=>$now]);$invoiceId=(int)$db->lastInsertId();$db->prepare("INSERT INTO card_purchases(user_id,card_id,category_id,description,total_amount,purchase_date,created_at) VALUES(:user,:card,:category,'Compra teste',10000,:date,:at)")->execute(['user'=>$userA,'card'=>$cardId,'category'=>$expenseCategory,'date'=>$today,'at'=>$now]);$purchaseId=(int)$db->lastInsertId();$db->prepare('INSERT INTO invoice_items(user_id,invoice_id,purchase_id,installment_number,total_installments,amount) VALUES(:user,:invoice,:purchase,1,1,10000)')->execute(['user'=>$userA,'invoice'=>$invoiceId,'purchase'=>$purchaseId]);$db->prepare("INSERT INTO invoice_payments(user_id,invoice_id,account_id,amount,paid_at,created_at) VALUES(:user,:invoice,:account,10000,:date,:at)")->execute(['user'=>$userA,'invoice'=>$invoiceId,'account'=>$accountA,'date'=>$now,'at'=>$now]);$withCard=DashboardService::data($userA,['period'=>'current_month']);$check($withCard['totals']['expense']===35000,'cartão contado pela parcela uma única vez');$check(end($withCard['balanceValues'])===65000,'pagamento da fatura debitado sem duplicar despesa');

    $db->prepare("INSERT INTO recurring_transactions(user_id,account_id,category_id,type,description,amount,frequency,interval_value,start_date,next_due_date,end_date,status,created_at,updated_at) VALUES(:user,:account,:category,'expense','Recorrência teste',500,'monthly',1,:date,:date,:date,'active',:at,:at)")->execute(['user'=>$userA,'account'=>$accountA,'category'=>$expenseCategory,'date'=>$today,'at'=>$now]);RecurrenceService::generateForUser($userA);RecurrenceService::generateForUser($userA);$occurrences=$db->prepare('SELECT COUNT(*) FROM recurrence_occurrences WHERE user_id=:user');$occurrences->execute(['user'=>$userA]);$check((int)$occurrences->fetchColumn()===1,'geração de recorrência idempotente');

    $db->prepare('INSERT INTO budgets(user_id,category_id,name,limit_amount,reference_month,created_at,updated_at) VALUES(:user,:category,:name,40000,:month,:at,:at)')->execute(['user'=>$userA,'category'=>$expenseCategory,'name'=>'Limite de teste','month'=>date('Y-m'),'at'=>$now]);$budgetId=(int)$db->lastInsertId();$db->prepare('INSERT INTO budget_alert_levels(user_id,budget_id,percentage,created_at) VALUES(:user,:budget,50,:at)')->execute(['user'=>$userA,'budget'=>$budgetId,'at'=>$now]);$budgets=BudgetService::forMonth($userA,date('Y-m'));$check($budgets[0]['spent']===35000&&$budgets[0]['reached_alerts']===[50],'gasto e alerta de orçamento');
    $db->prepare("INSERT INTO goals(user_id,name,target_amount,start_date,category_id,created_at,updated_at) VALUES(:user,'Meta de teste',100000,:date,:category,:at,:at)")->execute(['user'=>$userA,'date'=>$today,'category'=>$expenseCategory,'at'=>$now]);$goalId=(int)$db->lastInsertId();$db->prepare("INSERT INTO goal_contributions(user_id,goal_id,amount,direction,movement_date,created_at) VALUES(:user,:goal,40000,'contribution',:date,:at)")->execute(['user'=>$userA,'goal'=>$goalId,'date'=>$today,'at'=>$now]);$goal=GoalService::all($userA)[0];$check($goal['saved']===40000&&$goal['progress']===40,'progresso da meta');
    $db->prepare("INSERT INTO investments(user_id,name,type,invested_amount,market_value,application_date,created_at,updated_at) VALUES(:user,'CDB teste','cdb',50000,55000,:date,:at,:at)")->execute(['user'=>$userA,'date'=>$today,'at'=>$now]);$check(InvestmentService::all($userA)[0]['gain']===5000,'variação da carteira');
    $db->prepare("INSERT INTO assets(user_id,name,type,current_value,valuation_date,created_at,updated_at) VALUES(:user,'Veículo teste','vehicle',200000,:date,:at,:at)")->execute(['user'=>$userA,'date'=>$today,'at'=>$now]);$db->prepare("INSERT INTO liabilities(user_id,name,type,original_amount,remaining_amount,created_at,updated_at) VALUES(:user,'Empréstimo teste','loan',80000,50000,:at,:at)")->execute(['user'=>$userA,'at'=>$now]);$patrimony=PatrimonyService::overview($userA);$check($patrimony['netWorth']===$patrimony['assetTotal']-$patrimony['liabilityTotal']&&count($patrimony['history'])===1,'patrimônio líquido e snapshot');

    $todayBr=date('d/m/Y');$todayOfx=date('Ymd').'120000';$csv=ImportService::csv("Data;Descrição;Valor;Tipo\n$todayBr;Café;12,30;D\n$todayBr;Salário;1.200,00;C\n");$mapped=ImportService::mapCsv($csv['headers'],$csv['records'],['date'=>0,'description'=>1,'amount'=>2,'type'=>3]);$check(count($mapped)===2&&$mapped[0]['amount']===1230&&$mapped[0]['type']==='expense'&&$mapped[1]['amount']===120000&&$mapped[1]['type']==='income','importação CSV localizada');
    $ofx=ImportService::ofx('<OFX><BANKTRANLIST><STMTTRN><TRNTYPE>DEBIT<DTPOSTED>'.$todayOfx.'<TRNAMT>-15.25<FITID>phase8-unique<NAME>Mercado</STMTTRN></BANKTRANLIST></OFX>');$check(count($ofx)===1&&$ofx[0]['amount']===1525&&$ofx[0]['type']==='expense','importação OFX');
    $report=ReportService::data($userA,['period'=>'current_month']);$csvReport=ReportService::csv($report);$pdfReport=ReportService::pdf($report);preg_match('/startxref\n(\d+)/',$pdfReport,$xref);$check(str_starts_with($csvReport,"\xEF\xBB\xBF")&&str_starts_with($pdfReport,'%PDF-1.4')&&isset($xref[1])&&substr($pdfReport,(int)$xref[1],4)==='xref','exportação de relatório CSV/PDF');$unsafeReport=$report;$unsafeReport['categories']=[['name'=>'=1+1','total'=>1]];$unsafeReport['category_share']=[100.0];$check(str_contains(ReportService::csv($unsafeReport),"'=1+1"),'proteção contra fórmula CSV');

    $path=BackupService::latestPath($userA);if(is_file($path))throw new RuntimeException('O teste não deve substituir um backup existente.');$backupPaths[]=$path;$backup=BackupService::create($userA);try{BackupService::restore((string)file_get_contents($backup),$userB);throw new RuntimeException('O backup foi aceito para outro usuário.');}catch(RuntimeException $error){$check(str_contains($error->getMessage(),'não pertence'),'backup restrito ao proprietário');}$db->prepare("UPDATE transactions SET description='Alterada' WHERE user_id=:user")->execute(['user'=>$userA]);$db->prepare("INSERT INTO accounts(user_id,name,type,opening_date,created_at,updated_at) VALUES(:user,'Extra','cash',:date,:at,:at)")->execute(['user'=>$userA,'date'=>$today,'at'=>$now]);BackupService::restore((string)file_get_contents($backup),$userA);$restored=$db->prepare("SELECT description,account_id FROM transactions WHERE user_id=:user AND description='Despesa de teste'");$restored->execute(['user'=>$userA]);$restoredRow=$restored->fetch();$accountCount=$db->prepare('SELECT COUNT(*) FROM accounts WHERE user_id=:user');$accountCount->execute(['user'=>$userA]);$otherCount=$db->prepare('SELECT COUNT(*) FROM accounts WHERE user_id=:user');$otherCount->execute(['user'=>$userB]);$check($restoredRow['description']==='Despesa de teste'&&(int)$accountCount->fetchColumn()===1&&(int)$otherCount->fetchColumn()===1,'restauração isolada entre usuários');$check((int)$restoredRow['account_id']!==$accountA,'remapeamento de IDs no backup');

    $corrupt=json_decode((string)file_get_contents($backup),true,128,JSON_THROW_ON_ERROR);$corrupt['datasets']['transactions'][0]['description']='Manipulada';try{BackupService::restore(json_encode($corrupt,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$userA);throw new RuntimeException('Checksum inválido foi aceito.');}catch(RuntimeException $error){$check(str_contains($error->getMessage(),'integridade'),'rejeição de backup corrompido');}
    $manifest=json_decode((string)file_get_contents(dirname(__DIR__).'/public/manifest.webmanifest'),true,32,JSON_THROW_ON_ERROR);$worker=(string)file_get_contents(dirname(__DIR__).'/public/sw.js');$check($manifest['display']==='standalone'&&str_contains($worker,"if (request.mode === 'navigate')")&&!str_contains($worker,'caches.put(request, response.clone())'),'PWA sem cache de páginas privadas');$css=(string)file_get_contents(dirname(__DIR__).'/public/assets/css/app.css');$check(str_contains($css,'.skip-link')&&str_contains($css,'@media(max-width:760px)')&&str_contains($css,'prefers-reduced-motion'),'acessibilidade e responsividade');
    $formInsert=$db->prepare("INSERT INTO transactions(user_id,account_id,category_id,type,description,amount,transaction_date,due_date,status,payment_method,notes,created_at,updated_at) VALUES(:user,:account,:category,:type,:description,:amount,:date,:due,:status,:method,:notes,:created,:now)");
    $currentAccount=$db->query("SELECT id FROM accounts WHERE user_id=$userA LIMIT 1")->fetchColumn();
    $currentCategory=$db->query("SELECT id FROM categories WHERE user_id=$userA AND type='expense' LIMIT 1")->fetchColumn();
    $formInsert->execute(['user'=>$userA,'account'=>(int)$currentAccount,'category'=>(int)$currentCategory,'type'=>'expense','description'=>'Inserida pelo formulário','amount'=>1590,'date'=>$today,'due'=>null,'status'=>'pending','method'=>'','notes'=>'','created'=>$now,'now'=>$now]);
    $saved=$db->query("SELECT description,amount,status FROM transactions WHERE description='Inserida pelo formulário'")->fetch();
    $check($saved['description']==='Inserida pelo formulário'&&$saved['amount']===1590&&$saved['status']==='pending','insert de movimentação com created_at/updated_at');
    echo "OK — $checks verificações passaram.\n";
} catch(Throwable $error) {
    fwrite(STDERR,$error->getMessage()."\n");$exitCode=1;
} finally {
    foreach($backupPaths as $path)if(is_file($path))@unlink($path);
}
if(isset($exitCode))exit($exitCode);
