<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class JciCarmonaProjectSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('role', 'admin')->orderBy('id')->first();
        if (!$owner) {
            $this->command?->warn('Create an administrator before seeding JCI Carmona project examples.');
            return;
        }

        // Publicly documented partner initiatives. Workflow fields below are examples
        // for local development and should be reviewed before operational use.
        $projects = [
            [
                'reference' => 'JCI-2026-LAPIS',
                'title' => 'Project LAPIS - Learning Assistance Program and Inclusive Support',
                'area' => 'Community Impact',
                'status' => 'Ongoing',
                'starts_on' => '2026-01-24',
                'venue' => 'Carmona City, Cavite',
                'concept' => [
                    'background' => 'Carmona launched Project LAPIS with local youth organizations, including JCI Carmona, to support literacy and inclusive learning.',
                    'objective' => 'Support children and out-of-school youth through learning assistance and inclusive community participation.',
                    'beneficiaries' => 'Children and out-of-school youth in Carmona.',
                    'approach' => 'Track JCI Carmona participation in the city-led literacy initiative. Confirm the chapter-specific schedule and commitments with the project team.',
                    'partners' => 'City of Carmona and participating youth organizations.',
                    'request' => 'Coordinate chapter ownership and approval.',
                ],
            ],
            [
                'reference' => 'JCI-2025-REDHEART',
                'title' => 'How to Spot the Red Heart - Youth Dialogue',
                'area' => 'Individual Development',
                'status' => 'Completed',
                'venue' => 'Carmona City, Cavite',
                'concept' => [
                    'background' => 'A Carmona youth dialogue on care for self, family, and community was reported with JCI Carmona among its partners.',
                    'objective' => 'Encourage young people to reflect on civic responsibility and active participation.',
                    'beneficiaries' => 'Youth participants from Carmona and nearby communities.',
                    'approach' => 'Document JCI Carmona participation in the dialogue. Confirm chapter records for attendance and outcomes.',
                    'partners' => 'City of Carmona and Community Affairs and Youth Development Office.',
                    'request' => 'Document chapter participation and outcomes.',
                ],
            ],
            [
                'reference' => 'JCI-2026-IVY',
                'title' => 'International Volunteer Year 2026 - CALABARZON Forum',
                'area' => 'Community Impact',
                'status' => 'Completed',
                'starts_on' => '2026-07-14',
                'venue' => 'Carmona City, Cavite',
                'concept' => [
                    'background' => 'The regional volunteerism forum in Carmona included JCI Carmona sharing its experience in youth-led mobilization for vulnerable groups.',
                    'objective' => 'Share practical approaches to volunteer mobilization and community partnerships.',
                    'beneficiaries' => 'Volunteer organizations and forum participants in CALABARZON.',
                    'approach' => 'Document the chapter contribution and follow-up opportunities from the forum.',
                    'partners' => 'Philippine National Volunteer Service Coordinating Agency and City of Carmona.',
                    'request' => 'Document chapter participation and outcomes.',
                ],
            ],
            [
                'reference' => 'JCI-2026-LAPIS-FAMILY',
                'title' => 'Project LAPIS - Family Learning Support',
                'area' => 'Business & Entrepreneurship',
                'status' => 'Draft Concept',
                'venue' => 'Carmona City, Cavite',
                'concept' => [
                    'background' => 'Project LAPIS includes plans to support parents while their children attend literacy sessions. This chapter follow-up is a proposed plan.',
                    'objective' => 'Plan practical family learning and skills sessions alongside child literacy support.',
                    'beneficiaries' => 'Parents and caregivers of Project LAPIS learners.',
                    'approach' => 'Coordinate with the city-led Project LAPIS team to identify suitable family sessions and volunteer roles.',
                    'partners' => 'City of Carmona and Project LAPIS partners.',
                    'request' => 'For chapter discussion and review.',
                ],
            ],
            [
                'reference' => 'JCI-2026-YOUTH-DIALOGUE',
                'title' => 'Carmona Youth Civic Action Workshop',
                'area' => 'Individual Development',
                'status' => 'Submitted for President Review',
                'starts_on' => '2026-11-20',
                'venue' => 'Carmona City, Cavite',
                'concept' => [
                    'background' => 'JCI Carmona helped deliver the reported How to Spot the Red Heart youth dialogue. This workshop is a proposed follow-up.',
                    'objective' => 'Help young participants turn dialogue themes into practical community action ideas.',
                    'beneficiaries' => 'Youth participants in Carmona.',
                    'approach' => 'Facilitate a workshop on civic responsibility and small team-led action plans.',
                    'participants' => 'To be confirmed with partner organizations.',
                    'partners' => 'City of Carmona and youth partner organizations.',
                    'resources' => 'Facilitators, venue, learning materials, and participant support.',
                    'benefit' => 'Draft youth action plans and documented learning.',
                    'risks' => 'Schedule and participant availability require coordination.',
                    'request' => 'For president review.',
                ],
            ],
            [
                'reference' => 'JCI-2026-VOLUNTEER-TOOLKIT',
                'title' => 'Youth Volunteer Mobilization Toolkit',
                'area' => 'Community Impact',
                'status' => 'Approved',
                'starts_on' => '2026-11-05',
                'venue' => 'Carmona City, Cavite',
                'concept' => [
                    'background' => 'JCI Carmona shared youth-led mobilization work at the regional International Volunteer Year 2026 forum. This toolkit is a proposed chapter follow-up.',
                    'objective' => 'Prepare a reusable guide for organizing youth volunteers around community needs.',
                    'beneficiaries' => 'JCI Carmona volunteers and community partners.',
                    'approach' => 'Capture practical recruitment, orientation, and follow-up steps for volunteer teams.',
                    'partners' => 'Prospective local volunteer organizations.',
                    'request' => 'For chapter implementation planning.',
                ],
                'proposal' => [
                    'rationale' => 'Document a repeatable volunteer mobilization approach based on chapter experience.',
                    'objectives' => 'Produce a draft toolkit and review it with chapter volunteers.',
                    'beneficiaries' => 'Chapter volunteers and local partners.',
                    'description' => 'Plan to create and review a volunteer mobilization toolkit.',
                    'activities' => 'Gather examples, draft sections, review with volunteers, revise.',
                    'outputs' => 'A reviewed draft toolkit.',
                    'team' => 'Chapter volunteer planning team.',
                    'timeline' => 'November to December 2026.',
                    'budget' => 'Planning estimate pending Treasurer review.',
                ],
            ],
        ];

        $chair = User::where('role', 'member')->where('status', 'active')->orderBy('id')->first();
        foreach ($projects as $attributes) {
            $project = Project::firstOrCreate(['reference' => $attributes['reference']], $attributes + [
                'created_by' => $owner->id,
                'proposed_budget' => 0,
                'revision' => 1,
            ]);
            if ($chair && !$project->chair_id) $project->update(['chair_id' => $chair->id]);
            if ($project->reference === 'JCI-2026-LAPIS' && $project->status !== 'Ongoing') $project->update(['status' => 'Ongoing']);
            $concept = $project->concept ?? [];
            if (isset($concept['source_url']) || isset($concept['sample_record'])) {
                unset($concept['source_url'], $concept['sample_record']);
                $concept['request'] = $attributes['concept']['request'];
                $concept['background'] = $attributes['concept']['background'];
                $project->update(['concept' => $concept]);
            }
            if (str_contains($project->title, ' ? ')) $project->update(['title' => $attributes['title']]);
        }
    }
}
