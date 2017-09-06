<?php
include_once('inc.menu.php');
echo '<link rel="stylesheet" type="text/css" href="styles.css">';
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
 * sistec report
 *
 * @package    report
 * @subpackage sistec
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->dirroot.'/lib/tablelib.php');
define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);
$id         = required_param('id', PARAM_INT); // course id.
$roleid     = optional_param('roleid', 0, PARAM_INT); // which role to show
//$instanceid = optional_param('instanceid', 0, PARAM_INT); // instance we're looking at.
$timefrom   = optional_param('timefrom', 0, PARAM_INT); // how far back to look...
$action     = optional_param('action', '', PARAM_ALPHA);
$page       = optional_param('page', 0, PARAM_INT);                     // which page to show
$perpage    = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page
$currentgroup = optional_param('group', 0, PARAM_INT); // Get the active group.
$url = new moodle_url('/report/sistec/index.php', array('id'=>$id));
if ($roleid !== 0) $url->param('roleid');
//if ($instanceid !== 0) $url->param('instanceid');
if ($timefrom !== 0) $url->param('timefrom');
if ($action !== '') $url->param('action');
if ($page !== 0) $url->param('page');
if ($perpage !== DEFAULT_PAGE_SIZE) $url->param('perpage');
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
if ($action != 'view' and $action != 'post') {
    $action = ''; // default to all (don't restrict)
}
if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourse');
}
if ($roleid != 0 and !$role = $DB->get_record('role', array('id'=>$roleid))) {
    print_error('invalidrole');
}
require_login($course);
$context = context_course::instance($course->id);
require_capability('report/sistec:view', $context);
$strsistec = get_string('sistecreport');
$strviews         = get_string('views');
$strposts         = get_string('posts');
$strview          = get_string('view');
$strpost          = get_string('post');
$strallactions    = get_string('allactions');
$strreports       = get_string('reports');
$actionoptions = array('' => $strallactions,
                       'view' => $strview,
                       'post' => $strpost,);
if (!array_key_exists($action, $actionoptions)) {
    $action = '';
}
$PAGE->set_title($course->shortname .': '. $strsistec);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
// Trigger a content view event.
$event = \report_sistec\event\content_viewed::create(array('courseid' => $course->id,
                                                               'other'    => array('content' => 'participants')));
$event->set_page_detail();
$event->set_legacy_logdata(array($course->id, "course", "report sistec",
        "report/sistec/index.php?id=$course->id", $course->id));
$event->trigger();
$modinfo = get_fast_modinfo($course);
$modules = $DB->get_records_select('modules', "visible = 1", null, 'name ASC');
$instanceoptions = array();
foreach ($modules as $module) {
    if (empty($modinfo->instances[$module->name])) {
        continue;
    }
    $instances = array();
    foreach ($modinfo->instances[$module->name] as $cm) {
        // Skip modules such as label which do not actually have links;
        // this means there's nothing to participate in
        if (!$cm->has_view()) {
            continue;
        }
        $instances[$cm->id] = format_string($cm->name);
    }
    if (count($instances) == 0) {
        continue;
    }
    $instanceoptions[] = array(get_string('modulenameplural', $module->name)=>$instances);
}
$timeoptions = array();
// get minimum log time for this course
$minlog = $DB->get_field_sql('SELECT min(time) FROM {log} WHERE course = ?', array($course->id));
$now = usergetmidnight(time());
// days
for ($i = 1; $i < 7; $i++) {
    if (strtotime('-'.$i.' days',$now) >= $minlog) {
        $timeoptions[strtotime('-'.$i.' days',$now)] = get_string('numdays','moodle',$i);
    }
}
// weeks
for ($i = 1; $i < 10; $i++) {
    if (strtotime('-'.$i.' weeks',$now) >= $minlog) {
        $timeoptions[strtotime('-'.$i.' weeks',$now)] = get_string('numweeks','moodle',$i);
    }
}
// months
for ($i = 2; $i < 12; $i++) {
    if (strtotime('-'.$i.' months',$now) >= $minlog) {
        $timeoptions[strtotime('-'.$i.' months',$now)] = get_string('nummonths','moodle',$i);
    }
}
// try a year
if (strtotime('-1 year',$now) >= $minlog) {
    $timeoptions[strtotime('-1 year',$now)] = get_string('lastyear');
}
// TODO: we need a new list of roles that are visible here
$roles = get_roles_used_in_context($context);
$guestrole = get_guest_role();
$roles[$guestrole->id] = $guestrole;
$roleoptions = role_fix_names($roles, $context, ROLENAME_ALIAS, true);

