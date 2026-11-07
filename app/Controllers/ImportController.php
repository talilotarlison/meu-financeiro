<?php
declare(strict_types=1);

final class ImportController
{
    public function index(): void
    {
        $user=require_auth();$uid=(int)$user['id'];$db=Database::connection();$stmt=$db->prepare('SELECT id,name FROM accounts WHERE user_id=:user AND is_active=1 ORDER BY name');$stmt->execute(['user'=>$uid]);$accounts=$stmt->fetchAll();$stage=$_SESSION['financial_import']??null;if($stage&&($stage['owner']??0)!==$uid){unset($_SESSION['financial_import']);$stage=null;}render('imports/index',['title'=>'Importar movimentações','accounts'=>$accounts,'stage'=>$stage,'errors'=>$_SESSION['_errors']??[],'notice'=>$_SESSION['_notice']??null]);unset($_SESSION['_errors'],$_SESSION['_notice']);
    }
    public function upload(): void
    {
        $user=require_auth();verify_csrf();$uid=(int)$user['id'];$account=(int)($_POST['account_id']??0);$stmt=Database::connection()->prepare('SELECT id FROM accounts WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$account,'user'=>$uid]);if(!$stmt->fetchColumn()){$this->fail('Escolha uma conta ativa sua.');}
        $file=$_FILES['statement']??null;if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']<1||$file['size']>3*1024*1024)$this->fail('Selecione um arquivo OFX ou CSV de até 3 MB.');$content=file_get_contents($file['tmp_name']);if($content===false)$this->fail('Não foi possível ler o arquivo.');$extension=mb_strtolower(pathinfo((string)$file['name'],PATHINFO_EXTENSION));
        try{if($extension==='ofx'){$rows=ImportService::ofx($content);if(!$rows)throw new RuntimeException('Não foram encontrados lançamentos OFX válidos.');$rows=ImportService::markDuplicates($uid,$account,$rows);$_SESSION['financial_import']=['owner'=>$uid,'account'=>$account,'rows'=>$rows,'stage'=>'preview'];}
            elseif($extension==='csv'){$csv=ImportService::csv($content);$_SESSION['financial_import']=['owner'=>$uid,'account'=>$account,'headers'=>$csv['headers'],'records'=>$csv['records'],'stage'=>'map'];}
            else throw new RuntimeException('Formato não suportado. Envie OFX ou CSV.');
        }catch(Throwable $error){$this->fail($error->getMessage());}redirect('/imports');
    }
    public function prepare(): void
    {
        $user=require_auth();verify_csrf();$stage=$_SESSION['financial_import']??null;if(!$stage||($stage['owner']??0)!==(int)$user['id']||($stage['stage']??'')!=='map')$this->fail('A etapa de mapeamento expirou. Envie o arquivo novamente.');try{$rows=ImportService::mapCsv($stage['headers'],$stage['records'],$_POST);$rows=ImportService::markDuplicates((int)$user['id'],(int)$stage['account'],$rows);$_SESSION['financial_import']=['owner'=>(int)$user['id'],'account'=>(int)$stage['account'],'rows'=>$rows,'stage'=>'preview'];}catch(Throwable $error){$this->fail($error->getMessage());}redirect('/imports');
    }
    public function confirm(): void
    {
        $user=require_auth();verify_csrf();$uid=(int)$user['id'];$stage=$_SESSION['financial_import']??null;if(!$stage||($stage['owner']??0)!==$uid||($stage['stage']??'')!=='preview')$this->fail('A prévia expirou. Envie o arquivo novamente.');$account=(int)$stage['account'];$stmt=Database::connection()->prepare('SELECT 1 FROM accounts WHERE id=:id AND user_id=:user AND is_active=1');$stmt->execute(['id'=>$account,'user'=>$uid]);if(!$stmt->fetchColumn())$this->fail('A conta selecionada não está mais disponível.');$selected=array_map('intval',(array)($_POST['row']??[]));$rows=ImportService::markDuplicates($uid,$account,$stage['rows']);$db=Database::connection();$now=gmdate('c');$insert=$db->prepare('INSERT OR IGNORE INTO transactions(user_id,account_id,category_id,type,description,amount,transaction_date,status,external_id,notes,created_at,updated_at) VALUES(:user,:account,NULL,:type,:description,:amount,:date,:status,:source,:notes,:created,:updated)');$count=0;$db->beginTransaction();try{foreach($selected as $index){if(!isset($rows[$index])||$rows[$index]['duplicate'])continue;$row=$rows[$index];$insert->execute(['user'=>$uid,'account'=>$account,'type'=>$row['type'],'description'=>$row['description'],'amount'=>$row['amount'],'date'=>$row['date'],'status'=>$row['type']==='income'?'received':'paid','source'=>$row['source_id'],'notes'=>'Importado de arquivo OFX/CSV','created'=>$now,'updated'=>$now]);$count+=$insert->rowCount();}$db->commit();}catch(Throwable $error){$db->rollBack();throw $error;}unset($_SESSION['financial_import']);$_SESSION['_notice']="$count lançamento(s) importado(s).";redirect('/imports?done=1');
    }
    public function discard(): void { $user=require_auth();verify_csrf();$stage=$_SESSION['financial_import']??null;if($stage&&($stage['owner']??0)===(int)$user['id'])unset($_SESSION['financial_import']);redirect('/imports'); }
    private function fail(string $message): never {$_SESSION['_errors']=[$message];redirect('/imports');}
}
