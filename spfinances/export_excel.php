<?php /* SPFINANCES export_excel.php, v 0.1.0 27.02.2014 */
/*
 * Copyright (c) 2014
*
* Author:		Stepan Poghosyan, <stepanpoghosyan@newspsoft.ru>
* WEB: http://newspsoft.ru/
* Description: Export the financial view
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation
*
*/

require_once $AppUI->getModuleClass('tasks');
require_once $AppUI->getModuleClass('projects');

// Set today
$df = $AppUI->getPref('SHDATEFORMAT');
$today = new CDate();
$start_date = dPgetParam($_GET, 'start_date1', '20000101');
$end_date = dPgetParam($_GET, 'end_date1', '20200101');

$hideNull 		= dPgetParam($_GET, 'hideNull', 1);
$tax 		= dPgetParam($_GET, 'tax', 0);												// 0: without, 1: with	 				Default: without
$company_id 		= dPgetParam($_GET, 'companyId', 0);
$department_id 		= dPgetParam($_GET, 'departmentId', 0);
$project_type 		= dPgetParam($_GET, 'projectType', 0);
$project_status 		= dPgetParam($_GET, '$projectStatus', 0);
$printproject 		= dPgetParam($_GET, 'project', 0);
$printmacroproject 		= dPgetParam($_GET, 'macroproject', 0);

$line=0;
$filename ="spfinances.XML";
$heding = "";
$worksheet = "";
$endworksheet = "";
$contents = "";
$rows = "";
header('Content-type: application/ms-excel');
header('Content-Disposition: attachment; filename='.$filename);
$rows .= '
<Row ss:AutoFitHeight="0">
    <Cell><Data ss:Type="String">'.$AppUI->_('Project / Task(Tax)').'</Data></Cell>
    <Cell><Data ss:Type="String">'.$AppUI->_('DATE').'</Data></Cell>
    <Cell ss:MergeAcross="1" ss:StyleID="s64"><Data ss:Type="String">'.$AppUI->_('PLAN').'</Data></Cell>
    <Cell ss:MergeAcross="3" ss:StyleID="s64"><Data ss:Type="String">'.$AppUI->_('ACTUAL').'</Data></Cell>
</Row>';

$rows .= '
   <Row ss:AutoFitHeight="0">
    <Cell ss:StyleID="s65"><Data ss:Type="String">'.$start_date."-".$end_date.'</Data></Cell>
    <Cell ss:StyleID="s65"/>
    <Cell ss:StyleID="s66"><Data ss:Type="String">'.$AppUI->_('Investment').'</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="String">'.$AppUI->_('Profit').'</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="String">'.$AppUI->_('Investment').'</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="String">'.$AppUI->_('Category').'</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="String">'.$AppUI->_('Profit').'</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="String">'.$AppUI->_('Category').'</Data></Cell>
   </Row>';
$line=2;
	
