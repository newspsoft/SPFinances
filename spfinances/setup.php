<?php
/* SPFINANCES setup.php, v 0.1.0 01.09.2013 */
/*
* Copyright (c) 2014 
*
* Description:	Setup page of the SPFinances module.
*
* Author:		Stepan Poghosyan, <stepanpoghosyan@newspsoft.ru>
* WEB: http://newspsoft.ru/
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation
*
*/
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

/**
 *  Name: SPFinances
 *  Directory: spfinances
 *  Version 1.0
 *  Type: user
 *  UI Name: spfinances
 *  UI Icon: ?
 */

$config = array();
$config['mod_name'] = 'SPFinances';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'spfinances';
$config['mod_setup_class'] = 'CSetupSPFinances';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'SPFinances';
$config['mod_ui_icon'] = 'folder5.png';
$config['mod_description'] = 'This module add budget improvements';
$config['mod_config'] = false;

require_once 'spfinances.class.php';
if (@$a == 'setup') {
	echo dPshowModuleConfig($config);
}


class CSetupSPFinances {

  function configure() { return true; }

  function remove() {
		$dbprefix = dPgetConfig('dbprefix', '');
		$success = 1;
/*
		$bulk_sql[] = "DROP TABLE `{$dbprefix}budget`";
		foreach ($bulk_sql as $s) {
			db_exec($s);
			if (db_error())
				$success = 0;
		} */
		return $success; 
	}
  
	function upgrade($old_version) { return true; }

	function install() { 
		$dbprefix = dPgetConfig('dbprefix', '');
		$success = 1;
		$bulk_sql[] = "
                  CREATE TABLE IF NOT EXISTS `".$dbprefix."spbudget` (
				  `budget_id` int(11) NOT NULL AUTO_INCREMENT,
				  `task_id` int(11) NOT NULL DEFAULT '0',
				  `Tax` decimal(4,2) NOT NULL DEFAULT '0',
				  `display_tax` tinyint(1) NOT NULL DEFAULT '0',
				  `only_financial` tinyint(1) NOT NULL DEFAULT '0',
				  `investment` decimal(15,2) DEFAULT '0',
				  `investment_id` int(11) NOT NULL DEFAULT '0',
				  `profit` decimal(15,2) DEFAULT '0',
				  `profit_id` int(11) NOT NULL DEFAULT '0',
				   PRIMARY KEY (`budget_id`)
				) ENGINE=MyISAM  AUTO_INCREMENT=7 ;";
		
		$bulk_sql[1] = "
  				CREATE TABLE IF NOT EXISTS `".$dbprefix."spbudget_typ` (
  				`id` int(11) NOT NULL auto_increment,
  				`parent_id` int(11) NOT NULL default '0',
  				`investment` tinyint(1) NOT NULL DEFAULT '0',
  				`title` varchar(255) NOT NULL default '',
 				`position` int(11) NOT NULL default '0',
 				 PRIMARY KEY  (`id`)
				) ENGINE=MyISAM  AUTO_INCREMENT=7 ;";
			foreach ($bulk_sql as $s) {
                  db_exec($s);
                  
                  if (db_error()) {
                        $success = 0;
                  }
            }
            //ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;
            //Updete from
            if($success) 
            {
            	$q = new DBQuery;
            	$q->addTable('budget');
            	$q->addQuery('*');
            	$rows = $q->loadList();
            	foreach ($rows as $row)
            	{
            		$bg = new SPCBudget();
            		$bg->budget_id = $row[budget_id];
            		$bg->task_id = $row[task_id];
            		$bg->only_financial = $row[only_financial];
            		$bg->Tax = $row[Tax];
            		$bg->display_tax = $row[display_tax];
            		
            		$inv = $row[equipment_investment];
            		if($inv>0)
            			$inv=-1*$inv;
            		
            		if($row[intangible_investment]>0)
            			$inv=$inv-$row[intangible_investment];
            		else 
            			$inv=$inv+$row[intangible_investment];
            		
            		if($row[service_investment]>0)
            			$inv=$inv-$row[service_investment];
            		else
            			$inv=$inv+$row[service_investment];
            		$bg->investment = $inv;
            		$bg->investment_id = 1;
            		
            		$pro = $row[equipment_operation];
            		if($pro<0)
            			$pro=-1*$pro;
            		
            		if($row[intangible_operation]<0)
            			$pro=$pro-$row[intangible_operation];
            		else
            			$pro=$pro+$row[intangible_operation];
            		
            		if($row[service_operation]<0)
            			$pro=$pro-$row[service_operation];
            		else
            			$pro=$pro+$row[service_operation];
            		
            		$bg->profit = $pro;
            		$bg->profit_id = 2;
            		
            		$bg->store();
            	}
            	//$q = new DBQuery;
            	//$q->addTable('spbudget_typ');
            }
		return $success; 
	}
}

/*
 $bulk_sql[1] = "
  CREATE TABLE IF NOT EXISTS `".$dbprefix."spbudget_typ` (
  `id` int(11) NOT NULL auto_increment,
  `sub_id` int(11) NOT NULL default '0',
  `investment` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL default '',
  `position` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;";

 * 
 * function install() {
		$ok = true;
		$q = new DBQuery;
		$sql = "(
			resource_id integer not null auto_increment,
			resource_name varchar(255) not null default '',
			resource_key varchar(64) not null default '',
			resource_type integer not null default 0,
			resource_note text not null default '',
			resource_max_allocation integer not null default 100,
			primary key (resource_id),
			key (resource_name),
			key (resource_type)
		)";
		$q->createTable('resources');
		$q->createDefinition($sql);
		$ok = $ok && $q->exec();
		$q->clear();

		$sql = "(
			resource_type_id integer not null auto_increment,
			resource_type_name varchar(255) not null default '',
			resource_type_note text,
			primary key (resource_type_id)
		)";
		$q->createTable('resource_types');
		$q->createDefinition($sql);
		$ok = $ok && $q->exec();
		$q->clear();


		$sql = "(
			resource_id integer not null default 0,
			task_id integer not null default 0,
			percent_allocated integer not null default 100,
			key (resource_id),
			key (task_id, resource_id)
		)";
		$q->createTable('resource_tasks');
		$q->createDefinition($sql);
		$ok = $ok && $q->exec();
		$q->clear();
		$q->addTable('resource_types');
		$q->addInsert('resource_type_name', 'Equipment');
		$q->exec();
		$q->addInsert('resource_type_name', 'Tool');
		$q->exec();
		$q->addInsert('resource_type_name', 'Venue');
		$ok = $ok && $q->exec();
		
		if (!$ok) {
			return false;
		}
		return null;
	}

	function remove() {
		$q = new DBQuery;
		$q->dropTable('resources');
		$q->exec();
		$q->clear();
		$q->dropTable('resource_tasks');
		$q->exec();
		$q->clear();
		$q->dropTable('resource_types');
		$q->exec();

		return null;
	}

	function upgrade($old_version) {
	  switch ($old_version) {
		case "1.0":
		  $q = new DBQuery;
		  $q->addTable('resources');
		  $q->addField('resource_key', "varchar(64) not null default ''");
		  $q->exec();
		  if (db_error()) {
			return false;
		  }
		  // FALLTHROUGH
		case "1.0.1":
		  break;
	  }
	  return true;
    }
}
*/
