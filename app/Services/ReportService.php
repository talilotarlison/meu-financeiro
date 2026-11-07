<?php
declare(strict_types=1);

final class ReportService
{
    public static function data(int $userId, array $filters): array
    {
        $data=DashboardService::data($userId,$filters);$db=Database::connection();
        $stmt=$db->prepare("SELECT name,SUM(amount) AS total FROM (SELECT COALESCE(c.name,'Sem categoria') AS name,t.amount,t.transaction_date AS event_date FROM transactions t LEFT JOIN categories c ON c.id=t.category_id AND c.user_id=t.user_id WHERE t.user_id=:user_tx AND t.type='expense' AND t.status='paid' UNION ALL SELECT COALESCE(c.name,'Sem categoria'),ii.amount,i.due_date FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id AND i.user_id=ii.user_id JOIN card_purchases p ON p.id=ii.purchase_id AND p.user_id=ii.user_id LEFT JOIN categories c ON c.id=p.category_id AND c.user_id=p.user_id WHERE ii.user_id=:user_card) expenses WHERE event_date>=:from_date AND event_date<=:to_date GROUP BY name ORDER BY total DESC");
        $stmt->execute(['user_tx'=>$userId,'user_card'=>$userId,'from_date'=>$data['effectiveFrom'],'to_date'=>$data['effectiveTo']]);$categories=$stmt->fetchAll();
        $data['categories']=$categories;$data['expense_total']=$data['totals']['expense'];$data['income_total']=$data['totals']['income'];
        $data['category_share']=array_map(fn($row)=>$data['expense_total']>0?round((int)$row['total']*100/$data['expense_total'],1):0,$categories);
        return $data;
    }

    public static function csv(array $data): string
    {
        $out=fopen('php://temp','r+');fwrite($out,"\xEF\xBB\xBF");
        $write=static function(array $row)use($out):void{fputcsv($out,array_map(static function($value):string{$value=(string)$value;$formula=preg_match('/^[\s]*[=+@]/u',$value)||preg_match('/^[\s]*-(?=[^0-9.,])/u',$value);return $formula?"'".$value:$value;},$row),';','"','\\');};
        $write(['Relatório financeiro']);$write(['Período',$data['periodLabel']]);$write(['De',$data['effectiveFrom'],'Até',$data['effectiveTo']]);$write([]);$write(['Resumo']);$write(['Receitas recebidas',self::decimal($data['income_total'])]);$write(['Despesas pagas',self::decimal($data['expense_total'])]);$write(['Resultado',self::decimal($data['income_total']-$data['expense_total'])]);$write(['Saldo no encerramento',self::decimal($data['periodBalance'])]);
        $write([]);$write(['Evolução mensal','Receitas','Despesas','Resultado']);foreach($data['months'] as $month)$write([$month['label'],self::decimal($month['income']),self::decimal($month['expense']),self::decimal($month['income']-$month['expense'])]);
        $write([]);$write(['Despesas por categoria','Valor','Participação (%)']);foreach($data['categories'] as $index=>$category)$write([$category['name'],self::decimal((int)$category['total']),number_format($data['category_share'][$index],1,',','')]);
        rewind($out);$csv=stream_get_contents($out);fclose($out);return $csv;
    }

    public static function pdf(array $data): string
    {
        $lines=['RELATORIO FINANCEIRO','Periodo: '.$data['periodLabel'].' ('.$data['effectiveFrom'].' a '.$data['effectiveTo'].')','',
            'RESUMO','Receitas recebidas: '.self::decimal($data['income_total']),'Despesas pagas: '.self::decimal($data['expense_total']),'Resultado: '.self::decimal($data['income_total']-$data['expense_total']),'Saldo no encerramento: '.self::decimal($data['periodBalance']),'','EVOLUCAO MENSAL','Mes              Receitas           Despesas           Resultado'];
        foreach($data['months'] as $month)$lines[]=sprintf('%-12s %15s %17s %17s',$month['label'],self::decimal($month['income']),self::decimal($month['expense']),self::decimal($month['income']-$month['expense']));
        $lines[]='';$lines[]='DESPESAS POR CATEGORIA';$lines[]='Categoria                                      Valor       Participacao';
        foreach($data['categories'] as $i=>$category)$lines[]=sprintf('%-43s %12s %10s%%',self::short((string)$category['name'],43),self::decimal((int)$category['total']),number_format($data['category_share'][$i],1,',','.'));
        return self::buildPdf($lines);
    }

    private static function decimal(int $cents): string { return number_format($cents/100,2,',','.'); }
    private static function short(string $text,int $max): string { $text=preg_replace('/[\r\n\t]+/u',' ',$text)??'';return mb_strimwidth($text,0,$max,'…','UTF-8'); }
    private static function buildPdf(array $lines): string
    {
        $pages=array_chunk($lines,48);$objects=[];$objects[1]='<< /Type /Catalog /Pages 2 0 R >>';$fontId=3+count($pages)*2;$kids=[];
        foreach($pages as $i=>$page){$pageId=3+$i*2;$streamId=$pageId+1;$kids[]=$pageId.' 0 R';$content="BT\n/F1 10 Tf\n48 790 Td\n14 TL\n";foreach($page as $line){$text=iconv('UTF-8','Windows-1252//TRANSLIT',(string)$line);$text=str_replace(['\\','(',')',chr(13),chr(10)],['\\\\','\\(','\\)',' ',' '],$text?:'');$content.='('.$text.") Tj\nT*\n";}$content.="ET";$objects[$pageId]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.$fontId.' 0 R >> >> /Contents '.$streamId.' 0 R >>';$objects[$streamId]='<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";}
        $objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($pages).' >>';$objects[$fontId]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';ksort($objects);$pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offsets=[0];foreach($objects as $id=>$object){$offsets[$id]=strlen($pdf);$pdf.=$id." 0 obj\n".$object."\nendobj\n";}$xref=strlen($pdf);$size=max(array_keys($objects))+1;$pdf.="xref\n0 $size\n0000000000 65535 f \n";for($id=1;$id<$size;$id++)$pdf.=sprintf('%010d 00000 n ',$offsets[$id]??0)."\n";$pdf.="trailer\n<< /Size $size /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";return $pdf;
    }
}
