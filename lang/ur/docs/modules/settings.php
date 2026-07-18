<?php

$field = static fn (string $name, string $label, string $type, bool $required, string $rules): array => compact('name', 'label', 'type', 'required', 'rules');
$stub = static function (string $title, string $summary, string $route, array $permissions, array $purpose, array $notes, array $technical): array {
    return [
        'title' => $title, 'summary' => $summary,
        'meta' => ['routes' => [$route], 'permissions' => $permissions, 'related' => []],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد اور موجودہ رویہ', 'paragraphs' => $purpose],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'Configuration', 'fields' => []],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['مطلوب permission کے ساتھ صفحہ کھولیں۔', 'موجودہ values/dependencies دیکھیں۔', 'Save کے بعد consuming feature میں تصدیق کریں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI کی حد', 'paragraphs' => ['AI اس configuration کو خاموشی سے نہ بدلے۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'قواعد', 'list' => ['Server-side authorization اور sensitive changes کا audit لازم۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['یہ settings متعلقہ application services کو configuration دیتی ہیں۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => $notes],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Unauthorized write reject ہو۔', 'Saved value consuming feature میں نظر آئے۔', 'Secrets/destructive operations اضافی حفاظت لیں۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => $technical],
        ],
    ];
};

return [
    'store-profile' => $stub('Store Profile', 'Company identity اور storefront chrome؛ Settings کا landing page، overview نہیں۔', '/admin/settings/store-profile', ['settings.view'], [
        'یہ صفحہ company-profile management دکھاتا ہے۔ StoreSetting public store کے top bar، header، social، menu، category grid، WhatsApp اور footer fields رکھتا ہے۔',
        'Settings cluster میں OverviewPage نہیں؛ orientation اسی صفحے کی docs میں ہے۔',
    ], [
        'CompanyProfile اور StoreSetting کی حدود واضح کر کے دونوں کو explicit UI دیں۔',
        'Media/public identity version اور audit کریں، URLs/phone/map validate کریں، save پر store cache clear کریں۔',
        'Dedicated manage permission بنائیں؛ settings.view کو write نہ سمجھیں۔',
    ], ['page' => 'App\\Filament\\Clusters\\Settings\\StoreSettingPage', 'livewire' => 'App\\Livewire\\Settings\\ManageCompanyProfile', 'models' => ['App\\Models\\CompanyProfile', 'App\\Models\\StoreSetting'], 'service' => 'App\\Services\\Settings\\CompanyProfileService']),

    'tax-system' => $stub('Tax System', 'Tax registration، rates، inclusive/exclusive pricing اور document rules کا placeholder۔', '/admin/settings/tax-system', ['settings.view'], [
        'Cluster page placeholder ہے؛ مکمل tax engine اس صفحے سے ثابت نہیں۔ Sales/Purchase کا موجودہ tax behavior ان کی services/fields میں ہے۔',
    ], [
        'Jurisdiction، registration، effective-dated rates، exemptions، rounding، price mode اور invoice snapshots model کریں۔',
        'Rate change تاریخی invoices recalculate نہ کرے؛ applied rate/base/tax line پر snapshot ہو۔',
        'settings.tax.manage اور audit شامل کریں۔',
    ], ['page' => 'App\\Filament\\Clusters\\Settings\\TaxSystemPage', 'status' => 'placeholder UI']),

    'ai-settings' => [
        'title' => 'AI Settings', 'summary' => 'Provider credentials، model discovery، tests، pause/resume اور active configuration۔',
        'meta' => ['routes' => ['/admin/settings/ai-settings'], 'permissions' => ['settings.view'], 'related' => ['ai-logs', 'catalog', 'contacts', 'finance', 'staff']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Enrichment services کے لیے active AI provider، encrypted API credential اور model configure کریں؛ test، pause/resume یا delete بھی ممکن ہے۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('provider', 'Provider', 'AiProvider enum', true, 'App config میں enabled ہو۔'),
                $field('apiKey / api_key', 'API key', 'secret', true, 'Form min 8؛ save کے بعد plaintext واپس نہ آئے۔'),
                $field('selectedModelId / model_id', 'Model ID', 'string', true, 'Fetched models سے انتخاب۔'),
                $field('selectedModelName / model_name', 'Model name', 'string', false, 'Display/audit label۔'),
                $field('status / paused state', 'Lifecycle', 'state', true, 'Test، pause، resume، delete۔'),
                $field('last_test_*', 'Connection evidence', 'status/message/time', false, 'Secret شامل نہ ہو۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Provider/key دیں۔', 'Models load/search/select کریں۔', 'Draft connection test کریں۔', 'Configuration save کریں۔', 'Bounded prompt test یا pause/resume/retest کریں۔', 'AI Logs دیکھیں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'Downstream behavior', 'paragraphs' => ['Domain enrichment services active config استعمال کرتی ہیں؛ output draft ہے اور save سے پہلے domain validation لازم۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Security', 'list' => ['Provider enum/enabled ہو۔', 'Save پر key/model ID لازم۔', 'Test prompt 2–500 chars۔', 'Key encrypted، logs/errors/backups میں redacted، dedicated permission سے write۔', 'Pause downstream calls friendly fail-closed کرے۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['AiConfigurationService، AiProviderRegistry، domain enrichment services، AI Logs۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['settings.ai.manage متعارف اور تمام mutations پر enforce کریں۔', 'Secret manager/encrypted casts، rotation، timeout/retry/rate limits، feature model policies۔', 'Sensitive prompts unredacted log نہ ہوں۔', 'Connection health اور active/paused الگ states ہوں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Disabled provider save نہیں۔', 'Key کے بغیر model load نہیں۔', 'Saved key plaintext render/log نہیں۔', 'Paused AI manual save کو نہ روکے مگر enrichment بند۔', 'ہر call safe metadata log کرے۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Settings\\AiSettingsPage', 'livewire' => ['App\\Livewire\\AiSettings\\Manager', 'App\\Livewire\\AiSettings\\Form'], 'services' => ['App\\Services\\Ai\\AiConfigurationService', 'App\\Services\\Ai\\AiProviderRegistry'], 'model' => 'App\\Models\\AiConfiguration']],
        ],
    ],

    'ai-logs' => $stub('AI Logs', 'AI requests، outcomes، timing اور safe diagnostics کا operational trace۔', '/admin/settings/ai-logs', ['settings.view'], [
        'List اور detail components diagnosis/governance دیتے ہیں؛ logs کو secret یا personal-data dump نہ بنائیں۔',
    ], [
        'Retention/redaction، provider request IDs، feature/actor correlation، latency/token/cost اور restricted payload visibility طے کریں۔',
        'settings.ai.logs.view اور الگ purge permission بنائیں۔',
    ], ['page' => 'App\\Filament\\Clusters\\Settings\\AiLogsPage', 'livewire' => ['App\\Livewire\\AiLogs\\ListLogs', 'App\\Livewire\\AiLogs\\Detail']]),

    'system-errors' => $stub('System Errors', 'محفوظ application exceptions اور محدود Laravel file-log fallback کے لیے operational inbox۔', '/admin/settings/system-errors', ['settings.system-errors.view', 'settings.system-errors.manage'], [
        'Unhandled اور explicitly reported exceptions fingerprint، redact اور occurrence کے مطابق group ہو کر Open یا Resolved حالت میں دکھتے ہیں۔',
        'File-log fallback صرف محدود laravel*.log entries پڑھتا ہے؛ arbitrary paths، raw request payloads، credentials، cookies یا authorization headers ظاہر نہیں ہوتے۔',
        'Copy Technical Report maintainers کے ساتھ شیئر کرنے کے لیے محفوظ diagnostic bundle بناتا ہے۔',
    ], [
        'Database persistence best-effort رہے تاکہ logging failure اصل application exception کو تبدیل نہ کرے۔',
        'Open records محفوظ اور resolved records 90 دن بعد prune ہوں؛ file-log access read-only اور محدود رہے۔',
        'Page اور ہر Livewire query/action پر dedicated view/manage permissions لازم ہوں۔',
    ], ['page' => 'App\\Filament\\Clusters\\Settings\\SystemErrorsPage', 'livewire' => ['App\\Livewire\\SystemErrors\\ListErrors', 'App\\Livewire\\SystemErrors\\Detail'], 'services' => ['App\\Services\\SystemErrors\\SystemErrorCaptureService', 'App\\Services\\SystemErrors\\SystemErrorSanitizer', 'App\\Services\\SystemErrors\\SystemLogFileReader'], 'model' => 'App\\Models\\SystemError']),

    'printing' => $stub('Printing', 'Documents، receipts، product labels اور price labels کے print profiles۔', '/admin/settings/printing', ['settings.view', 'settings.printing.manage'], [
        'Bilingual profile name، category، label kind، mm dimensions، margin، orientation، printer hint اور default profile۔ Current UI create/edit/default/search/delete دیتی ہے۔',
    ], [
        'System/default profiles محفوظ رکھیں اور required default delete نہ ہو۔',
        'Template/version assignment، printable-area validation، preview/test print اور per-document defaults شامل کریں۔',
        'settings.printing.manage server-side جاری رکھیں۔',
    ], ['page' => 'App\\Filament\\Clusters\\Settings\\PrintingPage', 'livewire' => 'App\\Livewire\\Settings\\ManagePrintProfiles', 'service' => 'App\\Services\\Printing\\PrintProfileService', 'model' => 'App\\Models\\PrintProfile']),

    'permission' => [
        'title' => 'Permissions', 'summary' => 'Roles اور grouped permission keys کی RBAC matrix۔',
        'meta' => ['routes' => ['/admin/settings/permission'], 'permissions' => ['settings.view', 'settings.roles.manage'], 'related' => ['users']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Non-system roles بنائیں اور PermissionRegistry groups سے keys دیں۔ User کو ایک role ملتا ہے؛ Super Admin ہر hasPermission() میں true۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('name', 'Role name', 'string', true, 'Unique max 255۔'),
                $field('slug', 'Slug', 'string', false, 'Stable key؛ super-admin bypass۔'),
                $field('description', 'Description', 'text', false, 'Max 2000۔'),
                $field('is_system', 'System role', 'boolean', true, 'Form میں read-only۔'),
                $field('selectedPermissions', 'Permission keys', 'array/pivot', false, 'Unique keys role_permission پر sync۔'),
                $field('permissions.key / group_key / labels / sort_order', 'Catalog', 'master', true, 'PermissionRegistry سے seed/define۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Groups دیکھیں۔', 'Least-privilege role بنائیں۔', 'Users پر role assign کریں۔', 'Non-super-admin سے allowed/denied actions test کریں۔', 'Changes کو security event سمجھیں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['AI permissions assign نہیں کرتا؛ authorized human approval لازم۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'RBAC قواعد', 'list' => ['Mutation کے لیے settings.roles.manage۔', 'System roles read-only؛ Super Admin all keys۔', 'Page، Livewire، controller، export اور service boundary پر authorization؛ صرف nav نہیں۔', 'Labels بدل سکتے ہیں، key stable رہے۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['User.role_id، User::hasPermission، cluster toolbars، finance.* keys، PermissionRegistry۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['ہر mutating route/action کا permission coverage test۔', 'Last Super Admin/self-lockout روکیں۔', 'Before/after permission audit۔', 'Role change پر auth cache invalidate۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Manage key کے بغیر direct mutation fail۔', 'System/Super Admin form سے کمزور نہ ہو۔', 'Changes فوراً nav/server actions پر اثر کریں۔', 'ہر finance action finance.* key مانے۔', 'Last active Super Admin محفوظ۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Settings\\PermissionPage', 'livewire' => 'App\\Livewire\\Roles\\Form', 'services' => ['App\\Services\\Access\\RoleService', 'App\\Services\\Access\\PermissionRegistry'], 'models' => ['App\\Models\\Role', 'App\\Models\\Permission'], 'tables' => ['roles', 'permissions', 'permission_role']]],
        ],
    ],

    'users' => [
        'title' => 'Users', 'summary' => 'Admin logins، role/status، bilingual profile، contacts، banks اور identity media۔',
        'meta' => ['routes' => ['/admin/settings/users'], 'permissions' => ['settings.view', 'settings.users.manage'], 'related' => ['permission', 'staff']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Application login accounts اور ایک role manage کریں۔ Non-super-admin admin access کے لیے active status + role لازم؛ form Staff context میں بھی استعمال ہوتا ہے۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('user_number', 'User number', 'string', false, 'Blank پر service-generated۔'),
                $field('name / name_ur', 'نام', 'strings', true, 'English required max 255؛ Urdu optional۔'),
                $field('role_id', 'Role', 'FK', true, 'Existing؛ current default super-admin risk۔'),
                $field('status', 'Status', 'enum', true, 'pending|active|inactive|rejected۔'),
                $field('email', 'Email', 'email', true, 'Unique max 255۔'),
                $field('password / confirmation', 'Password', 'secret', true, 'Create min 8 + confirmation؛ edit optional۔'),
                $field('address / address_ur', 'پتہ', 'text', false, 'Max 2000۔'),
                $field('phone_numbers', 'Phones', 'JSON', false, 'Contact EN/UR + phone max 30۔'),
                $field('bank_accounts', 'Banks', 'JSON', false, 'Bank/title/account number۔'),
                $field('profile_photo_path', 'Photo', 'WebP path', false, 'Max 5MB۔'),
                $field('cnic_front_path / cnic_back_path', 'CNIC', 'paths', false, 'Current public storage؛ rebuild میں private۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Least-privilege role پہلے بنائیں۔', 'Identity/email/password/role درج کریں۔', 'Verification تک pending/inactive رکھیں۔', 'Edit سے credentials/status/role بدلیں۔', 'Compromised/departed account فوراً disable کریں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI enrichment', 'paragraphs' => ['UserEnrichmentService missing bilingual text بھر سکتی ہے؛ role/status/password/email/phone/account/CNIC نہ چنے یا infer کرے۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Access/login', 'list' => ['Settings mutation settings.users.manage؛ Staff context staff.create/edit۔', 'Non-super-admin: active + role؛ Super Admin بھی active۔', 'Password hashed/hidden۔', 'Current default role super-admin ہے؛ rebuild میں no role یا safe role۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['Role permissions، approval/login، finance purchaser reference، shared Staff form۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['Auto super-admin default ختم؛ elevated role پر deliberate re-auth۔', 'Phones/banks normalize اور mask۔', 'MFA، reset/session revoke، last-login، audit، concurrency۔', 'Last active Super Admin/self-change protection۔', 'CNIC private authorized download۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Unique email/role/confirmed password لازم۔', 'Inactive/pending/rejected admin میں داخل نہ ہو۔', 'Non-manager direct event سے mutate نہ کرے۔', 'نیا user implicit Super Admin نہ ہو۔', 'CNIC explicit authorization کے بغیر نہیں۔', 'Role/status audit اور فوری revoke۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Settings\\UsersPage', 'livewire' => ['App\\Livewire\\Users\\ListUsers', 'App\\Livewire\\Users\\Form'], 'services' => ['App\\Services\\Access\\UserService', 'App\\Services\\Access\\UserEnrichmentService'], 'models' => ['App\\Models\\User', 'App\\Models\\Role'], 'table' => 'users']],
        ],
    ],

    'backups' => $stub('Backups', 'Database/full archives create، import، list، restore، export اور delete۔', '/admin/settings/backups', ['settings.view', 'settings.backups.manage'], [
        'Current manager full/database backup، incoming import، controller upload، products/categories export اور RESTORE confirmation کے بعد restore دیتا ہے۔ Safety backup/local login preservation options ہیں؛ restored AI keys clear ہو سکتی ہیں۔',
    ], [
        'settings.backups.manage، re-auth، CSRF، signature/integrity، encryption، off-site copy اور restore audit۔',
        'Isolated restore tests، retention، size/upload limits، DB compatibility اور recovery objectives document کریں۔',
        'Backup archives public web root میں نہ ہوں۔',
    ], ['page' => 'App\\Filament\\Clusters\\Settings\\BackupsPage', 'livewire' => 'App\\Livewire\\Settings\\ManageBackups', 'services' => ['App\\Services\\Backup\\BackupService', 'App\\Services\\Backup\\RestoreService']]),
];
