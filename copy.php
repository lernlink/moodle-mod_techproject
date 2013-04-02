<?php // $Id: copy.php,v 1.3 2011-12-22 23:23:13 vf Exp $

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * This screen gives access to copy operations.
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    if (!has_capability('mod/techproject:manage', $context)){
        print_error(get_string('notateacher','techproject'));
        return;
    }
    
/// get groups, will be usefull at many locations

    $groups = get_groups($course->id);
    
    if ($work == 'docopy'){
        function protectTextRecords(&$aRecord, $fieldList){
            $fields = explode(",", $fieldList);
            foreach($fields as $aField){
                if (isset($aRecord->$aField))
                    $aRecord->$aField = str_replace("'", "\\'", $aRecord->$aField);
            }
        }
        /**
        * @return an array for all copied ids translations so foreign keys can be fixed
        */    
        function unitCopy($project, $from, $to, $what, $detail = false){
            $copied = array();
    
            foreach(array_keys($what) as $aDatatable){
                
                // skip unchecked entites for copying
                if(!$what[$aDatatable]) continue;
    
                echo '<tr><td align="left">' . get_string('copying', 'techproject') . ' ' . get_string("{$aDatatable}s", 'techproject') . '...';
                delete_records("techproject_$aDatatable", 'projectid', $project->id, 'groupid', $to);
                if($records = get_records_select("techproject_$aDatatable", "projectid = $project->id AND groupid = $from")){
    
                    // copying each record into target recordset
                    if ($detail)
                        echo '<br/><span class="smalltechnicals">&nbsp&nbsp;&nbsp;copying '. count($records) . " from $aDatatable</span>";
                    
                    foreach($records as $aRecord){
                        $id = $aRecord->id;
                        if ($detail)
                            echo '<br/><span class="smalltechnicals">&nbsp&nbsp;&nbsp;copying item : ['. $id . '] ' . @$aRecord->abstract . '</span>';
                        $aRecord->id = 0;
                        $aRecord->groupid = $to;
    
                        protectTextRecords($aRecord, 'title,abstract,rationale,description,environement,organisation,department');
                        
                        // unassigns users from entites in copied entities (not relevant context)
                        if (isset($aRecord->assignee)){
                            $aRecord->assignee = 0;
                        }
                        if (isset($aRecord->owner)){
                            $aRecord->owner = 0;
                        }
                        // if milestones are not copied, no way to keep milestone assignation
                        if (isset($aRecord->milestoneid) && $what['milestone'] == 0){
                            $aRecord->milestoneid = 0;
                        }
                        
                        $insertedid = insert_record("techproject_$aDatatable", $aRecord);
                        $copied[$aDatatable][$id] = $insertedid;
                    }
                }
                echo '</td><td align="right"><span class="technicals">' . get_string('done', 'techproject') . '</span></td></tr>';
            }
            
            return $copied;
        }
        
        /**
        * this function fixes in new records (given in recordSet as a comma separated list of indexes
        * some foreign key (fKey) that should shift from an unfixedValue to a fixed value in translations
        * table.
        * @return true if no errors.
        */
        function fixForeignKeys($project, $group, $table, $fKey, $translations, $recordSet){
           global $CFG;
           
           $result = 1;
           $recordList = implode(',', $recordSet);
           foreach(array_keys($translations) as $unfixedValue){
               $query = "
                   UPDATE 
                      {$CFG->prefix}techproject_{$table}
                   SET
                      $fKey = $translations[$unfixedValue]
                   WHERE
                      projectid = {$project->id} AND
                      $fKey = $unfixedValue AND
                      id IN ($recordList)
               ";
               $result = $result && execute_sql($query, false);        
            }
            return $result;
        }
        
        $from = required_param('from', PARAM_INT);
        $to = required_param('to', PARAM_RAW);
        $detail = optional_param('detail', 0, PARAM_INT);
        $what['heading'] = optional_param('headings', 0, PARAM_INT);
        $what['requirement'] = optional_param('requs', 0, PARAM_INT);
        $what['spec_to_req'] = optional_param('specstoreq', 0, PARAM_INT);
        $what['specification'] = optional_param('specs', 0, PARAM_INT);
        $what['task_to_spec'] = optional_param('taskstospec', 0, PARAM_INT);
        $what['task_to_deliv'] = optional_param('tasktodeliv', 0, PARAM_INT);
        $what['task_dependency'] = optional_param('tasktotask', 0, PARAM_INT);
        $what['milestone'] = optional_param('miles', 0, PARAM_INT);
        $what['task'] = optional_param('tasks', 0, PARAM_INT);
        $what['deliverable'] = optional_param('deliv', 0, PARAM_INT);
    
        $targets = explode(',', $to);
        print_simple_box_start('center', '70%');
        foreach($targets as $aTarget){
            // do copy data
            echo '<table width="100%">';
            $copied = unitCopy($project, $from, $aTarget, $what, $detail);
    
            // do fix some foreign keys
            echo '<tr><td align="left">' . get_string('fixingforeignkeys', 'techproject') . '...</td><td align="right">';
            if (array_key_exists('spec_to_req', $copied) && count(array_values(@$copied['spec_to_req']))){
                fixForeignKeys($project, $aTarget, 'spec_to_req', 'specid', $copied['specification'], array_values($copied['spec_to_req']));
                fixForeignKeys($project, $aTarget, 'spec_to_req', 'reqid', $copied['requirement'], array_values($copied['spec_to_req']));
            }
            if (array_key_exists('task_to_spec', $copied) && count(array_values(@$copied['task_to_spec']))){
                fixForeignKeys($project, $aTarget, 'task_to_spec', 'taskid', $copied['task'], array_values($copied['task_to_spec']));
                fixForeignKeys($project, $aTarget, 'task_to_spec', 'specid', $copied['specification'], array_values($copied['task_to_spec']));
            }
            if (array_key_exists('task_to_deliv', $copied) && count(array_values(@$copied['task_to_deliv']))){
                fixForeignKeys($project, $aTarget, 'task_to_deliv', 'taskid', $copied['task'], array_values($copied['task_to_deliv']));
                fixForeignKeys($project, $aTarget, 'task_to_deliv', 'delivid', $copied['deliverable'], array_values($copied['task_to_deliv']));
            }
            if (array_key_exists('task_dependency', $copied) && count(array_values(@$copied['task_dependency']))){
                fixForeignKeys($project, $aTarget, 'task_dependency', 'master', $copied['task'], array_values($copied['task_dependency']));
                fixForeignKeys($project, $aTarget, 'task_dependency', 'slave', $copied['task'], array_values($copied['task_dependency']));
            }
            if (array_key_exists('milestone', $copied) && array_key_exists('task', $copied) && count(array_values(@$copied['task'])) && count(array_values(@$copied['milestone']))){
                fixForeignKeys($project, $aTarget, 'task', 'milestoneid', $copied['milestone'], array_values($copied['task']));
            }
            if (array_key_exists('milestone', $copied) && array_key_exists('deliverable', $copied) && count(array_values(@$copied['deliverable'])) && count(array_values(@$copied['milestone']))){
                fixForeignKeys($project, $aTarget, 'deliverable', 'milestoneid', $copied['milestone'], array_values($copied['deliverable']));
            }
            
            // fixing fatherid values
            if(array_key_exists('specification', $copied))
                fixForeignKeys($project, $aTarget, 'specification', 'fatherid', $copied['specification'], array_values($copied['specification']));
            if(array_key_exists('requirement', $copied))
                fixForeignKeys($project, $aTarget, 'requirement', 'fatherid', $copied['requirement'], array_values($copied['requirement']));
            if(array_key_exists('task', $copied))
                fixForeignKeys($project, $aTarget, 'task', 'fatherid', $copied['task'], array_values($copied['task']));
            if(array_key_exists('deliverable', $copied))
                fixForeignKeys($project, $aTarget, 'deliverable', 'fatherid', $copied['deliverable'], array_values($copied['deliverable']));
    
            // must delete all grades in copied group
            delete_records('techproject_assessment', 'projectid', $project->id, 'groupid', $aTarget);
            echo '<span class="technicals">' . get_string('done', 'techproject') . '</td></tr>';
        }
        echo '</table>';
        print_simple_box_end();
    }

