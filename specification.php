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

if ($work == 'add' || $work == 'update') {
    include($CFG->dirroot.'/mod/techproject/edit_specification.php');
} else if ($work == 'groupcmd') {
    // Group operation form *********************************************************.
    echo $pagebuffer;
    $ids = required_param_array('ids', PARAM_INT);
    $cmd = required_param('cmd', PARAM_ALPHA);

?>

<center>
<?php echo $OUTPUT->heading(get_string('groupoperations', 'techproject')); ?>
<?php echo $OUTPUT->heading(get_string("group$cmd", 'techproject'), 3); ?>
<script type="text/javascript">
//<![CDATA[
function senddata(cmd){
    document.forms['groupopform'].work.value="do" + cmd;
    document.forms['groupopform'].submit();
}

function cancel(){
    document.forms['groupopform'].submit();
}
//]]>
</script>
<form name="groupopform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="work" value="" />
<?php
    foreach ($ids as $anid) {
        echo "<input type=\"hidden\" name=\"ids[]\" value=\"{$anid}\" />\n";
    }
    if (($cmd == 'move')||($cmd == 'copy')) {
        echo get_string('to', 'techproject');
        if (@$project->projectusesrequs) {
            $options['requs'] = get_string('requirements', 'techproject');
        }
        if (@$project->projectusesrequs) {
            $options['requswb'] = get_string('requirementswithbindings', 'techproject');
        }
        $options['tasks'] = get_string('tasks', 'techproject');
        $options['taskswb'] = get_string('taskswithbindings', 'techproject');
        if (@$project->projectusesdelivs) {
            $options['deliv'] = get_string('deliverables', 'techproject');
        }
        echo html_writer::select($options, 'to', '', 'choose');
    }

    if ($cmd == 'applytemplate') {
        echo '<input type="checkbox" name="applyroot" value="1" /> '.get_string('alsoapplyroot', 'techproject');
        echo '<br/>';
    }
    echo '<br/>';
?>
<input type="button" name="go_btn" value="<?php print_string('continue') ?>" onclick="senddata('<?php p($cmd) ?>')" />
<input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
</form>
</center>

<?php
} else {
    if ($work) {
        include($CFG->dirroot.'/mod/techproject/specifications.controller.php');
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
<form name="groupopform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="work" value="groupcmd" />
<?php
    if ($USER->editmode == 'on' && has_capability('mod/techproject:changespecs', $context)) {
        echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addspec','techproject')."</a>&nbsp; ";
    }
    techproject_print_specifications($project, $currentgroupid, 0, $cm->id);
    if ($USER->editmode == 'on' && has_capability('mod/techproject:changespecs', $context)) {
        echo "<br/><a href='javascript:selectall(document.forms[\"groupopform\"])'>".get_string('selectall','techproject')."</a>&nbsp;";
        echo "<a href='javascript:unselectall(document.forms[\"groupopform\"])'>".get_string('unselectall','techproject')."</a>&nbsp;";
        echo "<a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addspec','techproject')."</a>&nbsp; ";
        if (@$SESSION->techproject->spectemplateid){
            techproject_print_group_commands(array('applytemplate'));
        } else {
            techproject_print_group_commands();
        }
    }
?>
</form>
<?php
    }
