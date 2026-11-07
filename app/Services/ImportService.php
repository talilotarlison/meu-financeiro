<?php
declare(strict_types=1);

final class ImportService
{
    public static function ofx(string $content): array
    {
        $rows=[];preg_match_all('/<STMTTRN>(.*?)(?:<\/STMTTRN>|(?=<STMTTRN>|<\/BANKTRANLIST>))/is',$content,$matches);foreach(array_slice($matches[1],0,500) as $block){$tag=static function(string $name)use($block):string{preg_match('/<'.preg_quote($name,'/').'>\s*([^<\r\n]+)/i',$block,$m);return trim($m[1]??'');};$date=substr(preg_replace('/\D/','',$tag('DTPOSTED'))??'',0,8);$amount=self::signedAmount($tag('TRNAMT'));$description=trim($tag('NAME').' '.$tag('MEMO'));$description=mb_substr(preg_replace('/\s+/u',' ',$description)??'',0,140);if(strlen($date)!==8||$amount===null||$amount===0||$description==='')continue;$iso=substr($date,0,4).'-'.substr($date,4,2).'-'.substr($date,6,2);$fitid=mb_substr($tag('FITID'),0,120);$sourceId='ofx:'.($fitid!==''?$fitid:hash('sha256',$iso.'|'.$amount.'|'.$description));$rows[]=['date'=>$iso,'type'=>$amount<0?'expense':'income','amount'=>abs($amount),'description'=>$description,'source_id'=>$sourceId];}return $rows;
    }

    public static function csv(string $content): array
    {
        $content=preg_replace('/^\xEF\xBB\xBF/','',$content)??$content;$firstLine=strtok($content,"\r\n")?:'';$delimiters=[';'=>substr_count($firstLine,';'),','=>substr_count($firstLine,','),"\t"=>substr_count($firstLine,"\t")];arsort($delimiters);$delimiter=(string)array_key_first($delimiters);$stream=fopen('php://temp','r+');fwrite($stream,$content);rewind($stream);$headers=fgetcsv($stream,0,$delimiter,'"','\\');if(!$headers||count($headers)<2)throw new RuntimeException('O CSV precisa ter cabeçalhos e colunas separadas.');$headers=array_map(static fn($h)=>trim((string)$h),$headers);$records=[];while(($row=fgetcsv($stream,0,$delimiter,'"','\\'))!==false){if(count($records)>=500)break;if(count(array_filter($row,fn($v)=>trim((string)$v)!==''))===0)continue;$records[]=array_map(static fn($v)=>trim((string)$v),$row);}fclose($stream);if(!$records)throw new RuntimeException('O arquivo CSV não contém lançamentos para importar.');return ['headers'=>$headers,'records'=>$records];
    }

    public static function mapCsv(array $headers,array $records,array $mapping): array
    {
        $indices=[];foreach(['date','description','amount'] as $key){$idx=filter_var($mapping[$key]??null,FILTER_VALIDATE_INT);if($idx===false||$idx<0||$idx>=count($headers))throw new RuntimeException('Mapeie as colunas de data, descrição e valor.');$indices[$key]=$idx;}$typeIndex=isset($mapping['type'])&&$mapping['type']!==''?(int)$mapping['type']:null;if($typeIndex!==null&&($typeIndex<0||$typeIndex>=count($headers)))$typeIndex=null;$rows=[];foreach($records as $row){$date=self::date((string)($row[$indices['date']]??''));$description=trim((string)($row[$indices['description']]??''));$signed=self::signedAmount((string)($row[$indices['amount']]??''));if(!$date||$description===''||$signed===null||$signed===0)continue;$typeValue=$typeIndex!==null?mb_strtolower(trim((string)($row[$typeIndex]??''))):'';$type=self::type($typeValue,$signed);$description=mb_substr($description,0,140);$source='csv:'.hash('sha256',mb_strtolower($date.'|'.$type.'|'.$signed.'|'.$description));$rows[]=['date'=>$date,'type'=>$type,'amount'=>abs($signed),'description'=>$description,'source_id'=>$source];if(count($rows)>=500)break;}if(!$rows)throw new RuntimeException('Nenhuma linha válida foi encontrada com esse mapeamento.');return $rows;
    }

    public static function markDuplicates(int $userId,int $accountId,array $rows): array
    {
        $db=Database::connection();$exact=$db->prepare('SELECT 1 FROM transactions WHERE user_id=:user AND account_id=:account AND (external_id=:source OR (type=:type AND amount=:amount AND transaction_date=:date AND lower(description)=lower(:description))) LIMIT 1');$seen=[];foreach($rows as &$row){$exact->execute(['user'=>$userId,'account'=>$accountId,'source'=>$row['source_id'],'type'=>$row['type'],'amount'=>$row['amount'],'date'=>$row['date'],'description'=>$row['description']]);$fingerprint=$row['date'].'|'.$row['type'].'|'.$row['amount'].'|'.mb_strtolower($row['description']);$row['duplicate']=(bool)$exact->fetchColumn()||isset($seen[$fingerprint]);$seen[$fingerprint]=true;}unset($row);return $rows;
    }

    private static function date(string $value): ?string
    {
        $value=trim($value);if(preg_match('/^\d{8}$/',$value))$value=substr($value,0,4).'-'.substr($value,4,2).'-'.substr($value,6,2);elseif(preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$value)){$parts=explode('/',$value);$value=$parts[2].'-'.$parts[1].'-'.$parts[0];}elseif(preg_match('/^\d{2}-\d{2}-\d{4}$/',$value)){$parts=explode('-',$value);$value=$parts[2].'-'.$parts[1].'-'.$parts[0];}return FinancialService::validDate($value)?$value:null;
    }
    private static function signedAmount(string $value): ?int
    {
        $value=trim(str_replace(['R$',' ',' '], '',$value));if($value==='')return null;$negative=str_starts_with($value,'-')||str_ends_with($value,'-')||str_starts_with($value,'(');$value=trim($value,'()-+');$comma=strrpos($value,',');$dot=strrpos($value,'.');if($comma!==false&&$dot!==false){if($comma>$dot){$value=str_replace('.','',$value);$value=str_replace(',','.',$value);}else $value=str_replace(',','',$value);}elseif($comma!==false){$fraction=substr($value,$comma+1);$value=strlen($fraction)===3?str_replace(',','',$value):str_replace(',','.',$value);}elseif(substr_count($value,'.')>1||($dot!==false&&strlen(substr($value,$dot+1))===3))$value=str_replace('.','',$value);if(!preg_match('/^\d+(?:\.\d{1,2})?$/',$value))return null;$cents=(int)round((float)$value*100);return $negative?-$cents:$cents;
    }
    private static function type(string $text,int $signed): string
    {
        if(in_array($text,['income','credit','cr','c','credito','crédito','receita','deposit'],true))return 'income';if(in_array($text,['expense','debit','db','d','debito','débito','despesa','withdrawal'],true))return 'expense';return $signed<0?'expense':'income';
    }
}
