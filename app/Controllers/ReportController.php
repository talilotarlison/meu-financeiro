<?php
declare(strict_types=1);

final class ReportController
{
    public function index(): void
    {
        $user=require_auth();$data=ReportService::data((int)$user['id'],$_GET);$settings=Database::connection()->prepare('SELECT currency,theme FROM user_settings WHERE user_id=:user');$settings->execute(['user'=>$user['id']]);
        render('reports/index',['title'=>'Relatórios','settings'=>$settings->fetch()]+$data);
    }

    public function exportCsv(): void { $this->export('csv'); }
    public function exportPdf(): void { $this->export('pdf'); }

    private function export(string $format): void
    {
        $user=require_auth();$data=ReportService::data((int)$user['id'],$_GET);$period=$data['effectiveFrom'].'_'.$data['effectiveTo'];
        header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');
        if($format==='csv'){$content=ReportService::csv($data);header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="relatorio-'.$period.'.csv"');echo $content;exit;}
        $content=ReportService::pdf($data);header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="relatorio-'.$period.'.pdf"');header('Content-Length: '.strlen($content));echo $content;exit;
    }
}
