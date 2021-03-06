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
 * @package mod-techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/uploadlib.php');

// Controller.
if ($work == 'add' || $work == 'update') {
    include($CFG->dirroot.'/mod/techproject/edit_deliverable.php');

    // Group operation form *********************************************************.

} else if ($work == 'groupcmd') {
    echo $pagebuffer;
    $ids = required_param_array('ids', PARAM_INT);
    $cmd = required_param('cmd', PARAM_ALPHA);

    echo '<center>';
    echo $OUTPUT->heading(get_string('groupoperations', 'techproject'));
    echo $OUTPUT->heading(get_string("group$cmd", 'techproject'), 3);

    echo $renderer->group_op_form();

    echo '<form name="groupopform" method="get" action="view.php">';
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
    echo '<input type="hidden" name="work" value="" />';

    foreach ($ids as $anid) {
        echo '<input type="hidden" name="ids[]" value="'.$anid.'" />'."\n";
    }
    if (($cmd == 'move') || ($cmd == 'copy')) {
        echo get_string('to', 'techproject');
        if (@$project->projectusesrequs) {
            $options['requs'] = get_string('requirements', 'techproject');
        }
        if (@$project->projectusesspecs) {
            $options['specs'] = get_string('specifications', 'techproject');
        }
        $options['tasks'] = get_string('tasks', 'techproject');
        echo html_writer::select($options, 'to', '', 'choose');
    }

    echo '<input type="button" name="go_btn" value="'.get_string('continue').'" onclick="senddata(\''.$cmd.'\')" />';
    echo '<input type="button" name="cancel_btn" value="'.get_string('cancel').'" onclick="cancel()" />';
    echo '</form>';
    echo '</center>';

} else {
    if ($work) {
         include($CFG->dirroot.'/mod/techproject/deliverables.controller.php');
    }
    echo $pagebuffer;

    echo $renderer->group_op_form_group();

    echo '<form name="groupopform" method="post" action="view.php">';
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
    echo '<input type="hidden" name="work" value="groupcmd" />';

    $params = array('id' => $cm->id, 'work' => 'add', 'fatherid' => 0);
    $linkurl = new moodle_url('/mod/techproject/view.php', $params);
    if ($USER->editmode == 'on' && has_capability('mod/techproject:changedelivs', $context)) {
        echo '<br/><a href="'.$linkurl.'">'.get_string('adddeliv', 'techproject').'</a>&nbsp; ';
    }

    techproject_print_deliverables($project, $currentgroupid, 0, $cm->id);

    if ($USER->editmode == 'on' && has_capability('mod/techproject:changedelivs', $context)) {
        echo '<br/><a href="'.$linkurl.'">'.get_string('adddeliv', 'techproject').'</a>&nbsp; ';
        techproject_print_group_commands();
    }

    echo '</form>';
}