$projects = SPloadProjects($company_id, $department_id, $project_type, $project_status);
	

	foreach($projects as $project){
		$line++;
		$negLine = 0;
		$projectBudget = 0;
		$tempContents = "";
		$tempProjectContents = "";
		if(getPermission('projects', 'view', $project->project_id)) { // Check permission
			$tasks = SPloadTasksID($project->project_id, $start_date,$end_date); // Load only corresponding tasks
			if ($tasks != null) {
				$proj =	new SPCProjectBudget($project->project_id,$tax,$start_date,$end_date);
				foreach($tasks as $task) {//if($hideNull !=1  || $task->task_dynamic == 1){//|| !$budget->isNull()
					$proj->AddTask(intval($task));
				}
				foreach($proj->tasksBudget as $tsbuget) {
					// Load the budget of each tasks
					
					if(!$tsbuget->onlyTotal){
$tempContents .= '
<Row ss:AutoFitHeight="0">
 <Cell><Data ss:Type="String">'.$tsbuget->ts->task_name.'</Data></Cell>
 <Cell><Data ss:Type="String">'.$tsbuget->tsstartdate->format($df).'</Data></Cell>
 <Cell><Data ss:Type="Number">'.$tsbuget->get_task_planned_investment(1).'</Data></Cell>
 <Cell><Data ss:Type="Number">'.$tsbuget->get_task_planned_profit(1).'</Data></Cell>
 <Cell><Data ss:Type="Number">'.$tsbuget->get_task_investment(1).'</Data></Cell>
 <Cell><Data ss:Type="String">'.$tsbuget->bg->get_Investmentcategory_Name().'</Data></Cell>
 <Cell><Data ss:Type="Number">'.$tsbuget->get_task_profit(1).'</Data></Cell>
 <Cell><Data ss:Type="String">'.$tsbuget->bg->get_Profitcategory_Name().'</Data></Cell>
</Row>';
						
						$projectBudget = 1;
						$line++;
					 }//if(!$tsbuget->onlyTotal)
					 else{
							$negLine++;
					     }
					}//foreach($proj->tasksBudget as $tsbuget)
				}//if ($tasks != null)
			}//if(getPermission('projects', 'view', $project->project_id))
			if(count($tasks) != 0){
$tempProjectContents .= ' 
<Row ss:AutoFitHeight="0">
 <Cell ss:StyleID="s67"><Data ss:Type="String">'.$project->project_name.'</Data></Cell>
 <Cell ss:StyleID="s67"/>
 <Cell ss:StyleID="s67" ss:Formula="=SUM(R[1]C:R['.(count($tasks)-$negLine).']C)"><Data ss:Type="Number">0</Data></Cell>
 <Cell ss:StyleID="s67" ss:Formula="=SUM(R[1]C:R['.(count($tasks)-$negLine).']C)"><Data ss:Type="Number">0</Data></Cell>
 <Cell ss:StyleID="s67" ss:Formula="=SUM(R[1]C:R['.(count($tasks)-$negLine).']C)"><Data ss:Type="Number">0</Data></Cell>
 <Cell ss:StyleID="s67"/>
 <Cell ss:StyleID="s67" ss:Formula="=SUM(R[1]C:R['.(count($tasks)-$negLine).']C)"><Data ss:Type="Number">0</Data></Cell>
 <Cell ss:StyleID="s67"/>
</Row>';
				
			}
			else{
				//$tempContents .= "\n";
			}
			if($projectBudget == 1 ){
				$rows .= $tempProjectContents.$tempContents;
				$lineOfProject[] = $line-count($tasks)+$negLine;
			}
			else{
				$line--;
			}
		}
$line++;
$rowtotal = '';
$sum_total = '0';
foreach($lineOfProject as $lop){
	$sum_total .= '+R[-'.($line-$lop).']C';
}
$rowtotal .= '
<Row ss:AutoFitHeight="0">
	<Cell ss:MergeDown="1" ss:StyleID="s69"><Data ss:Type="String">'.$AppUI->_('TOTAL').'</Data></Cell>
	<Cell ss:StyleID="s70"/>
	<Cell ss:StyleID="s71" ss:Formula="='.$sum_total.'"><Data
	ss:Type="Number">0</Data></Cell>
	<Cell ss:StyleID="s71" ss:Formula="='.$sum_total.'"><Data
	ss:Type="Number">0</Data></Cell>
	<Cell ss:StyleID="s71" ss:Formula="='.$sum_total.'"><Data
	ss:Type="Number">0</Data></Cell>
	<Cell ss:StyleID="s71"/>
	<Cell ss:StyleID="s71" ss:Formula="='.$sum_total.'"><Data
	ss:Type="Number">0</Data></Cell>
	<Cell ss:StyleID="s71"/>
</Row>';
$line++;
$rowtotal .= '
<Row ss:AutoFitHeight="0">
<Cell ss:Index="3" ss:MergeAcross="1" ss:StyleID="s69"
		ss:Formula="=R[-1]C+R[-1]C[1]"><Data ss:Type="Number">0</Data></Cell>
		<Cell ss:MergeAcross="3" ss:StyleID="s69" ss:Formula="=R[-1]C+R[-1]C[2]"><Data
		ss:Type="Number">0</Data></Cell>
</Row>';

$rows .= $rowtotal;
	

