<?php
declare(strict_types=1);

final class CategoryController
{
    public function index(): void
    {
        $user=require_auth();$db=Database::connection();$uid=(int)$user['id'];
        $stmt=$db->prepare("SELECT c.*,COUNT(t.id) AS usage_count FROM categories c LEFT JOIN transactions t ON t.category_id=c.id AND t.user_id=c.user_id AND t.status<>'cancelled' WHERE c.user_id=:user GROUP BY c.id ORDER BY c.type,c.is_active DESC,c.name");$stmt->execute(['user'=>$uid]);$categories=$stmt->fetchAll();
        $edit=null;if(isset($_GET['edit'])){$stmt=$db->prepare('SELECT * FROM categories WHERE id=:id AND user_id=:user');$stmt->execute(['id'=>(int)$_GET['edit'],'user'=>$uid]);$edit=$stmt->fetch()?:null;}
        render('categories/index',['title'=>'Categorias','categories'=>$categories,'edit'=>$edit,'errors'=>$_SESSION['_errors']??[]]);unset($_SESSION['_errors']);
    }
    public function save(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$uid=(int)$user['id'];$id=(int)($_POST['id']??0);$name=trim((string)($_POST['name']??''));$type=(string)($_POST['type']??'');$color=(string)($_POST['color']??'#78909c');$icon=trim((string)($_POST['icon']??'◈'));
        $errors=[];if(mb_strlen($name)<2||mb_strlen($name)>60)$errors[]='O nome precisa ter entre 2 e 60 caracteres.';if(!in_array($type,['income','expense'],true))$errors[]='Tipo de categoria inválido.';if(!preg_match('/^#[0-9a-fA-F]{6}$/',$color))$color='#78909c';if($icon==='')$icon='◈';
        $check=$db->prepare('SELECT id FROM categories WHERE user_id=:user AND type=:type AND name=:name AND id<>:id');$check->execute(['user'=>$uid,'type'=>$type,'name'=>$name,'id'=>$id]);if($check->fetchColumn())$errors[]='Já existe uma categoria com esse nome e tipo.';
        if($id){$stmt=$db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=:user AND category_id=:id AND status<>'cancelled'");$stmt->execute(['user'=>$uid,'id'=>$id]);if((int)$stmt->fetchColumn()>0){$stmt=$db->prepare('SELECT type FROM categories WHERE id=:id AND user_id=:user');$stmt->execute(['id'=>$id,'user'=>$uid]);$existingType=$stmt->fetchColumn();if($existingType!==false&&$existingType!==$type)$errors[]='Não é possível trocar o tipo de uma categoria que já possui movimentações.';}}
        if($errors){$_SESSION['_errors']=$errors;redirect($id?'/categories?edit='.$id:'/categories');}$now=gmdate('c');
        if($id){$stmt=$db->prepare('UPDATE categories SET name=:name,type=:type,color=:color,icon=:icon,updated_at=:now WHERE id=:id AND user_id=:user');$stmt->execute(['name'=>$name,'type'=>$type,'color'=>$color,'icon'=>$icon,'now'=>$now,'id'=>$id,'user'=>$uid]);}
        else{$stmt=$db->prepare('INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at) VALUES(:user,:name,:type,:color,:icon,:created,:updated)');$stmt->execute(['user'=>$uid,'name'=>$name,'type'=>$type,'color'=>$color,'icon'=>$icon,'created'=>$now,'updated'=>$now]);}redirect('/categories');
    }
    public function toggle(): void
    {
        $user=require_auth();verify_csrf();$db=Database::connection();$id=(int)($_POST['id']??0);$stmt=$db->prepare('SELECT COUNT(*) FROM transactions WHERE user_id=:user AND category_id=:id AND status<>\'cancelled\'');$stmt->execute(['user'=>$user['id'],'id'=>$id]);$usage=(int)$stmt->fetchColumn();
        if($usage>0){$_SESSION['_errors']=['Esta categoria possui movimentações. Para preservar o histórico, ela não pode ser desativada.'];redirect('/categories');}
        $stmt=$db->prepare('UPDATE categories SET is_active=1-is_active,updated_at=:at WHERE id=:id AND user_id=:user');$stmt->execute(['at'=>gmdate('c'),'id'=>$id,'user'=>$user['id']]);redirect('/categories');
    }
}
