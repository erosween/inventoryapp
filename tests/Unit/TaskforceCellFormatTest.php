<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
use App\Support\TaskforceCellFormat;
class TaskforceCellFormatTest extends TestCase
{
 public function test_quantities_are_grouped_without_changing_identity_or_existing_formats():void
 {
  foreach ([['ST PV MTD','1234567','1.234.567'],['ST SA FM1','1234','1.234'],['Gap MoM','-12000','-12.000'],['Qty','0','0'],['Qty','12345678901234567890','12.345.678.901.234.567.890'],['ID Outlet','1201133850','1201133850'],['Nomor HP','081234567890','081234567890'],['No','1234','1234'],['Tanggal','20261028','20261028'],['%MOM','1200','1200'],['Qty','1.234','1.234'],['Qty','12,5','12,5'],['Nama Outlet','1234','1234']] as [$header,$input,$expected]) $this->assertSame($expected,TaskforceCellFormat::display($header,$input));
 }
 public function test_export_uses_real_numbers_for_quantities_without_changing_identifiers_or_formulas():void
 {
  $this->assertSame(-2,TaskforceCellFormat::forExport('Qty PV','-2'));
  $this->assertSame('1201133850',TaskforceCellFormat::forExport('ID Outlet','1201133850'));
  $this->assertSame('081234567890',TaskforceCellFormat::forExport('Nomor HP','081234567890'));
  $this->assertSame('=1+1',TaskforceCellFormat::forExport('Qty','=1+1'));
  $this->assertSame('12345678901234567890',TaskforceCellFormat::forExport('Qty','12345678901234567890'));
 }

}