/// Setup project copy operations by defining source and destinations 

    if ($work == 'what'){
        $from = required_param('from', PARAM_INT);
        $to = required_param('to', PARAM_RAW);
        
        // check some inconsistancies here : 
        
        if (in_array($from, $to)){
            $errormessage = get_string('cannotselfcopy', 'techproject');
            $work = 'setup';
        } else {
        
    ?>
    <center>
    <?php 
    print_heading(get_string('copywhat', 'techproject'), 'center'); 
    $toArray = array();
    foreach ($to as $aTarget) $toArray[] = $groups[$aTarget]->name;
    if ($from){
        print_simple_box($groups[$from]->name . ' &gt;&gt; ' . implode(',',$toArray), 'center');
    } else {
        print_simple_box(get_string('groupless', 'techproject') . ' &gt;&gt; ' . implode(',',$toArray), 'center');
    }
    ?>
    
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['copywhatform'].work.value='confirm';
        document.forms['copywhatform'].submit();
    }
    
    function cancel(){
        document.forms['copywhatform'].work.value='setup';
        document.forms['copywhatform'].submit();
    }
    
    function formControl(entity){
        switch(entity){
            case 'requs':{
                if (!document.forms['copywhatform'].requs.checked == true){
                    document.forms['copywhatform'].spectoreq.disabled = true;
                    aDiv = document.getElementById('spectoreq_span');
                    aDiv.className = 'dithered';
                } else {
                    document.forms['copywhatform'].spectoreq.disabled = false;
                    aDiv = document.getElementById('spectoreq_span');
                    aDiv.className = '';
                }
                break;
            }
            case 'specs':{
                if (!document.forms['copywhatform'].specs.checked == true){
                    document.forms['copywhatform'].spectoreq.disabled = true;
                    document.forms['copywhatform'].tasktospec.disabled = true;
                    aDiv = document.getElementById('tasktospec_span');
                    aDiv.className = 'dithered';
                    aDiv = document.getElementById('spectoreq_span');
                    aDiv.className = 'dithered';
                } else {
                    document.forms['copywhatform'].spectoreq.disabled = false;
                    document.forms['copywhatform'].tasktospec.disabled = false;
                    aDiv = document.getElementById('tasktospec_span');
                    aDiv.className = '';
                    aDiv = document.getElementById('spectoreq_span');
                    aDiv.className = '';
                }
                break;
            }
            case 'tasks':{
                if (!document.forms['copywhatform'].tasks.checked == true){
                    document.forms['copywhatform'].tasktospec.disabled = true;
                    document.forms['copywhatform'].tasktodeliv.disabled = true;
                    document.forms['copywhatform'].tasktotask.disabled = true;
                    aDiv = document.getElementById('tasktospec_span');
                    aDiv.className = 'dithered';
                    aDiv = document.getElementById('tasktotask_span');
                    aDiv.className = 'dithered';
                    aDiv = document.getElementById('tasktodeliv_span');
                    aDiv.className = 'dithered';
                } else {
                    document.forms['copywhatform'].tasktospec.disabled = false;
                    document.forms['copywhatform'].tasktodeliv.disabled = false;
                    document.forms['copywhatform'].tasktotask.disabled = false;
                    aDiv = document.getElementById('tasktospec_span');
                    aDiv.className = '';
                    aDiv = document.getElementById('tasktotask_span');
                    aDiv.className = '';
                    aDiv = document.getElementById('tasktodeliv_span');
                    aDiv.className = '';
                }
                break;
            }
            case 'deliv':{
                if (!document.forms['copywhatform'].deliv.checked == true){
                    document.forms['copywhatform'].tasktodeliv.disabled = true;
                    aDiv = document.getElementById('tasktodeliv_span');
                    aDiv.className = 'dithered';
                } else {
                    document.forms['copywhatform'].tasktodeliv.disabled = false;
                    aDiv = document.getElementById('tasktodeliv_span');
                    aDiv.className = '';
                }
                break;
            }
        }
    }
    //]]>
    </script>
    <form name="copywhatform" action="view.php" method="post">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>"/>
    <input type="hidden" name="from" value="<?php p($from) ?>"/>
    <input type="hidden" name="to" value="<?php p(implode(',', $to)) ?>"/>
    <input type="hidden" name="work" value=""/>
    <table cellpadding="5">
    
    <tr valign="top">
        <td align="right"><b><?php print_string('what', 'techproject') ?></b></td>
        <td align="left">
            <p><b><?php print_string('entities', 'techproject') ?></b></p>
            <p><input type="checkbox" name="headings" value="1" checked="checked" /> <?php print_string('headings', 'techproject'); ?>
            <?php
            if (@$project->projectusesrequs) echo "<br/><input type=\"checkbox\" name=\"requs\" value=\"1\" checked=\"checked\" onclick=\"formControl('requs')\" /> " . get_string('requirements', 'techproject');
            if (@$project->projectusesspecs) echo "<br/><input type=\"checkbox\" name=\"specs\" value=\"1\" checked=\"checked\" onclick=\"formControl('specs')\" /> " . get_string('specifications', 'techproject');
            ?>
            <br/><input type="checkbox" name="tasks" value="1" checked="checked" onclick="formControl('tasks')" /> <?php print_string('tasks', 'techproject'); ?>
            <br/><input type="checkbox" name="miles" value="1" checked="checked"  onclick="formControl('miles')" /> <?php print_string('milestones', 'techproject'); ?>
            <?php
            if (@$project->projectusesspecs) echo "<br/><input type=\"checkbox\" name=\"deliv\" value=\"1\" checked=\"checked\"  onclick=\"formControl('deliv')\" /> " . get_string('deliverables', 'techproject');
            ?>
            </p>
    
            <p><b><?php print_string('crossentitiesmappings', 'techproject') ?></b></p>
            <?php
            if (@$project->projectusesrequs && @$project->projectusesspecs) echo "<p><input type=\"checkbox\" name=\"spectoreq\" value=\"1\" checked=\"checked\" /> <span id=\"spectoreq_span\" class=\"\"> " . get_string('spec_to_req', 'techproject') . '</span>';
            if (@$project->projectusesspecs) echo "<br/><input type=\"checkbox\" name=\"tasktospec\" value=\"1\" checked=\"checked\" /> <span id=\"tasktospec_span\" class=\"\"> ". get_string('task_to_spec', 'techproject') . '</span>';
            ?>
            <br/><input type="checkbox" name="tasktotask" value="1" checked="checked" /> <span id="tasktotask_span" class=""><?php print_string('task_to_task', 'techproject'); ?></span>
            <?php
            if (@$project->projectusesdelivs) echo "<br/><input type=\"checkbox\" name=\"tasktodeliv\" value=\"1\" checked=\"checked\" /> <span id=\"tasktodeliv_span\" class=\"\"> " . get_string('task_to_deliv', 'techproject') . '</span>';
            ?>
            </p>
        </td>
    </tr>
    </table>
    <p><input type="button" name="go_btn" value="<?php print_string('continue'); ?>" onclick="senddata()" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel'); ?>" onclick="cancel()" /></p>
    </form>
    </center>
    <?php
        }
    }

