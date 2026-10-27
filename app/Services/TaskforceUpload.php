<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class TaskforceUpload
{
    public function import(UploadedFile $file, string $month, string $username): array
    {
        $reader=IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(false);
        if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
            // Auto-detection can mistake letters for delimiters in a one-column list.
            $handle=fopen($file->getRealPath(),'rb');
            try { $headerLine=(string)fgets($handle); } finally { fclose($handle); }
            $delimiter=',';
            foreach([',',';',"\t"] as $candidate) {
                $names=array_map(fn($value)=>preg_replace('/[^a-z0-9]/','',strtolower((string)$value)),str_getcsv($headerLine,$candidate));
                if(array_intersect($names,['idoutlet','iddigipos'])!==[]) { $delimiter=$candidate; break; }
            }
            $reader->setDelimiter($delimiter);
        }
        $info=$reader->listWorksheetInfo($file->getRealPath())[0]??null;
        if (!$info || $info['totalRows']>10001) $this->invalid('Maksimal 10.000 outlet per file.');
        if ($info['totalColumns']>60) $this->invalid('Maksimal 60 kolom per file taskforce.');
        $reader->setLoadSheetsOnly($info['worksheetName']);
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell($columnAddress, $row, $worksheetName='')
            {
                return Coordinate::columnIndexFromString($columnAddress)<=60 && $row<=10001;
            }
        });
        $book=$reader->load($file->getRealPath());
        $ids=[];
        $rows=[];
        $headers=[];
        $duplicates=0;
        try {
            $sheet=$book->getSheet(0);
            $idColumn=null;
            for($column=1;$column<=$info['totalColumns'];$column++) {
                $cell=$sheet->getCell(Coordinate::stringFromColumnIndex($column).'1');
                if($cell->getDataType()==='f') $this->invalid('Header harus berupa teks, bukan rumus.');
                $header=trim((string)$cell->getFormattedValue());
                if($header==='' || mb_strlen($header)>200) $this->invalid('Setiap kolom harus memiliki header, maksimal 200 karakter.');
                $headers[]=$header;
                $normalized=preg_replace('/[^a-z0-9]/','',strtolower($header));
                if(in_array($normalized,['idoutlet','iddigipos'],true)) {
                    if($idColumn!==null) $this->invalid('Gunakan satu kolom ID Outlet saja.');
                    $idColumn=$column;
                }
            }
            if($idColumn===null) $this->invalid('File harus memiliki satu kolom berjudul ID Outlet atau ID Digipos. Urutan kolom lainnya bebas.');
            for($row=2;$row<=$info['totalRows'];$row++) {
                $cell=$sheet->getCell(Coordinate::stringFromColumnIndex($idColumn).$row);
                if ($cell->getDataType()==='f') $this->invalid("Baris {$row}: ID outlet harus berupa nilai, bukan rumus.");
                $value=$cell->getValue();
                if(is_float($value) && floor($value)!==$value) $this->invalid("Baris {$row}: ID outlet tidak boleh berupa pecahan.");
                $id=is_float($value) ? sprintf('%.0f',$value) : trim((string)$value);
                $values=[];
                for($column=1;$column<=count($headers);$column++) {
                    $current=$sheet->getCell(Coordinate::stringFromColumnIndex($column).$row);
                    if($current->getDataType()==='f') $this->invalid("Baris {$row}: ubah rumus menjadi nilai sebelum upload.");
                    $display=(string)$current->getFormattedValue();
                    if(mb_strlen($display)>5000) $this->invalid("Baris {$row}: maksimal 5.000 karakter per sel.");
                    $values[]=$display;
                }
                if($id==='') {
                    if(collect($values)->contains(fn($value)=>trim($value)!=='')) $this->invalid("Baris {$row}: ID Outlet wajib diisi.");
                    continue;
                }
                if(strlen($id)>50 || !preg_match('/^[A-Za-z0-9_-]+$/',$id)) $this->invalid("Baris {$row}: format ID outlet tidak valid. Simpan ID sebagai teks.");
                if(isset($ids[$id])) { $duplicates++; continue; }
                $ids[$id]=true;
                $rows[$id]=['id_outlet'=>$id,'cells'=>json_encode($values,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'row_position'=>$row];
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
        $uploadId=DB::transaction(function() use($file,$month,$username,$ids,$headers,$rows) {
            $now=now();
            $id=DB::table('taskforce_uploads')->insertGetId([
                'period_month'=>$month,'filename'=>mb_substr(basename($file->getClientOriginalName()),0,255),
                'outlet_count'=>count($ids),'username'=>$username,'created_at'=>$now,'updated_at'=>$now,
                'headers'=>json_encode($headers,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),
            ]);
            foreach(array_chunk(array_values($rows),100) as $chunk) DB::table('taskforce_upload_outlets')->insert(array_map(fn($row)=>['upload_id'=>$id]+$row,$chunk));
            return $id;
        });
        return ['upload_id'=>$uploadId,'count'=>count($ids),'duplicates'=>$duplicates];
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['taskforce_file'=>$message]);
    }
}