// Prints Menu
echo $pluginifrs_menu;

$baseurl =  $CFG->wwwroot.'/report/sistec/page_confirmar.php?id='.$course->id.'&amp;roleid='
    .$roleid.'&amp;timefrom='.$timefrom.'&amp;action='.$action.'&amp;perpage='.$perpage;
//if (!empty($roleid)) {
    $table = new flexible_table('course-sistec-'.$course->id.'-'.$cm->id.'-'.$roleid);
    $table->course = $course;
    $table->define_columns(array('id','fullname','select', 'count'));
    $table->define_headers(array('ID',get_string('user'),'E-mail','Cursos Concluídos'));
    $table->define_baseurl($baseurl);
    $table->set_attribute('cellpadding','5');
    $table->set_attribute('class', 'generaltable generalbox reporttable');
    $table->sortable(true,'lastname','ASC');
    $table->no_sorting('select');
    $table->set_control_variables(array(
                                        TABLE_VAR_SORT    => 'ssort',
                                        TABLE_VAR_HIDE    => 'shide',
                                        TABLE_VAR_SHOW    => 'sshow',
                                        TABLE_VAR_IFIRST  => 'sifirst',
                                        TABLE_VAR_ILAST   => 'silast',
                                        TABLE_VAR_PAGE    => 'spage'
                                        ));
    $table->setup();
    echo '<div id="sistecreport">' . "\n";
    
    $sql = "   
        SELECT t.turma, u.firstname, u.lastname, uf.data cpf, cast(to_char(now()-to_timestamp(u.lastaccess), 'DD')as integer) dif, 

        (CASE WHEN (cc.timecompleted IS NULL OR cc.timecompleted = 0) THEN 

        (CASE WHEN (cast(to_char(now()-to_timestamp(u.lastaccess), 'DD')as integer)> 60) THEN 'Evasão' ELSE 'Em andamento' END)

        ELSE 'Concluído' END)

        FROM mdl_user u, mdl_user_info_data uf, (SELECT a.course, ass.userid, ass.timecreated, to_char(to_timestamp(ass.timecreated), 'Mon YYYY') turma

        FROM mdl_assign a,  mdl_assign_submission ass

        WHERE ass.attemptnumber = 0 and a.name like '9.7. Entrega da 1ª parte do Projeto Final%' and a.id = ass.assignment

        ORDER BY turma) t LEFT JOIN mdl_course_completions cc
        ON cc.course = t.course AND cc.userid = t.userid
        WHERE uf.fieldid = 2 and t.userid = u.id and u.id = uf.userid
        order by t.turma, u.firstname, u.lastname
    ";
 
/* IF THE QUERY IS NOT SUCCESSFUL, THE ARRAY USERS IS LEFT BLANK, OTHERWISE, IT WILL STORE THE QUERY RESULTS */
    if (!$users = $DB->get_records_sql($sql)) {
        $users = array(); // tablelib will handle saying 'Nothing to display' for us.
    }
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    echo '<h3>Usuários com maior quantidade de conclusões de curso: </h3>'."\n";

    $table->print_html();

    $PAGE->requires->js_init_call('M.report_sistec.init');
//}
echo $OUTPUT->footer();