/// Copy last confirmation form 

    if ($work == 'confirm'){
        $from = required_param('from', PARAM_INT);
        $to = required_param('to', PARAM_RAW);
        $copyheadings = optional_param('headings', 0, PARAM_INT);
        $copyrequirements = optional_param('requs', 0, PARAM_INT);
        $copyspecifications = optional_param('specs', 0, PARAM_INT);
        $copymilestones = optional_param('miles', 0, PARAM_INT);
        $copytasks = optional_param('tasks', 0, PARAM_INT);
        $copydeliverables = optional_param('deliv', 0, PARAM_INT);
        $copyspectoreq = optional_param('spectoreq', 0, PARAM_INT);
        $copytasktospec = optional_param('tasktospec', 0, PARAM_INT);
        $copytasktotask = optional_param('tasktotask', 0, PARAM_INT);
        $copytasktodeliv = optional_param('tasktodeliv', 0, PARAM_INT);
    ?>
    <center>
    <?php 
    print_heading(get_string('copyconfirm', 'techproject'), 'center'); 
    print_simple_box(get_string('copyadvice', 'techproject'), 'center');
    ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['confirmcopyform'].work.value='docopy';
        document.forms['confirmcopyform'].submit();
    }
    
    function cancel(){
        document.forms['confirmcopyform'].work.value='setup';
        document.forms['confirmcopyform'].submit();
    }
    //]]>
    </script>
    <form name="confirmcopyform" action="view.php" method="post">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="from" value="<?php p($from) ?>" />
    <input type="hidden" name="to" value="<?php p($to) ?>" />
    <input type="hidden" name="headings" value="<?php p($copyheadings) ?>" />
    <input type="hidden" name="requs" value="<?php p($copyrequirements) ?>" />
    <input type="hidden" name="specs" value="<?php p($copyspecifications) ?>" />
    <input type="hidden" name="tasks" value="<?php p($copytasks) ?>" />
    <input type="hidden" name="miles" value="<?php p($copymilestones) ?>" />
    <input type="hidden" name="deliv" value="<?php p($copydeliverables) ?>" />
    <input type="hidden" name="spectoreq" value="<?php p($copyspectoreq) ?>" />
    <input type="hidden" name="tasktospec" value="<?php p($copytasktospec) ?>" />
    <input type="hidden" name="tasktotask" value="<?php p($copytasktotask) ?>" />
    <input type="hidden" name="tasktodeliv" value="<?php p($copytasktodeliv) ?>" />
    <input type="hidden" name="work" value="" />
    <input type="checkbox" name="detail" value="1" /> <?php print_string('givedetail', 'techproject') ?>
    <input type="button" name="go_btn" value="<?php print_string('continue'); ?>" onclick="senddata()" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel'); ?>" onclick="cancel()" />
    </form>
    </center>
    <?php
    }

