<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class TaskforceUpload
{
    public function import(UploadedFile $file, string $month, string $username): array
    {
        $reader=IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
            // Auto-detection can mistake letters for delimiters in a one-column list.
            $handle=fopen($file->getRealPath(),'rb');
            try { $headerLine=(string)fgets($handle); } finally { fclose($handle); }
            $reader->setDelimiter(str_contains($headerLine,';') ? ';' : (str_contains($headerLine,"\t") ? "\t" : ','));
        }
        $info=$reader->listWorksheetInfo($file->getRealPath())[0]??null;
        if (!$info || $info['totalRows']>10001) $this->invalid('Maksimal 10.000 outlet per file.');
        $reader->setLoadSheetsOnly($info['worksheetName']);
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell($columnAddress, $row, $worksheetName='')
            {
                return $columnAddress==='A' && $row<=10001;
            }
        });
        $book=$reader->load($file->getRealPath());
        $ids=[];
        $duplicates=0;
        try {
            $sheet=$book->getSheet(0);
            $header=preg_replace('/[^a-z0-9]/','',strtolower((string)$sheet->getCell('A1')->getValue()));
            if (!in_array($header,['idoutlet','iddigipos'],true)) $this->invalid('Kolom A harus berjudul ID Outlet. Gunakan template taskforce.');
            for($row=2;$row<=$info['totalRows'];$row++) {
                $cell=$sheet->getCell('A'.$row);
                if ($cell->getDataType()==='f') $this->invalid("Baris {$row}: ID outlet harus berupa nilai, bukan rumus.");
                $value=$cell->getValue();
                $id=is_float($value) ? sprintf('%.0f',$value) : trim((string)$value);
                if($id==='') continue;
                if(strlen($id)>50 || !preg_match('/^[A-Za-z0-9_-]+$/',$id)) $this->invalid("Baris {$row}: format ID outlet tidak valid. Simpan ID sebagai teks.");
                if(isset($ids[$id])) $duplicates++;
                $ids[$id]=true;
            }
        } finally {
            $book->disconnectWorksheets();
        }
        if($ids===[]) $this->invalid('File tidak berisi outlet. Daftar yang sudah ada tetap digunakan.');
        $ids=array_map('strval',array_keys($ids));
        $known=[];
        foreach(array_chunk($ids,500) as $chunk) {
            foreach(DB::table('appsdumais')->whereIn('id_outlet',$chunk)->pluck('id_outlet') as $id) $known[(string)$id]=true;
        }
        $unknown=array_values(array_filter($ids,fn($id)=>!isset($known[$id])));
        if($unknown!==[]) $this->invalid('ID outlet tidak terdaftar: '.implode(', ',array_slice($unknown,0,15)).'. Seluruh file belum disimpan; perbaiki ID lalu upload ulang.');
        $uploadId=DB::transaction(function() use($file,$month,$username,$ids) {
            $now=now();
            $id=DB::table('taskforce_uploads')->insertGetId([
                'period_month'=>$month,'filename'=>mb_substr(basename($file->getClientOriginalName()),0,255),
                'outlet_count'=>count($ids),'username'=>$username,'created_at'=>$now,'updated_at'=>$now,
            ]);
            foreach(array_chunk($ids,500) as $chunk) DB::table('taskforce_upload_outlets')->insert(array_map(fn($outlet)=>['upload_id'=>$id,'id_outlet'=>$outlet],$chunk));
            return $id;
        });
        return ['upload_id'=>$uploadId,'count'=>count($ids),'duplicates'=>$duplicates];
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['taskforce_file'=>$message]);
    }
}
