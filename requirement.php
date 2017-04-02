<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

// Controller.

if ($work == 'add' || $work == 'update') {
    include($CFG->dirroot.'/mod/techproject/edit_requirement.php');
} else if ($work == 'groupcmd') {
    // Group operation form.
    echo $pagebuffer;
    $ids = required_param_array('ids', PARAM_INT);
    $cmd = required_param('cmd', PARAM_ALPHA);

    echo '<center>';
    echo $OUTPUT->heading(get_string('groupoperations', 'techproject'));
    echo $OUTPUT->heading(get_string("group$cmd", 'techproject'), 3);
?>

<script type="text/javascript">
//<!{CDATA{
function senddata(cmd) {
    document.forms['groupopform'].work.value="do" + cmd;
    document.forms['groupopform'].submit();
}
function cancel() {
    document.forms['groupopform'].submit();
}
//]]>
</script>
<?php

    echo '<form name="groupopform" method="post" action="view.php">';
    echo '<input type="hidden" name="id" value="'.$cm->id).'" />';
    echo '<input type="hidden" name="work" value="" />';

    foreach ($ids as $anid) {
        echo "<input type=\"hidden\" name=\"ids[]\" value=\"{$anid}\" />\n";
    }
    if (($cmd == 'move')||($cmd == 'copy')) {
        echo get_string('to', 'techproject');
        if (@$project->projectusesspecs) {
            $options['specs'] = get_string('specifications', 'techproject');
        }
        if (@$project->projectusesspecs) {
            $options['specswb'] = get_string('specificationswithbindings', 'techproject');
        }
        $options['tasks'] = get_string('tasks', 'techproject');
        if (@$project->projectusesdelivs) {
            $options['deliv'] = get_string('deliverables', 'techproject');
        }
        echo html_writer::select($options, 'to', '', array('' => 'choosedots'));
    }
    echo '<br/>';

    echo '<input type="button" name="go_btn" value="'.get_string('continue').'" onclick="senddata(\''.$cmd.'\')" />';
    echo '<input type="button" name="cancel_btn" value="'.get_string('cancel').'" onclick="cancel()" />';
    echo '</form>';
    echo '</center>';

} else {
    if ($work) {
        include($CFG->dirroot.'/mod/techproject/requirements.controller.php');
    }
    echo $pagebuffer;
?>
<script type="text/javascript">
//<![CDATA[
function sendgroupdata() {
    document.forms['groupopform'].submit();
}
//]]>
</script>
<?php

    echo '<form name="groupopform" method="post" action="view.php">';
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
    echo '<input type="hidden" name="work" value="groupcmd" />';

    $indicatordesc = get_string('requirementriskcalculation', 'techproject');
    $params = array('id' => $cm->id, 'projectid' => $project->id, 'group' => $currentgroupid);
    $generatorurl = new moodle_url('/mod/techproject/gdgenerators/projectrisk.php', $params);
    echo '<center><table width="80%"><tr>';
    echo '<td width="60%">'.$indicatordesc.'</td>';
    echo '<td><img src="'.$generatorurl.'" width="250" height="250" /></td>';
    echo '</tr></table></center>';
    if ($USER->editmode == 'on' && has_capability('mod/techproject:changerequs', $context)) {
        echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addrequ','techproject')."</a>&nbsp;";
    }
    techproject_print_requirements($project, $currentgroupid, 0, $cm->id);
    if ($USER->editmode == 'on' && has_capability('mod/techproject:changerequs', $context)) {
        echo "<br/><a href='javascript:selectall(document.forms[\"groupopform\"])'>".get_string('selectall','techproject')."</a>&nbsp;";
        echo "<a href='javascript:unselectall(document.forms[\"groupopform\"])'>".get_string('unselectall','techproject')."</a>&nbsp;";
        echo "<a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addrequ','techproject')."</a>&nbsp;";
        techproject_print_group_commands();
    }
    echo '</form>';
}
