<?php
namespace App\Support;

class ChapterForms
{
    public const AREAS = ['Community Impact', 'Individual Development', 'Business & Entrepreneurship', 'International Cooperation'];
    public const SDGS = [
        '1' => 'No Poverty',
        '2' => 'Zero Hunger',
        '3' => 'Good Health and Well-Being',
        '4' => 'Quality Education',
        '5' => 'Gender Equality',
        '6' => 'Clean Water and Sanitation',
        '7' => 'Affordable and Clean Energy',
        '8' => 'Decent Work and Economic Growth',
        '9' => 'Industry, Innovation and Infrastructure',
        '10' => 'Reduced Inequalities',
        '11' => 'Sustainable Cities and Communities',
        '12' => 'Responsible Consumption and Production',
        '13' => 'Climate Action',
        '14' => 'Life Below Water',
        '15' => 'Life on Land',
        '16' => 'Peace, Justice and Strong Institutions',
        '17' => 'Partnerships for the Goals',
    ];
    public const MEMBERSHIP_INTERESTS = ['Debate', 'Public speaking', 'Training as participant', 'Training as facilitator', 'Charity', 'Starting a business', 'Growing a business', 'Business networking', 'Project management', 'International events'];
    public const CONCEPT = [
        'letter_date' => 'Letter date',
        'president_name' => 'Local Organization President',
        'chapter_address' => 'Chapter address',
        'sdg_number' => 'United Nations SDG number',
        'sdg_name' => 'United Nations SDG name',
        'objective' => 'Primary project objective',
        'beneficiaries' => 'Target beneficiaries / expected participants',
        'committee_size' => 'Required number of active JCI members',
        'funding_source' => 'Fundraising strategy or chapter allocation',
        'resources' => 'Additional resource requirements',
        'request' => 'Formal request to the Board',
        'proponent_position' => 'Your JCI position / title',
        'contact_info' => 'Your contact information',
    ];
    public const CONCEPT_SECTIONS = [
        'Recipient and SDG alignment' => ['letter_date', 'president_name', 'chapter_address', 'sdg_number', 'sdg_name'],
        'Project overview' => ['objective', 'beneficiaries', 'committee_size'],
        'Resource requirements and signatory' => ['funding_source', 'resources', 'request', 'proponent_position', 'contact_info'],
    ];
    public const CONCEPT_REQUIRED = [
        'president_name' => 'Local Organization President',
        'sdg_number' => 'SDG number',
        'sdg_name' => 'SDG name',
        'objective' => 'Primary project objective',
        'beneficiaries' => 'Target beneficiaries',
        'committee_size' => 'Required active JCI members',
        'funding_source' => 'Funding source',
        'proponent_position' => 'JCI position / title',
        'contact_info' => 'Contact information',
    ];
    public const PROPOSAL = [
        'rationale' => 'Project rationale / needs analysis',
        'objectives' => 'SMART objectives',
        'beneficiaries' => 'Target beneficiaries',
        'description' => 'Detailed project description',
        'activities' => 'Activities / program flow',
        'outputs' => 'Potential benefits and general comments',
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
        'letters' => 'Partner commitments and supporting letters',
        'sustainability' => 'Sustainability / follow-up plan',
    ];
    public const PROPOSAL_SECTIONS = [
        'Background and rationale' => ['rationale', 'description'],
        'Goals and objectives' => ['objectives'],
        'Target beneficiaries' => ['beneficiaries'],
        'Estimated JCI members and their functions' => ['team', 'volunteers'],
        'Plan of action' => ['activities', 'timeline', 'resources', 'partners', 'risks'],
        'Estimated budget and funding source' => ['funding', 'budget'],
        'Potential benefits and general comments' => ['outputs', 'evaluation', 'indicators', 'documentation', 'letters', 'sustainability'],
    ];
    public const PROPOSAL_REQUIRED = [
        'rationale' => 'Background and rationale',
        'objectives' => 'Goals and objectives',
        'beneficiaries' => 'Target beneficiaries',
        'team' => 'JCI members and their functions',
        'activities' => 'Plan of action',
        'funding' => 'Funding source',
        'budget' => 'Estimated expenditures',
        'outputs' => 'Potential benefits and general comments',
    ];
    public const PROPOSAL_INCOME_LINES = [
        'chapter_allocation' => 'Chapter allocation',
        'sponsorships' => 'Sponsorships / partners',
        'other_sources' => 'Other sources',
    ];
    public const PROPOSAL_EXPENSE_LINES = [
        'logistics_venue' => 'Logistics and venue',
        'marketing_materials' => 'Marketing materials',
        'tokens_refreshments' => 'Tokens and refreshments',
        'other_expenses' => 'Other expenses',
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
    public const LIQUIDATION = [
        'report_date' => 'Report date',
        'voucher_reference' => 'Cash disbursement voucher / request number',
        'cash_received' => 'Total cash advanced / received (PHP)',
        'receipt_notes' => 'Receipt and invoice references',
        'reconciliation_notes' => 'Cash return or reimbursement details',
        'treasurer_name' => 'Verified by (LO Treasurer)',
        'president_name' => 'Approved by (LO President)',
    ];
    public const FINANCIAL_REPORT = [
        'period_start' => 'Reporting period start (YYYY-MM-DD)',
        'period_end' => 'Reporting period end (YYYY-MM-DD)',
        'summary' => 'Financial summary', 'reconciliation' => 'Reconciliation / fund balances',
        'issues' => 'Outstanding items / liquidation', 'recommendations' => 'Recommendations',
    ];
}

