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
        'letters' => 'External LOI / partnership requirements',
        'sustainability' => 'Sustainability / follow-up plan',
    ];
    public const LETTER = [
        'recipient' => 'Recipient / contact person', 'organization' => 'Organization',
        'address' => 'Address / contact details', 'purpose' => 'Purpose',
        'support' => 'Requested collaboration / support', 'commitments' => 'Commitments / specific requests',
        'signatory' => 'Signatory details', 'body' => 'Letter body',
    ];
    public const REPORT = [
        'summary' => 'Project summary', 'objectives' => 'Objectives achieved',
        'beneficiaries' => 'Beneficiaries reached', 'activities' => 'Activities completed',
        'outputs' => 'Outputs', 'outcomes' => 'Outcomes / community impact',
        'participation' => 'Member / volunteer participation', 'issues' => 'Issues encountered',
        'recommendations' => 'Recommendations / follow-up', 'evidence' => 'Evidence / documentation references',
    ];
    public const FINANCIAL_REPORT = [
        'period_start' => 'Reporting period start (YYYY-MM-DD)',
        'period_end' => 'Reporting period end (YYYY-MM-DD)',
        'summary' => 'Financial summary', 'reconciliation' => 'Reconciliation / fund balances',
        'issues' => 'Outstanding items / liquidation', 'recommendations' => 'Recommendations',
    ];
}

