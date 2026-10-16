<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\CalendarEvent;
use App\Models\Loi;
use App\Models\MemberDue;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class JciCarmonaContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->orderBy('id')->first();
        $treasurer = User::where('role', 'treasurer')->orderBy('id')->first() ?? $admin;
        $member = User::where('role', 'member')->orderBy('id')->first();

        if (!$admin || !$member) {
            $this->command?->warn('Create the local JCI Carmona accounts before seeding page content.');
            return;
        }

        $projects = Project::whereIn('reference', [
            'JCI-2026-LAPIS',
            'JCI-2026-IVY',
            'JCI-2026-VOLUNTEER-TOOLKIT',
        ])->get()->keyBy('reference');

        if ($projects->count() < 3) {
            $this->command?->warn('Run JciCarmonaProjectSeeder before seeding page content.');
            return;
        }

        $this->seedDocuments($projects, $admin);
        $this->seedCalendar($projects, $admin);
        $this->seedLetters($projects, $admin);
        $this->seedReports($projects, $admin);
        $this->seedDues($member, $treasurer);
        $this->seedNotifications($projects);
        $this->seedActivity($projects, $admin);
    }

    private function seedDocuments($projects, User $admin): void
    {
        $documents = [
            [
                'project' => 'JCI-2026-LAPIS',
                'title' => 'Volunteer Orientation Guide',
                'category' => 'Volunteer guide',
                'filename' => 'project-lapis-volunteer-orientation.txt',
                'body' => "JCI CARMONA 2026\r\nPROJECT LAPIS - VOLUNTEER ORIENTATION GUIDE\r\n\r\nPurpose\r\nPrepare volunteers to support inclusive literacy sessions with care, consistency, and respect.\r\n\r\nBefore the session\r\n1. Confirm the assigned schedule and learning station.\r\n2. Review safeguarding and learner support reminders.\r\n3. Check reading materials and activity supplies.\r\n\r\nDuring the session\r\n1. Follow the facilitator's learning plan.\r\n2. Encourage participation without pressure.\r\n3. Record attendance and observations accurately.\r\n\r\nAfter the session\r\nReturn materials, report concerns to the project chair, and submit the session notes.\r\n\r\nPlanning resource for local system demonstration. Confirm operational details with the project team.\r\n",
            ],
            [
                'project' => 'JCI-2026-LAPIS',
                'title' => 'Literacy Session Checklist',
                'category' => 'Field checklist',
                'filename' => 'project-lapis-session-checklist.txt',
                'body' => "JCI CARMONA 2026\r\nPROJECT LAPIS - SESSION CHECKLIST\r\n\r\n[ ] Attendance sheet prepared\r\n[ ] Reading kits complete\r\n[ ] Activity materials organized\r\n[ ] Volunteer roles confirmed\r\n[ ] Learner support notes prepared\r\n[ ] Venue safety check completed\r\n[ ] Session photos authorized and logged\r\n[ ] Post-session observations submitted\r\n\r\nPlanning resource for local system demonstration.\r\n",
            ],
            [
                'project' => 'JCI-2026-LAPIS',
                'title' => 'Learning Kit Inventory Template',
                'category' => 'Inventory template',
                'filename' => 'project-lapis-learning-kit-inventory.csv',
                'body' => "Item,Planned Quantity,Prepared Quantity,Released Quantity,Returned Quantity,Condition,Notes\r\nReading workbook,0,0,0,0,,\r\nPencil set,0,0,0,0,,\r\nActivity cards,0,0,0,0,,\r\nStory book,0,0,0,0,,\r\nArt materials,0,0,0,0,,\r\n",
            ],
            [
                'project' => 'JCI-2026-IVY',
                'title' => 'Volunteer Forum Action Notes',
                'category' => 'Forum documentation',
                'filename' => 'ivy-2026-forum-action-notes.txt',
                'body' => "JCI CARMONA 2026\r\nINTERNATIONAL VOLUNTEER YEAR FORUM - ACTION NOTES\r\n\r\n1. Capture practical volunteer recruitment steps.\r\n2. Strengthen coordination with local volunteer organizations.\r\n3. Document orientation, deployment, and follow-up practices.\r\n4. Translate lessons into a reusable chapter toolkit.\r\n\r\nPlanning summary for local system demonstration. Confirm final forum documentation with the chapter.\r\n",
            ],
            [
                'project' => 'JCI-2026-VOLUNTEER-TOOLKIT',
                'title' => 'Volunteer Mobilization Toolkit Outline',
                'category' => 'Planning document',
                'filename' => 'volunteer-mobilization-toolkit-outline.txt',
                'body' => "JCI CARMONA 2026\r\nYOUTH VOLUNTEER MOBILIZATION TOOLKIT - OUTLINE\r\n\r\n1. Community need and volunteer role\r\n2. Recruitment and partner coordination\r\n3. Volunteer screening and orientation\r\n4. Team assignments and communication\r\n5. Activity safety and documentation\r\n6. Recognition, reflection, and follow-up\r\n7. Monitoring indicators and lessons learned\r\n\r\nWorking outline for chapter review.\r\n",
            ],
            [
                'project' => 'JCI-2026-VOLUNTEER-TOOLKIT',
                'title' => 'Volunteer Roster Template',
                'category' => 'Team template',
                'filename' => 'volunteer-roster-template.csv',
                'body' => "Volunteer Name,Member Number,Mobile Number,Assigned Team,Role,Schedule,Emergency Contact,Attendance,Notes\r\n,,,,,,,,\r\n,,,,,,,,\r\n,,,,,,,,\r\n",
            ],
        ];

        foreach ($documents as $document) {
            $project = $projects[$document['project']];
            $path = 'project-documents/seeded/'.$document['filename'];
            Storage::disk('local')->put($path, $document['body']);

            ProjectDocument::updateOrCreate(
                ['project_id' => $project->id, 'original_name' => $document['filename']],
                ['uploaded_by' => $admin->id, 'title' => $document['title'], 'category' => $document['category'], 'path' => $path]
            );
        }
    }

    private function seedCalendar($projects, User $admin): void
    {
        $events = [
            ['JCI-2026-LAPIS', 'Project LAPIS volunteer coordination', 'Meeting', '2026-10-22', 'Carmona City, Cavite', 'Confirm team assignments, learning stations, and field requirements.'],
            ['JCI-2026-LAPIS', 'Learning materials preparation', 'Activity', '2026-10-29', 'Carmona City, Cavite', 'Prepare and check reading kits and session materials.'],
            ['JCI-2026-VOLUNTEER-TOOLKIT', 'Volunteer toolkit kickoff', 'Meeting', '2026-11-05', 'JCI Carmona chapter workspace', 'Agree on the toolkit structure, contributors, and review schedule.'],
            ['JCI-2026-VOLUNTEER-TOOLKIT', 'Toolkit first draft review', 'Review', '2026-11-27', 'JCI Carmona chapter workspace', 'Review the first working draft and record chapter feedback.'],
        ];

        foreach ($events as [$reference, $title, $type, $date, $venue, $description]) {
            CalendarEvent::updateOrCreate(
                ['project_id' => $projects[$reference]->id, 'title' => $title],
                ['created_by' => $admin->id, 'type' => $type, 'starts_on' => $date, 'ends_on' => $date, 'venue' => $venue, 'description' => $description]
            );
        }
    }

    private function seedLetters($projects, User $admin): void
    {
        $letters = [
            ['JCI-2026-LAPIS', 'Project LAPIS Partner Coordination Letter', 'City and community partners', 'Coordinate learning sessions, volunteers, and activity requirements.'],
            ['JCI-2026-IVY', 'Volunteer Forum Follow-up Letter', 'Volunteer sector partners', 'Continue collaboration and exchange practical volunteer mobilization resources.'],
            ['JCI-2026-VOLUNTEER-TOOLKIT', 'Toolkit Resource Partnership Letter', 'Prospective local partners', 'Invite resource inputs and review support for the chapter volunteer toolkit.'],
        ];

        foreach ($letters as [$reference, $title, $organization, $purpose]) {
            Loi::updateOrCreate(
                ['project_id' => $projects[$reference]->id, 'title' => $title],
                [
                    'created_by' => $admin->id,
                    'type' => 'External Partner Letter',
                    'status' => 'Sent',
                    'version' => 1,
                    'data' => [
                        'recipient' => 'Partner organization representative',
                        'organization' => $organization,
                        'address' => 'Carmona City, Cavite',
                        'purpose' => $purpose,
                        'support' => 'Coordination, subject matter input, and activity support as available.',
                        'commitments' => 'Confirm the appropriate focal persons and agreed next steps.',
                        'signatory' => 'JCI Carmona chapter representative',
                        'body' => 'This planning copy supports the local demonstration workspace and should be reviewed before external use.',
                    ],
                    'versions' => [],
                    'review_comments' => 'Demonstration correspondence record for local development.',
                ]
            );
        }
    }

    private function seedReports($projects, User $admin): void
    {
        $reports = [
            ['JCI-2026-LAPIS', 'Project LAPIS Mobilization Progress Report', 'Volunteer scheduling and preparation activities were organized for continued literacy support.'],
            ['JCI-2026-IVY', 'International Volunteer Year Forum Completion Report', 'The chapter participation and key volunteer mobilization lessons were documented for follow-up.'],
            ['JCI-2026-VOLUNTEER-TOOLKIT', 'Volunteer Toolkit Development Progress Report', 'The working outline, source gathering, and internal review activities were recorded.'],
        ];

        foreach ($reports as [$reference, $title, $summary]) {
            ProjectReport::updateOrCreate(
                ['project_id' => $projects[$reference]->id, 'title' => $title],
                [
                    'created_by' => $admin->id,
                    'type' => str_contains($title, 'Completion') ? 'Completion' : 'Progress',
                    'status' => 'Reviewed',
                    'version' => 1,
                    'data' => [
                        'summary' => $summary,
                        'objectives' => 'Record progress, decisions, responsibilities, and next steps.',
                        'beneficiaries' => 'JCI Carmona members, volunteers, partners, and intended community participants.',
                        'activities' => 'Coordination, preparation, documentation, and team review.',
                        'outputs' => 'Working records and clear follow-up actions.',
                        'outcomes' => 'Improved continuity and accountability for chapter activities.',
                        'participation' => 'Chapter project team and partner representatives as applicable.',
                        'issues' => 'Final figures and operational details require confirmation from the project team.',
                        'recommendations' => 'Confirm the record after each activity and attach verified supporting evidence.',
                        'evidence' => 'See the supporting files in the project document library.',
                    ],
                    'versions' => [],
                    'review_comments' => 'Demonstration report for the local workspace. Replace unverified details with approved chapter records.',
                ]
            );
        }
    }

    private function seedDues(User $member, ?User $recorder): void
    {
        foreach ([
            ['2026-08', '2026-08-31'],
            ['2026-09', '2026-09-30'],
            ['2026-10', '2026-10-31'],
        ] as [$period, $dueDate]) {
            MemberDue::updateOrCreate(
                ['member_id' => $member->id, 'period' => $period],
                ['due_date' => $dueDate, 'amount' => 500, 'remarks' => 'Demonstration monthly chapter dues assessment.', 'recorded_by' => $recorder?->id]
            );
        }
    }

    private function seedNotifications($projects): void
    {
        $items = [
            ['Project LAPIS coordination', 'Volunteer coordination is scheduled for October 22.', 'JCI-2026-LAPIS'],
            ['Learning materials preparation', 'Review the checklist before the October 29 preparation activity.', 'JCI-2026-LAPIS'],
            ['Volunteer toolkit kickoff', 'The toolkit planning meeting is scheduled for November 5.', 'JCI-2026-VOLUNTEER-TOOLKIT'],
        ];

        foreach (User::where('status', 'active')->get() as $user) {
            foreach ($items as [$title, $body, $reference]) {
                $project = $projects[$reference];
                Notification::firstOrCreate(
                    ['user_id' => $user->id, 'title' => $title],
                    ['body' => $body, 'url' => '/projects/'.$project->id, 'read_at' => null]
                );
            }
        }
    }

    private function seedActivity($projects, User $admin): void
    {
        foreach ([
            ['project_content_prepared', 'JCI-2026-LAPIS', 'Project LAPIS planning resources were added to the document library.'],
            ['project_event_scheduled', 'JCI-2026-LAPIS', 'The upcoming volunteer coordination activities were added to the calendar.'],
            ['project_report_recorded', 'JCI-2026-IVY', 'The volunteer forum completion record was added for chapter reference.'],
        ] as [$action, $reference, $remarks]) {
            $project = $projects[$reference];
            AuditLog::firstOrCreate(
                ['action' => $action, 'object_type' => Project::class, 'object_id' => $project->id],
                ['user_id' => $admin->id, 'before' => [], 'after' => ['reference' => $reference], 'remarks' => $remarks]
            );
        }
    }
}
