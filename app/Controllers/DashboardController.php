<?php
declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        $user = require_auth();
        RecurrenceService::generateForUser((int)$user['id']);
        $db=Database::connection();$uid=(int)$user['id'];$stmt=$db->prepare('SELECT currency, theme FROM user_settings WHERE user_id = :id');$stmt->execute(['id'=>$uid]);$settings=$stmt->fetch();
        $data=DashboardService::data($uid,$_GET);
        render('dashboard/index',['title'=>'Visão geral','user'=>$user,'settings'=>$settings]+$data);
    }
}
