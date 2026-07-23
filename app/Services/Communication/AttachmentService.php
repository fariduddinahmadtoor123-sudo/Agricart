<?php

namespace App\Services\Communication;

use App\Models\Communication\Attachment;
use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly CommunicationAuthorizer $authorizer,
        private readonly MessageService $messages,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<Attachment>
     */
    public function postWithFiles(
        Conversation $conversation,
        ActorRef $actor,
        array $files,
        ?string $body = null,
        ?int $parentId = null,
    ): array {
        $conversation->loadMissing('channel');
        $channel = $conversation->channel;

        if (! $channel->allows_attachments) {
            throw ValidationException::withMessages([
                'attachments' => ['Attachments are disabled on this channel.'],
            ]);
        }

        if ($files === []) {
            throw ValidationException::withMessages([
                'attachments' => ['At least one file is required.'],
            ]);
        }

        $maxBytes = (int) config('communication.max_attachment_bytes', 10_485_760);
        $prepared = [];

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                throw ValidationException::withMessages([
                    'attachments' => ['Invalid upload.'],
                ]);
            }

            $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType());
            $group = $this->channels->mimeGroupFor($mime);

            if ($group === null || ! $channel->allowsMimeGroup($group)) {
                throw ValidationException::withMessages([
                    "attachments.{$index}" => ["File type [{$mime}] is not allowed on this channel."],
                ]);
            }

            if ($file->getSize() > $maxBytes) {
                throw ValidationException::withMessages([
                    "attachments.{$index}" => ['File exceeds the maximum allowed size.'],
                ]);
            }

            $prepared[] = [
                'file' => $file,
                'mime' => $mime,
                'kind' => $group === 'audio' ? 'audio' : ($group === 'image' ? 'image' : ($group === 'archive' ? 'archive' : 'document')),
                'size' => (int) $file->getSize(),
                'original_name' => Str::limit((string) $file->getClientOriginalName(), 240, ''),
            ];
        }

        $this->authorizer->assertCanPost($conversation, $actor);

        return DB::transaction(function () use ($conversation, $actor, $prepared, $body, $parentId): array {
            $messageBody = trim((string) $body);
            $message = $this->messages->postText(
                $conversation,
                $actor,
                $messageBody !== '' ? $messageBody : '[Attachment]',
                $parentId,
            );

            // Re-type as attachment when files are present.
            $message->update(['message_type' => Message::TYPE_ATTACHMENT]);

            $disk = (string) config('communication.attachment_disk', 'local');
            $root = trim((string) config('communication.attachment_root', 'communication/attachments'), '/');
            $attachments = [];

            foreach ($prepared as $item) {
                /** @var UploadedFile $file */
                $file = $item['file'];
                $path = $file->store("{$root}/{$conversation->id}", $disk);

                if ($path === false) {
                    throw ValidationException::withMessages([
                        'attachments' => ['Could not store attachment.'],
                    ]);
                }

                $attachments[] = Attachment::query()->create([
                    'message_id' => $message->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $item['original_name'],
                    'mime' => $item['mime'],
                    'size' => $item['size'],
                    'kind' => $item['kind'],
                ]);
            }

            return $attachments;
        });
    }

    public function deleteFile(Attachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }
}
