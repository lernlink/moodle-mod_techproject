<?php

/**
* Master Controler for all domains
*/

/**
* Security
*/
if (!defined('MOODLE_INTERNAL')) die("You cannot directly invoke this script");

/**
* Requires and includes
*/

switch($action){
    /*************************************** adds a new company ***************************/
    case 'add':
		require_once('forms/form_domain.class.php');
		if ($data = data_submitted()){
    	// if there is some error
	        if ($data->code == '') {
	        	print_error('err_code', 'techproject', "{$CFG->wwwroot}/mod/techproject/view.php?id={$id}&amp;view=domains_$domain&amp;action=add&amp;view=domains_$domain");
	        } elseif ($data->label == '') {
	        	print_error('err_value', 'techproject', "{$CFG->wwwroot}/mod/techproject/view.php?id={$id}&amp;view=domains_$domain&amp;action=add&amp;view=domains_$domain");
	        } else {
            	//data was submitted from this form, process it
                $domainrec->projectid = $scope;
                $domainrec->domain = $domain;
                $domainrec->code = addslashes(clean_param($data->code, PARAM_ALPHANUM));
	        	$domainrec->label = addslashes(clean_param($data->label, PARAM_CLEANHTML));
	        	$domainrec->description = addslashes(clean_param($data->description, PARAM_CLEANHTML));

	            if ($DB->get_record('techproject_qualifier', array('domain' => $domain, 'code' => $data->code, 'projectid' => $scope))){
	                print_error('err_codeexists', 'techproject', "{$CFG->wwwroot}/mod/techproject/view.php?id={$id}&amp;action=add&amp;view=domains_$domain");
	            } else {
	                if (!$DB->insert_record('techproject_qualifier', $domainrec)){
	                    print_error('errorinsertqualifier', 'techproject');
	                }
	            	redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$id}&view=domains_{$domain}");
	            }
	        }
		} else {
		    $default->code = 'XXX';
		    $default->label = '';
		    $default->description = '';
			$newdomain = new Domain_Form($domain, $default, "{$CFG->wwwroot}/mod/techproject/view.php?id={$id}&what=add");    
			$newdomain->display();
			return -1;
		}
        break;
    /********************************** Updates a domain value **************************************/
    case 'update':
    	$domainid = required_param('domainid', PARAM_INT);

		require_once('forms/form_domain.class.php');

		// Check the company
	    if (!$domainrec = $DB->get_record('techproject_qualifier', array('id' => $domainid))) {
	        print_error('errorinvalidedoomainid', 'techproject');
	    }

		// data was submitted from this form, process it
    	if ($data = data_submitted()){
        	$domainrec->id = $domainid;
            // $domainrec->projectid = 0;
            $domainrec->domain = $domain;
            $domainrec->code = clean_param($data->code, PARAM_ALPHANUM);
        	$domainrec->label = addslashes(clean_param($data->label, PARAM_CLEANHTML));
        	$domainrec->description = addslashes(clean_param($data->description, PARAM_CLEANHTML));
        	if (!$DB->update_record('techproject_qualifier', $domainrec)){
        	    print_error('errorupdatedomainvalue', 'techproject');
        	}
        	redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$id}&view=domains_{$domain}");
		} else {
		    //no data submitted : print the form
			$newdomain = new Domain_Form($domain, $domainrec, "{$CFG->wwwroot}/mod/techproject/view.php?id={$id}&what=update");
			$newdomain->display();
			return -1;
		}
    	break;
    /********************************** deletes domain value **************************************/
    case "delete":
    	$domainid = required_param('domainid', PARAM_INT);
    	if (!$DB->delete_records('techproject_qualifier', array('id' => $domainid))){
    	    print_error('errordeletedomainvalue', 'techproject');
    	}

    	break;
}	