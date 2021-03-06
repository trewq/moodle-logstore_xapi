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

namespace src\transformer\events\core;

defined('MOODLE_INTERNAL') || die();

use src\transformer\utils as utils;

function course_module_completion_updated(array $config, \stdClass $event) {
    $repo = $config['repo'];
    $user = $repo->read_record_by_id('user', $event->relateduserid);
    $course = $repo->read_record_by_id('course', $event->courseid);
    $coursemodule = $repo->read_record_by_id('course_modules', $event->contextinstanceid);
    $moduletype = $repo->read_record_by_id('modules', $coursemodule->module);
    $module = $repo->read_record_by_id($moduletype->name, $coursemodule->instance);
    $lang = utils\get_course_lang($course);
    $completion = $repo->read_record_by_id('course_modules_completion', $event->objectid);

    $statement = [
        'actor' => utils\get_user($config, $user),
        'verb' => [
            'id' => 'http://id.tincanapi.com/verb/completed',
            'display' => [
                $lang => 'completed'
            ],
        ],
        'object' => utils\get_activity\course_module(
            $config,
            $course,
            $event->contextinstanceid,
            'http://id.tincanapi.com/activitytype/lms/module'
        ),
        'timestamp' => utils\get_event_timestamp($event),
        'context' => [
            'platform' => $config['source_name'],
            'language' => $lang,
            'extensions' => [
                utils\INFO_EXTENSION => utils\get_info($config, $event),
                utils\EVENT_EXTENSION => $event,
                'https://w3id.org/learning-analytics/learning-management-system/timemodified' => $completion->timemodified
            ],
            'contextActivities' => [
                'grouping' => [
                    utils\get_activity\site($config),
                    utils\get_activity\course($config, $course),
                ],
                'category' => [
                    utils\get_activity\source($config),
                ]
            ],
        ]
    ];

    $unserializedother = property_exists($event, 'other') ? unserialize($event->other) : [];
    if (isset($unserializedother['overrideby']) && $unserializedother['overrideby'] > 0) {
        $instructor = $repo->read_record_by_id('user', $unserializedother['overrideby']);
        $statement['context']['instructor'] = utils\get_user($config, $instructor);
    }

    return [$statement];
}
