<?php
// This file is part of Stack - http://stack.maths.ed.ac.uk/
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with STACK.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script lets the user import questions from the library folder.
 *
 * @copyright  2024 University of Edinburgh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__.'/../../../config.php');

require_once($CFG->libdir . '/questionlib.php');
require_once(__DIR__ . '/vle_specific.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/stack/utils.class.php');
require_once(__DIR__ . '/stack/questionlibrary.class.php');


if ($cmid = optional_param('cmid', 0, PARAM_INT)) {
    $cm = get_coursemodule_from_id(false, $cmid);
    require_login($cm->course, false, $cm);
    $liburlparams['cmid'] = $cmid;
    $qburlparams['cmid'] = $cmid;

} else if ($courseid = optional_param('courseid', 0, PARAM_INT)) {
    require_login($courseid);
    $liburlparams['courseid'] = $courseid;
    $qburlparams['courseid'] = $courseid;
}

// Check user has add capability for the required category.
$categoryid = required_param('category', PARAM_INT);
$category = $DB->get_record('question_categories', ['id' => $categoryid], 'name, info, contextid');
$thiscontext = context::instance_by_id($category->contextid);
require_capability('moodle/question:add', $thiscontext);
$liburlparams['category'] = $categoryid;

// Initialise $PAGE.
$PAGE->set_context($thiscontext);
$PAGE->set_url('/question/type/stack/questionlibrary.php', $liburlparams);
$title = stack_string('stack_library');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('popup');
echo $OUTPUT->header();

$questionbanklink = new moodle_url('/question/edit.php', $qburlparams);
$PAGE->requires->js_amd_inline(
    'require(["qtype_stack/library"], '
    . 'function(library,){library.setup(' . $categoryid . ');});'
);

// Get list of files.
$cache = cache::make('qtype_stack', 'librarycache');
$files = $cache->get('library_file_list');
if (!$files) {
    $files = stack_question_library::get_file_list('samplequestions/stacklibrary/*');
    $cache->set('library_file_list', $files);
}

// Prepare data for template.
$outputdata = new StdClass();
$outputdata->questionbanklink = $questionbanklink->out();
$outputdata->catname = $category->name;
$outputdata->catinfo = $category->info;
$outputdata->files = $files->children;
$x = json_encode($files->children);
echo $OUTPUT->render_from_template('qtype_stack/questionlibrary', $outputdata);

// Finish output.
echo $OUTPUT->footer();