/// Copy first setup form 

    if ($work == '' || $work == 'setup'){
    ?>
    <center>
    <?php 
        print_heading(get_string('copysetup', 'techproject'), 'center'); 
        if (isset($errormessage)){
            print_simple_box("<span style=\"color:white\">$errormessage</span>", 'center', '70%', 'warning');
        }
    ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['copysetupform'].work.value='what';
        document.forms['copysetupform'].submit();
    }
    //]]>
    </script>
    <form name="copysetupform" action="view.php" method="post">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
    <table width="80%">
    <tr valign="top">
        <td align="right"><b><?php print_string('from', 'techproject') ?></b></td>
        <td align="left">
    <?php 
        $fromgroups = array();
        if (!empty($groups)){
            foreach(array_keys($groups) as $aGroupId){
                $fromgroups[$groups[$aGroupId]->id] = $groups[$aGroupId]->name;
            }
        }
        choose_from_menu($fromgroups,  'from', 0 + get_current_group($course->id), get_string('groupless', 'techproject'));
    ?>
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right"><b><?php print_string('upto', 'techproject') ?></b></td>
        <td align="left">
            <select name="to[]" multiple="multiple" size="6" style="width : 80%">
    <?php
        echo "<option value=\"0\">".get_string('groupless', 'techproject')."</option>";
        if (!empty($groups)){
            foreach($groups as $aGroup){
                echo "<option value=\"{$aGroup->id}\">{$aGroup->name}</option>";
            }
        }
    ?>
            </select>
        </td>
    </tr>
    
    </table>
    <input type="button" name="go_btn" value="<?php print_string('continue'); ?>" onclick="senddata()" />
    </form>
    </center>
<?php
    }
?>