<?php

$field = static fn (string $name, string $label, string $type, bool $required, string $rules): array => compact('name', 'label', 'type', 'required', 'rules');
$stub = static function (string $title, string $summary, string $route, array $permissions, array $purpose, array $notes, array $technical): array {
    return [
        'title' => $title,
        'summary' => $summary,
        'meta' => ['routes' => [$route], 'permissions' => $permissions, 'related' => []],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'Purpose and current behavior', 'paragraphs' => $purpose],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'Configuration surface', 'fields' => []],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'Workflow', 'steps' => ['Open the page with the required settings permission.', 'Review current values and dependencies.', 'Apply and verify the change in the consuming module.']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI boundary', 'paragraphs' => ['AI must not silently change this configuration.']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Rules', 'list' => ['Enforce authorization server-side and audit sensitive changes.']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'Integrations', 'list' => ['The settings page supplies configuration to the relevant application services.']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild notes', 'list' => $notes],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'Acceptance', 'acceptance' => ['Unauthorized writes are rejected.', 'Saved values are reflected by their consuming feature.', 'Secrets and destructive operations receive additional protection.']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'Technical', 'classes' => $technical],
        ],
    ];
};

return [
    'overview' => $stub(
        'Settings Overview',
        'A dedicated orientation page linking to each authorized settings area while Business Profile remains separate.',
        '/admin/settings/overview',
        ['settings.view'],
        ['The Settings sidebar destination opens this overview instead of redirecting into Business Profile.'],
        ['Keep this page read-only and hide links the current user cannot access.'],
        ['page' => 'App\\Filament\\Clusters\\Settings\\OverviewPage']
    ),

    'store-profile' => $stub(
        'Store Profile',
        'Company identity and storefront chrome configuration, separate from the Settings overview.',
        '/admin/settings/store-profile',
        ['settings.view'],
        [
            'The page currently combines company-profile management with settings context. StoreSetting separately holds storefront top bar, header, social, menu, category grid, WhatsApp, and footer fields consumed by the public store.',
            'The dedicated Settings Overview provides orientation; this page remains focused on Business Profile.',
        ],
        [
            'Clarify whether CompanyProfile and StoreSetting are one settings aggregate or two bounded records, then expose both explicitly.',
            'Version media and public identity changes, validate URLs/phone/map embed, and invalidate storefront caches after save.',
            'Add a dedicated manage permission and audit trail; settings.view alone should not imply write access.',
        ],
        ['page' => 'App\\Filament\\Clusters\\Settings\\StoreSettingPage', 'livewire' => 'App\\Livewire\\Settings\\ManageCompanyProfile', 'models' => ['App\\Models\\CompanyProfile', 'App\\Models\\StoreSetting'], 'service' => 'App\\Services\\Settings\\CompanyProfileService']
    ),

    'tax-system' => $stub(
        'Tax System',
        'Placeholder for tax registration, rates, inclusive/exclusive pricing, and document rules.',
        '/admin/settings/tax-system',
        ['settings.view'],
        ['The cluster page exists as a placeholder. No complete tax engine is evidenced by this page; current sales/purchase tax behavior remains in their existing services and fields.'],
        [
            'Model jurisdictions, registrations, effective-dated rates, exemptions, rounding, inclusive/exclusive prices, and document snapshots before enabling writes.',
            'Never recalculate historical invoices when a rate changes; store the applied rate/base/tax on each document line.',
            'Add settings.tax.manage and tax-change audit records.',
        ],
        ['page' => 'App\\Filament\\Clusters\\Settings\\TaxSystemPage', 'status' => 'placeholder UI']
    ),

    'media-library' => $stub(
        'Media Library',
        'A searchable registry and safe reuse workflow for public application images.',
        '/admin/settings/media-library',
        ['settings.media.view', 'settings.media.manage'],
        [
            'The library indexes public catalog, store, company, customer, supplier, and user profile images while excluding CNICs, receipts, proofs, bills, and cards.',
            'Original upload names are retained as metadata; random physical paths prevent unsafe filenames and collisions.',
            'Usage records protect shared and historical snapshot images from accidental deletion.',
        ],
        [
            'Only unused images can enter Trash, and permanent deletion is delayed for 30 days.',
            'Backfill is idempotent and does not copy existing files.',
            'Keep every new public-image producer registered through OptimizedImageStorage and MediaUsageObserver.',
        ],
        ['page' => 'App\\Filament\\Clusters\\Settings\\MediaLibraryPage', 'livewire' => ['App\\Livewire\\Media\\ListMedia', 'App\\Livewire\\Media\\Form'], 'service' => 'App\\Services\\Media\\MediaLibraryService', 'models' => ['App\\Models\\MediaAsset', 'App\\Models\\MediaUsage']]
    ),

    'ai-settings' => [
        'title' => 'AI Settings',
        'summary' => 'Provider credentials, model discovery, connection tests, pause/resume, and the active AI configuration.',
        'meta' => ['routes' => ['/admin/settings/ai-settings'], 'permissions' => ['settings.view'], 'related' => ['ai-logs', 'catalog', 'contacts', 'finance', 'staff']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'Purpose', 'paragraphs' => ['Configure the active AI provider, encrypted API credential, and model used by enrichment services. The manager can test prompts, retest, pause/resume, or delete the configuration.']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'Fields and state', 'fields' => [
                $field('provider', 'Provider', 'AiProvider enum', true, 'Must be enabled by application configuration.'),
                $field('apiKey / api_key', 'API key', 'secret string', true, 'Min 8 in form; never return plaintext after save.'),
                $field('selectedModelId / model_id', 'Model ID', 'string', true, 'Selected from fetched provider models.'),
                $field('selectedModelName / model_name', 'Model name', 'string', false, 'Display/audit label.'),
                $field('status / paused state', 'Lifecycle', 'enum/state', true, 'Active configuration can be tested, paused, resumed, deleted.'),
                $field('last_test_*', 'Connection evidence', 'status/message/timestamp', false, 'Updated by tests; do not include secret.'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'Workflow', 'steps' => ['Choose an enabled provider and enter its key.', 'Load provider models, search, and select one.', 'Test the draft connection.', 'Save the active configuration.', 'Send a bounded test prompt; pause/resume or retest as needed.', 'Review operational requests in AI Logs.']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'Downstream behavior', 'paragraphs' => ['Catalog, contacts, finance masters, and users call enrichment services through the active configuration. AI output is a draft and must pass domain validation before persistence.']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Security and validation', 'list' => ['Provider must map to AiProvider and be enabled.', 'API key and model ID are required to save.', 'Test prompt is 2–500 characters and requires active configuration.', 'Keys must be encrypted at rest, redacted from logs/errors/backups where policy requires, and writable only by a dedicated permission.', 'Pause must make downstream calls fail closed with a friendly configuration state.']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'Integrations', 'list' => ['AiConfigurationService fetches models, saves, tests, prompts, pauses, resumes, and deletes.', 'AiProviderRegistry exposes enabled providers.', 'Domain enrichment services consume active configuration.', 'AI Logs records request metadata and outcomes.']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild notes', 'list' => ['Current page metadata advertises settings.view; introduce settings.ai.manage and enforce it in every mutation.', 'Use secret-manager/encrypted casts, key rotation, timeout/retry/rate limits, and per-feature model policy.', 'Never place raw prompts containing credentials or sensitive identity in unredacted logs.', 'Separate connection health from configuration active/paused status.']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'Acceptance', 'acceptance' => ['Disabled provider cannot save.', 'Models cannot load without a plausible key.', 'Saved key is never rendered or logged plaintext.', 'Paused AI prevents enrichment without affecting manual save.', 'Every call records safe success/failure metadata.']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'Technical', 'classes' => ['page' => 'App\\Filament\\Clusters\\Settings\\AiSettingsPage', 'livewire' => ['App\\Livewire\\AiSettings\\Manager', 'App\\Livewire\\AiSettings\\Form'], 'services' => ['App\\Services\\Ai\\AiConfigurationService', 'App\\Services\\Ai\\AiProviderRegistry'], 'model' => 'App\\Models\\AiConfiguration']],
        ],
    ],

    'ai-logs' => $stub(
        'AI Logs',
        'Operational trace of AI requests, outcomes, timing, and safe diagnostics.',
        '/admin/settings/ai-logs',
        ['settings.view'],
        ['The page provides AI log listing and detail components. Logs support diagnosis and governance; they must not become a secret or personal-data dump.'],
        ['Define retention and redaction policy, provider request IDs, feature/actor correlation, latency/token/cost fields, and restricted payload visibility.', 'Add settings.ai.logs.view and a separate purge permission.'],
        ['page' => 'App\\Filament\\Clusters\\Settings\\AiLogsPage', 'livewire' => ['App\\Livewire\\AiLogs\\ListLogs', 'App\\Livewire\\AiLogs\\Detail']]
    ),

    'system-errors' => $stub(
        'System Errors',
        'A secure operational inbox for sanitized application exceptions and bounded Laravel file-log fallback entries.',
        '/admin/settings/system-errors',
        ['settings.system-errors.view', 'settings.system-errors.manage'],
        [
            'Unhandled and explicitly reported exceptions are fingerprinted, redacted, grouped by occurrence, and shown with Open or Resolved status.',
            'The file-log fallback reads only bounded laravel*.log entries and never exposes arbitrary paths, raw request payloads, credentials, cookies, or authorization headers.',
            'Copy Technical Report produces a sanitized diagnostic bundle suitable for sharing with maintainers.',
            'Bell alerts are per-user unread notifications: opening System Errors acknowledges current critical occurrences, while a later recurrence becomes unread again.',
            'Ignored transient HTTP connection failures remain in AI Logs; verified hosting storage-link limitations can be reconciled with a recorded resolution reason.',
        ],
        [
            'Keep database persistence best-effort so a logging failure never replaces the original application exception.',
            'Retain open records and prune resolved records after 90 days; keep file-log access read-only and bounded.',
            'Require dedicated view/manage permissions in the page and every Livewire query or action.',
        ],
        ['page' => 'App\\Filament\\Clusters\\Settings\\SystemErrorsPage', 'livewire' => ['App\\Livewire\\SystemErrors\\ListErrors', 'App\\Livewire\\SystemErrors\\Detail'], 'services' => ['App\\Services\\SystemErrors\\SystemErrorCaptureService', 'App\\Services\\SystemErrors\\SystemErrorSanitizer', 'App\\Services\\SystemErrors\\SystemLogFileReader'], 'model' => 'App\\Models\\SystemError']
    ),

    'printing' => $stub(
        'Printing',
        'Document, receipt, product-label, and price-label print profiles.',
        '/admin/settings/printing',
        ['settings.view', 'settings.printing.manage'],
        ['Manage bilingual profile names, category, optional label kind, dimensions in millimetres, margins, orientation, printer hint, and the default profile. Current implementation supports create, edit, set default, search, and delete through PrintProfileService.'],
        ['Preserve system/default profiles and block deleting a required default.', 'Add template/version assignment, printable-area validation, preview/test print, and per-document default selection.', 'Continue enforcing settings.printing.manage server-side.'],
        ['page' => 'App\\Filament\\Clusters\\Settings\\PrintingPage', 'livewire' => 'App\\Livewire\\Settings\\ManagePrintProfiles', 'service' => 'App\\Services\\Printing\\PrintProfileService', 'model' => 'App\\Models\\PrintProfile']
    ),

    'permission' => [
        'title' => 'Permissions',
        'summary' => 'Role definitions and grouped permission-key assignment for application RBAC.',
        'meta' => ['routes' => ['/admin/settings/permission'], 'permissions' => ['settings.view', 'settings.roles.manage'], 'related' => ['users']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'Purpose', 'paragraphs' => ['Create non-system roles and assign permission keys from PermissionRegistry groups. Users receive permissions through one role. Super Admin returns true for every hasPermission() call.']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'Fields', 'fields' => [
                $field('name', 'Role name', 'string', true, 'Unique max 255.'),
                $field('slug', 'Role slug', 'string', false, 'Service-managed stable key; super-admin has bypass semantics.'),
                $field('description', 'Description', 'text', false, 'Max 2000.'),
                $field('is_system', 'System role', 'boolean', true, 'System roles are read-only in the form.'),
                $field('selectedPermissions', 'Permission keys', 'array/pivot', false, 'De-duplicated and synced to role_permission.'),
                $field('permissions.key / group_key / labels / sort_order', 'Permission catalog', 'master', true, 'Defined/seeded from PermissionRegistry.'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'Workflow', 'steps' => ['Review PermissionRegistry groups.', 'Create a role with least-privilege keys or edit a non-system role.', 'Assign the role on Users.', 'Test denied and allowed routes/actions with a non-super-admin account.', 'Review permission changes as security events.']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['AI does not assign permissions. Role design and approval require an authorized human.']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'RBAC rules', 'list' => ['Role mutation requires settings.roles.manage.', 'System roles are read-only; Super Admin sees/implicitly holds all permission keys.', 'Authorization must exist in pages, Livewire actions, controllers, exports, and services where appropriate—not only navigation.', 'Permission keys are stable API identifiers; changing a label must not change the key.']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'Integrations', 'list' => ['User.role_id selects one role.', 'User::hasPermission loads role.permissions and grants Super Admin bypass.', 'Cluster toolbars and module forms use keys such as finance.view/create/edit/delete.', 'PermissionRegistry organizes the matrix.']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild notes', 'list' => ['Add a permission coverage test that enumerates every mutating route/action and expected key.', 'Prevent removing the last active Super Admin or self-lockout.', 'Audit role create/update/delete and before/after permission sets.', 'Invalidate cached authorization immediately after role changes.']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'Acceptance', 'acceptance' => ['Without settings.roles.manage a direct Livewire mutation fails.', 'System/Super Admin role cannot be weakened through the form.', 'Permission changes take effect promptly across navigation and server actions.', 'Every finance action respects its finance.* key.', 'Last active Super Admin cannot be removed or disabled.']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'Technical', 'classes' => ['page' => 'App\\Filament\\Clusters\\Settings\\PermissionPage', 'livewire' => 'App\\Livewire\\Roles\\Form', 'services' => ['App\\Services\\Access\\RoleService', 'App\\Services\\Access\\PermissionRegistry'], 'models' => ['App\\Models\\Role', 'App\\Models\\Permission'], 'tables' => ['roles', 'permissions', 'permission_role']]],
        ],
    ],

    'users' => [
        'title' => 'Users',
        'summary' => 'Admin login accounts, role assignment, status, bilingual profile, contacts, bank references, and identity media.',
        'meta' => ['routes' => ['/admin/settings/users'], 'permissions' => ['settings.view', 'settings.users.manage'], 'related' => ['permission', 'staff']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'Purpose', 'paragraphs' => ['Manage application login accounts and their single role. Active status plus role is required for non-super-admin panel access. The shared user form is also used in Staff context with different permissions/status choices.']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'Fields', 'fields' => [
                $field('user_number', 'User number', 'string', false, 'Service-generated when blank.'),
                $field('name / name_ur', 'Name', 'strings', true, 'English name required max 255; Urdu optional.'),
                $field('role_id', 'Role', 'FK', true, 'Must exist. Default settings create may select super-admin; review this risk.'),
                $field('status', 'Status', 'enum', true, 'pending|active|inactive|rejected in Settings context.'),
                $field('email', 'Login email', 'email', true, 'Unique max 255.'),
                $field('password / confirmation', 'Password', 'secret', true, 'Create required min 8 and confirmation; edit optional.'),
                $field('address / address_ur', 'Address', 'text', false, 'Max 2000 each.'),
                $field('phone_numbers', 'Phone rows', 'JSON', false, 'Contact person EN/UR and phone max 30.'),
                $field('bank_accounts', 'Bank rows', 'JSON', false, 'Bank name, account title EN/UR, account number.'),
                $field('profile_photo_path', 'Profile photo', 'WebP path', false, 'Image max 5MB under users/profile-photos.'),
                $field('cnic_front_path / cnic_back_path', 'CNIC images', 'public paths', false, 'Image max 5MB under users/cnic.'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'Workflow', 'steps' => ['Create the least-privilege role first.', 'Add user identity, unique email, secure password, and role.', 'Keep pending/inactive until approved; activate only after verification.', 'Use edit to rotate credentials, change status, or role.', 'Immediately disable compromised/departed accounts and review audit activity.']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI enrichment', 'paragraphs' => ['UserEnrichmentService may fill missing bilingual names, address, contact-person, and account-title text. It must never choose a role/status, generate or inspect passwords, change email/phone/account number, or infer CNIC.']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Access and login rules', 'list' => ['Settings user mutation requires settings.users.manage; Staff context uses staff.create/edit.', 'Non-super-admin can access admin only when status=active and role_id is present.', 'Super Admin still requires active status.', 'Password is hashed; hidden fields must not be serialized.', 'Current default role assignment selects super-admin when present—rebuild should default to no role or an explicitly safe role.']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'Integrations', 'list' => ['Role permissions drive User::hasPermission.', 'Approval/status controls admin login.', 'Finance transfers may reference a purchaser User.', 'Staff module shares the form with restricted statuses.']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild notes', 'list' => ['Remove automatic super-admin default; require deliberate elevated-role assignment and re-authentication.', 'Normalize phones/banks into protected child tables and mask sensitive values.', 'Add MFA, password-reset/revoke sessions, last-login, actor audit, and optimistic concurrency.', 'Protect the last active Super Admin and prevent unsafe self-role/status changes.', 'Store CNIC privately with authorized download, not public disk URLs.']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'Acceptance', 'acceptance' => ['Create requires unique email, role, and confirmed 8+ character password.', 'Inactive/pending/rejected users cannot enter admin.', 'A non-manager cannot mutate a user through direct events.', 'No new user receives Super Admin implicitly.', 'CNIC is unavailable without explicit authorization.', 'Role/status changes are audited and revoke access promptly.']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'Technical', 'classes' => ['page' => 'App\\Filament\\Clusters\\Settings\\UsersPage', 'livewire' => ['App\\Livewire\\Users\\ListUsers', 'App\\Livewire\\Users\\Form'], 'services' => ['App\\Services\\Access\\UserService', 'App\\Services\\Access\\UserEnrichmentService'], 'models' => ['App\\Models\\User', 'App\\Models\\Role'], 'table' => 'users']],
        ],
    ],

    'backups' => $stub(
        'Backups',
        'Create, import, list, restore, export, and delete database/full backup archives.',
        '/admin/settings/backups',
        ['settings.view', 'settings.backups.manage'],
        ['The manager creates full or database backups, imports incoming archives, exports products/categories, and restores only after typing RESTORE. ZIP entries are enumerated before extraction and approved files are streamed into a random private work directory. Production local-login preservation is authoritative by default and writes a private audit report. Automatic backups: daily database at 01:30 and weekly full (default Sunday 02:00) when Hostinger cron runs php artisan schedule:run every minute. Deploy also creates a database safety backup before migrate.'],
        ['Keep settings.backups.manage, re-authentication, CSRF, safety backups, encrypted off-site copies, and restore audits.', 'Configured limits cover compressed upload size, entry count, per-entry/total uncompressed size, compression ratio, retention (default 14 archives), and database-driver compatibility.', 'Never expose archives, restore work directories, or audit reports from the public web root.'],
        ['page' => 'App\\Filament\\Clusters\\Settings\\BackupsPage', 'livewire' => 'App\\Livewire\\Settings\\ManageBackups', 'services' => ['App\\Services\\Backup\\BackupService', 'App\\Services\\Backup\\RestoreService']]
    ),
];
