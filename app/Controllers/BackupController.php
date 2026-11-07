<?php
declare(strict_types=1);

final class BackupController
{
    public function index(): void
    {
        $user=require_auth();$uid=(int)$user['id'];render('backup/index',['title'=>'Backup e restauração','lastBackup'=>BackupService::latestDate($uid),'errors'=>$_SESSION['_errors']??[],'notice'=>$_SESSION['_notice']??null]);unset($_SESSION['_errors'],$_SESSION['_notice']);
    }
    public function create(): void
    {
        $user=require_auth();verify_csrf();try{BackupService::create((int)$user['id']);$_SESSION['_notice']='Backup da sua conta criado com sucesso.';}catch(Throwable $error){$_SESSION['_errors']=[$error->getMessage()];}redirect('/backup');
    }
    public function download(): void
    {
        $user=require_auth();$path=BackupService::latestPath((int)$user['id']);if(!is_file($path)){try{$path=BackupService::create((int)$user['id']);}catch(Throwable){http_response_code(500);exit('Não foi possível gerar o backup.');}}header('Content-Type: application/json; charset=UTF-8');header('Content-Disposition: attachment; filename="meu-financeiro-backup.json"');header('Content-Length: '.filesize($path));header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');readfile($path);exit;
    }
    public function restore(): void
    {
        $user=require_auth();verify_csrf();if(($_POST['confirm_restore']??'')!=='yes'){$this->fail('Marque a confirmação para substituir os dados atuais desta conta.');}$file=$_FILES['backup_file']??null;if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']<1||$file['size']>25*1024*1024)$this->fail('Selecione um arquivo de backup JSON de até 25 MB.');$content=file_get_contents($file['tmp_name']);if($content===false)$this->fail('Não foi possível ler o arquivo enviado.');try{BackupService::restore($content,(int)$user['id']);$_SESSION['_notice']='Os dados desta conta foram restaurados.';}catch(Throwable $error){$this->fail($error->getMessage());}redirect('/backup');
    }
    private function fail(string $message): never {$_SESSION['_errors']=[$message];redirect('/backup');}
}
