<?php

    /**
    * Moodle - Modular Object-Oriented Dynamic Learning Environment
    *          http://moodle.org
    * Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    * This program is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 2 of the License, or
    * (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with this program.  If not, see <http://www.gnu.org/licenses/>.
    *
    * @package    block-corporate
    * @author     Valery Fremaux <valery@valeisti.fr>
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
    * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    */

    /// Security

    if (!defined('MOODLE_INTERNAL')) die("You cannot directly invoke this script");

    /// Master controller

    $result = 0;

    $scope = optional_param('scope', 0, PARAM_INT);
    $domain = str_replace('domains_', '', $view);
    
    if (!empty($action)){
        $result = include_once('view_domain.controller.php');
    }
    
    if($result == -1){ 
        // if controller already output the screen we might jump
        return -1;
    }
    

	echo '<table width="100%" class="generaltable"><tr><td align="left">';
	// print the scopechanging 
	echo '<form name="changescopeform">';
	echo "<input type=\"hidden\" name=\"view\" value=\"domains_$domain\" />";
	echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
	$scopeoptions[0] = get_string('sharedscope', 'techproject');
	$scopeoptions[$project->id] = get_string('projectscope', 'techproject');
	choose_from_menu($scopeoptions, 'scope', $scope, '', 'forms[\'changescopeform\'].submit();');
	echo '</from></td><td align="right">';
	echo "<a href=\"view.php?view=domains_{$domain}&amp;id={$id}&amp;what=add\" >".get_string('addvalue', 'techproject').'</a>';
	echo '</td></tr></table>';

	$domainvalues = techproject_get_domain($domain, null, 'all', $scope, 'code');
	
    if(!empty($domainvalues)){
		$table->head = array(	"<b>".get_string('code', 'techproject')."</b>", 
									"<b>".get_string('label', 'techproject')."</b>",
									"<b>".get_string('description')."</b>",
		    						"<b></b>");
		$table->width = "100%";
		$table->align = array('left', 'left', 'left', 'right');
		$table->size = array('10%', '20%', '50%', '20%');
		$table->data = array();

		foreach($domainvalues as $value){
            $view = array();
			$view[] = $value->code;
			$view[] = filter_string($value->label);
			$view[] = filter_string($value->description);
			$deletestr = get_string('delete');
			$updatestr = get_string('update');
			$commands = "<a href=\"{$CFG->wwwroot}/mod/techproject/view.php?view=domains_$domain&amp;id={$id}&amp;what=update&amp;domainid=$value->id\" title=\"$updatestr\" ><img src=\"{$CFG->wwwroot}/pix/t/edit.gif\"></a>";
			$commands .= " <a href=\"{$CFG->wwwroot}/mod/techproject/view.php?view=domains_$domain&amp;id={$id}&amp;domain=$domain&amp;what=delete&amp;domainid=$value->id\" title=\"$deletestr\" ><img src=\"{$CFG->wwwroot}/pix/t/delete.gif\"></a>";
			$view[] = $commands;
			$table->data[] = $view;
		}
		print_table($table);		
    } else {
		print("<p style=\"text-align: center; font-style: italic;\">".get_string('novaluesindomain', 'techproject')."</p>");
	}
?>