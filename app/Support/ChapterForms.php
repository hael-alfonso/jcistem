<?php
namespace App\Support;

class ChapterForms
{
    public const AREAS = ['Community Impact', 'Individual Development', 'Business & Entrepreneurship', 'International Cooperation'];
    public const CONCEPT = [
        'background' => 'Background / community need',
        'objective' => 'Main objective',
        'beneficiaries' => 'Target beneficiaries / community',
        'approach' => 'Brief project concept / proposed approach',
        'participants' => 'Estimated participants / beneficiaries',
        'partners' => 'Possible partners / stakeholders',
        'resources' => 'Preliminary resource requirements',
        'benefit' => 'Expected output / community benefit',
        'risks' => 'Initial risks / considerations',
        'request' => 'Request for endorsement / proponent signature',
    ];
    public const PROPOSAL = [
        'rationale' => 'Project rationale / needs analysis',
        'objectives' => 'SMART objectives',
        'beneficiaries' => 'Target beneficiaries',
        'description' => 'Detailed project description',
        'activities' => 'Activities / program flow',
        'outputs' => 'Expected outputs and outcomes',
        'team' => 'Project team / committees',
        'volunteers' => 'Volunteer / member participation',
        'partners' => 'Partner / stakeholder plan',
        'timeline' => 'Detailed timeline / milestones',
        'resources' => 'Resources and logistics',
        'budget' => 'Detailed budget / categories and cost breakdown',
        'funding' => 'Proposed funding sources',
        'risks' => 'Risk assessment / contingency plan',
        'evaluation' => 'Monitoring and evaluation plan',
        'indicators' => 'Success indicators / KPIs',
        'documentation' => 'Documentation requirements',
        'letters' => 'JCI LOI / partnership requirements',
        'sustainability' => 'Sustainability / follow-up plan',
    ];
    public const PROPOSAL_SECTIONS = [
        'Community need and intended impact' => ['rationale', 'objectives', 'beneficiaries', 'description', 'outputs'],
        'Delivery plan and people' => ['activities', 'timeline', 'team', 'volunteers', 'partners'],
        'Resources and funding' => ['resources', 'budget', 'funding'],
        'Safeguards and follow-through' => ['risks', 'evaluation', 'indicators', 'documentation', 'letters', 'sustainability'],
    ];
    public const LETTER = [
        'letter_date' => 'Letter date',
        'recipient' => 'Recipient name',
        'recipient_title' => 'Recipient title / position',
        'organization' => 'Company, organization, or LGU',
        'address' => 'Mailing address',
        'purpose' => 'Community problem or need',
        'project_action' => 'Proposed project action or solution',
        'target_location' => 'Target community or location',
        'beneficiary_count' => 'Estimated number of beneficiaries',
        'partner_alignment' => 'Why this partner is a good fit',
        'support' => 'Specific support requested',
        'commitments' => 'Recognition or benefits the chapter can provide',
        'signatory' => 'Proposed project chair / signatory name',
        'president_name' => 'Local Organization President name',
        'body' => 'Additional message (optional)',
    ];
    public const LETTER_REQUIRED = [
        'letter_date' => 'Letter date',
        'recipient' => 'Recipient name',
        'organization' => 'Company, organization, or LGU',
        'purpose' => 'Community problem or need',
        'project_action' => 'Proposed project action or solution',
        'target_location' => 'Target community or location',
        'beneficiary_count' => 'Estimated number of beneficiaries',
        'support' => 'Specific support requested',
        'signatory' => 'Proposed project chair / signatory name',
        'president_name' => 'Local Organization President name',
    ];
    public const REPORT = [
        'summary' => 'Project summary', 'objectives' => 'Objectives achieved',
        'beneficiaries' => 'Beneficiaries reached', 'activities' => 'Activities completed',
        'outputs' => 'Outputs', 'outcomes' => 'Outcomes / community impact',
        'participation' => 'Member / volunteer participation', 'issues' => 'Issues encountered',
        'recommendations' => 'Recommendations / follow-up', 'evidence' => 'Evidence / documentation references',
    ];
    public const REPORT_SECTIONS = [
        'Purpose and results' => ['summary', 'objectives', 'beneficiaries', 'activities', 'outputs', 'outcomes'],
        'People, evidence, and follow-up' => ['participation', 'evidence', 'issues', 'recommendations'],
    ];
    public const FINANCIAL_REPORT = [
        'period_start' => 'Reporting period start (YYYY-MM-DD)',
        'period_end' => 'Reporting period end (YYYY-MM-DD)',
        'summary' => 'Financial summary', 'reconciliation' => 'Reconciliation / fund balances',
        'issues' => 'Outstanding items / liquidation', 'recommendations' => 'Recommendations',
    ];
}

