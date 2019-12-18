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
 *
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_sitenotice\helper;

require_once(__DIR__.'/../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
$PAGE->set_context(context_system::instance());

$thispage = '/local/sitenotice/report.php';
$managenoticepage = '/local/sitenotice/managenotice.php';
$PAGE->set_url(new moodle_url($thispage));

$noticeid = required_param('noticeid', PARAM_INT);
$download   = optional_param('download', false, PARAM_BOOL);

$records = helper::retrieve_acknowlegement();
$notice = helper::retrieve_notice($noticeid);

if(!empty($records)) {
    if (!$download) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('report:name', 'local_sitenotice'));

        $button = $OUTPUT->single_button(new moodle_url($thispage, ['noticeid' => $noticeid, 'download' => true
        ]), get_string("downloadtext"));
        echo html_writer::tag('div', $button, array('class' => 'noticereport'));

        // Notice table.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable';
        $table->head = array(
            get_string('notice:title', 'local_sitenotice'),
            get_string('username'),
            get_string('firstname'),
            get_string('lastname'),
            get_string('idnumber'),
            get_string('notice:hlinkcount', 'local_sitenotice'),
            get_string('time'),
        );
        $currentuserid = 0;
        foreach ($records as $record) {
            $row = array();
            $row[] = $record->noticetitle;
            $row[] = $record->username;
            $row[] = $record->firstname;
            $row[] = $record->lastname;
            $row[] = $record->idnumber;

            $hlinkcount = '';
            if ($currentuserid !=  $record->userid) {
                $currentuserid = $record->userid;
                $linkcounts = helper::retrieve_hlink_count($record->userid, $record->noticeid);
                foreach ($linkcounts as $count) {
                    $hlinkcount .= "<a href=\"{$count->link}\">{$count->text}</a>: $count->count <br/>";
                }
            }
            $row[] = $hlinkcount;

            $row[] = userdate($record->timecreated);
            $table->data[] = $row;
        }

        echo html_writer::table($table);
        echo $OUTPUT->footer();
    } else {
        $filename = clean_filename(strip_tags(format_string($notice->title,true)).'.csv');
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        // Fields/
        $hlinks = helper::retrieve_notice_hlinks($noticeid);

        $hlinkheaders = [];
        foreach ($hlinks as $link) {
            $hlinkheaders[$link->id] =  "$link->text ($link->link)";
        }

        $header = array(
            get_string('notice:title', 'local_sitenotice'),
            get_string('username'),
            get_string('firstname'),
            get_string('lastname'),
            get_string('idnumber'),
            get_string('time'),
        );

        $header = array_merge($header, $hlinkheaders);

        echo implode("\t", $header) . "\n";

        // Rows.
        $currentuserid = 0;
        foreach ($records as $record) {
            $row = array();
            $row[] = $record->noticetitle;
            $row[] = $record->username;
            $row[] = $record->firstname;
            $row[] = $record->lastname;
            $row[] = $record->idnumber;
            $row[] = userdate($record->timecreated);

            if ($currentuserid != $record->userid) {
                $currentuserid = $record->userid;
                $linkcounts = helper::retrieve_hlink_count($record->userid, $record->noticeid);
                foreach (array_keys($hlinkheaders) as $linkid ) {
                    $row[] = $linkcounts[$linkid]->count;
                }
            }
            
            echo implode("\t", $row) . "\n";
        }
    }

}
