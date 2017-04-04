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
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * This screen show tasks plan by assignee. Unassigned tasks are shown 
 * below assigned tasks
 */
defined('MOODLE_INTERNAL') || die();

echo $pagebuffer;

$timeunits = array(get_string('unset','techproject'),
                   get_string('hours','techproject'),
                   get_string('halfdays','techproject'),
                   get_string('days','techproject'));

$haveassignedtasks = false;

if (!groups_get_activity_groupmode($cm, $project->course)) {
    $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',u.email, u.picture';
    $groupusers = get_users_by_capability($context, 'mod/techproject:beassignedtasks', $fields, 'u.lastname');
} else {
    if ($currentgroupid) {
        $groupusers = groups_get_members($currentgroupid);
    } else {
        // We could not rely on the legacy function.
        $groupusers = techproject_get_users_not_in_group($project->course);
    }
}
if (!isset($groupusers) || count($groupusers) == 0 || empty($groupusers)) {
    echo $OUTPUT->box(get_string('noassignee','techproject'), 'center');
} else {
    echo $OUTPUT->heading(get_string('assignedtasks','techproject'));
    echo '<br/>';
    echo $OUTPUT->box_start('center', '100%');
    foreach ($groupusers as $auser) {
        techproject_complete_user($auser);
?>
<table width="100%">
    <tr>
        <td class="byassigneeheading level1">
<?php
        $jshandler = 'javascript:toggle('.$auser->id.',\'sub'.$auser->id.'\', false);';
        $hidesub = '<a href="'.$jshandler.'"><img name=\"img{$auser->id}\" src=\"{$CFG->wwwroot}/mod/techproject/pix/p/switch_minus.gif\" alt=\"collapse\" /></a>";
        echo $hidesub.' '.get_string('assignedto','techproject').' '.fullname($auser).' '.$OUTPUT->user_picture($USER);
?>
        </td>
        <td class="byassigneeheading level1" align="right">
<?php
            $query = "
               SELECT
                  SUM(planned) as planned,
                  SUM(done) as done,
                  SUM(spent) as spent,
                  COUNT(*) as count
               FROM
                  {techproject_task} as t
               WHERE
                  t.projectid = ? AND
                  t.groupid = ? AND
                  t.assignee = ?
               GROUP BY
                  t.assignee
            ";
            $res = $DB->get_record_sql($query, array($project->id, $currentgroupid, $auser->id));
            if ($res) {
                $over = ($res->planned) ? round((($res->spent - $res->planned) / $res->planned) * 100) : 0;
                // calculates a local alarm for lateness
                $hurryup = '';
                if ($res->planned && ($res->spent <= $res->planned)) {
                    $pixurl = $OUTPUT->pix_url('p/late', 'techproject');
                    $pix = '<img src="'.$pixurl.'" title="'.get_string('hurryup','techproject').'" />';
                    $hurryup = (round(($res->spent / $res->planned) * 100) > ($res->done / $res->count)) ? $pix : '';
                }
                $lateclass = ($over > 0) ? 'toolate' : 'intime';
                $workplan = get_string('assignedwork','techproject').' '.(0 + $res->planned).' '.$timeunits[$project->timeunit];
                $latespan = ' <span class="'.$lateclass.'">'.(0 + $res->spent).' '.$timeunits[$project->timeunit].'</span>';
                $realwork = get_string('realwork','techproject').$latespan;
                $bar = $renderer->bar_graph_over($res->done / $res->count, $over, 100, 10);
                $completion = ($res->count != 0) ? $bar : $renderer->bar_graph_over(-1, 0);
                echo "{$workplan} - {$realwork} {$completion} {$hurryup}";
            }
?>
        </td>
    </tr>
</table>
<table id="<?php echo "sub{$auser->id}" ?>" width="100%">
<?php
        // Get assigned tasks.
        $query = "
           SELECT
              t.*,
              qu.label as statuslabel,
              COUNT(tts.specid) as specs
           FROM
              {techproject_qualifier} as qu,
              {techproject_task} as t
           LEFT JOIN
              {techproject_task_to_spec} as tts
           ON
              tts.taskid = t.id
           WHERE
              t.projectid = ? AND
              t.groupid = ? AND
              qu.domain = 'taskstatus' AND
              qu.code = t.status AND
              t.assignee = ?
           GROUP BY
              t.id
        ";
        $tasks = $DB->get_records_sql($query, array($project->id, $currentgroupid, $auser->id));
        if (!isset($tasks) || count($tasks) == 0 || empty($tasks)) {
            echo '<tr>';
            echo '<td>';
            echo $OUTPUT->notification(get_string('notaskassigned', 'techproject'));
            echo '</td>';
            echo '</tr>';
        } else {
            foreach($tasks as $atask) {
                $haveassignedtasks = true;
                // Feed milestone titles for popup display.
                if ($milestone = $DB->get_record('techproject_milestone', array('id' => $atask->milestoneid))) {
                    $atask->milestoneabstract = $milestone->abstract;
                }
                echo '<tr>';
                echo '<td class="level2">';
                techproject_print_single_task($atask, $project, $currentgroupid, $cm->id, count($tasks), true,
                                              'SHORT_WITHOUT_ASSIGNEE_NOEDIT');
                echo '</td>';
                echo '</tr>';
            }
        }
?>
</table>
<?php
    }
    echo $OUTPUT->box_end();
}
// Get unassigned tasks.
$query = "
   SELECT
      *
   FROM
      {techproject_task}
   WHERE
      projectid = ? AND
      groupid = ? AND
      assignee = 0
";
$unassignedtasks = $DB->get_records_sql($query, array($project->id, $currentgroupid));
echo $OUTPUT->heading(get_string('unassignedtasks','techproject'));
?>
<br/>
<?php
echo $OUTPUT->box_start('center', '100%');
?>
<center>
<table width="100%">
<?php
if (!isset($unassignedtasks) || count($unassignedtasks) == 0 || empty($unassignedtasks)) {
?>
    <tr>
        <td>
            <?php print_string('notaskunassigned', 'techproject') ?>
        </td>
    </tr>
<?php
} else {
    foreach ($unassignedtasks as $atask) {
?>
    <tr>
        <td class="level2">
            <?php
            $branch = techproject_tree_get_upper_branch('techproject_task', $atask->id, true, true);
            echo 'T'.implode('.', $branch) . '. ' . $atask->abstract ;
            $params = array('id' => $cm->id, 'view' => 'view_detail', 'objectClass' => 'task', 'objectId' => $atask->id);
            $detailurl = new moodle_url('/mod/techproject/view.php', $params);
            $pixurl = $OUTPUT->pix_url('p/hide', 'techproject');
            $pix = '<img src="'.$pixurl.'" title="'.get_string('detail','techproject').'" />';
            echo '&nbsp;<a href="'.$detailurl.'">'.$pix.'</a>';
            ?>
        </td>
        <td>
        </td>
    </tr>
<?php
    }
}
?>
</table>
</center>
<?php
echo $OUTPUT->box_end();
