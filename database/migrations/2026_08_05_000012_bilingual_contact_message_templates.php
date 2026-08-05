<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_message_templates', function (Blueprint $table): void {
            $table->string('name_en', 120)->nullable()->after('key');
            $table->string('name_ur', 120)->nullable()->after('name_en');
            $table->string('subject_en', 200)->nullable()->after('name_ur');
            $table->string('subject_ur', 200)->nullable()->after('subject_en');
            $table->text('body_en')->nullable()->after('subject_ur');
            $table->text('body_ur')->nullable()->after('body_en');
        });

        $rows = DB::table('contact_message_templates')->orderBy('id')->get();

        foreach ($rows as $row) {
            $name = (string) ($row->name ?? '');
            $subject = (string) ($row->subject ?? '');
            $body = (string) ($row->body ?? '');

            [$subjectEn, $subjectUr] = $this->splitSubject($subject);
            [$bodyEn, $bodyUr] = $this->splitBody($body);

            DB::table('contact_message_templates')->where('id', $row->id)->update([
                'name_en' => $name !== '' ? $name : 'Template',
                'name_ur' => $row->key === 'welcome' ? 'خوش آمدید' : null,
                'subject_en' => $subjectEn,
                'subject_ur' => $subjectUr,
                'body_en' => $bodyEn,
                'body_ur' => $bodyUr,
            ]);
        }

        Schema::table('contact_message_templates', function (Blueprint $table): void {
            $table->dropColumn(['name', 'subject', 'body']);
        });
    }

    public function down(): void
    {
        Schema::table('contact_message_templates', function (Blueprint $table): void {
            $table->string('name', 120)->nullable()->after('key');
            $table->string('subject', 200)->nullable()->after('name');
            $table->text('body')->nullable()->after('subject');
        });

        $rows = DB::table('contact_message_templates')->orderBy('id')->get();

        foreach ($rows as $row) {
            $subject = trim((string) ($row->subject_en ?? ''));
            if (filled($row->subject_ur ?? null)) {
                $subject .= ($subject !== '' ? ' · ' : '').trim((string) $row->subject_ur);
            }

            $body = trim((string) ($row->body_en ?? ''));
            if (filled($row->body_ur ?? null)) {
                $body .= ($body !== '' ? "\n\n" : '').trim((string) $row->body_ur);
            }

            DB::table('contact_message_templates')->where('id', $row->id)->update([
                'name' => (string) ($row->name_en ?: $row->name_ur ?: 'Template'),
                'subject' => $subject,
                'body' => $body,
            ]);
        }

        Schema::table('contact_message_templates', function (Blueprint $table): void {
            $table->dropColumn([
                'name_en',
                'name_ur',
                'subject_en',
                'subject_ur',
                'body_en',
                'body_ur',
            ]);
        });
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function splitSubject(string $subject): array
    {
        if (str_contains($subject, ' · ')) {
            [$en, $ur] = explode(' · ', $subject, 2);

            return [trim($en), trim($ur) !== '' ? trim($ur) : null];
        }

        return [$subject !== '' ? $subject : 'Message', null];
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function splitBody(string $body): array
    {
        $marker = "\n\nہیلو";
        $pos = mb_strpos($body, $marker);

        if ($pos !== false) {
            $en = trim(mb_substr($body, 0, $pos));
            $ur = trim(mb_substr($body, $pos + 2)); // keep ہیلو in UR part

            return [$en !== '' ? $en : $body, $ur !== '' ? $ur : null];
        }

        return [$body !== '' ? $body : 'Message body', null];
    }
};
