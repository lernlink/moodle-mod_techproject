<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * A document generator, using XML -> XSLT transform to HTML
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */
    
    /**
    * Requires and includes
    */
    require_once('../../config.php');
    require_once($CFG->dirroot.'/mod/techproject/lib.php');
    require_once($CFG->dirroot.'/mod/techproject/locallib.php');
    
/// fixes locale for all date printing.

    setLocale(LC_TIME, substr(current_language(), 0, 2));

/// get context information

    $id = required_param('id', PARAM_INT);   // module id
    $view = optional_param('view', '', PARAM_CLEAN);   // viewed page id
    $accesskey = optional_param('accesskey', '', PARAM_TEXT);
    
    $timenow = time();
    
    // get some useful stuff...
    if (! $cm = get_record('course_modules', "id", $id)) {
        error('Course Module ID was incorrect');
    }
    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }
    if (! $project = get_record('techproject', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

	// allow anonymized access (document reading) with access key   
    if (empty($project->accesskey) || $accesskey != $project->accesskey){
	    require_login($course->id, false, $cm);
	} else {
		$COURSE = $course;
	}

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
/// check current group and change, for anyone who could
    if (!$groupmode = groupmode($course, $cm)){ // groups are being used ?
    	$currentGroupId = 0;
    } else {
        $changegroup = isset($_GET['group']) ? $_GET['group'] : -1;  // Group change requested?
        if (isguest()){ // for guests, use session
            if ($changegroup >= 0){
                $_SESSION['guestgroup'] = $changegroup;
            }
            $currentGroupId = 0 + @$_SESSION['guestgroup'];
        } else { // for normal users, change current group
            $currentGroupId = 0 + get_and_set_current_group($course, $groupmode, $changegroup);
            if (!ismember($currentGroupId , $USER->id) && !isadmin()) $USER->editmode = "off";
        }
    }

/// get all information about the current project    

    $xml = techproject_get_full_xml($project, $currentGroupId);

/// invoke XSLT transformation for making the output document

    if (phpversion() >= 5.0){
        $xsl = new XSLTProcessor();
        
        $doc = new DOMDocument();
        $xsl_sheet = $project->xslfilter;
        // $doc->load($CFG->dataroot.'/'.$course->id.'/moddata/techproject/'.$project->id.'/'.$xsl_sheet);
        $doc->load($xsl_sheet);
        $xsl->importStyleSheet($doc);
        
        
        
        $doc = DOMDocument::loadXML($xml);
        if (is_object($doc)){
            $html = $xsl->transformToXML($doc);
        } else {
            $formattedxml = htmlentities($xml, ENT_QUOTES, 'UTF-8');
            $formattedxmllines = explode("\n", $formattedxml);
            $html = "XML Generation Error";
            $html .= "<hr/><pre>";
            $i = 1;
            foreach($formattedxmllines as $line){
                $html .= $i . " " . $line."\n";
                $i++;
            }
            $html .= "</pre><hr/>";
        }
    } else {
        /*
        $arguments = array(
             '/_xml' => $xml,
        );
    
        $procesor = xslt_create();
        if ($html = xslt_process($processor, "arg:/_xml", $project->xslfilter, null, $arguments)){
        }
        else{
           $html = xslt_error($processor);
        }
        */
        notify("Php 4 implementation of XSL processing code has not been experimented.");
    }

/// deliver the document

    header("Content-Type:text/html\n\n");
    echo $html;
?>