//Stile
$heding .= '<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author>newspsoft.ru</Author>
  <LastAuthor>newspsoft.ru</LastAuthor>
  <Created>'.$today->format($df).'</Created>
  <Version>15.00</Version>
 </DocumentProperties>
 <OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office">
  <AllowPNG/>
 </OfficeDocumentSettings>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>7755</WindowHeight>
  <WindowWidth>15360</WindowWidth>
  <WindowTopX>0</WindowTopX>
  <WindowTopY>0</WindowTopY>
  <ProtectStructure>False</ProtectStructure>
  <ProtectWindows>False</ProtectWindows>
 </ExcelWorkbook>
<Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:CharSet="1" x:Family="Swiss" ss:Size="11"
    ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="s62">
   <Alignment ss:Vertical="Center"/>
   <Font ss:FontName="Times New Roman" x:CharSet="204" x:Family="Roman"
    ss:Size="11" ss:Color="#000000"/>
  </Style>
  <Style ss:ID="s64">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Times New Roman" x:CharSet="204" x:Family="Roman"
    ss:Size="11" ss:Color="#000000"/>
  </Style>
  <Style ss:ID="s65">
   <Alignment ss:Vertical="Center"/>
   <Font ss:FontName="Times New Roman" x:CharSet="204" x:Family="Roman"
    ss:Size="11" ss:Color="#000000"/>
   <Interior ss:Color="#D0CECE" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s66">
   <Alignment ss:Vertical="Center"/>
   <Font ss:FontName="Times New Roman" x:CharSet="204" x:Family="Roman"
    ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#D0CECE" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s67">
   <Alignment ss:Vertical="Center"/>
   <Font ss:FontName="Times New Roman" x:CharSet="204" x:Family="Roman"
    ss:Size="11" ss:Color="#000000"/>
   <Interior ss:Color="#BDD7EE" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s69">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Times New Roman" x:CharSet="204" x:Family="Roman"
    ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
  </Style>
  <Style ss:ID="s70">
   <Alignment ss:Vertical="Center"/>
   <Font ss:FontName="Times New Roman" x:CharSet="204" x:Family="Roman"
    ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <NumberFormat ss:Format="dd/mm/yyyy;@"/>
  </Style>
  <Style ss:ID="s71">
   <Alignment ss:Vertical="Center"/>
   <Font ss:FontName="Times New Roman" x:CharSet="204" x:Family="Roman"
    ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
  </Style>
 </Styles>';

$worksheet .= '
<Worksheet ss:Name="spfinances">
  <Table ss:ExpandedColumnCount="8" ss:ExpandedRowCount="'.$line.'" x:FullColumns="1"
   x:FullRows="1" ss:StyleID="s62" ss:DefaultRowHeight="15">
   <Column ss:StyleID="s62" ss:AutoFitWidth="0" ss:Width="232.5"/>
   <Column ss:StyleID="s62" ss:AutoFitWidth="0" ss:Width="78"/>
   <Column ss:Index="6" ss:StyleID="s62" ss:Width="58.5"/>
   <Column ss:Index="8" ss:StyleID="s62" ss:Width="58.5"/>';

$endworksheet .= '</Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <PageSetup>
    <Header x:Margin="0.3"/>
    <Footer x:Margin="0.3"/>
    <PageMargins x:Bottom="0.75" x:Left="0.7" x:Right="0.7" x:Top="0.75"/>
   </PageSetup>
   <Unsynced/>
   <Print>
    <ValidPrinterInfo/>
    <PaperSizeIndex>9</PaperSizeIndex>
    <HorizontalResolution>600</HorizontalResolution>
    <VerticalResolution>600</VerticalResolution>
   </Print>
   <Selected/>
   <TopRowVisible>78</TopRowVisible>
   <Panes>
    <Pane>
     <Number>3</Number>
     <ActiveRow>91</ActiveRow>
     <ActiveCol>7</ActiveCol>
    </Pane>
   </Panes>
   <ProtectObjects>False</ProtectObjects>
   <ProtectScenarios>False</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>
</Workbook>
';


echo $heding;echo $worksheet;echo $rows;echo $endworksheet;

//$ex= $heding + $worksheet+ $contents+$endworksheet;
//echo $ex;
?>